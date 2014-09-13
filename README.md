M9 SoundCloud App
=================

This is an M9 App for [Moxi9](http://moxi9.com/) products such as [PHPfox](http://moxi9.com/phpfox).

This app adds support to add [SoundCloud](https://soundcloud.com/) songs/playlists to the activity feed. All SoundCloud feeds are parsed and we display the audio player SoundCloud provides.

Installation
============

Before installing make sure to [create an app](http://unity.moxi9.com/techie/create) on Moxi9 so you get your app ID, public and private keys.

1) Clone to a server that has PHP support

2) Rename **config.php.new** to **config.php**

3) Open **config.php** and fill in your App details

4) Open **actions.js** and look for...
```
var APP_ID = 'app343';
```

Change **app343** with your App ID.

5) Edit your App on Moxi9 and set your **APP URL** to where you cloned this app

6) Click on **HTML Header & Footer** and for **FOOTER** add the **actions.js** file.
```
<script type="text/javascript" src="http://yourserver.com/soundcloud/actions.js?v=1"></script>
```
You can place the **actions.js** file on a CDN, it is not required to be on the same server as the PHP file.

