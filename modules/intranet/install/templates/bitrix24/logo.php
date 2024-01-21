<?php
/**
 * @param CMain $APPLICATION
 */
use Bitrix\Intranet;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

//These settings are set in intranet.configs
$siteLogo = Intranet\Portal::getInstance()->getSettings()->getLogo();
$siteTitle = Intranet\Portal::getInstance()->getSettings()->getTitle();

$siteTitle = htmlspecialcharsbx($siteTitle);
$siteUrl = htmlspecialcharsbx(SITE_DIR);
$logo24 = Intranet\Util::getLogo24()

?><div class="menu-switcher"><?php
	?><span class="menu-switcher-lines"></span><?php
?></div>
<div class="logo">
<a href="<?=$siteUrl?>" title="<?=GetMessage("BITRIX24_LOGO_TOOLTIP")?>" class="logo-link"><?php

	if (isset($siteLogo['src'])):
		?><span class="logo-image-container"><?php
			?><img src="<?=$siteLogo['src']?>"
				<?php if (isset($siteLogo['srcset'])): ?>
					srcset="<?=$siteLogo['srcset']?> 2x"
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
	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.settings.widget',
		'.default'
	);
	?>
</div>