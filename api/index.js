const express = require('express');
const session = require('cookie-session');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const crypto = require('crypto');

const app = express();

// --- Paths ---
const ROOT = path.resolve(__dirname, '..');
const UPLOADS_PATH = path.join(ROOT, 'uploads');

// On Vercel the filesystem is read-only except /tmp, so we store data files there
const DATA_PATH = (() => {
  const p = process.env.VERCEL ? '/tmp/data' : path.join(ROOT, 'data');
  if (!fs.existsSync(p)) fs.mkdirSync(p, { recursive: true });
  // On Vercel cold start, seed /tmp/data from the project data (read-only)
  if (process.env.VERCEL) {
    try {
      const projectData = path.join(ROOT, 'data');
      if (fs.existsSync(projectData)) {
        for (const f of fs.readdirSync(projectData)) {
          const dest = path.join(p, f);
          if (!fs.existsSync(dest)) {
            fs.copyFileSync(path.join(projectData, f), dest);
          }
        }
      }
    } catch (e) { console.error('Seed /tmp/data error:', e.message); }
  }
  return p;
})();

const ARTICLES_FILE = path.join(DATA_PATH, 'articles.json');
const SETTINGS_FILE = path.join(DATA_PATH, 'settings.json');
const MESSAGES_FILE = path.join(DATA_PATH, 'messages.json');

const ALL_SECTIONS = ['أخبار المنوفية', 'منشآت الجامعة', 'ندوات ومؤتمرات', 'تكريم ومسابقات', 'الفن والمسابقات', 'رياضة ومسابقات', 'قيادات جامعية', 'تقارير'];
const SECTION_FILE_MAP = {
  'أخبار المنوفية': 'اخبار المنوفية.htm',
  'منشآت الجامعة': 'منشات الجامعه.htm',
  'ندوات ومؤتمرات': 'ندوات ومؤتمرات.htm',
  'تكريم ومسابقات': 'تكريم ومسابقات.htm',
  'الفن والمسابقات': 'صفحه الفن والمسابقات اخير.htm',
  'رياضة ومسابقات': '',
  'قيادات جامعية': '',
  'تقارير': ''
};

// --- Data helpers (module-level cache) ---
let _articles = null;
let _settings = null;

function loadArticles() {
  if (_articles) return _articles;
  try {
    if (fs.existsSync(ARTICLES_FILE)) {
      _articles = JSON.parse(fs.readFileSync(ARTICLES_FILE, 'utf8'));
    }
  } catch (e) { _articles = []; }
  return _articles || [];
}

function loadSettings() {
  if (_settings) return _settings;
  try {
    if (fs.existsSync(SETTINGS_FILE)) {
      _settings = JSON.parse(fs.readFileSync(SETTINGS_FILE, 'utf8'));
    }
  } catch (e) { _settings = {}; }
  return _settings || {};
}

function saveArticles(data) {
  _articles = data;
  const dir = path.dirname(ARTICLES_FILE);
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(ARTICLES_FILE, JSON.stringify(data, null, 4), 'utf8');
}

function saveSettings(data) {
  _settings = data;
  const dir = path.dirname(SETTINGS_FILE);
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(SETTINGS_FILE, JSON.stringify(data, null, 4), 'utf8');
}

function getSetting(key, def) {
  const s = loadSettings();
  return s[key] !== undefined ? s[key] : (def !== undefined ? def : '');
}

function setSetting(key, val) {
  const s = loadSettings();
  s[key] = val;
  saveSettings(s);
}

function getArticle(id) {
  const arts = loadArticles();
  return arts.find(a => a.id === parseInt(id)) || null;
}

function getNextId() {
  const arts = loadArticles();
  let max = 0;
  for (const a of arts) { if (a.id > max) max = a.id; }
  return max + 1;
}

function getArticlesBySection(section) {
  return loadArticles().filter(a => a.section === section);
}

function getSections() {
  const saved = getSetting('sections', []);
  if (saved && saved.length > 0) return saved;
  const defaults = [
    { name: 'أخبار المنوفية', slug: 'akhbar' },
    { name: 'منشآت الجامعة', slug: 'monshat' },
    { name: 'ندوات ومؤتمرات', slug: 'nadawat' },
    { name: 'تكريم ومسابقات', slug: 'takreem' },
    { name: 'الفن والمسابقات', slug: 'fann' },
    { name: 'رياضة ومسابقات', slug: 'ryada' },
    { name: 'قيادات جامعية', slug: 'qiyadat' },
    { name: 'تقارير', slug: 'takarer' }
  ];
  setSetting('sections', defaults);
  return defaults;
}

function sectionSlug(sectionName) {
  const map = {
    'أخبار المنوفية': 'akhbar',
    'منشآت الجامعة': 'monshat',
    'ندوات ومؤتمرات': 'nadawat',
    'تكريم ومسابقات': 'takreem',
    'الفن والمسابقات': 'fann',
    'رياضة ومسابقات': 'ryada',
    'قيادات جامعية': 'qiyadat',
    'تقارير': 'takarer'
  };
  return map[sectionName] || '';
}

function saveSections(data) {
  setSetting('sections', data);
}

function normalizeArabic(s) {
  s = s.replace(/\s+/g, ' ').trim();
  s = s.replace(/[أإآٱ]/g, 'ا');
  s = s.replace(/[ىئ]/g, 'ي');
  s = s.replace(/ة/g, 'ه');
  s = s.replace(/[\u064B-\u065F\u0670]/g, '');
  return s;
}

function dateToKey(d) {
  const arabicMonths = { 'يناير': '01', 'فبراير': '02', 'مارس': '03', 'أبريل': '04', 'مايو': '05', 'يونيو': '06', 'يوليو': '07', 'أغسطس': '08', 'سبتمبر': '09', 'أكتوبر': '10', 'نوفمبر': '11', 'ديسمبر': '12' };
  if (!d) return '';
  const parts = d.trim().split(' ');
  if (parts.length < 3) return d;
  const day = String(parts[0]).padStart(2, '0');
  const month = arabicMonths[parts[1]] || '00';
  const year = parts[2] || '0000';
  return year + '-' + month + '-' + day;
}

