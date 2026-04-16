const express = require('express');
const { Pool } = require('pg');
const session = require('express-session');
const bcrypt = require('bcryptjs');
const path = require('path');

const app = express();

// PostgreSQL connection
const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
    ssl: process.env.NODE_ENV === 'production' ? { rejectUnauthorized: false } : false
});

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(express.static(path.join(__dirname, '../')));
app.use(express.urlencoded({ extended: true }));
app.use(session({
    secret: process.env.SESSION_SECRET || 'secret-key-123',
    resave: false,
    saveUninitialized: false
}));

// Helpers for views
app.use((req, res, next) => {
    res.locals.user = req.session.user || null;
    res.locals.isAdmin = req.session.user?.role === 'admin';
    res.locals.isCustomer = req.session.user?.role === 'customer';
    res.locals.formatPrice = (p) => '₹' + parseFloat(p).toFixed(2);
    res.locals.catBadge = (cat) => {
        const m = {'Romantic':'badge-romantic','Sad':'badge-sad','Party':'badge-party','Sufi':'badge-sufi',
                   'Folk':'badge-folk','Patriotic':'badge-patriotic','Classical':'badge-classical',
                   'Devotional':'badge-devotional','Item':'badge-item'};
        return `<span class="badge ${m[cat]||'badge-other'}">${cat}</span>`;
    };
    next();
});

// Auth Middleware
const reqL = (req, res, next) => { if(!req.session.user) return res.redirect('/login'); next(); };
const reqA = (req, res, next) => { if(req.session.user?.role !== 'admin') return res.status(403).send('Denied'); next(); };

// --- ROUTES ---

// Home
app.get('/', async (req, res) => {
    try {
        const songs = await pool.query('SELECT * FROM v_song_details ORDER BY song_id DESC LIMIT 6');
        const stats = await pool.query(`SELECT 
            (SELECT COUNT(*) FROM songs) AS total_songs,
            (SELECT COUNT(*) FROM singers) AS total_singers,
            (SELECT COUNT(*) FROM customers) AS total_customers`);
        res.render('index', { songs: songs.rows, stats: stats.rows[0] });
    } catch (err) {
        res.status(500).send('Error: ' + err.message);
    }
});

// Shop
app.get('/shop', async (req, res) => {
    const q = req.query.q || '';
    const cat = req.query.cat || '';
    
    let sql = 'SELECT * FROM v_song_details WHERE 1=1 ';
    let params = [];
    let paramCount = 1;
    
    if (q) {
        sql += ` AND (title ILIKE $${paramCount} OR movie_name ILIKE $${paramCount+1} OR singers ILIKE $${paramCount+2} OR composers ILIKE $${paramCount+3} OR company_name ILIKE $${paramCount+4})`;
        const like = `%${q}%`;
        params.push(like, like, like, like, like);
        paramCount += 5;
    }
    if (cat) {
        sql += ` AND category = $${paramCount}`;
        params.push(cat);
    }
    sql += ' ORDER BY title ASC';
    
    try {
        const result = await pool.query(sql, params);
        res.render('shop', { songs: result.rows, q, cat });
    } catch (err) {
        res.status(500).send('Error: ' + err.message);
    }
});

// Song Detail & Buy
app.all('/song/:id', async (req, res) => {
    const id = req.params.id;
    try {
        const songResult = await pool.query('SELECT * FROM v_song_details WHERE song_id = $1', [id]);
        if (songResult.rows.length === 0) return res.status(404).send('Not Found');
        
        const song = songResult.rows[0];
        const custId = req.session.user?.customer_id;
        const purchaseResult = await pool.query('SELECT purchase_id FROM purchases WHERE customer_id = $1 AND song_id = $2', [custId, id]);
        let alreadyOwned = purchaseResult.rows.length > 0;
        let flash = '';

        if (req.method === 'POST' && req.body.buy) {
            if (!req.session.user) return res.redirect('/login');
            if (req.session.user.role === 'admin') {
                flash = 'Admins cannot purchase.';
            } else if (!alreadyOwned) {
                const fmt = req.body.format || 'MP3';
                await pool.query('INSERT INTO purchases (customer_id, song_id, amount_paid, format_chosen) VALUES ($1, $2, $3, $4)',
                    [custId, id, song.price, fmt]);
                alreadyOwned = true;
                flash = 'Purchase successful!';
            }
        }
        res.render('song', { song, alreadyOwned, flash });
    } catch (err) {
        res.status(500).send('Error: ' + err.message);
    }
});

