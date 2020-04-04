<?php
use \Bitrix\Landing\Manager;
use \Bitrix\Landing\Assets;
use \Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Loader::includeModule('landing'))
{
	return;
}

Manager::setTheme();
$assets = Assets\Manager::getInstance();
$assets->addAsset('landing_auto_font_scale');

$APPLICATION->ShowProperty('FooterJS');
?>

</main>
<?$APPLICATION->ShowProperty('BeforeBodyClose');?>
</body>
</html>