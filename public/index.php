<?php declare(strict_types=1);

// OAuth flow adapted from https://github.com/youtube/api-samples/blob/master/php/add_subscription.php

// Load Composer installed dependencies.
require_once '../vendor/autoload.php';
session_start();

// Create API Client for YouTube. Configure ClientId, ClientSecret, and RedirectUri per client configs.
$client = new Google_Client();
$client->setScopes([Google_Service_YouTube::YOUTUBE]);
$client->setAuthConfig('../authconfig.json');
$client->setRedirectUri('http://localhost:1234/');
// Decide a session key based on required scopes.
$tokenSessionKey = 'token-' . $client->prepareScopes();
// If an access token exists in the session, add it to the client.
if (isset($_SESSION[$tokenSessionKey])) {
  $client->setAccessToken($_SESSION[$tokenSessionKey]);
}

// Is there an OAuth code in the URL to exchange for an access token?
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }
  $client->fetchAccessTokenWithAuthCode($_GET['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
  header('Location: ' . $client->getRedirectUri());
  die(); // Do not continue running code after redirect.
}

// If no access token is available, start the OAuth flow.
if (!$client->getAccessToken()) {
  // Generate a state parameter.
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  $authUrl = $client->createAuthUrl();

  die(sprintf(<<<'HTML'
<!doctype html>
<html>
<head>
<title>YouTube Subscription</title>
</head>
<body>
  <h3>Authorization Required</h3>
  <p>You need to <a href="%s">authorize access</a> before proceeding.<p>
</body>
</html>
HTML, $client->createAuthUrl()));
}

// All is good, time to read the API!
$youtube = new Google_Service_YouTube($client);
$items = [];
try {
  $options = [
    'mine' => true,
    'order' => 'alphabetical',
    'maxResults' => 50
  ];
  do {
    $subscriptions = $youtube->subscriptions->listSubscriptions('snippet', $options);
    $items = array_merge($items, $subscriptions['items']);
    $options['pageToken'] = $subscriptions['nextPageToken'];
  } while (!empty($subscriptions['nextPageToken']));
  $items = array_map(function ($item) {
    $snippet = $item->snippet;
    return [
      'name' => $snippet->title,
      'id' => $snippet->resourceId->channelId,
      'subscribed' => $snippet->publishedAt,
      'icon' => $snippet->thumbnails->default->url
    ];
  }, $items);
} catch (Google_Service_Exception $e) {
  die(sprintf('<p>A service error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage())));
} catch (Google_Exception $e) {
  die(sprintf('<p>An client error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage())));
}

header('Content-Type: text/x-opml');
?><opml version="1.0">
  <body>
    <outline text="YouTube Subscriptions" title="YouTube Subscriptions">
<?php foreach ($items as $subscription): ?>
      <outline text="<?= $subscription['name'] ?>" title="<?= $subscription['name'] ?>" type="rss" xmlUrl="https://www.youtube.com/feeds/videos.xml?channel_id=<?= $subscription['id'] ?>" />
<?php endforeach; ?>
    </outline>
  </body>
</opml>
