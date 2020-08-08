<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/marketing/.left.menu.php");

$aMenuLinks = Array();

if (!\Bitrix\Main\Loader::includeModule('sender'))
{
	return;
}

if (!\Bitrix\Sender\Security\User::current()->hasAccess())
{
	return;
}

if (\Bitrix\Sender\Security\Access::current()->canViewStart())
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_START'),
		"/marketing/",
		Array(),
		Array(),
		""
	);
}

if (\Bitrix\Sender\Security\Access::current()->canViewLetters())
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_LETTERS'),
		"/marketing/letter/",
		Array(),
		Array(),
		""
	);
}

if (\Bitrix\Sender\Security\Access::current()->canViewAds())
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_ADS'),
		"/marketing/ads/",
		Array(),
		Array(),
		""
	);
}

if (\Bitrix\Sender\Security\Access::current()->canViewSegments())
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_SEGMENTS'),
		"/marketing/segment/",
		Array(),
		Array(),
		""
	);
}

if (\Bitrix\Sender\Security\Access::current()->canViewRc())
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_RETURN_CUSTOMER'),
		"/marketing/rc/",
		Array(),
		Array(),
		""
	);
}

if (
	method_exists(\Bitrix\Sender\Security\Access::current(), 'canViewToloka')
	&& \Bitrix\Sender\Security\Access::current()->canViewToloka()
)
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_YANDEX_TOLOKA'),
		"/marketing/toloka/",
		Array(),
		Array(),
		""
	);
}

if (\Bitrix\Sender\Security\Access::current()->canViewLetters())
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_TEMPLATES'),
		"/marketing/template/",
		Array(),
		Array(),
		""
	);
}

if (\Bitrix\Sender\Security\Access::current()->canViewBlacklist())
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_BLACKLIST'),
		"/marketing/blacklist/",
		Array(),
		Array(),
		""
	);
}

if (\Bitrix\Sender\Security\Access::current()->canViewSegments())
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_CONTACT'),
		"/marketing/contact/",
		Array(),
		Array(),
		""
	);
}

if (\Bitrix\Sender\Security\Access::current()->canModifySettings())
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_CONFIG'),
		"/marketing/config.php",
		Array(),
		Array(),
		""
	);
}

if (\Bitrix\Sender\Security\Access::current()->canModifySettings())
{
	$aMenuLinks[] = Array(
		GetMessage('SERVICES_MENU_MARKETING_ROLE'),
		"/marketing/config/role/",
		Array(),
		Array(),
		""
	);
}