<p align="center"><img src="./art/logo-mock.svg" width="400" alt=""></p>

Laravel History
================

Eloquent model history for Laravel.

Package provides easy to use functions to log the activities of the users of your app. It can also log model events.

## General System Requirements

- [PHP ^8.0](http://php.net/)
- [Laravel ^9.0](https://github.com/laravel/framework)

## Quick Installation

If necessary, use the composer to download the library

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
```bash
php artisan migrate
```

## Usage

Add `HasOperations` trait to user model.

```php
<?php

namespace App;

use Rudashi\LaravelHistory\Traits\HasOperations;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasOperations;
}
```

Add `HasHistory` trait to tracked model.

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Rudashi\LaravelHistory\Traits\HasHistory;
use Rudashi\LaravelHistory\Contracts\HasHistoryInterface;

class Message extends Model implements HasHistoryInterface
{
    use HasHistory;
}
```

### Get model history relationship

```php
$model->history;
History::ofModel($model);
History::ofModel(Model::class, 1);
```
### Get user operations relationship

```php
$user->operations;
History::ofUser($user);
History::ofUser(User::class, 1);
```

## Authors

* **Borys Żmuda** - Lead designer - [LinkedIn](https://www.linkedin.com/in/boryszmuda/), [Portfolio](https://rudashi.github.io/)
