<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Response\Converter;

/**
 * @var array $arResult
 * @var array $arParams
 */

$arResult['JS_DATA'] = [
	'app' => [
		'paySystems' => $arResult['PAYSYSTEMS_LIST'],
		'template' => 'paySystemList',
		'title' => Loc::getMessage('SPP_SELECT_PAYMENT_TITLE_NEW_NEW_MSGVER_1'),
	],
	'consent' => [
		'items' => Converter::toJson()->process($arResult['USER_CONSENTS']),
		'title' => Loc::getMessage('SPP_PAY_BUTTON'),
		'eventName' => 'bx-spp-submit',
		'autoSave' => ($arResult['USER_CONSENT_AUTO_SAVE'] ?? 'N') === 'Y',
		'originatorId' => $arResult['USER_CONSENT_ORIGINATOR_ID'] ?? null,
		'originId' => $arResult['PAYMENT']['ID'],
	],
	'paymentProcess' => [
		'allowPaymentRedirect' => ($arParams['ALLOW_PAYMENT_REDIRECT'] === 'Y'),
		'returnUrl' => CUtil::JSEscape($arResult['RETURN_URL']),
		'orderId' => $arResult['PAYMENT']['ORDER_ID'],
		'paymentId' => $arResult['PAYMENT']['ID'],
		'accessCode' => $arParams['ACCESS_CODE'],
	],
	'payment' => [
		'id' => $arResult['PAYMENT']['ID'],
		'sum' => $arResult['PAYMENT']['SUM'],
		'sumFormatted' => $arResult['PAYMENT']['FORMATTED_SUM'],
		'currency' => $arResult['PAYMENT']['CURRENCY'],
		'paid' => ($arResult['PAYMENT']['PAID'] === 'Y'),
		'checks' => [],
	],
];

if ($arResult['PAYMENT']['PAID'] === 'Y' || $arParams['ALLOW_SELECT_PAY_SYSTEM'] !== 'Y')
{
	$arResult['JS_DATA']['app']['paySystems'] = [$arResult['PAYMENT']['PAY_SYSTEM_INFO']];
	$arResult['JS_DATA']['app']['template'] = 'paymentInfo';
	$arResult['JS_DATA']['app']['title'] = Loc::getMessage('SPP_PAID_TITLE', [
		'#ACCOUNT_NUMBER#' => htmlspecialcharsbx($arResult['PAYMENT']['ACCOUNT_NUMBER']),
		'#DATE_INSERT#' => $arResult['PAYMENT']['DATE_BILL_FORMATTED'],
	]);

	$arResult['JS_DATA']['app']['consent']['autoSave'] = false;
	$arResult['JS_DATA']['app']['consent']['originatorId'] = '';

	if ($arResult['CHECK'])
	{
		$culture = Main\Context::getCurrent()->getCulture();
		foreach ($arResult['CHECK'] as $check)
		{
			$jsCheck = [
				'status' => $check['STATUS'],
				'link' => $check['LINK'],
			];
			if ($check['STATUS'] === 'Y' && $check['LINK'])
			{
				$jsCheck['title'] = Loc::getMessage("SPP_CHECK_TITLE", [
					'#CHECK_ID#' => $check['ID'],
					'#DATE_CREATE#' => \FormatDate($culture->getLongDateFormat(), $check['DATE_CREATE']->getTimestamp()),
				]);
			}
			elseif ($check['STATUS'] === 'P')
			{
				$jsCheck['title'] = Loc::getMessage("SPP_CHECK_PRINT_TITLE", [
					'#CHECK_ID#' => $check['ID'],
					'#DATE_CREATE#' => \FormatDate($culture->getLongDateFormat(), $check['DATE_CREATE']->getTimestamp()),
				]);
			}

			if ($jsCheck['title'])
			{
				$arResult['JS_DATA']['payment']['checks'][] = $jsCheck;
			}
		}
	}
}
