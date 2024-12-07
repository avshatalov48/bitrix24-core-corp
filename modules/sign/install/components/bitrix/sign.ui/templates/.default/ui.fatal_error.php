<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */

if ($arResult['ERRORS'])
{
	echo '<div class="sign-message-label error">';

	foreach ($arResult['ERRORS'] as $error)
	{
		echo $error->getMessage() . '<br/>';
	}

	echo '</div>';
}
