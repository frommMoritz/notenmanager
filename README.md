# Notenmanager
[WIP]
## 0. Requirements
 - PHPthe  7.2 (only tested in 7.2, according to [the symfony docs](https://symfony.com/doc/current/reference/requirements.html) PHP 7.1.3 is the minimum) 
 - MySQL Server (SQLite or Postgres may work, but not testet. You may need to run `php bin/console make:migration` before installing)
 - Apache / Nginx server. (For additional config please read [the symfony docs](https://symfony.com/doc/current/setup/web_server_configuration.html))

### 1. Initial Setup
To clone the code and install al the dependencies run:
```
git clone https://github.com/frommMoritz/notenmanager
composer install
```

Now you basicaly need to go to the `.env ` (created while composer install) and provide **at least** a mysql server. You also can add an mailsever (at the moment only used when a admin resets users password).

Now you can run:
```
php bin/console doctrine:migration:migrate
```

### 2. Updating
To update simply run:
```
git pull;
composer install;
php bin/console doctrine:migration:migrate --no-interaction;
php bin/console cache:clear --env=prod;
```

## Bug reports
If you encounter any bugs, feel free to [open a issue](https://github.com/frommMoritz/notenmanager/issues/new).
