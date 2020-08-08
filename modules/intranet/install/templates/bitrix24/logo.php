<?
use Bitrix\Intranet;
use Bitrix\Main\ModuleManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

//These settings are set in intranet.configs
$siteLogo = Intranet\Util::getClientLogo();
$siteTitle = trim(COption::GetOptionString("bitrix24", "site_title", ""));
if ($siteTitle == '')
{
	$siteTitle =
		ModuleManager::isModuleInstalled("bitrix24")
			? GetMessage('BITRIX24_SITE_TITLE_DEFAULT')
			: COption::GetOptionString("main", "site_name", "")
	;
}

$siteTitle = htmlspecialcharsbx($siteTitle);
$siteUrl = htmlspecialcharsbx(SITE_DIR);
$logo24 = Intranet\Util::getLogo24()

?><div class="menu-switcher"><?
	?><span class="menu-switcher-lines"></span><?
?></div><?

?><a href="<?=$siteUrl?>" title="<?=GetMessage("BITRIX24_LOGO_TOOLTIP")?>" class="logo"><?

	if ($siteLogo["logo"]):
		?><span class="logo-image-container"><?
			?><img
				src="<?=CFile::getPath($siteLogo["logo"])?>"
				<? if ($siteLogo["retina"]): ?>
				srcset="<?=CFile::getPath($siteLogo["retina"])?> 2x"
				<? endif ?>
			/><?
		?></span><?
	else:
		?><span class="logo-text-container">
			<span class="logo-text"><?=$siteTitle?></span><?
			if ($logo24):
				?><span class="logo-color"><?=$logo24?></span><?
			endif
		?></span><?
	endif

?></a>
