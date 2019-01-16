geo_local
=========

A Symfony project created on January 15, 2017, 2:44 pm.

Described in blog post [Polish language]

> https://blog.gustawdaniel.pl/2017/01/17/aplikacja-z-fosuserbundle-i-api-google-maps.html

![](http://i.imgur.com/YwW9q5l.png)

### Installation:

Frontend packages:

```
npm install -g bower
bower install
```

or

```
yarn
```

Backend packages:

```
composer install
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console assets:install --symlink
php bin/console server:run --docroot=web
```

```
firefox localhost:8000
```
