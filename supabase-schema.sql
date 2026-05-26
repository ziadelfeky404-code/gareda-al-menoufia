-- Schema for جامعة المنوفية الإخبارية
-- Run this in Supabase SQL Editor (https://supabase.com/dashboard/project/_/sql/new)

-- Articles table
CREATE TABLE IF NOT EXISTS articles (
  id BIGINT PRIMARY KEY,
  title TEXT NOT NULL,
  date TEXT,
  section TEXT,
  paragraphs JSONB DEFAULT '[]',
  images JSONB DEFAULT '[]',
  videos JSONB DEFAULT '[]',
  link TEXT DEFAULT '',
  created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_articles_section ON articles(section);
CREATE INDEX IF NOT EXISTS idx_articles_date ON articles(date DESC);

-- Settings (key-value store)
CREATE TABLE IF NOT EXISTS settings (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  key TEXT UNIQUE NOT NULL,
  value JSONB NOT NULL
);

-- Messages from contact form
CREATE TABLE IF NOT EXISTS messages (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name TEXT,
  email TEXT,
  message TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Row Level Security (allow public read/write via anon key)
ALTER TABLE articles ENABLE ROW LEVEL SECURITY;
ALTER TABLE settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE messages ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Allow all on articles" ON articles FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on settings" ON settings FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all on messages" ON messages FOR ALL USING (true) WITH CHECK (true);
