<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Localization\Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/".SITE_TEMPLATE_ID."/header.php");

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.montserrat',
	'popup',
	'fx',
]);

?><!DOCTYPE html>
<html>
<head>
	<title><?$APPLICATION->ShowTitle();?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="robots" content="noindex, nofollow" />
	<?if (IsModuleInstalled("bitrix24")):?>
	<meta name="apple-itunes-app" content="app-id=561683423">
	<link rel="apple-touch-icon-precomposed" href="/images/iphone/57x57.png" />
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="/images/iphone/72x72.png" />
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="/images/iphone/114x114.png" />
	<link rel="apple-touch-icon-precomposed" sizes="144x144" href="/images/iphone/144x144.png" />
	<?endif?>
	<?$APPLICATION->ShowHead();?>
</head>
<body>
<?
/*
This is commented to avoid Project Quality Control warning
$APPLICATION->ShowPanel();
*/
?>
<table class="log-main-table">
	<tr>
		<td class="log-top-cell">
			<a class="main-logo main-logo-<?if (LANGUAGE_ID === "ru"):?>ru<?elseif(LANGUAGE_ID === "ua"):?>ua<?else:?>en<?endif?>" href="/" title="<?=GetMessage("BITRIX24_TITLE")?>"></a>
		</td>
	</tr>
	<tr>
		<td class="log-main-cell">
			<div class="log-popup-wrap <? $APPLICATION->ShowProperty("popup_class","") ?>" id="login-popup-wrap">
				<div class="log-popup" id="login-popup">
