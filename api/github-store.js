const https = require('https');

const TOKEN = process.env.GITHUB_TOKEN || '';
const REPO = 'ziadelfeky404-code/gareda-al-menoufia';
const BRANCH = 'master';

let _shaCache = {};

function isActive() {
  return !!TOKEN;
}

function getFileSha(path, callback) {
  // Always fetch fresh SHA from GitHub to avoid 409 conflicts with other instances
  const req = https.request({
    hostname: 'api.github.com',
    path: '/repos/' + REPO + '/contents/' + path + '?ref=' + BRANCH,
    method: 'GET',
    headers: {
      'User-Agent': 'gareda-al-menoufia',
      'Authorization': 'Bearer ' + TOKEN,
      'Accept': 'application/vnd.github.v3+json'
    }
  }, (res) => {
    let data = '';
    res.on('data', c => data += c);
    res.on('end', () => {
      if (res.statusCode === 200) {
        try {
          const json = JSON.parse(data);
          _shaCache[path] = json.sha;
          return callback(null, json.sha);
        } catch (e) { return callback(e); }
      } else if (res.statusCode === 404) {
        return callback(null, null); // File doesn't exist yet
      } else {
        return callback(new Error('GET ' + path + ' status ' + res.statusCode));
      }
    });
  });
  req.on('error', callback);
  req.end();
}

function commitFile(path, content, callback) {
  const encoded = Buffer.from(content, 'utf8').toString('base64');
  getFileSha(path, (err, sha) => {
    if (err) { if (callback) callback(err); return; }
    const body = JSON.stringify({
      message: 'Auto-save: ' + path,
      content: encoded,
      branch: BRANCH,
      sha: sha || undefined
    });
    const req = https.request({
      hostname: 'api.github.com',
      path: '/repos/' + REPO + '/contents/' + path,
      method: 'PUT',
      headers: {
        'User-Agent': 'gareda-al-menoufia',
        'Authorization': 'Bearer ' + TOKEN,
        'Accept': 'application/vnd.github.v3+json',
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(body)
      }
    }, (res) => {
      let data = '';
      res.on('data', c => data += c);
      res.on('end', () => {
        if (res.statusCode === 200 || res.statusCode === 201) {
          if (callback) callback(null);
        } else {
          if (callback) callback(new Error('PUT ' + path + ' status ' + res.statusCode + ': ' + data.substring(0, 200)));
        }
      });
    });
    req.on('error', callback);
    req.write(body);
    req.end();
  });
}

function commitArticles(data) {
  return new Promise((resolve, reject) => {
    commitFile('data/articles.json', JSON.stringify(data, null, 4), (err) => {
      if (err) reject(err); else resolve();
    });
  });
}

function commitSettings(data) {
  return new Promise((resolve, reject) => {
    commitFile('data/settings.json', JSON.stringify(data, null, 4), (err) => {
      if (err) reject(err); else resolve();
    });
  });
}

function commitMessages(data) {
  return new Promise((resolve, reject) => {
    commitFile('data/messages.json', JSON.stringify(data, null, 4), (err) => {
      if (err) reject(err); else resolve();
    });
  });
}

module.exports = { isActive, commitArticles, commitSettings, commitMessages };
