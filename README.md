# roolith-template-engine
No overcomplicated stuff! Just plain PHP in template file. No `eval`, it uses output buffering!

#### Install
```
composer install roolith/template-engine
```

#### Usage
```php
$view = new \Roolith\Template\Engine\View(__DIR__ . '/views');

try {
    $data = [
        'content' => 'home content',
        'title' => 'home page',
    ];

    echo $view->compile('home', $data);
} catch (\Roolith\Template\Engine\Exceptions\Exception $e) {
    echo $e->getMessage();
}
```


`views` folder contains - 
```
home.php
partials/header.php
partials/footer.php
```

Where `home.php`
```php
<?php $this->inject('partials/header') ?>

    <p><?= $this->escape('content') ?></p>

<?php $this->inject('partials/footer') ?>
```

`header.php`
```php
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $this->escape('title') ?></title>
    <link rel="stylesheet" href="<?= $this->url('assets/app.css') ?>">
</head>
<body>
```

`footer.php`
```php
    <script src="<?= $this->url('assets/app.js') ?>"></script>

</body>
</html>
```

You may use `escape` method or just print variable as plain

```php
<title><?= $this->escape('title') ?></title>
```
or
```php
<title><?= $title ?></title>
```

`inject` method allows to inject another view
```php
$this->inject('partials/footer')
```

For nested call

```php
$view->compile('nested.template', $data);
```
It will look for `nested` folder and `template.php` file.

Expected unit test result 

```bash
$ ./vendor/bin/phpunit --testdox tests --stderr
PHPUnit 9.6.23 by Sebastian Bergmann and contributors.

View
 ✔ Should set view folder
 ✔ Should escape variable
 ✔ Should add slash before url
 ✔ Should compile view file
 ✔ Should compile nested view file

Time: 00:00.009, Memory: 4.00 MB

OK (5 tests, 11 assertions)
```