function parseArticleDate(dateStr) {
  const arMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
  const enMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  let d = dateStr;
  for (let i = 0; i < arMonths.length; i++) {
    d = d.replace(arMonths[i], enMonths[i]);
  }
  const ts = Date.parse(d);
  return ts ? new Date(ts).toUTCString() : new Date().toUTCString();
}

// --- Multer setup for file uploads ---
const UPLOADS_DIR = process.env.VERCEL ? '/tmp/uploads' : UPLOADS_PATH;
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    if (!fs.existsSync(UPLOADS_DIR)) fs.mkdirSync(UPLOADS_DIR, { recursive: true });
    cb(null, UPLOADS_DIR);
  },
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname);
    const name = 'article_' + Date.now() + '_' + crypto.randomBytes(4).toString('hex') + ext;
    cb(null, name);
  }
});
const upload = multer({
  storage,
  limits: { fileSize: 10 * 1024 * 1024 }
});

// --- Middleware ---
app.set('view engine', 'ejs');
app.set('views', path.join(ROOT, 'views'));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));
app.use(express.json({ limit: '10mb' }));

app.use(session({
  name: 'admin_session',
  secret: 'menofia-news-secret-key-2026',
  maxAge: 24 * 60 * 60 * 1000,
  httpOnly: true,
  sameSite: 'lax'
}));

// Static files
app.use('/uploads', express.static(UPLOADS_PATH));
if (process.env.VERCEL) app.use('/uploads', express.static('/tmp/uploads'));
app.use(express.static(path.join(ROOT, 'public')));

// --- Admin auth middleware ---
function requireAdmin(req, res, next) {
  if (req.session && req.session.admin) return next();
  res.redirect('/admin/login');
}

// --- Make helpers available in all views ---
app.use((req, res, next) => {
  res.locals.getSetting = getSetting;
  res.locals.getSections = getSections;
  res.locals.sectionSlug = sectionSlug;
  res.locals.getArticle = getArticle;
  res.locals.getArticlesBySection = getArticlesBySection;
  res.locals.loadArticles = loadArticles;
  res.locals.sectionSlug = sectionSlug;
  res.locals.isAdmin = !!(req.session && req.session.admin);
  res.locals.req = req;
  res.locals.path = req.path;
  next();
});

// ============================================================
// PUBLIC ROUTES
// ============================================================

// --- HOME ---
app.get('/', (req, res) => {
  const articles = loadArticles();
  const sections = ALL_SECTIONS;
  const sectionArticles = {};
  for (const sec of sections) {
    const arts = getArticlesBySection(sec).reverse();
    sectionArticles[sec] = arts.slice(0, 8);
  }
  const akhbarArts = sectionArticles['أخبار المنوفية'] || [];
  const monshatArts = sectionArticles['منشآت الجامعة'] || [];
  const nadawatArts = sectionArticles['ندوات ومؤتمرات'] || [];
  const takreemArts = sectionArticles['تكريم ومسابقات'] || [];
  const fannArts = sectionArticles['الفن والمسابقات'] || [];
  const ryadaArts = sectionArticles['رياضة ومسابقات'] || [];
  const qiyadatArts = sectionArticles['قيادات جامعية'] || [];
  const takarerArts = sectionArticles['تقارير'] || [];

  // Ticker — auto-fill with random articles if not enough
  const tickerIds = getSetting('ticker_news_ids', []);
  const tickerArticles = [];
  for (const tid of tickerIds) {
    const a = getArticle(tid);
    if (a) tickerArticles.push(a);
  }
  if (tickerArticles.length < 3) {
    const all = loadArticles();
    const shuffled = [...all].sort(() => Math.random() - 0.5);
    for (const a of shuffled) {
      if (!tickerArticles.find(t => t.id === a.id)) {
        tickerArticles.push(a);
        if (tickerArticles.length >= 10) break;
      }
    }
  }

  // Hero pins
  let heroPins = getSetting('hero_pins', []);
  let heroArts = [];
  for (const pid of heroPins) {
    const a = getArticle(pid);
    if (a) heroArts.push(a);
  }
  const allArts = [...articles].reverse();
  for (const aa of allArts) {
    if (heroArts.length >= 6) break;
    if (!heroArts.find(h => h.id === aa.id)) heroArts.push(aa);
  }
  while (heroArts.length < 6) { heroArts.push(null); }

  // Hero slides
  let heroSlidePins = getSetting('hero_slide_pins', []);
  let heroSlides = [];
  for (const sid of heroSlidePins) {
    const a = getArticle(sid);
    if (a) heroSlides.push(a);
  }
  for (const aa of allArts) {
    if (heroSlides.length >= 7) break;
    if (!heroSlides.find(h => h.id === aa.id)) heroSlides.push(aa);
  }
  if (heroSlides.length === 0) heroSlides.push(allArts[0] || null);

  const mostReadIds = [1, 2, 5, 7];
  const mostReadArticles = [];
  for (const id of mostReadIds) {
    const a = getArticle(id);
    if (a) mostReadArticles.push(a);
  }

  const urgentIds = [22, 23, 29, 28];
  const urgentArticles = [];
  for (const id of urgentIds) {
    const a = getArticle(id);
    if (a) urgentArticles.push(a);
  }

  res.render('index', {
    title: 'بوابة جامعة المنوفية الإخبارية',
    akhbarArts, monshatArts, nadawatArts, takreemArts, fannArts,
    ryadaArts, qiyadatArts, takarerArts,
    tickerArticles, heroArts, heroSlides,
    mostReadArticles, urgentArticles, articles
  });
});

