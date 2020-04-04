<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

CUtil::initJSCore(array('core'));
/*
switch($arResult['CUSTOMIZATION']['TEMPLATE_ID'])
{
	case 'colorless':
		$APPLICATION->SetPageProperty(
			"BodyClass",
			$APPLICATION->GetPageProperty("BodyClass") . ' page-theme-transparent'
		);
		break;
	case 'color':
		$APPLICATION->SetPageProperty(
			"BodyClass",
			$APPLICATION->GetPageProperty("BodyClass") . ' page-theme-colored'
		);

		$additionalCssString .= "\n.crm-webform-header-container, .crm-webform-header-container h2 {" .
			"\n" . 'background: ' . $arResult['CUSTOMIZATION']['BUTTON_COLOR_BG'] . ';' .
			"\n" . 'color: ' . $arResult['CUSTOMIZATION']['BUTTON_COLOR_FONT'] . ';' .
			"\n" . '}';
		break;
}
*/
if($arResult['CUSTOMIZATION']['BACKGROUND_IMAGE_PATH'])
{
	$APPLICATION->SetPageProperty(
		"BodyClass",
		$APPLICATION->GetPageProperty("BodyClass") . ' page-theme-image'
	);
	$additionalCssString .= "\n.page-theme-image {" .
		"\n\t" . 'background-image: url("' . $arResult['CUSTOMIZATION']['BACKGROUND_IMAGE_PATH'] . '");' .
		"\n" . '}';

	$additionalCssString .= "\n.title-num {" .
		"\n\t" . 'color: #fff;' .
		"\n" . '}';
}

\Bitrix\Main\Page\Asset::getInstance()->addString(
	'<meta name="viewport" content="width=device-width, initial-scale=0.6"/>'
);
\Bitrix\Main\Page\Asset::getInstance()->addString(
	'<meta property="og:title" content="' . $arResult['CUSTOMIZATION']['OG_CAPTION'] . '" />'
);

\Bitrix\Main\Page\Asset::getInstance()->addString(
	'<meta property="og:description" content="' . $arResult['CUSTOMIZATION']['OG_DESCRIPTION'] . '" />'
);

if(is_array($arResult['CUSTOMIZATION']['OG_IMAGE']))
{
	foreach($arResult['CUSTOMIZATION']['OG_IMAGE'] as $image)
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
