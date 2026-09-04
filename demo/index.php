<?php
require_once __DIR__ .'/../vendor/autoload.php';

$view = new \Roolith\Template\Engine\View(__DIR__ . '/views');
$view->setBaseUrl('http://localhost/roolith-template-engine/');
try {
    $data = [
        'content' => 'home content',
        'title' => 'home page',
    ];

    echo $view->compile('nested/template', $data);
} catch (\Roolith\Template\Engine\Exceptions\Exception | \Roolith\Template\Engine\Exceptions\InvalidArgumentException $e) {
    echo $e->getMessage();
}