// --- ARTICLE ---
app.get('/article', (req, res) => {
  const id = parseInt(req.query.id) || 0;
  const article = getArticle(id);
  if (!article) return res.redirect('/');

  const sameSection = getArticlesBySection(article.section);
  const shuffled = [...sameSection].sort(() => Math.random() - 0.5);
  const related = [];
  for (const ra of shuffled) {
    if (ra.id !== id) { related.push(ra); if (related.length >= 4) break; }
  }

  const mostRead = [...loadArticles()].sort(() => Math.random() - 0.5).slice(0, 6);
  const hourlyNews = [...loadArticles()].sort(() => Math.random() - 0.5).slice(0, 5);

  const currentUrl = req.protocol + '://' + req.get('host') + '/article?id=' + id;
  const articleTitle = article.title;
  const shareFb = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(currentUrl);
  const shareTw = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(articleTitle + ' ' + currentUrl);
  const shareLi = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(currentUrl);

  res.render('article', {
    title: article.title,
    article, related, mostRead, hourlyNews,
    currentUrl, shareFb, shareTw, shareLi
  });
});

// --- SECTION ---
app.get('/section', (req, res) => {
  const allSecs = getSections();
  const slugMap = {};
  for (const s of allSecs) { slugMap[s.slug] = s.name; }
  const slugReverse = {};
  for (const s of allSecs) { slugReverse[s.name] = s.slug; }

  const slug = (req.query.slug || '').trim();
  const section = slugMap[slug] || '';

  if (!section) {
    return res.status(404).render('404-section', { slug });
  }

  let sectionArticles = getArticlesBySection(section);
  const orders = getSetting('section_orders', {});
  const orderIds = orders[section] || [];
  if (orderIds.length > 0) {
    const ordered = [];
    const unordered = [];
    for (const a of sectionArticles) {
      const idx = orderIds.indexOf(a.id);
      if (idx !== -1) ordered[idx] = a;
      else unordered.push(a);
    }
    sectionArticles = ordered.filter(Boolean).concat(unordered.sort((a, b) => (b.id || 0) - (a.id || 0)));
  } else {
    sectionArticles = sectionArticles.reverse();
  }
  const page = Math.max(1, parseInt(req.query.page) || 1);
  const perPage = 12;
  const total = sectionArticles.length;
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  const offset = (page - 1) * perPage;
  const pageArticles = sectionArticles.slice(offset, offset + perPage);

  const view = (req.query.view || '').trim();
  const hasOrder = orderIds.length > 0;

  res.render('section', {
    title: section,
    section, slug, page, totalPages, pageArticles, sectionArticles,
    slugReverse, hasOrder, view
  });
});

// --- SEARCH ---
app.get('/search', (req, res) => {
  const query = (req.query.q || '').trim();
  const sectionFilter = (req.query.section || '').trim();
  const authorFilter = (req.query.author || '').trim();
  const fromDate = (req.query.from || '').trim();
  const toDate = (req.query.to || '').trim();
  const page = Math.max(1, parseInt(req.query.page) || 1);
  const perPage = 6;

  const results = [];
  const hasFilters = !!(query || sectionFilter || authorFilter || fromDate || toDate);

  if (hasFilters) {
    const articles = loadArticles();
    const q = query ? normalizeArabic(query) : '';
    const words = q ? q.split(' ') : [];
    const fromKey = fromDate ? dateToKey(fromDate) : '';
    const toKey = toDate ? dateToKey(toDate) : '';

    for (const a of articles) {
      if (sectionFilter && a.section !== sectionFilter) continue;
      if (authorFilter && normalizeArabic(a.author || '').indexOf(normalizeArabic(authorFilter)) === -1) continue;

      if (fromKey || toKey) {
        const aDateKey = dateToKey(a.date || '');
        if (!aDateKey) continue;
        if (fromKey && aDateKey < fromKey) continue;
        if (toKey && aDateKey > toKey) continue;
      }

      if (!query) { results.push({ article: a, score: 0 }); continue; }

      let score = 0;
      const wordCount = words.length;
      const titleNorm = normalizeArabic(a.title);
      const authorNorm = normalizeArabic(a.author || '');
      const secNorm = normalizeArabic(a.section);
      let hitFields = 0;

      if (q) {
        const exactTitle = titleNorm.indexOf(q) !== -1;
        if (exactTitle) { score += 100 * wordCount; hitFields++; }
        else if (words.length) {
          let mc = 0; for (const w of words) { if (titleNorm.indexOf(w) !== -1) mc++; }
          if (mc === wordCount) { score += 60; hitFields++; }
          else if (mc > 0) score += mc * 15;
        }

        const exactAuthor = authorNorm.indexOf(q) !== -1;
        if (exactAuthor) { score += 40 * wordCount; hitFields++; }
        else if (words.length) {
          for (const w of words) { if (authorNorm.indexOf(w) !== -1) { score += 20; hitFields++; break; } }
        }

        const tags = Array.isArray(a.tags) ? a.tags : (a.tags ? a.tags.split(',') : []);
        for (const tag of tags) {
          const tagNorm = normalizeArabic(tag.trim());
          if (tagNorm.indexOf(q) !== -1) { score += 30 * wordCount; hitFields++; break; }
          for (const w of words) { if (tagNorm.indexOf(w) !== -1) { score += 15; hitFields++; break; } }
        }

        const exactSec = secNorm.indexOf(q) !== -1;
        if (exactSec) { score += 20 * wordCount; hitFields++; }
        else if (words.length) {
          for (const w of words) { if (secNorm.indexOf(w) !== -1) { score += 10; hitFields++; break; } }
        }

        let paraExact = false, paraAll = false, paraAny = false;
        for (const p of a.paragraphs) {
          const pNorm = normalizeArabic(p);
          if (pNorm.indexOf(q) !== -1) { paraExact = true; break; }
          if (words.length) {
            let mc = 0; for (const w of words) { if (pNorm.indexOf(w) !== -1) mc++; }
            if (mc === wordCount) paraAll = true;
            else if (mc > 0) paraAny = true;
          }
        }
        if (paraExact) { score += 15 * wordCount; hitFields++; }
        else if (paraAll) { score += 8; hitFields++; }
        else if (paraAny) { score += 3; }

        if (hitFields >= 3) score += 40;
        else if (hitFields >= 2) score += 15;
      }
      if (score > 0) results.push({ article: a, score });
    }

    if (query) results.sort((x, y) => y.score - x.score);
  }

  const totalResults = results.length;
  const totalPages = Math.max(1, Math.ceil(totalResults / perPage));
  const offset = (page - 1) * perPage;
  const pageResults = results.slice(offset, offset + perPage);
  const allSections = getSections();

  res.render('search', {
    title: 'بحث',
    query, sectionFilter, authorFilter, fromDate, toDate,
    page, totalPages, totalResults, pageResults, results,
    allSections, hasFilters
  });
});

