# Deploy to Render - Complete Guide
### PHP Music Shop with PostgreSQL

---

## Prerequisites
- GitHub account (free)
- Render account (free - no credit card needed)
- Your project code (already configured for PostgreSQL)
- **Dockerfile is already included** - no need to create it

---

## Step-by-Step Deployment

### **STEP 1: Push Code to GitHub**

```bash
# Initialize git (if not already done)
git init
git add .
git commit -m "Deploy music shop to Render"

# Create a new repository on GitHub, then:
git remote add origin https://github.com/YOUR_USERNAME/music-shop.git
git branch -M main
git push -u origin main
```

---

### **STEP 2: Create Render Account**

1. Go to **https://render.com**
2. Click **Get Started for Free**
3. Sign up with **GitHub** (recommended)
4. Authorize Render to access your repositories

---

### **STEP 3: Create PostgreSQL Database**

1. In Render Dashboard, click **New +** (top right)
2. Select **PostgreSQL**
3. Configure:
   - **Name**: `music-shop-db`
   - **Database**: `music_shop`
   - **User**: `music_user` (auto-generated is fine)
   - **Region**: Choose closest (Singapore/Frankfurt/Oregon)
   - **PostgreSQL Version**: 16
   - **Plan**: **Free** ($0/month)
4. Click **Create Database**
5. Wait 1-2 minutes for provisioning

---

### **STEP 4: Import Database Schema**

1. Once database is ready, click on it
2. Click **Connect** → Copy **PSQL Command**
3. Open terminal and paste the command (requires `psql` installed)
4. Once connected, run:

```sql
-- Copy and paste your PostgreSQL schema here
-- (converted from database/schema.sql)
```

**OR** use a GUI tool like pgAdmin/DBeaver with the **External Database URL**

---

### **STEP 5: Deploy PHP Web Service**

1. Go back to Render Dashboard
2. Click **New +** → **Web Service**
3. Click **Connect a repository**
4. Select your GitHub repository

#### **Configure Service:**

**Basic Settings:**
- **Name**: `music-shop` (will be your URL)
- **Region**: **Same as database** (important!)
- **Branch**: `main`
- **Root Directory**: Leave **EMPTY**
- **Runtime**: **Docker** (we'll use custom Dockerfile)

**Build & Deploy:**
- **Build Command**: Leave empty
- **Start Command**: Leave empty (Dockerfile handles it)

**Instance Type:**
- Select **Free** ($0/month)

---

### **STEP 6: Set Environment Variables**

In Render Web Service settings:

1. Click **Environment** tab
2. Click **Add Environment Variable**

Add this variable:
- **Key**: `DATABASE_URL`
- **Value**: Click **Add from Database** → Select `music-shop-db` → Choose **Internal Database URL**

3. Click **Save Changes**

---

### **STEP 7: Deploy**

1. Render will automatically start deploying
2. Watch the logs (takes 3-5 minutes first time)
3. When you see **"Your service is live"** → Done!

Your app will be at: `https://music-shop-XXXX.onrender.com`

---

## Demo Login Credentials

| Role     | Username | Password   |
|----------|----------|------------|
| Admin    | `admin`  | `password` |
| Customer | `alice`  | `password` |
| Customer | `bob`    | `password` |

---

## Troubleshooting

### ❌ "Error: connect ECONNREFUSED 10.211.60.239:5432"
**Cause:** Database connection not using Render's `DATABASE_URL`

**Fix:**
- Verify `DATABASE_URL` is set in Environment tab
- Make sure you're using the updated `config/db.php` from this zip
- Redeploy: **Manual Deploy** → **Clear build cache & deploy**

---

### ❌ "Database connection failed"
- Use **Internal Database URL** (not External)
- Ensure database and web service are in **same region**
- Check database status is **Available** (green)

---

### ❌ "Application failed to respond"
- Check **Logs** tab for specific errors
- Verify Dockerfile is in root directory
- Make sure PostgreSQL extension is installed (check Dockerfile)

---

### ❌ App keeps crashing/restarting
- Check if database schema was imported
- Look at logs for PHP errors
- Verify `config/db.php` is reading `DATABASE_URL` correctly

---

### ⚠️ App is slow on first load
- **Normal behavior** - Free tier has cold starts (30-60 seconds)
- App sleeps after 15 minutes of inactivity
- After first load, it's fast

---

## Updating Your App

After making changes:

```bash
git add .
git commit -m "Update description"
git push
```

Render will **auto-deploy** in 2-3 minutes!

---

## Architecture

```
┌─────────────────────────────────────┐
│   Render Web Service (PHP + Apache) │
│   - Frontend (HTML/CSS/JS)          │
│   - Backend (PHP)                   │
│   - Sessions                        │
└──────────────┬──────────────────────┘
               │
               │ DATABASE_URL
               │
┌──────────────▼──────────────────────┐
│   Render PostgreSQL Database        │
│   - Users, Songs, Purchases         │
│   - Singers, Composers, Companies   │
└─────────────────────────────────────┘
```

**Everything runs on Render** - no separate frontend/backend deployment needed.

---

## Important Notes

✅ **Database is already configured** - `config/db.php` reads `DATABASE_URL` automatically

✅ **No code changes needed** - Just follow deployment steps

✅ **Free tier limits:**
- Database: 1GB storage, 90 days retention
- Web service: 750 hours/month
- Perfect for college projects!

⚠️ **First deployment takes 3-5 minutes** - be patient

⚠️ **Cold starts are normal** - Free tier sleeps after 15min inactivity

---

## Need Help?

1. Check **Logs** tab in Render dashboard
2. Verify **Environment** variables are set
3. Ensure database status is **Available**
4. Try **Manual Deploy** → **Clear build cache & deploy**

---

**Tech Stack:**
- Backend: PHP 8.1 + PDO
- Database: PostgreSQL 16
- Frontend: Bootstrap 5 + Vanilla JS
- Server: Apache
- Hosting: Render (Free tier)
