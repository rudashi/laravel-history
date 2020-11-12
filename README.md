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

use Rudashi\LaravelHistory\Traits\HasHistory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasHistory;
}
```

#### Get model history relationship

```php
$model->history;
```

#### Get user operations relationship

```php
$user->operations;
```

Authors
-------------
* **Borys Zmuda** - Lead designer - [LinkedIn](https://www.linkedin.com/in/boryszmuda/), [Portfolio](https://rudashi.github.io/)