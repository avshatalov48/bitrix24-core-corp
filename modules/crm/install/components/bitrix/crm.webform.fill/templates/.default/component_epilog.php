<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\WebForm\Helper;

CUtil::initJSCore(array('core'));

$APPLICATION->SetPageProperty(
	"BodyClass",
	$APPLICATION->GetPageProperty("BodyClass") . ' footer-logo-none'
);

if($arParams['VIEW_TYPE'] == 'frame')
{
	$APPLICATION->SetPageProperty(
		"BodyClass",
		$APPLICATION->GetPageProperty("BodyClass") . ' crm-webform-iframe'
	);
}

if($arResult['CUSTOMIZATION']['NO_BORDERS'])
{
	$APPLICATION->SetPageProperty(
		"BodyClass",
		$APPLICATION->GetPageProperty("BodyClass") . ' crm-webform-no-borders'
	);
}

$additionalCssString = '
.crm-webform-submit-button-loader-customize::before{
'. (
	$arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT']
		?
		'border-color: ' . $arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT'] . ';'
		:
		''
	) .
	(
	$arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT']
		?
		'color: ' . $arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT'] . ';'
		:
		''
	) . '
}
.crm-webform-submit-button-loader-customize::after{
' . (
	$arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT']
		?
		'background: ' . $arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT'] . ';'
		:
		''
	) . '

}
button.crm-webform-submit-button {
' . (
	$arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT']
		?
		'color: ' . $arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT'] . ';'
		:
		''
	) .
	(
	$arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT']
		?
		'background: ' . $arResult['CUSTOMIZATION']['BUTTON_COLOR_BG'] . ';'
		:
		''
	) . '
}';

switch($arResult['CUSTOMIZATION']['TEMPLATE_ID'])
{
	case Helper::ENUM_TEMPLATE_TRANSPARENT:
		$APPLICATION->SetPageProperty(
			"BodyClass",
			$APPLICATION->GetPageProperty("BodyClass") . ' page-theme-transparent'
		);
		break;
	case Helper::ENUM_TEMPLATE_COLORED:
		$APPLICATION->SetPageProperty(
			"BodyClass",
			$APPLICATION->GetPageProperty("BodyClass") . ' page-theme-colored'
		);

		$additionalCssString .= ".crm-webform-header-container, .crm-webform-header-container h2 {" .
			"\n" . 'background: ' . $arResult['CUSTOMIZATION']['BUTTON_COLOR_BG'] . ';' .
			"\n" . 'color: ' . $arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT'] . ';' .
			"\n" . '}';
		break;
}

if($arResult['CUSTOMIZATION']['BACKGROUND_IMAGE_PATH'])
{
	$APPLICATION->SetPageProperty(
		"BodyClass",
		$APPLICATION->GetPageProperty("BodyClass") . ' page-theme-image'
	);
	$additionalCssString .= ".page-theme-image {" .
		"\n" . 'background-image: url("' . $arResult['CUSTOMIZATION']['BACKGROUND_IMAGE_PATH'] . '");' .
		"\n" . '}';
}

\Bitrix\Main\Page\Asset::getInstance()->addString(
	'<meta property="og:title" content="' . $arResult['CUSTOMIZATION']['OG_CAPTION'] . '" />'
);

\Bitrix\Main\Page\Asset::getInstance()->addString(
	'<meta property="og:description" content="' . $arResult['CUSTOMIZATION']['OG_DESCRIPTION'] . '" />'
);

if(is_array($this->arResult['CUSTOMIZATION']['OG_IMAGE']))
{
	foreach($this->arResult['CUSTOMIZATION']['OG_IMAGE'] as $image)
	{
		\Bitrix\Main\Page\Asset::getInstance()->addString('<meta property="og:image" content="' . $image['PATH']  . '" />');
		if(\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps())
		{
			\Bitrix\Main\Page\Asset::getInstance()->addString('<meta property="og:image:secure_url" content="' . $image['PATH_HTTPS'] . '" />');
		}
		if($image['TYPE'])
		{
			\Bitrix\Main\Page\Asset::getInstance()->addString('<meta property="og:image:type" content="' . $image['TYPE'] . '" />');
		}
		\Bitrix\Main\Page\Asset::getInstance()->addString('<meta property="og:image:width" content="' . $image['WIDTH'] . '" />');
		\Bitrix\Main\Page\Asset::getInstance()->addString('<meta property="og:image:height" content="' . $image['HEIGHT'] . '" />');
	}
}

\Bitrix\Main\Page\Asset::getInstance()->addString(
	'<style type="text/css">' . "\n" . $additionalCssString . "\n" . '</style>'
);
\Bitrix\Main\Page\Asset::getInstance()->addString(
	'<style type="text/css">' . "\n" . $arResult['CUSTOMIZATION']['CSS_TEXT'] . "\n" . '</style>'
);