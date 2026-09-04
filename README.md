# roolith-template-engine
No overcomplicated stuff! Just plain PHP in template file. No `eval`, it uses output buffering!

Requires PHP `>=8.0`.

#### Install
```
composer require roolith/template-engine
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
} catch (\Roolith\Template\Engine\Exceptions\Exception | \Roolith\Template\Engine\Exceptions\InvalidArgumentException $e) {
    echo $e->getMessage();
}
```
Missing templates throw `Exceptions\Exception` with a `resolved:` path in the message.
Invalid view names, folders and extensions throw `Exceptions\InvalidArgumentException`, which extends SPL `\InvalidArgumentException` so it can be caught as either.
Catching base `\Exception` also catches both, but the union above is explicit.

`views` folder contains -
```
home.php
partials/header.php
partials/footer.php
```

Where `home.php`
```php
<?php /** @var \Roolith\Template\Engine\Interfaces\TemplateContextInterface $this */ ?>
<?php $this->inject('partials/header') ?>

    <p><?= $this->escape('content') ?></p>

<?php $this->inject('partials/footer') ?>
```

`header.php`
```php
<?php /** @var \Roolith\Template\Engine\Interfaces\TemplateContextInterface $this */ ?>
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
<?php /** @var \Roolith\Template\Engine\Interfaces\TemplateContextInterface $this */ ?>
    <script src="<?= $this->url('assets/app.js') ?>"></script>

</body>
</html>
```

#### Editor support (IDE)
Templates are included in `View` scope, so `$this` is a `View` at runtime.
Editors analyze template files standalone and flag `$this` as invalid.
Add this as line 1 in every template that uses `$this`:
```php
<?php /** @var \Roolith\Template\Engine\Interfaces\TemplateContextInterface $this */ ?>
```
It changes no runtime behavior and shows only template helpers in autocomplete: `inject()`, `escape()`, `e()` and `url()`.
`View` implements both `ViewInterface` and `TemplateContextInterface`, so this docblock stays valid at runtime.
Use `View` or `ViewInterface` in the docblock only if you also need app methods like `compile()`, `setViewFolder()` or `setBaseUrl()` inside a template.

#### Escaping
You may use `escape` method or just print variable as plain.

```php
<title><?= $this->escape('title') ?></title>
```
or
```php
<title><?= $title ?></title>
```

`e($value)` escapes a raw value with `htmlspecialchars` (`ENT_QUOTES | ENT_SUBSTITUTE`, UTF-8).
`null` and `false` render as `''`.
Scalars and stringable objects are supported.
Arrays and non-stringable objects throw `Exceptions\InvalidArgumentException`.
`escape($var)` escapes a named template variable and throws `Exceptions\Exception` when the variable is not defined.
XSS payloads, UTF-8 and invalid UTF-8 sequences are covered by tests.

#### Inject
`inject` method allows to inject another view.
```php
$this->inject('partials/footer')
```
It accepts optional data for the partial.
```php
$this->inject('partials/header', ['title' => 'home page'])
```
Injected data does not leak into sibling or parent templates.
Consecutive `compile()` calls also start clean, and output buffers are fully drained even when templates open nested buffers or throw.

For nested call.

```php
$view->compile('nested/template', $data);
```
It will look for `nested` folder and `template.php` file.
Note: `demo/views` ships `nested/template.php`, while `tests/viewsForTest` uses `nested/nested.php` as its nested fixture.

View names use `/` as the canonical separator.
`partials/header` resolves to `partials/header.php`.
The dot form `partials.header` resolves to the same file for backward compatibility, but it is deprecated and triggers `E_USER_DEPRECATED`.
New code should use `/`.

#### Path resolver
`src/TemplatePathResolver.php` validates names and resolves them to file paths.
`View` delegates to it, and it is the source of truth for view folder and file extension.
```php
$resolver = new \Roolith\Template\Engine\TemplatePathResolver(__DIR__ . '/views', 'php');
$resolver->setViewFolder(__DIR__ . '/views');
$resolver->setFileExtension('phtml'); // leading dot is optional
$path = $resolver->resolve('partials/header');
```
Inject a custom resolver into `View`.
```php
$view = new \Roolith\Template\Engine\View(null, $resolver);
$view->getPathResolver();
$view->setPathResolver($resolver);
```
`View::$viewFolder` is only a backward compatible mirror.
Use `getPathResolver()->getViewFolder()` and `getPathResolver()->getFileExtension()` as the source of truth.

