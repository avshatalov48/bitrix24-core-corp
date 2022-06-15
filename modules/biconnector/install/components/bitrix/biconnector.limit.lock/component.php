<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 */

use Bitrix\Main\Localization\Loc;

if (!\Bitrix\Main\Loader::includeModule('biconnector'))
{
	return;
}

if (!\Bitrix\Main\Loader::includeModule('ui'))
{
	return;
}

$limitManager = \Bitrix\BIConnector\LimitManager::getInstance();
if ($limitManager->checkLimitWarning())
{
	return;
}

$fullLock = $limitManager->checkLimit() ? 'N' : 'Y';

if (\Bitrix\Main\Loader::includeModule('bitrix24'))
{
	$licensePath = \CBitrix24::PATH_LICENSE_ALL;
	if ($fullLock == 'Y')
	{
		$content = Loc::getMessage('CC_BLL_CONTENT_BLOCKED', [
			'#LIMIT#' => $limitManager->getLimit(),
			'#SHORT_DATE#' => $limitManager->getLimitDate(),
			'#ABOUT_LIMITS_HREF#' => \Bitrix\UI\Util::getArticleUrlByCode('14888370'),
		]);
	}
	else
	{
		$content = Loc::getMessage('CC_BLL_CONTENT_WARNING', [
			'#LIMIT#' => $limitManager->getLimit(),
			'#SHORT_DATE#' => $limitManager->getLimitDate(),
			'#ABOUT_LIMITS_HREF#' => \Bitrix\UI\Util::getArticleUrlByCode('14888370'),
		]);
	}
}
else
{
	$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
	switch ($region)
	{
	case 'ru':
		$licensePath = 'https://www.1c-bitrix.ru/buy/products/b24.php';
		break;
	case 'ua':
		$licensePath = 'https://www.bitrix.ua/buy/products/b24.php';
		break;
	case 'kz':
		$licensePath = 'https://www.1c-bitrix.kz/buy/products/b24.php';
		break;
	case 'by':
		$licensePath = 'https://www.1c-bitrix.by/buy/products/b24.php';
		break;
	case 'de':
		$licensePath = 'https://store.bitrix24.de/profile/license-keys.php';
		break;
	default:
		$licensePath = 'https://store.bitrix24.com/profile/license-keys.php';
		break;
	}

	if ($fullLock == 'Y')
	{
		$content = Loc::getMessage('CC_BLL_CONTENT_BLOCKED_BOX', [
			'#SHORT_DATE#' => $limitManager->getLimitDate(),
			'#ABOUT_LIMITS_HREF#' => \Bitrix\UI\Util::getArticleUrlByCode('15702822'),
		]);
	}
	else
	{
		$content = Loc::getMessage('CC_BLL_CONTENT_WARNING_BOX', [
			'#SHORT_DATE#' => $limitManager->getLimitDate(),
			'#ABOUT_LIMITS_HREF#' => \Bitrix\UI\Util::getArticleUrlByCode('15702822'),
		]);
	}
}

$arResult['JS_PARAMS'] = [
	'TITLE' => Loc::getMessage('CC_BLL_TITLE'),
	'CONTENT' => $content,
	'LICENSE_BUTTON_TEXT' => Loc::getMessage('CC_BLL_LICENSE_BUTTON_BOX'),
	'LATER_BUTTON_TEXT' => Loc::getMessage('CC_BLL_LATER_BUTTON'),
	'LICENSE_PATH' => $licensePath,
	'FULL_LOCK' => $fullLock,
];

$this->IncludeComponentTemplate();
