# Web application API server

## Getting started
If you want to try to use and tweak that example, you can follow these steps:

1. Run `git clone https://github.com/netjan/product-server` to clone the project
1. Generate the certificate `make certificate`
1. Create docker networks if not exist `docker network create backend` and `docker network create frontend`
1. Run `make install` to install the project
1. Run `make start` to up your containers
1. Visit https://localhost:8002/ and play with your app!

## Web application API client
Web application API client uses data from that application. See details at page `https://github.com/netjan/product-client`.
