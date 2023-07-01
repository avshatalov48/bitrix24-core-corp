<?php

use Bitrix\Intranet;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;

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

?><div class="menu-switcher"><?php
	?><span class="menu-switcher-lines"></span><?php
?></div>
<div class="logo">
<a href="<?=$siteUrl?>" title="<?=GetMessage("BITRIX24_LOGO_TOOLTIP")?>" class="logo-link"><?php

	if ($siteLogo["logo"]):
		?><span class="logo-image-container"><?php
			?><img
				src="<?=CFile::getPath($siteLogo["logo"])?>"
				<?php if ($siteLogo["retina"]): ?>
				srcset="<?=CFile::getPath($siteLogo["retina"])?> 2x"
				<?php endif ?>
			/><?php
		?></span><?php
	else:
		?><span class="logo-text-container">
			<span class="logo-text"><?=$siteTitle?></span><?php
			if ($logo24):
				?><span class="logo-color"><?=$logo24?></span><?php
			endif
		?></span><?php
	endif;?>
</a>
	<?php if (Loader::includeModule("bitrix24")):
		if (\CBitrix24::IsPortalAdmin($USER->GetID()) && !\CBitrix24::isDomainChanged()):?>
			<div class="header-logo-block-settings header-logo-block-settings-show" data-rename-portal="true">
				<span
					class="header-logo-block-settings-item"
					onclick="BX.Bitrix24.renamePortal(this)"
					title="<?=GetMessage('BITRIX24_SETTINGS_TITLE')?>"></span>
			</div><?php
		endif;

		$APPLICATION->IncludeComponent(
				'bitrix:bitrix24.holding',
				'.default', [],
				false,
				['HIDE_ICONS' => 'Y']
			);
	endif;?>
</div>