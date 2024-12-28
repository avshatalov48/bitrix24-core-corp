<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var $APPLICATION CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponent $component */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\UI\ScopeDictionary;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Asset::getInstance()->addCss('/bitrix/components/bitrix/tasks.interface.toolbar/templates/.default/style.css');

$bodyClass = $APPLICATION->getPageProperty('BodyClass');

$APPLICATION->setPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass . ' ' : '')
	. 'pagetitle-toolbar-field-view no-all-paddings no-background tasks-flow__pagetitle-view'
);

if ($arResult['canCreateFlow'])
{
	$click = (
		($arResult['isFeatureEnabled'] || $arResult['canTurnOnTrial'])
			? 'BX.Tasks.Flow.EditForm.createInstance'
			: new Buttons\JsCode('BX.Tasks.Flow.Grid.showFlowLimit();')
	);

	$addButton = new Buttons\Button([
		'color' => Buttons\Color::SUCCESS,
		'text' => Loc::getMessage('TASKS_FLOW_ADD_BUTTON'),
		'click' => $click,
		'dataset' => [
			'toolbar-collapsed-icon' => Buttons\Icon::ADD
		]
	]);

	$addButton->addAttribute('id', 'tasks-flow-add-button');
	Toolbar::addButton($addButton, ButtonLocation::AFTER_TITLE);
}

Toolbar::addFilter([
	'FILTER_ID' => $arResult['filterId'],
	'GRID_ID' => $arResult['gridId'],
	'FILTER' => $arResult['filter'],
	'FILTER_PRESETS' => $arResult['presets'],
	'ENABLE_LABEL' => true,
	'RESET_TO_DEFAULT_MODE' => true,
	'THEME' => \Bitrix\Main\UI\Filter\Theme::LIGHT,
]);

$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';

if ($isBitrix24Template)
{
	$this->setViewTarget('below_pagetitle');
}

$isTrial = $arResult['isFeatureTrialable'] ? 'Y' : 'N';

?>
	<div class="task-interface-toolbar">
		<?php

		$APPLICATION->IncludeComponent(
			'bitrix:tasks.interface.counters',
			'',
			[
				'USER_ID' => \Bitrix\Tasks\Util\User::getId(),
				'GROUP_ID' => (int) ($arParams['GROUP_ID'] ?? null),
				'ROLE' => 'view_all',
				'COUNTERS' => [
					CounterDictionary::COUNTER_FLOW_TOTAL_EXPIRED,
					CounterDictionary::COUNTER_FLOW_TOTAL_COMMENTS,
				],
				'GRID_ID' => $arResult['filterId'],
				'FILTER_FIELD' => 'PROBLEM',
				'SCOPE' => ScopeDictionary::SCOPE_TASKS_FLOW,
			],
			$component
		);
		?>
		<div id="tasks-flow-switcher" class="task-interface-toolbar--item --visible"></div>

	<div class="tasks-flow__additional-items pagetitle-align-right-container">
		<div
			class="tasks-flow__guide-btn"
			onclick="BX.Tasks.Flow.Grid.showGuide('<?=$isTrial?>');"
			data-id="tasks-flow-guide-btn"
		>
			<span class="tasks-flow__guide-btn_icon-avatar <?=$arResult['guidePhotoClass']?>">
				<div class="tasks-flow__guide-btn_icon">
					<div
						class="ui-icon-set --play-circle"
						style="--ui-icon-set__icon-size: 18px; --ui-icon-set__icon-color: white;"
					></div>
				</div>
			</span>
			<span class="tasks-flow__guide-btn_text">
				<?=Loc::getMessage("TASKS_FLOW_LIST_GUIDE_BTN")?>
			</span>
		</div>
		<?php
			include_once ('bi-analytics.php');
		?>
	</div>
</div>

<?php

if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}

if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
