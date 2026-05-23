const { createClient } = require('@supabase/supabase-js');

const supabaseUrl = process.env.SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_ANON_KEY;
const supabase = supabaseUrl && supabaseKey ? createClient(supabaseUrl, supabaseKey) : null;

function isActive() {
  return !!supabase;
}

// --- Articles ---
async function loadArticles() {
  if (!supabase) return null;
  const { data, error } = await supabase.from('articles').select('*').order('id', { ascending: false });
  if (error) { console.error('Supabase loadArticles error:', error.message); return null; }
  return data || [];
}

async function saveArticles(articles) {
  if (!supabase) return false;
  // Upsert all articles
  const { error } = await supabase.from('articles').upsert(articles.map(a => ({
    ...a,
    tags: a.tags || [],
    paragraphs: a.paragraphs || [],
    images: a.images || []
  })), { onConflict: 'id' });
  if (error) { console.error('Supabase saveArticles error:', error.message); return false; }
  return true;
}

// --- Settings ---
async function loadSettings() {
  if (!supabase) return null;
  const { data, error } = await supabase.from('settings').select('*');
  if (error) { console.error('Supabase loadSettings error:', error.message); return null; }
  const result = {};
  for (const row of data || []) {
    result[row.key] = row.value;
  }
  return result;
}

async function saveSettings(settings) {
  if (!supabase) return false;
  const rows = Object.entries(settings || {}).map(([key, value]) => ({ key, value }));
  const { error } = await supabase.from('settings').upsert(rows, { onConflict: 'key' });
  if (error) { console.error('Supabase saveSettings error:', error.message); return false; }
  return true;
}

// --- Messages ---
async function loadMessages() {
  if (!supabase) return null;
  const { data, error } = await supabase.from('messages').select('*').order('created_at', { ascending: false });
  if (error) { console.error('Supabase loadMessages error:', error.message); return null; }
  return data || [];
}

async function saveMessages(messages) {
  if (!supabase) return false;
  const { error } = await supabase.from('messages').upsert(messages.map(m => ({
    ...m,
    read: !!m.read
  })), { onConflict: 'id' });
  if (error) { console.error('Supabase saveMessages error:', error.message); return false; }
  return true;
}

// --- Sections ---
async function loadSections() {
  if (!supabase) return null;
  const { data, error } = await supabase.from('sections').select('*').order('ord', { ascending: true });
  if (error) { console.error('Supabase loadSections error:', error.message); return null; }
  return data || [];
}

async function saveSections(sections) {
  if (!supabase) return false;
  const rows = sections.map((s, i) => ({ slug: s.slug, name: s.name, ord: i }));
  const { error } = await supabase.from('sections').upsert(rows, { onConflict: 'slug' });
  if (error) { console.error('Supabase saveSections error:', error.message); return false; }
  return true;
}

// --- Seeding ---
async function seedFromFiles(articles, settings, messages, sections) {
  if (!supabase) return;
  console.log('Seeding Supabase from file data...');
  if (articles && articles.length) {
    await supabase.from('articles').upsert(articles.map(a => ({
      ...a, tags: a.tags || [], paragraphs: a.paragraphs || [], images: a.images || []
    })), { onConflict: 'id', ignoreDuplicates: true });
    console.log('Seeded ' + articles.length + ' articles');
  }
  if (settings && Object.keys(settings).length) {
    const rows = Object.entries(settings).map(([key, value]) => ({ key, value }));
    await supabase.from('settings').upsert(rows, { onConflict: 'key', ignoreDuplicates: true });
  }
  if (messages && messages.length) {
    await supabase.from('messages').upsert(messages.map(m => ({ ...m, read: !!m.read })), { onConflict: 'id', ignoreDuplicates: true });
  }
  if (sections && sections.length) {
    const rows = sections.map((s, i) => ({ slug: s.slug, name: s.name, ord: i }));
    await supabase.from('sections').upsert(rows, { onConflict: 'slug', ignoreDuplicates: true });
  }
  console.log('Supabase seeding complete');
}

module.exports = { isActive, loadArticles, saveArticles, loadSettings, saveSettings, loadMessages, saveMessages, loadSections, saveSections, seedFromFiles };