<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CMain $APPLICATION */
/** @var array $arResult */
/** @var \CBitrixComponentTemplate $this */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load([
	'ui.vue3',
	'ui.vue3.vuex',
	'ui.entity-selector',
	'ui.alerts',
	'ui.dialogs.messagebox',
	'ui.forms',
	'ui.layout-form',
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.info-helper',
	'ui.analytics',
	'crm.integration.analytics',
]);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."crm-automated-solution-details-slider-wrapper");

/** @var CrmAutomatedSolutionDetailsComponent $component */
$component = $this->getComponent();

$component->addJsRouter($this);
$component->addToolbar($this);

if ($component->getErrors()): ?>
	<div class="ui-alert ui-alert-danger">
		<?php foreach($component->getErrors() as $error):?>
			<span class="ui-alert-message"><?= htmlspecialcharsbx($error->getMessage()) ?></span>
		<?php endforeach;?>
	</div>
	<?php

	return;
endif;

$emitJsEvent = function (string $eventName, array $params = []): string {
	$escapedEventName = \CUtil::JSEscape($eventName);

	// json string with double quotes in it will break markup of ui.buttons.panel
	$paramsObject = \CUtil::PhpToJSObject($params, false, false, true);

	return "BX.Event.EventEmitter.emit('BX.Crm.AutomatedSolution.Details:{$escapedEventName}', $paramsObject);";
};

$activeTabId = $arResult['activeTabId'];

$menuItems = [
	[
		'NAME' => Loc::getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TAB_COMMON'),
		'ACTIVE' => $activeTabId === 'common',
		'ATTRIBUTES' => [
			'onclick' => $emitJsEvent('showTab', ['tabId' => 'common']),
		],
	],
	[
		'NAME' => Loc::getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TAB_TYPES'),
		'ACTIVE' => $activeTabId === 'types',
		'ATTRIBUTES' => [
			'onclick' => $emitJsEvent('showTab', ['tabId' => 'types']),
		],
	]
];

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrappermenu',
	'',
	[
		'TITLE' => Loc::getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TITLE'),
		'ITEMS' => $menuItems,
	],
	$this->getComponent()
);

?>

<div id="crm-automated-solution-details-app-container"></div>

<div><?php
	$buttons = [
		[
			'TYPE' => 'save',
			'onclick' => $emitJsEvent('save'),
		],
		[
			'TYPE' => 'cancel',
			'onclick' => $emitJsEvent('close'),
		],
	];
	if (!$arResult['isNew'])
	{
		$buttons[] = [
			'TYPE' => 'remove',
			'onclick' => $emitJsEvent('delete'),
		];
	}

	$APPLICATION->IncludeComponent(
		'bitrix:ui.button.panel',
		'',
		[
			'BUTTONS' => $buttons,
			'ALIGN' => 'center',
		],
		$this->getComponent(),
	);
?></div>

<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(
			array_merge(
				\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages(),
				Loc::loadLanguageFile(__FILE__),
			)
		) ?>);

		const app = new BX.Crm.AutomatedSolution.Details.App(<?= Json::encode([
			'containerId' => 'crm-automated-solution-details-app-container',
			'activeTabId' => $activeTabId,
			'state' => $arResult['state'],
		]) ?>);

		app.start();
	});
</script>
