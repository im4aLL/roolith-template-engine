<?php /** @var \Roolith\Template\Engine\View $this */ ?>
<?php $this->inject('partials/header') ?>

    <p><?= $this->escape('content') ?></p>

<?php $this->inject('partials/footer', ['templateVar' => 'something']) ?>
