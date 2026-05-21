<?php
define('IS_VERCEL', !is_writable(__DIR__ . '/../data'));

if (!IS_VERCEL) return;

define('GH_REPO', 'ziadelfeky404-code/gareda-al-menoufia');
define('GH_BRANCH', 'master');
define('GH_TOKEN', getenv('GH_TOKEN') ?: '');
define('GITHUB_TOKEN', GH_TOKEN);
define('IMGBB_KEY', getenv('IMGBB_KEY') ?: '');
define('SESS_SECRET', getenv('SESS_SECRET') ?: 'change-me-in-production');

class VercelSessionHandler implements SessionHandlerInterface {
    private $cookieName = 'VSESS';
    private $lifetime = 604800;

    public function open($sp, $sn) { return true; }
    public function close() { return true; }
    public function read($id) {
        if (!isset($_COOKIE[$this->cookieName])) return '';
        $parts = explode('.', $_COOKIE[$this->cookieName], 2);
        if (count($parts) !== 2) return '';
        $payload = $parts[0];
        if (!hash_equals(hash_hmac('sha256', $payload, SESS_SECRET), $parts[1])) return '';
        $decoded = json_decode(base64_decode($payload), true);
        if (!is_array($decoded)) return '';
        if (isset($decoded['_exp']) && $decoded['_exp'] < time()) return '';
        $data = [];
        foreach ($decoded as $k => $v) {
            if ($k !== '_exp') $data[$k] = $v;
        }
        return serialize($data);
    }
    public function write($id, $data) {
        $arr = unserialize($data) ?: [];
        $arr['_exp'] = time() + $this->lifetime;
        $payload = base64_encode(json_encode($arr, JSON_UNESCAPED_UNICODE));
        $sig = hash_hmac('sha256', $payload, SESS_SECRET);
        setcookie($this->cookieName, $payload . '.' . $sig, time() + $this->lifetime, '/', '', false, true);
        return true;
    }
    public function destroy($id) {
        setcookie($this->cookieName, '', time() - 3600, '/');
        return true;
    }
    public function gc($mxl) { return true; }
}

session_set_save_handler(new VercelSessionHandler(), true);

function gh_api($method, $path, $data = null) {
    $url = 'https://api.github.com/repos/' . GH_REPO . '/' . ltrim($path, '/');
    $ch = curl_init($url);
    $headers = [
        'Authorization: token ' . GH_TOKEN,
        'User-Agent: gareda-al-menoufia',
        'Accept: application/vnd.github.v3+json'
    ];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ];
    if ($method === 'PUT') {
        $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
        $opts[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    curl_setopt_array($ch, $opts);
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return json_decode($result, true);
}

function gh_get_file($path) {
    $result = gh_api('GET', 'contents/' . $path);
    if (isset($result['content'])) {
        return [
            'content' => base64_decode(str_replace("\n", '', $result['content'])),
            'sha' => $result['sha']
        ];
    }
    return null;
}

function gh_put_file($path, $content, $message = 'Update via Vercel') {
    $existing = gh_get_file($path);
    $data = [
        'message' => $message,
        'content' => base64_encode($content),
        'branch' => GH_BRANCH
    ];
    if ($existing) $data['sha'] = $existing['sha'];
    $result = gh_api('PUT', 'contents/' . $path, $data);
    return isset($result['content']['sha']);
}

function v_put_data($file, $data) {
    if (IS_VERCEL) {
        $relPath = 'data/' . basename($file);
        $content = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return gh_put_file($relPath, $content);
    }
    $content = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return file_put_contents($file, $content);
}

function v_upload_image($tmpPath, $originalName) {
    if (!IS_VERCEL) {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $name = 'article_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;
        $dest = __DIR__ . '/../uploads/' . $name;
        if (move_uploaded_file($tmpPath, $dest)) return 'uploads/' . $name;
        return null;
    }
    if (!IMGBB_KEY) return null;
    $imageData = base64_encode(file_get_contents($tmpPath));
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.imgbb.com/1/upload?key=' . IMGBB_KEY,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['image' => $imageData, 'name' => pathinfo($originalName, PATHINFO_FILENAME)]),
        CURLOPT_TIMEOUT => 30
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($result, true);
    return $data['data']['url'] ?? null;
}
