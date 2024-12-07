<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arParams */
/** @var \CMain $APPLICATION */
/** @var SignStartComponent $component */

$urlAfterClose = ($component->getRequest('inlineCrm') === 'Y' || $component->getRequest('noRedirect') === 'Y')
	? '' : \Bitrix\Sign\Document\Entity\SmartB2e::getEntityDetailUrlId()
;
?>

<?php
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.master',
		'POPUP_COMPONENT_PARAMS' => [
			'CATEGORY_ID' => $component->getRequest('categoryId'),
			'PAGE_URL_EDIT' => $arParams['PAGE_URL_EDIT'],
			'VAR_DOC_ID' => 'docId',
			'VAR_STEP_ID' => 'stepId',
			'CRM_ENTITY_TYPE_ID' => \Bitrix\Sign\Document\Entity\SmartB2e::getEntityTypeId(),
			'ENTITY_TYPE_ID' => \Bitrix\Sign\Type\Document\EntityType::SMART_B2E,
			'OPEN_URL_AFTER_CLOSE' => $urlAfterClose,
			'SCENARIO' => \Bitrix\Sign\Type\DocumentScenario::SCENARIO_TYPE_B2E,
		],
		'PLAIN_VIEW' => true,
		'USE_BACKGROUND_CONTENT' => false,
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => false
	],
	$this->getComponent()
);
