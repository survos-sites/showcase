# Survos Showcase

Symfony applications built by Survos.  At attempt to organize various projects.

```bash
git clone git@github.com:survos-sites/showcase && cd showcase
echo "DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db" > .env.local
composer install
bin/console doctrine:schema:update --force
bin/console app:load 
symfony server:start -d
symfony open:local --path=/player/reg
````

For screenshots, etc.
    
```bash
vendor/bin/bdi detect drivers
bin/console app:screenshots 

```

mkdir casts -p
acine rec casts/${HOSTNAME}.${USER}--$(date '+%Y-%m-%d-%H-%M-%S')-$$.cast

# Tutorial Idea: Quotes

## Steps

* symfony new 
* composer req survos/code-bundle --dev
* bin/console survos:make:command app:load "Load the quote data from remote source"
* fields: url

## Zipped Show File

Cast + images/*.png

Multiple cast files, order by time?
@todo: look for ciine/snapshot /info/abc and it's OUTPUT (screenshot saved to /casts/abc.jpg)
