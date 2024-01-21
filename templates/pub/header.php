<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
\Bitrix\Main\Localization\Loc::loadMessages(__DIR__."/footer.php");

\Bitrix\Main\Loader::includeModule('intranet');

CUtil::initJSCore(array('ajax', 'popup', 'ui.design-tokens', 'ui.fonts.opensans', 'ui.fonts.roboto'));

?><!DOCTYPE html>
<html>
<head>
<meta name="robots" content="noindex, nofollow, noarchive">
<?php
$APPLICATION->showHead();
$APPLICATION->setAdditionalCSS("/bitrix/templates/bitrix24/interface.css", true);
\Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH."/template_scripts.js", true);

$publicPageSiteName = COption::getOptionString('intranet', 'public_page_site_name', '');
if ($publicPageSiteName)
{
	$siteName = $publicPageSiteName;
}
else
{
	$siteName = \Bitrix\Intranet\Portal::getInstance()->getSettings()->getTitle();
}

$customTitle = '';
if (defined('CUSTOM_HEADER_TITLE') && is_string(CUSTOM_HEADER_TITLE))
{
	$customTitle = htmlspecialcharsbx(CUSTOM_HEADER_TITLE);
}

?>
<title><?php $APPLICATION->showTitle(); ?></title>
</head>

<body class="<?php $APPLICATION->showProperty("BodyClass")?>">
<?php
/*
This is commented to avoid Project Quality Control warning
$APPLICATION->ShowPanel();
*/
?>
<table class="main-wrapper">
	<tr>
		<td class="main-wrapper-content-cell">
			<div class="content-wrap">
				<div class="content">
					<h1 class="main-title">
						<?php if (!empty($customTitle)): ?>
							<span class="main-title-custom"><?=$customTitle?></span>
						<?php elseif ($clientLogo = \Bitrix\Intranet\Portal::getInstance()->getSettings()->getLogo()): ?>
							<img class="intranet-pub-title-user-logo" src="<?=$clientLogo['src']?>"
								<?php if (isset($clientLogo['srcset'])): ?> srcset="<?=$clientLogo['srcset'] ?>"<?php endif ?>>
						<?php elseif (Bitrix\Main\Config\Option::get('main', 'wizard_site_logo', '', SITE_ID)): ?>
							<?php $APPLICATION->includeComponent(
								'bitrix:main.include', '',
								array('AREA_FILE_SHOW' => 'file', 'PATH' => SITE_DIR.'include/company_name.php')
							); ?>
						<?php else : ?>
							<span class="main-title-inner"><?=htmlspecialcharsbx($siteName); ?></span>
							<?php if ($logo24 = Bitrix\Intranet\Portal::getInstance()->getSettings()->getLogo24()): ?>
								<span class="title-num"><?=$logo24 ?></span>
							<?php endif ?>
						<?php endif; ?>
					</h1>