// --- CONTACT ---
app.get('/contact', (req, res) => {
  res.render('contact', { title: 'اتصل بنا', sent: false, error: '' });
});

app.post('/contact', (req, res) => {
  const name = (req.body.name || '').trim();
  const email = (req.body.email || '').trim();
  const message = (req.body.message || '').trim();
  let error = '';

  if (!name || !email || !message) {
    error = 'برجاء ملء جميع الحقول';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    error = 'البريد الإلكتروني غير صحيح';
  } else {
    let msgs = [];
    try {
      if (fs.existsSync(MESSAGES_FILE)) {
        msgs = JSON.parse(fs.readFileSync(MESSAGES_FILE, 'utf8'));
      }
    } catch (e) { msgs = []; }
    msgs.push({
      id: Date.now() + '_' + crypto.randomBytes(4).toString('hex'),
      name, email, message,
      date: new Date().toISOString().replace('T', ' ').substring(0, 19),
      read: false
    });
    const dir = path.dirname(MESSAGES_FILE);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(MESSAGES_FILE, JSON.stringify(msgs, null, 4), 'utf8');
    return res.render('contact', { title: 'اتصل بنا', sent: true, error: '' });
  }

  res.render('contact', { title: 'اتصل بنا', sent: false, error });
});

// --- RSS ---
app.get('/rss', (req, res) => {
  const siteName = getSetting('site_name', 'جامعة المنوفية');
  let siteUrl = getSetting('site_url', '');
  if (!siteUrl) {
    siteUrl = req.protocol + '://' + req.get('host');
  }
  const articles = loadArticles();
  const latest = [...articles].reverse().slice(0, 20);

  res.set('Content-Type', 'application/rss+xml; charset=utf-8');
  res.render('rss', {
    siteName, siteUrl, latest, parseArticleDate
  });
});

// --- 404 catch-all for .php URLs and unknown pages ---
app.get(['/index.php', '/article.php', '/section.php', '/search.php', '/contact.php', '/rss.php', '/404.php'], (req, res) => {
  // Redirect PHP URLs to clean URLs
  const map = {
    '/index.php': '/',
    '/article.php': '/article' + (req.url.indexOf('?') !== -1 ? req.url.substring(req.url.indexOf('?')) : ''),
    '/section.php': '/section' + (req.url.indexOf('?') !== -1 ? req.url.substring(req.url.indexOf('?')) : ''),
    '/search.php': '/search' + (req.url.indexOf('?') !== -1 ? req.url.substring(req.url.indexOf('?')) : ''),
    '/contact.php': '/contact',
    '/rss.php': '/rss',
    '/404.php': '/404'
  };
  const dest = map[req.path];
  if (dest) return res.redirect(301, dest + (req.url.indexOf('?') !== -1 ? '' : '') );
  res.redirect(301, '/');
});

// ============================================================
// ADMIN ROUTES
// ============================================================

// --- LOGIN ---
app.get('/admin/login', (req, res) => {
  if (req.session && req.session.admin) return res.redirect('/admin/dashboard');
  res.render('admin/login', { title: 'تسجيل الدخول', error: '', logo: getSetting('logo_url', '') });
});

app.post('/admin/login', (req, res) => {
  const username = req.body.username || '';
  const password = req.body.password || '';
  let error = '';

  if (username === 'admin' && password === 'password') {
    req.session.admin = { username: 'admin', display_name: 'مدير الموقع' };
    return res.redirect('/admin/dashboard');
  }
  error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
  res.render('admin/login', { title: 'تسجيل الدخول', error, logo: getSetting('logo_url', '') });
});

// --- LOGOUT ---
app.get('/admin/logout', (req, res) => {
  req.session = null;
  res.redirect('/admin/login');
});

// --- DASHBOARD ---
app.get(['/admin', '/admin/dashboard'], requireAdmin, (req, res) => {
  const articles = loadArticles();
  const totalArticles = articles.length;
  const sectionCounts = {};
  for (const s of ALL_SECTIONS) sectionCounts[s] = 0;
  for (const a of articles) {
    const sec = a.section || '';
    if (sectionCounts[sec] !== undefined) sectionCounts[sec]++;
  }
  const recentArticles = [...articles].reverse().slice(0, 5);

  let unreadMsgs = 0;
  try {
    if (fs.existsSync(MESSAGES_FILE)) {
      const allMsgs = JSON.parse(fs.readFileSync(MESSAGES_FILE, 'utf8'));
      if (Array.isArray(allMsgs)) {
        for (const m of allMsgs) { if (!m.read) unreadMsgs++; }
      }
    }
  } catch (e) {}

  res.render('admin/dashboard', {
    title: 'لوحة التحكم',
    totalArticles, sectionCounts, recentArticles, unreadMsgs,
    admin: req.session.admin
  });
});

// --- ARTICLES ---
app.get('/admin/articles', requireAdmin, (req, res) => {
  let articles = loadArticles();
  const sections = ['أخبار المنوفية', 'منشآت الجامعة', 'ندوات ومؤتمرات',
    'تكريم ومسابقات', 'الفن والمسابقات',
    'رياضة ومسابقات', 'قيادات جامعية', 'تقارير'];
  const selectedSection = req.query.section || '';

  if (selectedSection) {
    articles = articles.filter(a => a.section === selectedSection);
  }

  const perPage = 20;
  const page = Math.max(1, parseInt(req.query.p) || 1);
  const total = articles.length;
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  const offset = (page - 1) * perPage;
  const pageArticles = articles.slice(offset, offset + perPage);

  res.render('admin/articles', {
    title: 'المقالات',
    sections, selectedSection, page, totalPages, pageArticles, total,
    admin: req.session.admin
  });
});

