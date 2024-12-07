<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CMain $APPLICATION */
/** @var array $arParams */
/** @var SignStartComponent $component */

$component->setMenuIndex('sign_contacts');
?>

<?php
$APPLICATION->IncludeComponent(
	'bitrix:sign.contact.list',
	'',
	[
		'MENU_ITEMS' => $arParams['MENU_ITEMS'],
	],
	$this->getComponent()
);
