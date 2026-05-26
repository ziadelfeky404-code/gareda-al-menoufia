# Product Requirements Document (PRD)

## Product: بوابة جامعة المنوفية الإخبارية
### Menoufia University News Portal

**Version:** 1.0  
**Date:** May 2026  
**Platform:** Web (Vercel Serverless)  
**Stack:** Node.js + Express + EJS  

---

## 1. Product Overview

**بوابة جامعة المنوفية الإخبارية** is a bilingual (Arabic-primary) digital news portal serving as the official student-run newspaper of Menoufia University, Egypt. It is produced by the Media Department graduating class of 2025–2026 as a special annual training publication commemorating the university's **Golden Jubilee (50th anniversary)**.

The system is a full-featured Content Management System (CMS) that enables student journalists to publish, manage, and organize news articles across 8 predefined sections, with administrative controls for homepage layout, section ordering, and site-wide settings.

### 1.1 Brand Identity

| Attribute | Value |
|-----------|-------|
| Site Name | جامعة المنوفية |
| Subtitle | البوابة الإخبارية الرسمية |
| Theme Color | Green (`#157039`) — representing the university identity |
| Font Stack | Tajawal, Cairo (Google Fonts), RTL layout |
| Icon Set | Font Awesome 6.4 |

---

## 2. Product Goals

### 2.1 Primary Goals

1. **Publish university news** digitally with a modern, responsive interface
2. **Train journalism students** in real-world digital publishing workflows
3. **Showcase university achievements** to students, faculty, and the public
4. **Commemorate the Golden Jubilee** with a dedicated visual identity

### 2.2 Technical Goals

1. **Zero-cost hosting** on Vercel free tier (no credit card required)
2. **Complete PHP-to-Node.js migration** for Vercel compatibility (PHP >250MB on Vercel)
3. **Preserve all existing data** (425+ articles, settings, messages)
4. **Maintain identical Arabic RTL design** and user experience
5. **Enable admin control** without requiring git commits for content changes

---

## 3. Target Audience

| Segment | Description | Key Needs |
|---------|-------------|-----------|
| University Administration | President, vice presidents, deans | Official news dissemination, achievement showcasing |
| Faculty & Staff | Professors, researchers, admin staff | Event announcements, institutional updates |
| Students | Undergrad and graduate students (17 colleges) | Campus news, competitions, leadership profiles |
| Alumni & Prospective Students | Former graduates, applicants | University reputation, achievements, facilities |
| General Public | Local community, media, researchers | University transparency, community engagement |

---

## 4. Functional Requirements

### 4.1 Public-Facing Pages

| Feature | Priority | Description |
|---------|----------|-------------|
| Homepage | P0 | Hero slideshow (7 slides), breaking news ticker, side panels (4), most-read (4), 8 section grids, golden jubilee branding |
| Article View | P0 | Full article with paragraphs, gallery images, breadcrumbs, social sharing, font size controls, lightbox, related articles |
| Section Listing | P0 | Grid view of articles filtered by section; Timeline view for ordered sections |
| Search | P0 | Full-text search with Arabic normalization, filters (section, author, date range), scoring algorithm |
| Contact Form | P1 | Name, email, message with validation, stored in messages.json |
| RSS Feed | P1 | RSS 2.0 feed of latest 20 articles with enclosures |
| 404 Page | P1 | Custom 404 error page |
| Responsive Design | P0 | Bootstrap 5.3 grid, mobile breakpoints (991px, 767px, 480px) |

### 4.2 Admin Pages (Authenticated)

| Feature | Priority | Description |
|---------|----------|-------------|
| Dashboard | P0 | Statistics: total articles, per-section counts, recent articles, unread messages |
| Article CRUD | P0 | Create, read, update, delete articles with full form (title, section, author, date, tags, images, paragraphs, gallery) |
| Article Import | P1 | Import from HTML, DOCX, or PDF files — auto-extract title, paragraphs, images |
| Quick Add | P0 | Streamlined article creation accessible from any section page |
| Section Management | P0 | Add/delete sections (8 default sections, custom sections supported) |
| Section Ordering | P0 | Drag-and-drop reorder articles within a section; Timeline view for ordered sections |
| Homepage Manager | P0 | Visual configuration of ticker IDs, hero slide pins, side panel pins, most-read IDs |
| Settings | P0 | Site name, colors, social links, contact info, editor info, logo URLs |
| Messages | P1 | View, mark-as-read, delete contact form submissions |
| Login/Logout | P0 | Admin authentication via cookie-session (credentials: admin / password) |