// --- ARTICLE ADD ---
app.get('/admin/article-add', requireAdmin, (req, res) => {
  res.render('admin/article-add', {
    title: 'إضافة مقال',
    message: '', error: '',
    sections: ALL_SECTIONS,
    admin: req.session.admin, body: {}
  });
});

app.post('/admin/article-add', requireAdmin, upload.fields([
  { name: 'image_file', maxCount: 1 },
  { name: 'cover_image_file', maxCount: 1 },
  { name: 'gallery_files', maxCount: 20 }
]), (req, res) => {
  const sections = ALL_SECTIONS;
  const sectionFileMap = SECTION_FILE_MAP;

  let { title, section, image, cover_image, image_desc, date, author, tags, paragraphs, gallery_urls, gallery_descs } = req.body;
  title = (title || '').trim();
  let error = '';

  if (!title) error = 'عنوان المقال مطلوب';
  else if (!sections.includes(section)) error = 'القسم غير صحيح';

  if (!error) {
    if (req.files && req.files.image_file && req.files.image_file[0]) {
      image = 'uploads/' + req.files.image_file[0].filename;
    }
    if (req.files && req.files.cover_image_file && req.files.cover_image_file[0]) {
      cover_image = 'uploads/' + req.files.cover_image_file[0].filename;
    }

    let tagsArray = (tags || '').split(',').map(t => t.trim()).filter(t => t);
    let paragraphsArray = (paragraphs || '').split('\n').map(p => p.trim()).filter(p => p);

    let gallery = [];
    if (gallery_urls) {
      const urls = gallery_urls.split('\n');
      const descs = gallery_descs ? gallery_descs.split('\n') : [];
      for (let i = 0; i < urls.length; i++) {
        const u = urls[i].trim();
        if (u) gallery.push({ url: u, desc: (descs[i] || '').trim() });
      }
    }
    if (req.files && req.files.gallery_files) {
      for (const f of req.files.gallery_files) {
        gallery.push({ url: 'uploads/' + f.filename, desc: '' });
      }
    }

    const articles = loadArticles();
    const newId = getNextId();

    const article = {
      id: newId,
      section,
      sectionFile: sectionFileMap[section] || '',
      title,
      image: image || '',
      cover_image: cover_image || '',
      image_desc: image_desc || '',
      date: date || '',
      author: author || '',
      tags: tagsArray,
      paragraphs: paragraphsArray,
      images: gallery
    };

    articles.push(article);
    saveArticles(articles);
    return res.render('admin/article-add', {
      title: 'إضافة مقال',
      message: 'تم إضافة المقال بنجاح', error: '',
      sections, admin: req.session.admin, body: {}
    });
  }

  res.render('admin/article-add', {
    title: 'إضافة مقال',
    message: '', error,
    sections, admin: req.session.admin, body: req.body
  });
});

// --- ARTICLE EDIT ---
app.get('/admin/article-edit/:id', requireAdmin, (req, res) => {
  const id = parseInt(req.params.id) || 0;
  const article = getArticle(id);
  if (!article) return res.redirect('/admin/articles');

  const sections = ALL_SECTIONS;

  res.render('admin/article-edit', {
    title: 'تعديل مقال',
    article, id, sections,
    message: '', error: '',
    admin: req.session.admin
  });
});

app.post('/admin/article-edit/:id', requireAdmin, upload.fields([
  { name: 'image_file', maxCount: 1 },
  { name: 'cover_image_file', maxCount: 1 },
  { name: 'gallery_files', maxCount: 20 }
]), (req, res) => {
  const id = parseInt(req.params.id) || 0;
  let article = getArticle(id);
  if (!article) return res.redirect('/admin/articles');

  const sections = ALL_SECTIONS;
  const sectionFileMap = SECTION_FILE_MAP;

  let { title, section, image, cover_image, image_desc, date, author, tags, paragraphs, gallery_urls, gallery_descs } = req.body;
  title = (title || '').trim();
  let error = '';

  if (!title) error = 'عنوان المقال مطلوب';
  else if (!sections.includes(section)) error = 'القسم غير صحيح';

  if (!error) {
    let tagsArray = (tags || '').split(',').map(t => t.trim()).filter(t => t);
    let paragraphsArray = (paragraphs || '').split('\n').map(p => p.trim()).filter(p => p);

    let gallery = [];
    if (gallery_urls) {
      const urls = gallery_urls.split('\n');
      const descs = gallery_descs ? gallery_descs.split('\n') : [];
      for (let i = 0; i < urls.length; i++) {
        const u = urls[i].trim();
        if (u) gallery.push({ url: u, desc: (descs[i] || '').trim() });
      }
    }
    if (req.files && req.files.image_file && req.files.image_file[0]) {
      image = 'uploads/' + req.files.image_file[0].filename;
    }
    if (req.files && req.files.cover_image_file && req.files.cover_image_file[0]) {
      cover_image = 'uploads/' + req.files.cover_image_file[0].filename;
    }
    if (req.files && req.files.gallery_files) {
      for (const f of req.files.gallery_files) {
        gallery.push({ url: 'uploads/' + f.filename, desc: '' });
      }
    }

    const allArticles = loadArticles();
    for (let i = 0; i < allArticles.length; i++) {
      if (allArticles[i].id === id) {
        allArticles[i].section = section;
        allArticles[i].sectionFile = sectionFileMap[section] || '';
        allArticles[i].title = title;
        allArticles[i].image = image || allArticles[i].image;
        allArticles[i].cover_image = cover_image || allArticles[i].cover_image || '';
        allArticles[i].image_desc = image_desc || '';
        allArticles[i].date = date || '';
        allArticles[i].author = author || '';
        allArticles[i].tags = tagsArray;
        allArticles[i].paragraphs = paragraphsArray;
        allArticles[i].images = gallery;
        break;
      }
    }
    saveArticles(allArticles);
    article = getArticle(id);
    return res.render('admin/article-edit', {
      title: 'تعديل مقال',
      article, id, sections,
      message: 'تم تحديث المقال بنجاح', error: '',
      admin: req.session.admin
    });
  }

  res.render('admin/article-edit', {
    title: 'تعديل مقال',
    article, id, sections,
    message: '', error,
    admin: req.session.admin
  });
});

