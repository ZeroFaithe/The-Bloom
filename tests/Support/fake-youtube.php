<?php
/*
 * A pretend YouTube API for the tests, started with "php -S".
 * It never talks to the real YouTube, so tests use no quota and need no API key.
 *
 *   /search?q=quota   -> 403 quotaExceeded
 *   /search?q=badkey  -> 400 keyInvalid
 *   /search?q=broken  -> 500 with an HTML page
 *   /search?q=...     -> two results (one with a bad ID that must be skipped)
 *   /videos?id=AAAAAAAAAAA -> one video, any other id -> no videos
 */

header('Content-Type: application/json');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (($_GET['key'] ?? '') === '') {
    http_response_code(403);
    echo json_encode(['error' => ['errors' => [['reason' => 'forbidden']]]]);
    return;
}

$snippet = fn (string $title) => [
    'title'        => $title,
    'channelTitle' => 'Bloom &amp; Co',
    'thumbnails'   => ['medium' => ['url' => 'https://i.ytimg.com/vi/x/mqdefault.jpg']],
];

if ($path === '/search') {
    switch ($_GET['q'] ?? '') {
        case 'quota':
            http_response_code(403);
            echo json_encode(['error' => ['errors' => [['reason' => 'quotaExceeded']]]]);
            return;
        case 'badkey':
            http_response_code(400);
            echo json_encode(['error' => ['errors' => [['reason' => 'keyInvalid']]]]);
            return;
        case 'broken':
            http_response_code(500);
            header('Content-Type: text/html');
            echo '<html>Server error</html>';
            return;
    }
    echo json_encode(['items' => [
        ['id' => ['videoId' => 'AAAAAAAAAAA'], 'snippet' => $snippet('Barista&#39;s V60 &quot;guide&quot;')],
        ['id' => ['videoId' => 'bad id!'], 'snippet' => $snippet('Should be skipped')],
        ['id' => ['videoId' => 'BBBBBBBBBBB'], 'snippet' => [
            'title' => 'No https thumbnail',
            'channelTitle' => 'X',
            'thumbnails' => ['medium' => ['url' => 'http://insecure.example/x.jpg']],
        ]],
    ]]);
    return;
}

if ($path === '/videos') {
    $items = ($_GET['id'] ?? '') === 'AAAAAAAAAAA' ? [['snippet' => $snippet('French Press Basics')]] : [];
    echo json_encode(['items' => $items]);
    return;
}

http_response_code(404);
echo json_encode(['error' => ['errors' => [['reason' => 'notFound']]]]);
