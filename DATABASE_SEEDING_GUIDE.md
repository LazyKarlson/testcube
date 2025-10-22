# Database Seeding Guide

## 🎯 What Will Be Created

### **Users**
- **2 Admin users**: `admin1@example.com`, `admin2@example.com`
- **5 Editor users**: `editor1@example.com` ... `editor5@example.com`
- **50 Author users**: `author1@example.com` ... `author50@example.com`
- **Total**: 57 users
- **Password for all**: `password`

### **Posts**
- **1-10 posts per author** (50 authors × 1-10 posts = ~250-500 posts)
- **~100 words each** with meaningful, themed content
- **80% published**, 20% draft
- **5 themed topics**:
  1. AI in Healthcare
  2. Sustainable Living
  3. Remote Work & Productivity
  4. Mindful Eating
  5. Cybersecurity

### **Comments**
- **5-50 comments per post** (~1,250-25,000 comments total)
- **Themed comments** related to post content (70%)
- **Generic comments** (30%)
- **From users with roles**: Admin, Editor, Author (no Viewers)
- **Same user can comment multiple times** on a post

---

## 🚀 Seeding Commands

### **Option 1: Complete Fresh Start (Recommended)**
Drops all tables, recreates them, and seeds everything:

```bash
cd src
php artisan migrate:fresh --seed
```

**This will create**:
- ✅ All database tables
- ✅ Roles and permissions
- ✅ 57 users (2 admins, 5 editors, 50 authors)
- ✅ ~250-500 posts with realistic content
- ✅ ~1,250-25,000 comments

**Estimated time**: 30-60 seconds

---

### **Option 2: Seed Only (Keep Existing Data)**
Runs seeders without dropping tables:

```bash
cd src
php artisan db:seed
```

⚠️ **Warning**: This will create duplicate data if run multiple times.

---

### **Option 3: Seed Specific Seeders**

#### Seed only users:
```bash
cd src
php artisan db:seed --class=UsersSeeder
```

#### Seed only posts and comments:
```bash
cd src
php artisan db:seed --class=PostsAndCommentsSeeder
```

#### Seed only roles and permissions:
```bash
cd src
php artisan db:seed --class=RolesAndPermissionsSeeder
```

---

## 📊 Expected Data Volume

| Item | Minimum | Maximum | Average |
|------|---------|---------|---------|
| **Users** | 57 | 57 | 57 |
| **Posts** | 50 | 500 | ~275 |
| **Comments** | 250 | 25,000 | ~12,625 |
| **Total Records** | ~357 | ~25,557 | ~12,957 |

---

## 🎨 Post Themes & Content

### **1. AI in Healthcare**
- **Title**: "The Future of Artificial Intelligence in Healthcare"
- **Topics**: Diagnostic imaging, personalized treatment, robotic surgery, drug discovery
- **Comments**: Technical discussions, ethical concerns, real-world experiences

### **2. Sustainable Living**
- **Title**: "Sustainable Living: Small Changes That Make a Big Impact"
- **Topics**: Reducing plastics, composting, energy efficiency, local shopping
- **Comments**: Personal success stories, practical tips, cost savings

### **3. Remote Work**
- **Title**: "Remote Work Revolution: Productivity Tips for Digital Nomads"
- **Topics**: Workspace setup, productivity techniques, work-life balance
- **Comments**: Tool recommendations, routine sharing, burnout prevention

### **4. Mindful Eating**
- **Title**: "The Art of Mindful Eating: Transform Your Relationship with Food"
- **Topics**: Hunger cues, eating slowly, food awareness, gratitude
- **Comments**: Weight loss stories, habit changes, nutritionist insights

### **5. Cybersecurity**
- **Title**: "Cybersecurity Essentials: Protecting Your Digital Life in 2024"
- **Topics**: Password management, 2FA, VPNs, phishing, backups
- **Comments**: Tool recommendations, personal security incidents, best practices

---

## 🔍 Sample Data Preview

