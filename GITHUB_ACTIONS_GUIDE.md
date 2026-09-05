# 🐙 GitHub Actions Automated Deployment Guide

This project includes a fully automated **GitHub Actions Workflow** (`.github/workflows/deploy.yml`) that automatically builds your application and deploys it to your shared hosting `public_html` directory whenever you push code to GitHub.

---

## ⚡ Quick Setup Guide (3 Steps)

### Step 1: Push Code to your GitHub Repository

In your terminal or Command Prompt inside the project folder (`C:\Users\rohan\.gemini\antigravity\scratch\box_bulk_retailer`), run:

```bash
git init
git add .
git commit -m "Initial commit: BOXRETAIL web app with zero-config PHP API and GitHub Actions"
git branch -M main
git remote add origin https://github.com/YOUR_GITHUB_USERNAME/YOUR_REPOSITORY_NAME.git
git push -u origin main
```

*(Replace `YOUR_GITHUB_USERNAME` and `YOUR_REPOSITORY_NAME` with your actual GitHub repository URL).*

---

### Step 2: Configure GitHub Repository Secrets

1. Go to your repository page on GitHub (e.g. `https://github.com/username/repository`).
2. Click **Settings** (top menu bar of the repository).
3. In the left sidebar, expand **Secrets and variables** -> click **Actions**.
4. Click **New repository secret** and add the following 3 secrets:

| Secret Name | Description / Example Value |
| :--- | :--- |
| `FTP_SERVER` | Your shared hosting FTP host (e.g. `ftp.yourdomain.com` or `123.45.67.89`) |
| `FTP_USERNAME` | Your cPanel or FTP username (e.g. `u12345678` or `admin@yourdomain.com`) |
| `FTP_PASSWORD` | Your cPanel or FTP password |

---

### Step 3: Trigger Automated Deployment

Every time you commit and push changes to the `main` or `master` branch:

```bash
git add .
git commit -m "Update homepage UI design"
git push origin main
```

GitHub Actions will automatically:
1. Check out your code.
2. Install dependencies & run `npm run build`.
3. Copy native PHP API endpoints & database into `dist/`.
4. Upload all built files directly to `public_html/` on your live shared hosting web server!

---

## 🔍 How to Monitor Deployment Status

1. Go to your GitHub repository on `GitHub.com`.
2. Click the **Actions** tab at the top.
3. You will see live logs for the **"Deploy BOXRETAIL to Shared Hosting (public_html)"** workflow.
4. When green checkmark appears ✅, your live website is updated!

---

## 🔒 Preserved Admin & Employee Logins

- **Master Admin:** `admin` / `admin123`
- **Employee:** `emp_john` / `boxemp123`
