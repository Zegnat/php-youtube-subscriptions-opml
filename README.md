# What

A straight forward PHP implementation against the YouTube API to replace the old subscription export button from the YouTube API.

# Why

I like watching YouTube content, but I do not like the YouTube main page. To follow the people I subscribe to, I have them loaded in my feed reader. Sometime during the latter half of 2020 it seems like YouTube removed the button to export your subscriptions as a list of RSS feeds. Thus I needed a replacement.

# How

1. Clone this repository.
2. In the root, run `composer update` to get all the dependencies.
3. Register an application at Google, configure it for YouTube, set the URL for it as `http://localhost:1234/` and download the OAuth 2.0 credentials as JSON.
4. Call this JSON file `authconfig.json` and put it in this root folder.
5. Start a PHP server from this root: `php -S localhost:1234 -t public`.
