# Deploying HAZINA ASILI to Render + TiDB Serverless

**Stack:** Render.com (free PHP/Docker hosting) + TiDB Serverless (free MySQL-compatible database)  
**Cost:** $0 — both services are free forever within their free quotas  
**Time:** ~20 minutes

---

## Prerequisites

- A [GitHub](https://github.com) account
- Your project pushed to a GitHub repository

---

## Step 1 — Push Your Code to GitHub

If not already done:

```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USERNAME/hazina-asili.git
git push -u origin main
```

> Make sure `.env` is in `.gitignore` (it already is) — never push real credentials.

---

## Step 2 — Create a Free TiDB Serverless Database

TiDB is a MySQL-compatible cloud database. Free forever up to 5GB storage.

1. Go to [tidbcloud.com](https://tidbcloud.com) and sign up (no credit card needed)
2. Click **Create Cluster** → select **Starter** (free tier)
3. Choose a region close to you (e.g. `eu-central-1` for Europe, `us-east-1` for USA)
4. Click **Create** — the cluster is ready in ~30 seconds

### Get Your Connection Credentials

1. In the TiDB Cloud console, click your cluster name
2. Click **Connect** (top right)
3. Select **Connect With: General** and **Operating System: Linux**
4. Note down these values — you'll need them for Render:

| Variable   | Example value                                         |
|------------|-------------------------------------------------------|
| `DB_HOST`  | `gateway01.eu-central-1.prod.aws.tidbcloud.com`       |
| `DB_PORT`  | `4000`                                                |
| `DB_USER`  | `2FHqXXXXXX.root`                                     |
| `DB_PASS`  | `your-generated-password`                             |
| `DB_NAME`  | `hazina_asili` (you'll create this in the next step)  |

### Create the Database Schema

1. In TiDB Cloud console, click **SQL Editor** (left sidebar)
2. Run this to create the database:
   ```sql
   CREATE DATABASE IF NOT EXISTS hazina_asili CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE hazina_asili;
   ```
3. Open the file `database/hazina_asili_tidb.sql` from this project
4. Copy its contents and paste into the SQL Editor, then run it
5. Then run `database/migration_v3_security.sql` the same way
6. Then run `database/migration_add_passport.sql`
7. Then run `database/migration_compound_structure.sql`
8. Then run `database/migration_organism_structure.sql`
9. Then run `database/migration_oauth.sql`

> **Tip:** Run each file one at a time. If a file doesn't exist, skip it.

---

## Step 3 — Deploy to Render

### 3a. Create a Render Account

1. Go to [render.com](https://render.com) and sign up (no credit card needed)
2. Connect your GitHub account when prompted

### 3b. Create a New Web Service

1. From the Render dashboard, click **New +** → **Web Service**
2. Select your `hazina-asili` GitHub repository
3. Render will detect the `render.yaml` automatically — click **Apply**

   > If it doesn't auto-detect, set these manually:
   > - **Runtime:** Docker
   > - **Dockerfile Path:** `./Dockerfile`
   > - **Plan:** Free

4. Click **Create Web Service**

### 3c. Set Environment Variables

In the Render dashboard for your service, go to **Environment** tab and add these:

| Key            | Value                                          |
|----------------|------------------------------------------------|
| `DB_HOST`      | your TiDB host (from Step 2)                   |
| `DB_PORT`      | `4000`                                         |
| `DB_USER`      | your TiDB username (from Step 2)               |
| `DB_PASS`      | your TiDB password (from Step 2)               |
| `DB_NAME`      | `hazina_asili`                                 |
| `DB_SSL`       | `true`                                         |
| `APP_URL`      | `https://hazina-asili.onrender.com/` (set after first deploy — use your actual URL) |
| `ADMIN_USERNAME` | `admin`                                      |
| `ADMIN_PASSWORD` | a strong password e.g. `MyAdmin@2024!`       |

> All other variables already have defaults set in `render.yaml`.

### 3d. Deploy

1. Click **Manual Deploy** → **Deploy latest commit**
2. Watch the build logs — the Docker image builds in ~3–5 minutes
3. Once you see `Your service is live`, click the URL at the top

---

## Step 4 — First Login

Open your Render URL (e.g. `https://hazina-asili.onrender.com`) and log in:

| Role       | Username (email)                  | Password              |
|------------|-----------------------------------|-----------------------|
| Admin      | Uses `ADMIN_USERNAME` env var     | Uses `ADMIN_PASSWORD` env var |

> On first visit after inactivity the app may take ~30 seconds to wake up (free tier cold start). This is normal.

---

## Step 5 — Update APP_URL

After your first deploy:

1. Copy your Render URL (e.g. `https://hazina-asili.onrender.com/`)
2. Go to Render dashboard → **Environment** → edit `APP_URL`
3. Paste your URL (include the trailing slash)
4. Save — Render will auto-redeploy

---

## Files Changed for This Deployment

| File | What Changed |
|------|-------------|
| `Dockerfile` | PORT is now read dynamically from env at runtime via `/start.sh` |
| `render.yaml` | Added `DB_PORT`, `DB_SSL=true`, all env vars with comments |
| `config/database.php` | DSN now includes port; SSL handling cleaned up for TiDB |
| `.env.example` | Added TiDB section with example values |

---

## Free Tier Limits Reference

| Service | Limit | Notes |
|---------|-------|-------|
| Render web service | 750 hrs/month | Enough for 1 always-on app |
| Render free tier | Spins down after 15 min inactivity | ~30s cold start on next visit |
| TiDB Serverless | 5GB storage, 50M reads/month | More than enough for a research DB |
| TiDB Serverless | No expiry, no credit card | Free forever |

---

## Troubleshooting

**App shows "Database Error"**
- Double-check `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME` in Render env vars
- Make sure `DB_SSL=true` is set
- Check that the schema was imported in TiDB SQL Editor

**Build fails in Render logs**
- Check that your GitHub repo has the `Dockerfile` and `composer.json` at the root
- Make sure `vendor/` is in `.gitignore` (Composer runs during Docker build)

**Login redirects loop**
- Make sure `APP_URL` is set to your exact Render URL including `https://` and trailing `/`
- Make sure `FORCE_HTTPS=true` is set

**Cold start is slow**
- This is normal on the free tier — Render spins down idle services after 15 min
- First request after inactivity takes ~30 seconds to wake up

---

## Optional — AI Assistant (Free via Groq)

The app has a built-in AI assistant. To enable it for free:

1. Sign up at [console.groq.com](https://console.groq.com) (free, no credit card)
2. Create an API key
3. In Render env vars, set:
   - `AI_ENABLED` = `true`
   - `AI_API_KEY` = your Groq API key
   - `AI_API_URL` = `https://api.groq.com/openai/v1/chat/completions`
   - `AI_MODEL` = `llama-3.3-70b-versatile`
