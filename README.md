# Safe NFT Metadata Provider

Hi! This project implements a basic HTTP metadata (and assets) provider using [Symfony](https://www.symfony.com).

## Dependencies
This project uses PHP `8.1`, but unfortunately the `sc0vu/web3.php` package doesn't support it yet (officially), so you
have to skip the PHP platform requirement when running composer commands until it gets updated. 
```bash
composer install --ignore-platform-req=php
```

## YouTube tutorial
Coming soon...

## Deployment suggestion
I suggest deploying this app using [DigitalOcean](https://m.do.co/c/bcc172152095), behind a strong CDN network like
[CloudFlare](https://www.cloudflare.com).

By using my link you will be given a 100$ credit on DigitalOcean: https://m.do.co/c/bcc172152095