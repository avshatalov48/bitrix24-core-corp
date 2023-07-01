<?php
/**
 * @var $component \CatalogProductVariationGridComponent
 * @var $this \CBitrixComponentTemplate
 * @var \CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\UI;

UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

global $APPLICATION;
$APPLICATION->SetTitle(
	Main\Localization\Loc::getMessage(
		'CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_TEMPLATE_TITLE',
		[
			'#NUMBER#' => $arResult['ACCOUNT_NUMBER'],
		]
	)
);

Main\UI\Extension::load(['ui.label']);

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
		]
	);

	return;
}

if (!empty($arResult['MARKED_MESSAGES']))
{
	Main\UI\Extension::load(['ui.common', 'ui.alerts']);

	$markedMessages = array_merge(
		[
			Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_DETAIL_COMPONENT_TEMPLATE_MARKER_ERROR'),
		],
		$arResult['MARKED_MESSAGES']
	)
	?>
	<div class="ui-alert ui-alert-danger ui-alert-icon-danger">
		<span class="ui-alert-message"><?= implode('<br>', $markedMessages) ?></span>
	</div>
	<?php
	unset($markedMessages);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.editor',
	'.default',
	[
		'GUID' => 'crm_terminal_payment_detail',
		'CONFIG_ID' => 'crm_terminal_payment_detail',
		'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
		'ENTITY_TYPE_NAME' => \CCrmOwnerType::OrderPaymentName,
		'ENTITY_ID' => $arParams['ID'],
		'INITIAL_MODE' => 'view',
		'READ_ONLY' => true,
		'IS_IDENTIFIABLE_ENTITY' => false,
		'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
		'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
		'ENTITY_DATA' => $arResult['ENTITY_DATA'],
		'ENABLE_COMMON_CONFIGURATION_UPDATE' => false,
		'ENABLE_PERSONAL_CONFIGURATION_UPDATE' => false,
		'ENABLE_SECTION_DRAG_DROP' => false,
		'ENABLE_CONFIG_CONTROL' => false,
		'ENABLE_FIELD_DRAG_DROP' => false,
		'ENABLE_COMMUNICATION_CONTROLS' => false,
		'COMPONENT_AJAX_DATA' => [
			'COMPONENT_NAME' => $component->getName(),
		],
	],
	$component
);