// --- ARTICLE DELETE ---
app.all('/admin/article-delete/:id', requireAdmin, (req, res) => {
  const id = parseInt(req.params.id) || 0;
  if (id > 0) {
    let articles = loadArticles();
    articles = articles.filter(a => a.id !== id);
    saveArticles(articles);
  }
  const redirect = req.query.redirect || '/admin/articles';
  if (redirect.indexOf('article-delete') === -1) {
    const sep = redirect.indexOf('?') !== -1 ? '&' : '?';
    res.redirect(redirect + sep + 'deleted=1');
  } else {
    res.redirect('/admin/articles?deleted=1');
  }
});

// --- SECTIONS ---
app.get('/admin/sections', requireAdmin, (req, res) => {
  const sectionsData = getSections();

  if (req.query.delete !== undefined) {
    const delIdx = parseInt(req.query.delete);
    if (delIdx >= 0 && delIdx < sectionsData.length) {
      sectionsData.splice(delIdx, 1);
      saveSections(sectionsData);
    }
    return res.redirect('/admin/sections');
  }

  res.render('admin/sections', {
    title: 'الأقسام',
    sectionsData,
    message: '', error: '',
    admin: req.session.admin
  });
});

app.post('/admin/sections', requireAdmin, (req, res) => {
  const sectionsData = getSections();
  let message = '', error = '';

  if (req.body.add_section) {
    const name = (req.body.name || '').trim();
    if (!name) {
      error = 'اسم القسم مطلوب';
    } else {
      if (sectionsData.find(s => s.name === name)) {
        error = 'القسم موجود بالفعل';
      }
      if (!error) {
        let slug = (req.body.slug || '').trim();
        if (!slug) slug = 'sec_' + crypto.randomBytes(3).toString('hex');
        sectionsData.push({ name, slug, file: req.body.file || '' });
        saveSections(sectionsData);
        message = 'تم إضافة القسم "' + name + '" بنجاح';
      }
    }
  }

  res.render('admin/sections', {
    title: 'الأقسام',
    sectionsData: getSections(),
    message, error,
    admin: req.session.admin
  });
});

// --- SETTINGS ---
app.get('/admin/settings', requireAdmin, (req, res) => {
  const settings = loadSettings();
  const tickerStr = (settings.ticker_news_ids || []).join(', ');
  res.render('admin/settings', {
    title: 'الإعدادات',
    settings, tickerStr,
    message: '', error: '',
    admin: req.session.admin
  });
});

app.post('/admin/settings', requireAdmin, (req, res) => {
  const settings = loadSettings();
  const fields = [
    'site_name', 'site_subtitle', 'site_url',
    'logo_url', 'favicon_url',
    'primary_color', 'primary_dark', 'primary_light', 'dark_color',
    'facebook_url', 'twitter_url', 'youtube_url', 'instagram_url',
    'linkedin_url', 'google_news_url',
    'address', 'maps_url', 'phone', 'fax', 'email',
    'footer_text', 'editor_name', 'editor_title', 'college_logo_url',
  ];

  for (const f of fields) {
    settings[f] = req.body[f] || '';
  }

  const tickerRaw = (req.body.ticker_news_ids || '').trim();
  const tickerIds = [];
  if (tickerRaw) {
    for (const p of tickerRaw.split(',')) {
      const trimmed = p.trim();
      if (trimmed && !isNaN(trimmed)) tickerIds.push(parseInt(trimmed));
    }
  }
  settings.ticker_news_ids = tickerIds;

  saveSettings(settings);
  const tickerStr = tickerIds.join(', ');

  res.render('admin/settings', {
    title: 'الإعدادات',
    settings, tickerStr,
    message: 'تم حفظ الإعدادات بنجاح', error: '',
    admin: req.session.admin
  });
});

// --- HOMEPAGE MANAGER ---
app.get('/admin/homepage', requireAdmin, (req, res) => {
  const settings = loadSettings();
  const articles = loadArticles();
  const tickerIds = settings.ticker_news_ids || [];
  const heroSlidePins = settings.hero_slide_pins || [];
  const heroPins = settings.hero_pins || [];
  const mostReadIds = settings.most_read_ids || [1, 2, 5, 7];
  const sections = getSections();
  const tickerArticles = tickerIds.map(id => getArticle(id)).filter(Boolean);
  const heroSlideArticles = heroSlidePins.map(id => getArticle(id)).filter(Boolean);
  const heroSideArticles = heroPins.map(id => getArticle(id)).filter(Boolean);
  const mostReadArticles = mostReadIds.map(id => getArticle(id)).filter(Boolean);
  res.render('admin/homepage', {
    title: 'إدارة الصفحة الرئيسية',
    tickerIds: tickerIds.join(', '),
    heroSlidePins: heroSlidePins.join(', '),
    heroPins: heroPins.join(', '),
    mostReadIds: mostReadIds.join(', '),
    tickerArticles, heroSlideArticles, heroSideArticles, mostReadArticles,
    articles, sections,
    message: '', error: '',
    admin: req.session.admin
  });
});