### 4.3 Content Structure

**Article Schema:**
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| id | Number | Yes | Auto-increment |
| section | String | Yes | One of 8 predefined sections |
| sectionFile | String | No | Original source HTM filename |
| title | String | Yes | Article headline |
| image | String | No | Main image URL (upload or external) |
| cover_image | String | No | Cover/hero image URL |
| image_desc | String | No | Image alt text/caption |
| date | String | No | Publication date (Arabic or Gregorian) |
| author | String | No | Author name |
| tags | Array | No | Comma-separated tags |
| paragraphs | Array | No | Array of paragraph strings |
| images | Array | No | Gallery array of { url, desc } |

**Sections (8 defaults):**
| # | Name | Slug | Status |
|---|------|------|--------|
| 1 | أخبار المنوفية | akhbar | Active |
| 2 | منشآت الجامعة | monshat | Active |
| 3 | ندوات ومؤتمرات | nadawat | Active |
| 4 | تكريم ومسابقات | takreem | Active |
| 5 | الفن والمسابقات | fann | Active |
| 6 | رياضة ومسابقات | ryada | Active |
| 7 | قيادات جامعية | qiyadat | Active |
| 8 | تقارير | takarer | Active |

### 4.4 Homepage Layout

```
┌─────────────────────────────────────────────────┐
│  HEADER: University Logo | Golden Jubilee | Info │
├─────────────────────────────────────────────────┤
│  NAVBAR: Home | 8 Sections | Search | Admin Bar  │
├─────────────────────────────────────────────────┤
│  TICKER: Scrolling breaking news (configurable)  │
├──────────────────┬──────────────────────────────┤
│  HERO SLIDESHOW  │  HERO SIDE (4 cards)          │
│  (7 slides)      │  (2+2 layout)                 │
├──────────────────┴──────────────────────────────┤
│  FULL-WIDTH FEATURED (art & competition section) │
├─────────────────────────────────────────────────┤
│  أخبار المنوفية SECTION BLOCK (8 articles)      │
├─────────────────────────────────────────────────┤
│  منشآت الجامعة SECTION BLOCK (4 articles)       │
├─────────────────────────────────────────────────┤
│  ندوات ومؤتمرات SECTION BLOCK (4 articles)      │
├─────────────────────────────────────────────────┤
│  تكريم ومسابقات SECTION BLOCK (4+2 articles)    │
├─────────────────────────────────────────────────┤
│  الفن والمسابقات SECTION BLOCK (4+2 articles)   │
├─────────────────────────────────────────────────┤
│  رياضة ومسابقات SECTION BLOCK (4 articles)      │
├─────────────────────────────────────────────────┤
│  قيادات جامعية SECTION BLOCK (4 articles)       │
├─────────────────────────────────────────────────┤
│  تقارير SECTION BLOCK (4 articles)              │
├─────────────────────────────────────────────────┤
│  FOOTER: About | Sections | Contact | Copyright  │
└─────────────────────────────────────────────────┘
```

---

## 5. Non-Functional Requirements

### 5.1 Performance
- Page load time < 2s on Vercel (cold start ~200ms additional)
- Static assets (CSS, JS, images) cached via CDN
- Article JSON cached in module memory during function lifetime
- Pagination: 20 articles per page in admin, 6 per page in search

### 5.2 Security
- Admin session via HMAC-signed cookies (cookie-session)
- No database — JSON file storage (no SQL injection risk)
- File upload size limited to 10MB
- Read-only filesystem on Vercel (except `/tmp`)
- `.htaccess` blocks direct access to `/data/` and `/includes/`
- No user registration — single admin account

### 5.3 Availability
- 99.9% uptime via Vercel's global CDN
- Stateless serverless functions
- Auto-scaling with Vercel free tier (100GB bandwidth, 100h compute)

### 5.4 Data Persistence (Vercel Constraint)
Vercel's serverless functions have a **read-only filesystem** (except `/tmp`). This introduces critical limitations:

