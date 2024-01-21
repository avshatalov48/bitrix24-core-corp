<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @var array $componentParams */

require_once __DIR__ . '/params.php';

$componentParams = array_merge(
	$componentParams,
	[
		'PAGE' => 'user_discussions',
		'PAGE_TYPE' => 'user',
		'PAGE_ID' => 'discussions',
	],
);

$listComponentParams = array_merge(
	$componentParams,
	[

	],
);

$menuComponentParams = array_merge(
	$componentParams,
	[

	],
);

$toolbarComponentParams = array_merge(
	$componentParams,
	[

	],
);

$contentComponentParams = array_merge(
	$componentParams,
	[

	],
);

require_once __DIR__ . '/template.php';
