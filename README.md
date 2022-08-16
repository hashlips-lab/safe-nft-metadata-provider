# Safe NFT Metadata Provider

A simple yet powerful way to protect your NFT tokens from snipers.

## Disclaimer
This project was created for educational purposes, please refer to the [LICENCE](LICENSE) file for further information.

## Main features
- fetches the collection data about which tokens should be made publicly visible
- hides/reveals the metadata and assets when needed (e.g. on mint, once every N seconds or even based on custom logic)
- works with the [HashLips Labs](https://www.github.com/hashlips-lab)' contracts out-of-the-box, but can be adapted to any collection/contract
- it's extremely customizable _(you can even turn on a light at home on every mint)_
- easy and strong deployment options _(ranging from the newbie to the expert)_
- open-source (for everyone, forever)

## Meant for decentralization
This solution provides a **fair minting experience for everyone**.
Use it during the minting stage, then move your collection back to a decentralized storage option like IPFS.

## YouTube tutorials
- How to protect your NFTs from snipers (v2.x): https://youtu.be/putK0ToTdhY
- How to manage cloud object storage (S3) on DigitalOcean: https://youtu.be/EgeX-gb7q4w

### Legacy videos
#### v1.x
- How to setup this app for a new collection: https://youtu.be/fO5nT-TCIZs
- How to setup this app for an **already revealed** collection: https://youtu.be/sHOt0xdg5Dg

## Requirements

- [NodeJS](https://nodejs.org/) (v16+)
- S3-compatible storage solution (e.g. [AWS S3](https://aws.amazon.com/s3/), [DigitalOcean Spaces](https://m.do.co/c/bcc172152095), etc.)
- S3-compatible client (e.g. [Cyberduck](https://cyberduck.io/)) to upload your collection files (optional)
- Web3 JSON-RPC node (e.g. [Infura](https://infura.io/), [Alchemy](https://www.alchemy.com/), etc.)

## Deployment suggestions
We suggest deploying this app using [DigitalOcean](https://m.do.co/c/bcc172152095).

By using the following referral link you will be given a 100$ free credit on DigitalOcean: https://m.do.co/c/bcc172152095

You can also do a one-click deployment on DigitalOcean:

[![Deploy to DO](https://www.deploytodo.com/do-btn-blue.svg)](https://cloud.digitalocean.com/apps/new?repo=https://github.com/hashlips-lab/safe-nft-metadata-provider/tree/2.x&refcode=bcc172152095)