#### Validation and security
View names must match `#^[A-Za-z0-9_-]+(?:[./][A-Za-z0-9_-]+)*$#`.
Empty names, `..` segments, absolute paths (`/etc/hostname`), backslashes, and stream wrappers (`php://`, `file://`, `data://`, anything containing `:`) are rejected with `InvalidArgumentException`.
Missing view folders, empty folders and empty extensions are also rejected.
Existing files are checked with `realpath` so symlinked paths escaping the view folder are rejected.

#### URLs
`setBaseUrl('http://example.com/')` sets the base URL, `setBaseUrl(false)` disables it.
`url('assets/app.css')` joins base and suffix while normalizing slashes.
Without a base URL it returns `/assets/app.css`.
`getBaseUrl()` returns the current base URL or `false`.

#### Template data
```php
$view->setTemplateData(['a' => 1]);
$view->addTemplateData(['b' => 2]);
$view->getTemplateData();
$view->resetTemplateData();
```
`compile($name, $data)` replaces template data for that render and resets it afterwards.
Template variables are extracted in an isolated scope, so keys like `filename`, `data`, `level`, `output` and `path` remain readable inside templates.

#### Contracts and structure
`src/Interfaces/TemplateContextInterface.php` is the narrow template scope seen as `$this` inside templates (`inject`, `url`, `e`, `escape`).
`src/Interfaces/ViewInterface.php` extends it with app methods (`setViewFolder`, `compile`, `setBaseUrl`, `getBaseUrl`).
The concrete `View` adds `getPathResolver`, `setPathResolver`, `getTemplateData`, `setTemplateData`, `resetTemplateData` and `addTemplateData` as composition and test seams.
`src/Exceptions/Exception.php` signals missing templates and undefined variables.
`src/Exceptions/InvalidArgumentException.php` signals invalid names, folders and escape values.
See `demo/index.php` and `demo/views` for a runnable example.

Expected unit test result.

```bash
$ ./vendor/bin/phpunit --testdox tests --stderr
PHPUnit 9.6.36 by Sebastian Bergmann and contributors.

Exceptions
 ✔ Exception should extend base exception
 ✔ Invalid argument exception should extend spl invalid argument exception

Template Path Resolver
 ✔ Should resolve simple name to candidate path
 ✔ Should resolve nested slash without deprecation
 ✔ Should resolve dot as deprecated alias
 ✔ Should return realpath for existing template
 ✔ Should return candidate path for missing template
 ✔ Should reject parent directory traversal
 ✔ Should reject absolute paths
 ✔ Should reject stream wrappers
 ✔ Should reject empty and invalid names
 ✔ Should require view folder
 ✔ Should validate view folder on set
 ✔ Should normalize trailing slash
 ✔ Should support custom file extension
 ✔ View should accept injected resolver
 ✔ View should sync view folder with resolver
 ✔ View constructor folder should override resolver folder
 ✔ View set path resolver should sync view folder
 ✔ Should reject empty file extension
 ✔ Should default to php extension
 ✔ Should resolve names with hyphen underscore and numbers
 ✔ Should normalize file extension variants

View
 ✔ Should set view folder
 ✔ Should have string and array type hints
 ✔ Should normalize view folder trailing slash
 ✔ Should reject invalid view folder
 ✔ Should reject invalid view folder in constructor
 ✔ Should escape variable
 ✔ Should throw when escaping undefined variable
 ✔ Should escape xss payload
 ✔ Should render null as empty string
 ✔ Should handle utf 8
 ✔ Should add slash before url
 ✔ Should join base url without trailing slash
 ✔ Should join base url with trailing slash
 ✔ Should trim leading slash from suffix
 ✔ Should compile view file
 ✔ Should throw for missing view file
 ✔ Should compile nested view file
 ✔ Should resolve slash as canonical separator
 ✔ Should support dot separator as deprecated alias
 ✔ Should resolve partial slash and dot to same file
 ✔ Should reject parent directory traversal
 ✔ Should reject absolute paths
 ✔ Should reject stream wrappers
 ✔ Should restore buffer level after throwing template
 ✔ Missing template message should contain resolved path
 ✔ Should not leak inject data between sibling partials
 ✔ Should not leak template data between consecutive compiles
 ✔ Should throw for missing injected partial and restore template data
 ✔ Should manage template data
 ✔ Should escape scalar values
 ✔ Should escape stringable object
 ✔ Should default base url to false and allow reset
 ✔ Should handle empty url suffix
 ✔ Should fail compile without view folder
 ✔ Should propagate custom extension from resolver
 ✔ Constructor folder should override different resolver folder
 ✔ Should not overwrite locals via colliding data keys
 ✔ Should restore buffer level after successful compile
 ✔ Should implement view interface fully

Time: 00:00.005, Memory: 6.00 MB

OK (62 tests, 168 assertions)
```
