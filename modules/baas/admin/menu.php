<?php

declare(strict_types=1);

global $adminMenu;

$menu = [];
$menu[] = [
	"sort" => 500,
	"parent_menu" => "global_menu_marketplace",
	"icon" => "update_marketplace",
	"page_icon" => "update_marketplace_page_icon",
	"text" => \Bitrix\Main\Localization\Loc::getMessage('BAAS_MENU_TITLE'),
	"url" => "baas_marketplace.php?lang=".LANGUAGE_ID,
	"more_url" => array("baas_marketplace.php", "baas_marketplace_package.php"),
	"title" => \Bitrix\Main\Localization\Loc::getMessage('BAAS_MENU_TITLE'),
];

return $menu;