| Operation | Behavior on Vercel |
|-----------|-------------------|
| Reading articles.json | Works (read from `/var/task/data/` or `/tmp/data/`) |
| Writing articles.json | Works (writes to `/tmp/data/` — persists within function instance lifetime) |
| Cold start data | `/tmp/data/` seeded from project data directory |
| Data across cold starts | **Changes are lost** — next cold start reloads from git-deployed state |

**Mitigation:** Admin users should periodically push changes to GitHub (auto-deploys to Vercel) to persist data permanently.

---

## 6. Technical Architecture

### 6.1 Stack

```
Frontend:  EJS Templates + Bootstrap 5.3 + Custom CSS + Font Awesome 6.4
Backend:   Node.js + Express 4.18
Storage:   JSON files (articles.json, settings.json, messages.json, users.json)
Auth:      cookie-session (HMAC-signed cookies)
Uploads:   multer (local: uploads/ dir, Vercel: /tmp/uploads/)
Deploy:    Vercel (via GitHub auto-deploy)
```

### 6.2 Directory Structure

```
/
├── api/index.js          # Express server (all routes)
├── views/                # EJS templates
│   ├── partials/         # Reusable components (header, topbar, navbar, footer)
│   └── admin/            # Admin panel templates (12 pages)
├── data/                 # JSON storage (articles, settings, messages, users)
├── uploads/              # Image uploads (local)
├── public/               # Static assets
├── vercel.json           # Vercel deployment config
└── package.json          # Dependencies
```

### 6.3 Route Map (Node.js Express)

| Path | Type | Handler |
|------|------|---------|
| `/` | Public | Homepage render |
| `/section` | Public | Section listing (param: slug) |
| `/article` | Public | Single article (param: id) |
| `/search` | Public | Full-text search |
| `/contact` | Public | Contact form (GET + POST) |
| `/rss` | Public | RSS feed |
| `/admin/*` | Protected | 14 admin routes (requireAdmin middleware) |
| `/__debug` | Diagnostic | Deployment verification |

---

## 7. Limitations & Known Issues

| Issue | Severity | Description |
|-------|----------|-------------|
| Data persistence on Vercel | High | Writes to JSON files survive only within function instance lifetime; cold start resets to git state |
| No database | Medium | JSON files not suitable for concurrent writes or large-scale data |
| Single admin account | Medium | No multi-user support or role-based access |
| Hardcoded credentials | Medium | Admin password hardcoded in source (admin/password) |
| No CI/CD | Low | No automated testing or staging environment |
| Image storage | Medium | Uploaded images on Vercel stored in `/tmp` — lost on cold start |
| Arabic date parsing | Low | Mixed Arabic/Gregorian date formats require custom parsing logic |
| PHP artifacts | Low | `<br /><b>Warning</b>` HTML remains in some article paragraphs from original PHP import |

---

## 8. Future Roadmap

### Phase 1: Persistence (Priority: Critical)
- [ ] Implement Vercel KV (Redis) for data persistence across cold starts
- [ ] OR integrate GitHub API for write-back to repo on every save
- [ ] OR migrate to SQLite / Supabase free tier

### Phase 2: Features (Priority: High)
- [ ] Multi-user admin with role-based permissions
- [ ] Media library for managing uploaded images
- [ ] WYSIWYG editor for article body
- [ ] Scheduled publishing (draft/publish dates)
- [ ] Article categories and subcategories

### Phase 3: Engagement (Priority: Medium)
- [ ] Newsletter subscription with email notifications
- [ ] Comments/disqus integration
- [ ] Analytics dashboard (page views, popular articles)
- [ ] Push notifications for breaking news
- [ ] Social media auto-posting

### Phase 4: Scale (Priority: Low)
- [ ] Search indexing with Elasticsearch or Meilisearch
- [ ] Image CDN (Cloudinary, Imgix)
- [ ] Multi-language support (English version)
- [ ] Mobile app (PWA or native)
- [ ] API for external consumption

---

## 9. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Page load time | < 2s | Browser DevTools |
| Admin save success | 100% | No 500 errors |
| Article count | 425+ (existing) | Dashboard count |
| Responsive layout | All pages | Mobile/tablet testing |
| Data persistence | Changes survive > 1 hour | Cold start test |
| Build time | < 2 min | Vercel deploy log |

---

*Prepared for جامعة المنوفية — قسم الإعلام — دفعة ٢٠٢٥-٢٠٢٦م*