app.post('/admin/homepage', requireAdmin, (req, res) => {
  const settings = loadSettings();
  const articles = loadArticles();
  const sections = getSections();
  const parseIds = (str) => (str || '').split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n) && n > 0);
  settings.ticker_news_ids = parseIds(req.body.ticker_ids);
  settings.hero_slide_pins = parseIds(req.body.hero_slide_ids);
  settings.hero_pins = parseIds(req.body.hero_side_ids);
  settings.most_read_ids = parseIds(req.body.most_read_ids);
  saveSettings(settings);
  const tickerIds = settings.ticker_news_ids || [];
  const heroSlidePins = settings.hero_slide_pins || [];
  const heroPins = settings.hero_pins || [];
  const mostReadIds = settings.most_read_ids || [1, 2, 5, 7];
  const tickerArticles = tickerIds.map(id => getArticle(id)).filter(Boolean);
  const heroSlideArticles = heroSlidePins.map(id => getArticle(id)).filter(Boolean);
  const heroSideArticles = heroPins.map(id => getArticle(id)).filter(Boolean);
  const mostReadArticles = mostReadIds.map(id => getArticle(id)).filter(Boolean);
  res.render('admin/homepage', {
    title: 'إدارة الصفحة الرئيسية',
    tickerIds: tickerIds.join(', '),
    heroSlidePins: heroSlidePins.join(', '),
    heroPins: heroPins.join(', '),
    mostReadIds: mostReadIds.join(', '),
    tickerArticles, heroSlideArticles, heroSideArticles, mostReadArticles,
    articles, sections,
    message: 'تم حفظ إعدادات الصفحة الرئيسية بنجاح', error: '',
    admin: req.session.admin
  });
});

// --- SECTION ORDER MANAGER ---
app.get('/admin/section-order', requireAdmin, (req, res) => {
  const sections = getSections();
  res.render('admin/section-order', {
    title: 'ترتيب الأقسام',
    sections,
    message: '', error: '',
    admin: req.session.admin
  });
});

app.get('/admin/section-order/:slug', requireAdmin, (req, res) => {
  const slug = req.params.slug;
  const sections = getSections();
  const slugMap = {};
  for (const s of sections) { slugMap[s.slug] = s.name; }
  const sectionName = slugMap[slug];
  if (!sectionName) return res.redirect('/admin/section-order');
  const allArticles = getArticlesBySection(sectionName);
  const orders = loadSettings().section_orders || {};
  const orderIds = orders[sectionName] || [];
  const ordered = [];
  const unordered = [];
  for (const a of allArticles) {
    const idx = orderIds.indexOf(a.id);
    if (idx !== -1) ordered[idx] = a;
    else unordered.push(a);
  }
  const finalList = ordered.filter(Boolean).concat(unordered.sort((a, b) => (b.id || 0) - (a.id || 0)));
  res.render('admin/section-order', {
    title: 'ترتيب: ' + sectionName,
    sections, currentSlug: slug, sectionName,
    articles: finalList,
    message: '', error: '',
    admin: req.session.admin
  });
});

app.post('/admin/section-order/:slug', requireAdmin, (req, res) => {
  const slug = req.params.slug;
  const sections = getSections();
  const slugMap = {};
  for (const s of sections) { slugMap[s.slug] = s.name; }
  const sectionName = slugMap[slug];
  if (!sectionName) return res.redirect('/admin/section-order');
  const ids = (req.body.article_order || '').split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n) && n > 0);
  const settings = loadSettings();
  if (!settings.section_orders) settings.section_orders = {};
  settings.section_orders[sectionName] = ids;
  saveSettings(settings);
  res.redirect('/admin/section-order/' + slug + '?saved=1');
});

// --- MESSAGES ---
app.get('/admin/messages', requireAdmin, (req, res) => {
  let msgs = [];
  try {
    if (fs.existsSync(MESSAGES_FILE)) {
      msgs = JSON.parse(fs.readFileSync(MESSAGES_FILE, 'utf8'));
    }
  } catch (e) {}

  if (req.query.mark) {
    const markId = req.query.mark;
    let all = msgs;
    for (const m of all) { if (m.id == markId) m.read = true; }
    fs.writeFileSync(MESSAGES_FILE, JSON.stringify(all, null, 4), 'utf8');
    return res.redirect('/admin/messages');
  }

  if (req.query.delete) {
    const delId = req.query.delete;
    let all = msgs;
    all = all.filter(m => m.id != delId);
    fs.writeFileSync(MESSAGES_FILE, JSON.stringify(all, null, 4), 'utf8');
    return res.redirect('/admin/messages');
  }

  msgs.reverse();
  res.render('admin/messages', {
    title: 'الرسائل',
    msgs,
    admin: req.session.admin
  });
});

// --- AJAX SAVE PIN ---
app.post('/admin/ajax-save-pin', requireAdmin, (req, res) => {
  const position = (req.body.position || '').trim();
  const articleId = parseInt(req.body.article_id) || 0;

  if (!position || !articleId) return res.status(400).send('Bad request');

  const article = getArticle(articleId);
  if (!article) return res.status(404).send('Article not found');

  const settings = loadSettings();

  if (position.indexOf('hero_slide_') === 0) {
    let pins = settings.hero_slide_pins || [24, 7, 2, 3, 4, 22, 23];
    const idx = parseInt(position.substring(11));
    if (idx >= 0 && idx < pins.length) {
      pins[idx] = articleId;
      settings.hero_slide_pins = pins;
    }
  } else if (position.indexOf('hero_') === 0) {
    let pins = settings.hero_pins || [0, 1, 5, 6, 24, 7];
    const idx = parseInt(position.substring(5));
    if (idx >= 0 && idx < 6) {
      pins[idx] = articleId;
      settings.hero_pins = pins;
    }
  }

  saveSettings(settings);
  res.send('OK');
});

// --- QUICK ADD ---
app.get('/admin/quick-add', requireAdmin, (req, res) => {
  const sections = ALL_SECTIONS;
  const presetSection = req.query.section && sections.includes(req.query.section) ? req.query.section : '';
  const today = new Date().toISOString().split('T')[0];

  res.render('admin/quick-add', {
    title: 'إضافة سريعة',
    sections, presetSection, today,
    message: '', error: '',
    admin: req.session.admin, body: {}
  });
});

