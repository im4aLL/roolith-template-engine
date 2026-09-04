<?php
use Roolith\Template\Engine\Exceptions\Exception;
use Roolith\Template\Engine\Exceptions\InvalidArgumentException;

require_once __DIR__ .'/../vendor/autoload.php';

$view = new \Roolith\Template\Engine\View(__DIR__ . '/views');
$view->setBaseUrl('http://localhost/roolith-template-engine/');
try {
    $data = [
        'content' => 'home content',
        'title' => 'home page',
    ];

    echo $view->compile('home', $data);
} catch (Exception | InvalidArgumentException $e) {
    echo $e->getMessage();
}
