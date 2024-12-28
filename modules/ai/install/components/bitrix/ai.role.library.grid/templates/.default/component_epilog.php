<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CMain $APPLICATION */
/** @var array $arParams */

$APPLICATION->clearViewContent('above_pagetitle');
$this->getTemplate()->setViewTarget('above_pagetitle', 100);

$APPLICATION->includeComponent(
	'bitrix:ai.library.top.menu',
	'',
	[
		'parent' => $this->getName()
	]
);

$this->getTemplate()->endViewTarget();