app.post('/admin/quick-add', requireAdmin, upload.fields([
  { name: 'image_file', maxCount: 1 }
]), (req, res) => {
  const sections = ALL_SECTIONS;
  const sectionFileMap = SECTION_FILE_MAP;
  const today = new Date().toISOString().split('T')[0];

  let { title, section, image, cover_image, image_desc, date, author, tags, paragraphs, gallery_urls, gallery_descs, redirect } = req.body;
  title = (title || '').trim();
  let error = '';

  if (!title) error = 'عنوان المقال مطلوب';
  else if (!sections.includes(section)) error = 'القسم غير صحيح';

  if (!error) {
    let tagsArray = (tags || '').split(',').map(t => t.trim()).filter(t => t);
    let paragraphsArray = (paragraphs || '').split('\n').map(p => p.trim()).filter(p => p);

    let gallery = [];
    if (gallery_urls) {
      const urls = gallery_urls.split('\n');
      const descs = gallery_descs ? gallery_descs.split('\n') : [];
      for (let i = 0; i < urls.length; i++) {
        const u = urls[i].trim();
        if (u) gallery.push({ url: u, desc: (descs[i] || '').trim() });
      }
    }
    if (req.files && req.files.image_file && req.files.image_file[0]) {
      image = 'uploads/' + req.files.image_file[0].filename;
    }

    const articles = loadArticles();
    const newId = getNextId();

    const article = {
      id: newId,
      section,
      sectionFile: sectionFileMap[section] || '',
      title,
      image: image || '',
      cover_image: cover_image || '',
      image_desc: image_desc || '',
      date: date || '',
      author: author || '',
      tags: tagsArray,
      paragraphs: paragraphsArray,
      images: gallery
    };

    articles.push(article);
    saveArticles(articles);

    if (redirect) {
      return res.redirect(redirect);
    }

    return res.render('admin/quick-add', {
      title: 'إضافة سريعة',
      sections, presetSection: section, today,
      message: 'تم إضافة المقال بنجاح', error: '',
      admin: req.session.admin, body: {}
    });
  }

  res.render('admin/quick-add', {
    title: 'إضافة سريعة',
    sections, presetSection: '', today,
    message: '', error,
    admin: req.session.admin, body: req.body
  });
});

// --- ARTICLE IMPORT ---
app.get('/admin/article-import', requireAdmin, (req, res) => {
  const sections = ALL_SECTIONS;
  res.render('admin/article-import', {
    title: 'استيراد مقال',
    sections,
    message: '', error: '',
    admin: req.session.admin
  });
});

app.post('/admin/article-import', requireAdmin, upload.single('article_file'), async (req, res) => {
  const sections = ALL_SECTIONS;
  const sectionFileMap = SECTION_FILE_MAP;

  const section = req.body.section || '';
  const author = req.body.author || '';
  const date = req.body.date || new Date().toISOString().split('T')[0];
  const manualImage = req.body.image || '';
  let error = '';

  if (!sections.includes(section)) {
    error = 'الرجاء اختيار قسم صحيح';
    return res.render('admin/article-import', { title: 'استيراد مقال', sections, message: '', error, admin: req.session.admin });
  }

  if (!req.file) {
    error = 'الرجاء رفع ملف';
    return res.render('admin/article-import', { title: 'استيراد مقال', sections, message: '', error, admin: req.session.admin });
  }

  try {
    const ext = path.extname(req.file.originalname).toLowerCase();
    let title = '';
    let paragraphs = [];
    let image = manualImage;

    if (ext === '.html' || ext === '.htm') {
      const html = fs.readFileSync(req.file.path, 'utf8');
      const cheerio = require('cheerio');
      const $ = cheerio.load(html);
      title = $('title').first().text().trim() || $('h1').first().text().trim();
      $('p').each((i, el) => {
        const text = $(el).text().trim();
        if (text.length > 20) paragraphs.push(text);
      });
      const firstImg = $('img').first().attr('src');
      if (firstImg && !firstImg.startsWith('data:')) image = firstImg;
    } else if (ext === '.docx') {
      const mammoth = require('mammoth');
      const result = await mammoth.extractRawText({ path: req.file.path });
      const lines = result.value.split('\n').map(l => l.trim()).filter(l => l.length > 20);
      if (lines.length > 0) {
        title = lines[0].substring(0, 150);
        paragraphs = lines.slice(1);
      }
    } else if (ext === '.pdf') {
      const pdfParse = require('pdf-parse');
      const buf = fs.readFileSync(req.file.path);
      const data = await pdfParse(buf);
      const lines = data.text.split('\n').map(l => l.trim()).filter(l => l.length > 10);
      if (lines.length > 0) {
        title = lines[0].substring(0, 150);
        paragraphs = lines.slice(1);
      }
    } else {
      error = 'الصيغة غير مدعومة. الصيغ المدعومة: HTML, DOCX, PDF';
      return res.render('admin/article-import', { title: 'استيراد مقال', sections, message: '', error, admin: req.session.admin });
    }

    if (!title && paragraphs.length === 0) {
      error = 'لم نتمكن من استخراج محتوى من الملف';
      return res.render('admin/article-import', { title: 'استيراد مقال', sections, message: '', error, admin: req.session.admin });
    }

    if (!title) title = 'مقال بدون عنوان - ' + date;

    const articles = loadArticles();
    const newId = getNextId();
    const article = {
      id: newId, section,
      sectionFile: sectionFileMap[section] || '',
      title, image: image || '',
      cover_image: '', image_desc: '',
      date, author,
      tags: [section],
      paragraphs, images: []
    };
    articles.push(article);
    saveArticles(articles);

    const message = 'تم استيراد المقال بنجاح! (ID: ' + newId + ')<br><small>العنوان: ' + title + '</small><br><small>عدد الفقرات: ' + paragraphs.length + '</small>';
    res.render('admin/article-import', { title: 'استيراد مقال', sections, message, error: '', admin: req.session.admin });
  } catch (e) {
    error = 'خطأ أثناء معالجة الملف: ' + e.message;
    res.render('admin/article-import', { title: 'استيراد مقال', sections, message: '', error, admin: req.session.admin });
  }
});

// --- 404 ---
app.use((req, res) => {
  res.status(404).render('404', { title: '404 - الصفحة غير موجودة' });
});

// --- Error handler ---
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).send('Something broke!');
});

// --- For local development ---
if (require.main === module) {
  const PORT = process.env.PORT || 3000;
  app.listen(PORT, () => {
    console.log('Server running on http://localhost:' + PORT);
  });
}

module.exports = app;
