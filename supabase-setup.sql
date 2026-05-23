-- Run this in Supabase SQL Editor (https://supabase.com/dashboard/project/_/sql/new)

-- Articles table
CREATE TABLE IF NOT EXISTS articles (
  id BIGINT PRIMARY KEY,
  section TEXT NOT NULL DEFAULT '',
  sectionFile TEXT NOT NULL DEFAULT '',
  title TEXT NOT NULL DEFAULT '',
  image TEXT NOT NULL DEFAULT '',
  cover_image TEXT NOT NULL DEFAULT '',
  image_desc TEXT NOT NULL DEFAULT '',
  date TEXT NOT NULL DEFAULT '',
  author TEXT NOT NULL DEFAULT '',
  tags JSONB DEFAULT '[]'::jsonb,
  paragraphs JSONB DEFAULT '[]'::jsonb,
  images JSONB DEFAULT '[]'::jsonb,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Settings table (key-value)
CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value JSONB NOT NULL DEFAULT '{}'::jsonb
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL DEFAULT '',
  email TEXT NOT NULL DEFAULT '',
  message TEXT NOT NULL DEFAULT '',
  subject TEXT NOT NULL DEFAULT '',
  date TEXT NOT NULL DEFAULT '',
  read BOOLEAN DEFAULT false,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Sections table
CREATE TABLE IF NOT EXISTS sections (
  slug TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  ord INTEGER DEFAULT 0
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_articles_section ON articles(section);
CREATE INDEX IF NOT EXISTS idx_articles_date ON articles(date);
CREATE INDEX IF NOT EXISTS idx_messages_read ON messages(read);
