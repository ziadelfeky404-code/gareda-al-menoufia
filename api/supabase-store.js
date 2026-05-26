const https = require('https');

const URL = 'https://qpzxdimqolhgbfhzcjay.supabase.co';
const KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InFwenhkaW1xb2xoZ2JmaHpjamF5Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3Nzk0ODQzNDgsImV4cCI6MjA5NTA2MDM0OH0.F-P3LGBgZRircgYOQc16XVa3ytVpi2HRFd6sWXTXxek';

function request(method, table, opts) {
  return new Promise((resolve, reject) => {
    const u = new URL('/rest/v1/' + table, URL);
    if (opts && opts.query) u.search = opts.query;
    const headers = {
      'apikey': KEY,
      'Authorization': 'Bearer ' + KEY,
      'Content-Type': 'application/json',
      'Prefer': 'return=minimal'
    };
    if (opts && opts.count) headers['Prefer'] = 'count=exact';
    const req = https.request({ hostname: u.hostname, path: u.pathname + u.search, method, headers }, (res) => {
      let d = '';
      res.on('data', c => d += c);
      res.on('end', () => {
        if (res.statusCode >= 200 && res.statusCode < 300) {
          if (opts && opts.count) resolve(parseInt(res.headers['content-range']?.split('/')[1] || '0'));
          else if (d) { try { resolve(JSON.parse(d)); } catch (e) { resolve(d); } }
          else resolve(null);
        } else reject(new Error(method + ' ' + table + ' ' + res.statusCode + ': ' + d.substring(0, 300)));
      });
    });
    req.on('error', reject);
    if (opts && opts.body) req.write(JSON.stringify(opts.body));
    req.end();
  });
}

async function loadArticles() {
  const rows = await request('GET', 'articles?select=id,title,date,section,paragraphs,images,videos,link&order=id.asc');
  return rows.map(r => ({
    id: r.id,
    title: r.title || '',
    date: r.date || '',
    section: r.section || '',
    paragraphs: typeof r.paragraphs === 'string' ? JSON.parse(r.paragraphs) : (r.paragraphs || []),
    images: typeof r.images === 'string' ? JSON.parse(r.images) : (r.images || []),
    videos: typeof r.videos === 'string' ? JSON.parse(r.videos) : (r.videos || []),
    link: r.link || ''
  }));
}

async function saveArticles(articles) {
  const count = await request('GET', 'articles?select=id', { count: true });
  if (count > 0) await request('DELETE', 'articles?id=gt.0');
  const BATCH = 100;
  for (let i = 0; i < articles.length; i += BATCH) {
    const batch = articles.slice(i, i + BATCH).map(a => ({
      id: a.id, title: a.title || '', date: a.date || '', section: a.section || '',
      paragraphs: JSON.stringify(a.paragraphs || []), images: JSON.stringify(a.images || []),
      videos: JSON.stringify(a.videos || []), link: a.link || ''
    }));
    await request('POST', 'articles', { body: batch });
  }
}

async function loadSettings() {
  const rows = await request('GET', 'settings?select=key,value&order=id.asc');
  const obj = {};
  for (const r of rows) obj[r.key] = r.value;
  return obj;
}

async function saveSettings(obj) {
  const count = await request('GET', 'settings?select=id', { count: true });
  if (count > 0) await request('DELETE', 'settings?id=gt.0');
  const rows = Object.keys(obj).map(key => ({ key, value: obj[key] }));
  await request('POST', 'settings', { body: rows });
}

async function saveMessage(msg) {
  await request('POST', 'messages', { body: msg });
}

module.exports = { loadArticles, saveArticles, loadSettings, saveSettings, saveMessage };
