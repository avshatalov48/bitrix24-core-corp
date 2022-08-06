<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 */

$errors = $arResult['errorMessage'] ?? [];

foreach ($errors as $error)
{
	?>
	<div class="page-description"><?= $error ?></div>
	<?php
}
