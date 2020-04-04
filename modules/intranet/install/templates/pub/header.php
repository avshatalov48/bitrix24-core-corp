<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
\Bitrix\Main\Localization\Loc::loadMessages(dirname(__FILE__)."/footer.php");

\Bitrix\Main\Loader::includeModule('intranet');

CUtil::initJSCore(array('ajax', 'popup'));

?><!DOCTYPE html>
<html>
<head>
<meta name="robots" content="noindex, nofollow, noarchive">
<?
$APPLICATION->showHead();
$APPLICATION->setAdditionalCSS("/bitrix/templates/bitrix24/interface.css", true);
\Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH."/template_scripts.js", true);

$publicPageSiteName = COption::getOptionString('intranet', 'public_page_site_name', '');
if ($publicPageSiteName)
{
	$siteName = $publicPageSiteName;
}
else if (isModuleInstalled('bitrix24'))
{
	$siteName = COption::getOptionString('bitrix24', 'site_title', '');
}
else
{
	$siteName = COption::getOptionString('main', 'site_name', '');
}

?>
<title><? $APPLICATION->showTitle(); ?></title>
</head>

<body class="<?$APPLICATION->showProperty("BodyClass")?>">
<?
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
						<? $clientLogo = Bitrix\Intranet\Util::getClientLogo(); ?>
						<? if ($clientLogo['logo']): ?>
							<img class="intranet-pub-title-user-logo" src="<?=CFile::getPath($clientLogo['logo']) ?>"
								<? if ($clientLogo['retina']): ?> srcset="<?=CFile::getPath($clientLogo['retina']) ?> 2x"<? endif ?>>
						<? elseif (Bitrix\Main\Config\Option::get('main', 'wizard_site_logo', '', SITE_ID)): ?>
							<? $APPLICATION->includeComponent(
								'bitrix:main.include', '',
								array('AREA_FILE_SHOW' => 'file', 'PATH' => SITE_DIR.'include/company_name.php')
							); ?>
						<? else : ?>
							<span class="main-title-inner"><?=htmlspecialcharsbx($siteName); ?></span>
							<? if ($logo24 = Bitrix\Intranet\Util::getLogo24()): ?>
								<span class="title-num"><?=$logo24 ?></span>
							<? endif ?>
						<? endif; ?>
					</h1>
