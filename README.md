Laravel History
================
Eloquent model history for Laravel.

General System Requirements
-------------
- [PHP >7.2.0](http://php.net/)
- [Laravel ~6.*](https://github.com/laravel/framework)


Quick Installation
-------------
If needed use composer to grab the library

```
$ composer require rudashi/laravel-history
```

Remember to put repository in a composer.json

```
"repositories": [
    {
        "type": "vcs",
        "url":  "https://github.com/rudashi/laravel-history.git"
    }
],
```

Finally, you'll need also to run migration
```
php artisan migrate
```

Usage
-------------


Authors
-------------
* **Borys Zmuda** - Lead designer - [LinkedIn](https://www.linkedin.com/in/boryszmuda/), [Portfolio](https://rudashi.github.io/)