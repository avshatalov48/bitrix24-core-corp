<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>

<?php foreach ($arResult['ERRORS'] as $error): ?>
	<?= ShowError($error); ?>
<?php endforeach ?>