### **Sample Post**
```
Title: The Future of Artificial Intelligence in Healthcare
Author: author23@example.com
Status: published
Body: Artificial intelligence is revolutionizing healthcare in unprecedented 
ways. From diagnostic imaging to personalized treatment plans, AI algorithms 
are helping doctors make more accurate decisions faster than ever before...
(~100 words total)
```

### **Sample Comments**
```
1. "This is fascinating! I recently read about AI detecting diabetic 
    retinopathy with 95% accuracy." - admin1@example.com

2. "Great article! However, we need to address the ethical concerns around 
    patient data privacy." - editor3@example.com

3. "As a healthcare professional, I can confirm AI is already making a huge 
    difference in our daily work." - author15@example.com
```

---

## ⚡ Quick Start

**One command to set up everything**:

```bash
cd src && php artisan migrate:fresh --seed
```

Then test the API:

```bash
# Get all posts
curl http://localhost:85/api/posts | jq

# Get statistics
curl http://localhost:85/api/stats/posts | jq
curl http://localhost:85/api/stats/comments | jq
curl http://localhost:85/api/stats/users | jq
```

---

## 🧪 Testing the Seeded Data

### **Login as different users**:

```bash
# Login as admin
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin1@example.com","password":"password"}'

# Login as editor
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"editor1@example.com","password":"password"}'

# Login as author
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"author1@example.com","password":"password"}'
```

### **Check statistics**:

```bash
# Total posts by status
curl http://localhost:85/api/stats/posts | jq '.posts_by_status'

# Top commented posts
curl http://localhost:85/api/stats/posts | jq '.top_commented_posts'

# Comment activity by hour
curl http://localhost:85/api/stats/comments | jq '.activity_by_hour'

# Top authors
curl http://localhost:85/api/stats/users | jq '.top_authors_by_posts'
```

---

## 🎯 Seeder Features

### **Smart Content Generation**
- ✅ Realistic, themed post content (~100 words)
- ✅ Contextual comments related to post topics
- ✅ Mix of themed (70%) and generic (30%) comments
- ✅ Unique post titles with variations
- ✅ Realistic timestamps (posts from last year, comments after posts)

### **Realistic Distribution**
- ✅ 1-10 posts per author (random)
- ✅ 5-50 comments per post (random)
- ✅ 80% published, 20% draft posts
- ✅ Comments from admins, editors, and authors (no viewers)
- ✅ Same user can comment multiple times

### **Data Integrity**
- ✅ All foreign keys properly set
- ✅ Timestamps are logical (comments after posts)
- ✅ Published posts have published_at dates
- ✅ Draft posts have null published_at

---

## 📝 Notes

- **Uniqueness**: Post titles are made unique by adding prefixes like "Exploring", "Understanding", etc.
- **Timestamps**: Posts are backdated up to 365 days, comments are created after their posts
- **Roles**: Only users with admin, editor, or author roles can comment (viewers excluded)
- **Repeatability**: Running the seeder multiple times will create duplicate data
- **Performance**: Seeding ~13,000 records takes about 30-60 seconds

---

## 🔄 Re-seeding

To completely reset and re-seed:

```bash
cd src
php artisan migrate:fresh --seed
```

This will:
1. Drop all tables
2. Run all migrations
3. Seed roles and permissions
4. Create 57 users
5. Create ~275 posts
6. Create ~12,625 comments

**Total time**: ~30-60 seconds

---

## ✅ Verification

After seeding, verify the data:

```bash
# Check user count
curl http://localhost:85/api/stats/users | jq '.total_users'
# Expected: 57

# Check post count
curl http://localhost:85/api/stats/posts | jq '.total_posts'
# Expected: 50-500

# Check comment count
curl http://localhost:85/api/stats/comments | jq '.total_comments'
# Expected: 250-25,000

# Check posts by status
curl http://localhost:85/api/stats/posts | jq '.posts_by_status'
# Expected: ~80% published, ~20% draft
```

---

## 🎉 Ready to Seed!

Run this command to get started:

```bash
cd src && php artisan migrate:fresh --seed
```

Enjoy your fully populated database with realistic, themed content! 🚀