// Auth
app.get('/login', (req, res) => res.render('login', { error: null }));
app.post('/login', async (req, res) => {
    const login = req.body.login;
    try {
        const result = await pool.query(
            'SELECT u.*, c.customer_id FROM users u LEFT JOIN customers c ON c.user_id = u.user_id WHERE u.username = $1 OR u.email = $1',
            [login]
        );
        const user = result.rows[0];
        if (user && bcrypt.compareSync(req.body.password, user.password)) {
            req.session.user = user;
            res.redirect(user.role === 'admin' ? '/admin' : '/');
        } else {
            res.render('login', { error: 'Invalid credentials.' });
        }
    } catch (err) {
        res.render('login', { error: 'Error: ' + err.message });
    }
});

app.get('/register', (req, res) => res.render('register', { error: null, success: null }));
app.post('/register', async (req, res) => {
    const { full_name, username, email, phone, password } = req.body;
    try {
        const existing = await pool.query('SELECT user_id FROM users WHERE username = $1 OR email = $2', [username, email]);
        if (existing.rows.length > 0) return res.render('register', { error: 'Username/Email exists.', success: null });
        
        const hash = bcrypt.hashSync(password, 10);
        const userResult = await pool.query(
            'INSERT INTO users (username, email, password, role) VALUES ($1, $2, $3, $4) RETURNING user_id',
            [username, email, hash, 'customer']
        );
        const userId = userResult.rows[0].user_id;
        await pool.query('INSERT INTO customers (user_id, full_name, phone) VALUES ($1, $2, $3)', [userId, full_name, phone]);
        res.render('register', { error: null, success: 'Account created! Login now.' });
    } catch (err) {
        res.render('register', { error: 'Error: ' + err.message, success: null });
    }
});

app.get('/logout', (req, res) => {
    req.session.destroy();
    res.redirect('/login');
});

// Customer
app.get('/my_purchases', reqL, async (req, res) => {
    if(req.session.user.role === 'admin') return res.redirect('/admin/purchases');
    try {
        const result = await pool.query(
            'SELECT vsd.*, p.amount_paid, p.format_chosen, p.purchase_date FROM purchases p JOIN v_song_details vsd ON vsd.song_id = p.song_id WHERE p.customer_id = $1 ORDER BY p.purchase_date DESC',
            [req.session.user.customer_id]
        );
        res.render('my_purchases', { purchases: result.rows });
    } catch (err) {
        res.status(500).send('Error: ' + err.message);
    }
});

// Admin Dashboard
app.get('/admin', reqA, async (req, res) => {
    try {
        const stats = await pool.query(`SELECT 
            (SELECT COUNT(*) FROM songs) AS songs,
            (SELECT COUNT(*) FROM singers) AS singers,
            (SELECT COUNT(*) FROM record_companies) AS companies,
            (SELECT COUNT(*) FROM customers) AS customers,
            (SELECT COUNT(*) FROM purchases) AS purchases,
            (SELECT COALESCE(SUM(amount_paid),0) FROM purchases) AS revenue`);
        const recent = await pool.query('SELECT * FROM purchases ORDER BY purchase_date DESC LIMIT 8');
        res.render('admin', { stats: stats.rows[0], recent: recent.rows });
    } catch (err) {
        res.status(500).send('Error: ' + err.message);
    }
});

app.get('/admin/add-song', reqA, async (req, res) => {
    try {
        const companies = await pool.query('SELECT * FROM record_companies');
        res.render('add_song', { companies: companies.rows, error: null, success: null });
    } catch (err) {
        res.status(500).send('Error: ' + err.message);
    }
});

app.post('/admin/add-song', reqA, async (req, res) => {
    const { title, movie_name, price, duration, category, available_as, size_mb, company_id } = req.body;
    try {
        await pool.query(
            'INSERT INTO songs (title, movie_name, price, duration, category, available_as, size_mb, company_id) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)',
            [title, movie_name, price, duration, category, available_as, size_mb, company_id]
        );
        const companies = await pool.query('SELECT * FROM record_companies');
        res.render('add_song', { companies: companies.rows, error: null, success: 'Song added successfully!' });
    } catch (err) {
        const companies = await pool.query('SELECT * FROM record_companies');
        res.render('add_song', { companies: companies.rows, error: 'Failed: ' + err.message, success: null });
    }
});

const PORT = process.env.PORT || 4000;
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});
