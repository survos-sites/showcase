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

