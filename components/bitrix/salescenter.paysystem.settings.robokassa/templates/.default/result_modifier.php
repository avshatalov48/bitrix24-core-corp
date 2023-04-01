<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

/**
 * @var array $arResult
 */

$arResult['HELPDESK'] = [
	'CODE' => 17168072,
];

if (isset($arResult['HELPDESK'], $arResult['ENTITY_CONFIG']))
{
	$helpdeskLinkTitle = Loc::getMessage('SALESCENTER_SPSR_TEMPLATE_SETTINGS_HINT');
	$helpdeskLink = <<<HELPDESK
		<a 
			href="javascript:void(0);"
			onclick="if (top.BX.Helper){top.BX.Helper.show('redirect=detail&code={$arResult['HELPDESK']['CODE']}')}"
			class="salescenter-paysystem-settings-helper-link"
		>
			{$helpdeskLinkTitle}
		</a>
	HELPDESK;

	$arResult['ENTITY_CONFIG'][0]['hint'] = $arResult['ENTITY_CONFIG'][0]['hint'] . '. ' . $helpdeskLink;
}
