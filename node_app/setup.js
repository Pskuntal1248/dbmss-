const sqlite3 = require('sqlite3').verbose();
const fs = require('fs');
const path = require('path');
const bcrypt = require('bcryptjs');

const dbFile = path.join(__dirname, 'database.sqlite');

// Remove old DB if exists
if (fs.existsSync(dbFile)) {
    fs.unlinkSync(dbFile);
}

const db = new sqlite3.Database(dbFile);

db.serialize(() => {
    // 1. Users
    db.run(`CREATE TABLE users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'customer',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);

    // 2. Customers
    db.run(`CREATE TABLE customers (
        customer_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        full_name TEXT NOT NULL,
        phone TEXT,
        address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )`);

    // 3. Singers
    db.run(`CREATE TABLE singers (
        singer_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        nationality TEXT DEFAULT 'Indian',
        bio TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);

    // 4. Composers
    db.run(`CREATE TABLE composers (
        composer_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        nationality TEXT DEFAULT 'Indian',
        bio TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);

    // 5. Record Companies
    db.run(`CREATE TABLE record_companies (
        company_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        country TEXT DEFAULT 'India',
        website TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);

    // 6. Songs
    db.run(`CREATE TABLE songs (
        song_id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        movie_name TEXT NOT NULL,
        price REAL NOT NULL CHECK (price >= 0),
        duration TEXT NOT NULL,
        category TEXT NOT NULL DEFAULT 'Other',
        available_as TEXT NOT NULL DEFAULT 'MP3',
        size_mb REAL NOT NULL,
        company_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES record_companies(company_id) ON UPDATE CASCADE
    )`);

    // 7. song_singers
    db.run(`CREATE TABLE song_singers (
        song_id INTEGER NOT NULL,
        singer_id INTEGER NOT NULL,
        PRIMARY KEY (song_id, singer_id),
        FOREIGN KEY (song_id) REFERENCES songs(song_id) ON DELETE CASCADE,
        FOREIGN KEY (singer_id) REFERENCES singers(singer_id) ON DELETE CASCADE
    )`);

    // 8. song_composers
    db.run(`CREATE TABLE song_composers (
        song_id INTEGER NOT NULL,
        composer_id INTEGER NOT NULL,
        PRIMARY KEY (song_id, composer_id),
        FOREIGN KEY (song_id) REFERENCES songs(song_id) ON DELETE CASCADE,
        FOREIGN KEY (composer_id) REFERENCES composers(composer_id) ON DELETE CASCADE
    )`);

    // 9. purchases
    db.run(`CREATE TABLE purchases (
        purchase_id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id INTEGER NOT NULL,
        song_id INTEGER NOT NULL,
        amount_paid REAL NOT NULL,
        format_chosen TEXT NOT NULL DEFAULT 'MP3',
        purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE RESTRICT,
        FOREIGN KEY (song_id) REFERENCES songs(song_id) ON DELETE RESTRICT,
        UNIQUE (customer_id, song_id)
    )`);

    // View to make reading easier for the frontend
    db.run(`CREATE VIEW v_song_details AS
        SELECT
            s.song_id,
            s.title,
            s.movie_name,
            s.price,
            s.duration,
            s.category,
            s.available_as,
            s.size_mb,
            rc.name AS company_name,
            (SELECT GROUP_CONCAT(name, ', ') FROM singers JOIN song_singers ON singers.singer_id = song_singers.singer_id WHERE song_singers.song_id = s.song_id) AS singers,
            (SELECT GROUP_CONCAT(name, ', ') FROM composers JOIN song_composers ON composers.composer_id = song_composers.composer_id WHERE song_composers.song_id = s.song_id) AS composers
        FROM songs s
        JOIN record_companies rc ON rc.company_id = s.company_id
    `);

    db.run(`CREATE VIEW v_purchase_details AS
        SELECT
            p.purchase_id,
            u.username,
            c.full_name,
            u.email,
            s.title AS song_title,
            s.movie_name,
            p.amount_paid,
            p.format_chosen,
            p.purchased_at
        FROM purchases p
        JOIN customers c ON c.customer_id = p.customer_id
        JOIN users u ON u.user_id = c.user_id
        JOIN songs s ON s.song_id = p.song_id
    `);

    // INSERT DEMO DATA
    const hash = bcrypt.hashSync('password', 10);
    const users = [
        ['admin', 'admin@music.com', hash, 'admin'],
        ['alice', 'alice@mail.com', hash, 'customer'],
        ['bob', 'bob@mail.com', hash, 'customer'],
        ['carol', 'carol@mail.com', hash, 'customer']
    ];
    const stmtUser = db.prepare(`INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)`);
    users.forEach(u => stmtUser.run(u));
    stmtUser.finalize();

    const customers = [
        [2, 'Alice Sharma', '9876543210', '12 MG Road, Bangalore, Karnataka'],
        [3, 'Bob Mehta', '9123456780', '45 Park Street, Kolkata, West Bengal'],
        [4, 'Carol Patel', '9988776655', '7 Linking Road, Mumbai, Maharashtra']
    ];
    const stmtCust = db.prepare(`INSERT INTO customers (user_id, full_name, phone, address) VALUES (?, ?, ?, ?)`);
    customers.forEach(c => stmtCust.run(c));
    stmtCust.finalize();

    const rc = [
        ['T-Series', 'India', 'https://www.t-series.com'],
        ['Sony Music India', 'India', 'https://www.sonymusic.co.in'],
        ['Zee Music Company', 'India', 'https://www.zeemusiccompany.com'],
        ['Saregama', 'India', 'https://www.saregama.com']
    ];
    const stmtRC = db.prepare(`INSERT INTO record_companies (name, country, website) VALUES (?, ?, ?)`);
    rc.forEach(r => stmtRC.run(r));
    stmtRC.finalize();

    const singers = [
        ['Arijit Singh', 'Indian', 'Playback singer known for melancholic romantic songs.'],
        ['Shreya Ghoshal', 'Indian', 'Versatile singer with a wide vocal range.'],
        ['Neha Kakkar', 'Indian', 'Popular Bollywood pop and item-song singer.'],
        ['A.R. Rahman', 'Indian', 'Oscar-winning composer and singer.'],
        ['Sonu Nigam', 'Indian', 'Classical-trained Bollywood veteran.'],
        ['Sunidhi Chauhan', 'Indian', 'Known for energetic and high-pitched songs.']
    ];
    const stmtS = db.prepare(`INSERT INTO singers (name, nationality, bio) VALUES (?, ?, ?)`);
    singers.forEach(s => stmtS.run(s));
    stmtS.finalize();

    const composers = [
        ['Pritam', 'Indian', 'Known for foot-tapping Bollywood scores.'],
        ['A.R. Rahman', 'Indian', 'Grammy and Oscar winning music director.'],
        ['Vishal-Shekhar', 'Indian', 'Dynamic Bollywood composing duo.'],
        ['Amit Trivedi', 'Indian', 'Eclectic composer known for experimental sounds.'],
        ['Shankar-Ehsaan-Loy', 'Indian', 'Trio responsible for iconic Bollywood albums.']
    ];
    const stmtC = db.prepare(`INSERT INTO composers (name, nationality, bio) VALUES (?, ?, ?)`);
    composers.forEach(c => stmtC.run(c));
    stmtC.finalize();

    const songs = [
        ['Tum Hi Ho', 'Aashiqui 2', 29.00, '00:04:22', 'Romantic', 'Both', 8.50, 2],
        ['Raabta', 'Agent Sai Srinivasa', 25.00, '00:04:10', 'Romantic', 'MP3', 5.80, 1],
        ['Jai Ho', 'Jai Ho', 35.00, '00:05:10', 'Patriotic', 'Both', 10.20, 1],
        ['Tere Bin', 'Wazir', 20.00, '00:04:03', 'Sad', 'MP3', 5.00, 3],
        ['London Thumakda', 'Queen', 30.00, '00:03:50', 'Folk', 'Both', 7.80, 2],
        ['Manwa Laage', 'Happy New Year', 22.00, '00:04:30', 'Romantic', 'WAV', 9.10, 1],
        ['Kun Faya Kun', 'Rockstar', 40.00, '00:07:52', 'Sufi', 'Both', 15.60, 2],
        ['Badtameez Dil', 'Yeh Jawaani Hai Deewani', 28.00, '00:04:15', 'Party', 'MP3', 6.30, 2],
        ['Chaiyya Chaiyya', 'Dil Se', 45.00, '00:06:30', 'Classical', 'Both', 13.20, 4],
        ['Channa Mereya', 'Ae Dil Hai Mushkil', 32.00, '00:04:49', 'Sad', 'Both', 9.60, 2]
    ];
    const stmtSong = db.prepare(`INSERT INTO songs (title, movie_name, price, duration, category, available_as, size_mb, company_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)`);
    songs.forEach(s => stmtSong.run(s));
    stmtSong.finalize();

    const ss = [[1,1],[2,1],[2,2],[3,4],[4,1],[5,3],[6,5],[6,2],[7,4],[8,6],[9,4],[10,1]];
    const stmtSS = db.prepare(`INSERT INTO song_singers (song_id, singer_id) VALUES (?, ?)`);
    ss.forEach(s => stmtSS.run(s));
    stmtSS.finalize();

    const sc = [[1,3],[2,1],[3,2],[4,5],[5,4],[6,1],[7,2],[8,1],[9,2],[10,3]];
    const stmtSC = db.prepare(`INSERT INTO song_composers (song_id, composer_id) VALUES (?, ?)`);
    sc.forEach(s => stmtSC.run(s));
    stmtSC.finalize();

    const purchases = [
        [1, 1, 29.00, 'MP3'],
        [1, 7, 40.00, 'WAV'],
        [2, 3, 35.00, 'MP3'],
        [2, 9, 45.00, 'WAV'],
        [3, 5, 30.00, 'MP3']
    ];
    const stmtP = db.prepare(`INSERT INTO purchases (customer_id, song_id, amount_paid, format_chosen) VALUES (?, ?, ?, ?)`);
    purchases.forEach(p => stmtP.run(p));
    stmtP.finalize();

    console.log("Database initialized successfully!");
});
