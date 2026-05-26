const https = require('https');
const fs = require('fs');
const path = require('path');

// === CONFIGURE THESE ===
const SUPABASE_URL = ''; // e.g. https://xxxxxxx.supabase.co
const SUPABASE_KEY = '';  // anon/public key from Settings > API

const ROOT = path.resolve(__dirname, '..');

async function supabaseFetch(method, table, body) {
  return new Promise((resolve, reject) => {
    const url = new URL('/rest/v1/' + table, SUPABASE_URL);
    const opts = {
      hostname: url.hostname,
      path: url.pathname + url.search,
      method,
      headers: {
        'apikey': SUPABASE_KEY,
        'Authorization': 'Bearer ' + SUPABASE_KEY,
        'Content-Type': 'application/json',
        'Prefer': 'return=minimal'
      }
    };
    const req = https.request(opts, (res) => {
      let d = '';
      res.on('data', c => d += c);
      res.on('end', () => {
        if (res.statusCode >= 200 && res.statusCode < 300) resolve(d);
        else reject(new Error(method + ' ' + table + ' status ' + res.statusCode + ': ' + d.substring(0, 200)));
      });
    });
    req.on('error', reject);
    if (body) req.write(JSON.stringify(body));
    req.end();
  });
}

async function seed() {
  if (!SUPABASE_URL || !SUPABASE_KEY) {
    console.error('❌ Set SUPABASE_URL and SUPABASE_KEY at the top of this script');
    process.exit(1);
  }

  console.log('📡 Connecting to Supabase...');

  // 1. Seed articles
  const articlesPath = path.join(ROOT, 'data', 'articles.json');
  const articles = JSON.parse(fs.readFileSync(articlesPath, 'utf8'));
  console.log(`📄 Articles: ${articles.length}`);

  // Clear existing
  await supabaseFetch('DELETE', 'articles?id=gt.0');
  // Insert in batches
  const BATCH = 100;
  for (let i = 0; i < articles.length; i += BATCH) {
    const batch = articles.slice(i, i + BATCH).map(a => ({
      id: a.id,
      title: a.title || '',
      date: a.date || '',
      section: a.section || '',
      paragraphs: JSON.stringify(a.paragraphs || []),
      images: JSON.stringify(a.images || []),
      videos: JSON.stringify(a.videos || []),
      link: a.link || ''
    }));
    await supabaseFetch('POST', 'articles', batch);
    process.stdout.write(`\r  ✅ Articles ${Math.min(i + BATCH, articles.length)}/${articles.length}`);
  }
  console.log();

  // 2. Seed settings (key-value store)
  const settingsPath = path.join(ROOT, 'data', 'settings.json');
  const settings = JSON.parse(fs.readFileSync(settingsPath, 'utf8'));
  const settingKeys = Object.keys(settings);
  console.log(`📄 Settings: ${settingKeys.length} keys`);

  await supabaseFetch('DELETE', 'settings?id=gt.0');
  for (const key of settingKeys) {
    await supabaseFetch('POST', 'settings', { key, value: settings[key] });
  }
  console.log('  ✅ Settings done');

  // 3. Seed messages
  const messagesPath = path.join(ROOT, 'data', 'messages.json');
  let messages = [];
  try { messages = JSON.parse(fs.readFileSync(messagesPath, 'utf8')); } catch (e) {}
  console.log(`📄 Messages: ${messages.length}`);

  await supabaseFetch('DELETE', 'messages?id=gt.0');
  for (const m of messages) {
    await supabaseFetch('POST', 'messages', {
      name: m.name || '',
      email: m.email || '',
      message: m.message || ''
    });
  }
  console.log('  ✅ Messages done');
  console.log('\n🎉 Seed complete!');
}

seed().catch(e => {
  console.error('\n❌ Seed failed:', e.message);
  process.exit(1);
});
