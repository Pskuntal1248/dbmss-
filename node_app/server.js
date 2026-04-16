const express = require('express');
const sqlite3 = require('sqlite3').verbose();
const session = require('express-session');
const bcrypt = require('bcryptjs');
const path = require('path');
const fs = require('fs');

const app = express();
const db = new sqlite3.Database(path.join(__dirname, 'database.sqlite'));

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(express.static(path.join(__dirname, '../'))); // Served to get assets
app.use(express.urlencoded({ extended: true }));
app.use(session({
    secret: 'secret-key-123',
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

// Require Auth Middleware
const reqL = (req, res, next) => { if(!req.session.user) return res.redirect('/login'); next(); };
const reqA = (req, res, next) => { if(req.session.user?.role !== 'admin') return res.status(403).send('Denied'); next(); };

// --- ROUTES ---

// Home
app.get('/', (req, res) => {
    db.all(`SELECT * FROM v_song_details ORDER BY song_id DESC LIMIT 6`, (err, songs) => {
        db.get(`SELECT 
            (SELECT COUNT(*) FROM songs) AS total_songs,
            (SELECT COUNT(*) FROM singers) AS total_singers,
            (SELECT COUNT(*) FROM customers) AS total_customers`, (err, stats) => {
            res.render('index', { songs, stats });
        });
    });
});

// Shop
app.get('/shop', (req, res) => {
    const q = req.query.q || '';
    const cat = req.query.cat || '';
    
    let sql = `SELECT * FROM v_song_details WHERE 1=1 `;
    let p = [];
    if (q) {
        sql += ` AND (title LIKE ? OR movie_name LIKE ? OR singers LIKE ? OR composers LIKE ? OR company_name LIKE ?)`;
        const like = `%${q}%`;
        p.push(like, like, like, like, like);
    }
    if (cat) {
        sql += ` AND category = ?`;
        p.push(cat);
    }
    sql += ` ORDER BY title ASC`;
    
    db.all(sql, p, (err, songs) => {
        res.render('shop', { songs, q, cat });
    });
});

// Song Detail & Buy
app.all('/song/:id', (req, res) => {
    const id = req.params.id;
    db.get(`SELECT * FROM v_song_details WHERE song_id = ?`, [id], (err, song) => {
        if (!song) return res.status(404).send('Not Found');
        
        const custId = req.session.user?.customer_id;
        db.get(`SELECT purchase_id FROM purchases WHERE customer_id = ? AND song_id = ?`, [custId, id], (err, row) => {
            let alreadyOwned = !!row;
            let flash = '';

            if (req.method === 'POST' && req.body.buy) {
                if (!req.session.user) return res.redirect('/login');
                if (req.session.user.role === 'admin') {
                    flash = 'Admins cannot purchase.';
                } else if (!alreadyOwned) {
                    const fmt = req.body.format || 'MP3';
                    db.run(`INSERT INTO purchases (customer_id, song_id, amount_paid, format_chosen) VALUES (?, ?, ?, ?)`,
                        [custId, id, song.price, fmt], (err) => {
                        alreadyOwned = true;
                        flash = 'Purchase successful!';
                        res.render('song', { song, alreadyOwned, flash });
                    });
                    return;
                }
            }
            res.render('song', { song, alreadyOwned, flash });
        });
    });
});

// Auth
app.get('/login', (req, res) => res.render('login', { error: null }));
app.post('/login', (req, res) => {
    const login = req.body.login;
    db.get(`SELECT u.*, c.customer_id FROM users u LEFT JOIN customers c ON c.user_id = u.user_id WHERE u.username = ? OR u.email = ?`, 
    [login, login], (err, user) => {
        if (user && bcrypt.compareSync(req.body.password, user.password)) {
            req.session.user = user;
            res.redirect(user.role === 'admin' ? '/admin' : '/');
        } else {
            res.render('login', { error: 'Invalid credentials.' });
        }
    });
});

app.get('/register', (req, res) => res.render('register', { error: null, success: null }));
app.post('/register', (req, res) => {
    const { full_name, username, email, phone, password } = req.body;
    db.get(`SELECT user_id FROM users WHERE username = ? OR email = ?`, [username, email], (err, row) => {
        if (row) return res.render('register', { error: 'Username/Email exists.', success: null });
        const hash = bcrypt.hashSync(password, 10);
        db.run(`INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')`, [username, email, hash], function(err) {
            const userId = this.lastID;
            db.run(`INSERT INTO customers (user_id, full_name, phone) VALUES (?, ?, ?)`, [userId, full_name, phone], () => {
                res.render('register', { error: null, success: 'Account created! Login now.' });
            });
        });
    });
});

app.get('/logout', (req, res) => {
    req.session.destroy();
    res.redirect('/login');
});

// Customer
app.get('/my_purchases', reqL, (req, res) => {
    if(req.session.user.role === 'admin') return res.redirect('/admin/purchases');
    db.all(`SELECT vsd.*, p.amount_paid, p.format_chosen, p.purchased_at FROM purchases p JOIN v_song_details vsd ON vsd.song_id = p.song_id WHERE p.customer_id = ? ORDER BY p.purchased_at DESC`,
    [req.session.user.customer_id], (err, purchases) => {
        res.render('my_purchases', { purchases });
    });
});

// Admin Dashboard
app.get('/admin', reqA, (req, res) => {
    db.get(`SELECT 
        (SELECT COUNT(*) FROM songs) AS songs,
        (SELECT COUNT(*) FROM singers) AS singers,
        (SELECT COUNT(*) FROM record_companies) AS companies,
        (SELECT COUNT(*) FROM customers) AS customers,
        (SELECT COUNT(*) FROM purchases) AS purchases,
        (SELECT COALESCE(SUM(amount_paid),0) FROM purchases) AS revenue`, (err, stats) => {
        db.all(`SELECT * FROM v_purchase_details ORDER BY purchased_at DESC LIMIT 8`, (err, recent) => {
            res.render('admin', { stats, recent });
        });
    });
});

app.get('/admin/add-song', reqA, (req, res) => {
    db.all(`SELECT * FROM record_companies`, (err, companies) => {
        res.render('add_song', { companies, error: null, success: null });
    });
});

app.post('/admin/add-song', reqA, (req, res) => {
    const { title, movie_name, price, duration, category, available_as, size_mb, company_id } = req.body;
    db.run(`INSERT INTO songs (title, movie_name, price, duration, category, available_as, size_mb, company_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
        [title, movie_name, price, duration, category, available_as, size_mb, company_id], function(err) {
        db.all(`SELECT * FROM record_companies`, (err2, companies) => {
            if (err || err2) return res.render('add_song', { companies, error: 'Failed to add song. '+err, success: null });
            res.render('add_song', { companies, error: null, success: 'Song added successfully to the database!' });
        });
    });
});

app.listen(4000, () => {
    console.log('Server is running at http://localhost:4000/');
});
