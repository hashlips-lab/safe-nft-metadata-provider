# Safe NFT Metadata Provider

A simple yet powerful HTTP metadata and assets provider for NFT collections using [Symfony](https://www.symfony.com).

## Disclaimer
This project was created for educational purposes, please refer to the [LICENCE](LICENSE) file for further information.

## Main features
- hide metadata for unminted tokens
- update the total supply in many ways _(manually, OpenSea API, Web3 or even your own implementation...)_
- shuffle a range of tokens without messing around with your original metadata
  _(e.g. owning a half-minted collection? We have you covered! Keep you tokens safe with a zero-downtime solution
  and keep snipers away)_
- powerful collection management tools built-in _(shuffle tokens, update metadata, etc.)_
- customizable to fit your needs
- easy and strong deployment options _(ranging from the newbie to the expert)_
- multiple storage options _(local, S3 or your own implementation...)_
- open-source (for everyone, forever)

## Meant for decentralization
This solution provides a **fair minting experience for everyone**.
Use it during the minting stage, then move your collection back to a decentralized storage using the included tools.

## YouTube tutorial
Coming soon...

## Deployment suggestions
We suggest deploying this app using [DigitalOcean](https://m.do.co/c/bcc172152095), behind a strong CDN network like
[CloudFlare](https://www.cloudflare.com).

By using the following referral link you will be given a 100$ credit on DigitalOcean: https://m.do.co/c/bcc172152095

You can also do a one-click deployment on DigitalOcean:

[![Deploy to DO](https://www.deploytodo.com/do-btn-blue.svg)](https://cloud.digitalocean.com/apps/new?repo=https://github.com/hashlips-lab/safe-nft-metadata-provider/tree/main)
