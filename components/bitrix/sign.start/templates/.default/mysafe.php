<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CMain $APPLICATION */
/** @var array $arParams */
/** @var SignStartComponent $component */

$component->setMenuIndex('sign_mysafe');
?>

<?php
$APPLICATION->IncludeComponent(
	'bitrix:sign.mysafe',
	'',
	[
	],
	$this->getComponent()
);
