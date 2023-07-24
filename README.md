# Craft CMS Cloudflare Stream

> This plugin offers a easy way to upload your videos assets from Craft CMS to Cloudflare stream.

Installation:

1) Install with composer

```sh
composer require deuxhuithuit/craft-cloudflare-stream
```

2) Install in craft

```sh
craft plugin/install cloudflare-stream
```

3) Add your account id and api token in the settings.

4) Create a video stream Field and add it to your Asset data model.

5) When editing an asset, you can now opt-in into sending videos in
[Cloudflare Stream](https://www.cloudflare.com/products/cloudflare-stream/).

This extension uses Craft's Queue system, so make sure it works properly.

Made with ❤️ in Montréal.

(c) Deux Huit Huit
