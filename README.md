# roolith-template-engine
No overcomplicated stuff! Just plain PHP in template file. No `eval`, it uses output buffering!

#### Install
```
composer install roolith/template-engine
```

#### Usage
```php
$view = new \Roolith\View(__DIR__ . '/views');

try {
    $data = [
        'content' => 'home content',
        'title' => 'home page',
    ];

    echo $view->compile('home', $data);
} catch (\Roolith\Exceptions\Exception $e) {
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