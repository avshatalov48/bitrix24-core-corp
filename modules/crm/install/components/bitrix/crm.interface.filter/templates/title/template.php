<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\EntityList\GridId;
use Bitrix\Crm\Integration\Intranet\BindingMenu\SectionCode;
use Bitrix\Crm\RepeatSale\Widget\WidgetManager;
use Bitrix\Crm\UI\Tools\NavigationBar;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

$gridID = $arParams['~GRID_ID'];
$gridIDLc = mb_strtolower($gridID);
$filterID = $arParams['~FILTER_ID'] ?? $gridID;
$filterIDLc = mb_strtolower($filterID);

//region Prepare custom fields
if (isset($arParams['~FILTER']) && is_array($arParams['~FILTER']))
{
	$entitySelectors = [];
	foreach($arParams['~FILTER'] as $filterItem)
	{
		if (
			!(isset($filterItem['type'])
			&& $filterItem['type'] === 'custom_entity'
			&& isset($filterItem['selector'])
			&& is_array($filterItem['selector']))
		)
		{
			continue;
		}

		$selector = $filterItem['selector'];

		$selectorType = $selector['TYPE'] ?? '';
		$selectorData = isset($selector['DATA']) && is_array($selector['DATA']) ? $selector['DATA'] : null;
		if (!isset($selectorData['IS_MULTIPLE']))
		{
			$selectorData['IS_MULTIPLE'] = isset($filterItem['params']['multiple']) && $filterItem['params']['multiple'] === 'Y';
		}

		if (empty($selectorData))
		{
			continue;
		}

		if ($selectorType === 'crm_entity')
		{
			$entitySelectors[] = $selectorData;
		}
	}

	// region CRM Entity Selectors
	// It is always required for dynamic filter fields
	Asset::getInstance()->addJs('/bitrix/js/crm/crm.js');
	?><script>
	BX.ready(function() {
		BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
		if(typeof(BX.CrmEntitySelector) !== "undefined")
		{
			BX.CrmEntitySelector.messages =
			{
				"selectButton": "<?=GetMessageJS('CRM_GRID_ENTITY_SEL_BTN')?>",
				"noresult": "<?=GetMessageJS('CRM_GRID_SEL_SEARCH_NO_RESULT')?>",
				"search": "<?=GetMessageJS('CRM_GRID_ENTITY_SEL_SEARCH')?>",
				"last": "<?=GetMessageJS('CRM_GRID_ENTITY_SEL_LAST')?>"
			};
		}
	});
	</script><?
	if (!empty($entitySelectors))
	{
		?><script><?
			foreach($entitySelectors as $entitySelector)
			{
				$selectorID = $entitySelector['ID'];
				$fieldID = $entitySelector['FIELD_ID'];
				$entityTypeNames = $entitySelector['ENTITY_TYPE_NAMES'];
				$isMultiple = $entitySelector['IS_MULTIPLE'];
				$title = $entitySelector['TITLE'] ?? '';
				?>BX.ready(function() {
					BX.CrmUIFilterEntitySelector.create(
						"<?=CUtil::JSEscape($selectorID)?>",
						{
							fieldId: "<?=CUtil::JSEscape($fieldID)?>",
							entityTypeNames: <?=CUtil::PhpToJSObject($entityTypeNames)?>,
							isMultiple: <?=$isMultiple ? 'true' : 'false'?>,
							title: "<?=CUtil::JSEscape($title)?>"
						}
					);
				});<?
			}
		?></script><?
	}
	//endregion
}
//endregion

$APPLICATION->includeComponent('bitrix:crm.filterdependent.wrapper', '');

//region Filter Navigation Bar
$navigationBarId = htmlspecialcharsbx("{$filterIDLc}_nav_bar");
$navigationBar = new NavigationBar($arParams);
$viewList = $navigationBar->getSwitchViewList();

Extension::load(['crm.toolbar-component', 'ui.fonts.opensans', 'ui.actions-bar']);

$shouldRenderNavigation = !empty($viewList['items']);
$shouldRenderCounters = !empty($arParams['COUNTER_PANEL']) && is_array($arParams['COUNTER_PANEL']);
$bindingMenuMatches = [];
$shouldRenderIntranetBindingsMenu = Loader::includeModule('intranet')
	&& $navigationBar->isEnabled()
	&& preg_match(NavigationBar::BINDING_MENU_MASK, $arParams['GRID_ID'], $bindingMenuMatches)
	&& mb_stripos($arParams['GRID_ID'], GridId::DEFAULT_GRID_MY_COMPANY_SUFFIX) === false
;
$shouldRenderAutomationButton = $arResult['SHOW_AUTOMATION_VIEW'] && !empty($navigationBar->getAutomationView());
$shouldRenderRepeatSaleButton = $arResult['SHOW_REPEAT_SALE_VIEW'] && !empty($navigationBar->getRepeatSaleView());

$shouldRenderActionsBar = $shouldRenderNavigation || $shouldRenderCounters || $shouldRenderIntranetBindingsMenu || $shouldRenderAutomationButton || $shouldRenderRepeatSaleButton;

$belowPageTitleFilled = false;

if ($shouldRenderActionsBar)
{
	// switch view panel region
	$this->SetViewTarget('below_pagetitle', 100);
	?>
	<div class="ui-actions-bar">
		<?php if ($shouldRenderNavigation):
			$belowPageTitleFilled = true;
		?>
			<div id="<?=$navigationBarId?>" class="ui-actions-bar__panel"></div>
			<script>
				// init navigation bar panel
				(new BX.Crm.NavigationBar({
					id: "<?= $navigationBarId ?>",
					items: <?= CUtil::PhpToJSObject($viewList['items']) ?>,
					binding: <?= CUtil::PhpToJSObject($viewList['binding']) ?>,
				})).init();
			</script>
		<?php endif; ?>

		<?php

		if ($shouldRenderCounters)
		{
			$APPLICATION->IncludeComponent(
				'bitrix:crm.entity.counter.panel',
				'',
				$arParams['COUNTER_PANEL'],
			);
		}

		if ($shouldRenderIntranetBindingsMenu || $shouldRenderAutomationButton || $shouldRenderRepeatSaleButton):
			?>
			<div id="crm-view-switcher-buttons-right" class="ui-actions-bar__buttons crm-view-switcher-buttons">
			<?php
				if ($shouldRenderRepeatSaleButton)
				{
					echo $navigationBar->getRepeatSaleView();
					WidgetManager::getInstance()->showBanner();
				}

				if ($shouldRenderAutomationButton)
				{
					echo $navigationBar->getAutomationView();
				}

				if ($shouldRenderIntranetBindingsMenu)
				{
					Extension::load('bizproc.script');

					$belowPageTitleFilled = true;

					$APPLICATION->includeComponent(
						'bitrix:intranet.binding.menu',
						'',
						[
							'SECTION_CODE' => SectionCode::SWITCHER,
							'MENU_CODE' => $bindingMenuMatches[0],
						]
					);
				}
			?>
			</div>
			<script>
				(new BX.UI.ActionsBar.RightButtons({
					buttonsContainer: document.getElementById('crm-view-switcher-buttons-right'),
					collapsable: true,
				})).init();
			</script>
		<?php endif; ?>
	</div>
	<?php
	$this->EndViewTarget();
}
//endregion

if ($belowPageTitleFilled)
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-pagetitle-view');
}

if (empty($arParams['~RENDER_INTO_VIEW']))
{
	Bitrix\UI\Toolbar\Facade\Toolbar::setTitleNoShrink();
	Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
		'GRID_ID' => $gridID,
		'FILTER_ID' => $filterID,
		'FILTER' => $arParams['~FILTER'],
		'FILTER_FIELDS' => $arParams['~FILTER_FIELDS'] ?? [],
		'FILTER_PRESETS' => $arParams['~FILTER_PRESETS'],
		'ENABLE_FIELDS_SEARCH' => (isset($arParams['~ENABLE_FIELDS_SEARCH']) && $arParams['~ENABLE_FIELDS_SEARCH'] === 'Y') ? 'Y' : 'N',
		'HEADERS_SECTIONS' => $arParams['~HEADERS_SECTIONS'] ?? [],
		'DISABLE_SEARCH' => isset($arParams['~DISABLE_SEARCH']) && $arParams['~DISABLE_SEARCH'] === true,
		'LAZY_LOAD' => $arParams['~LAZY_LOAD'] ?? null,
		'VALUE_REQUIRED_MODE' => isset($arParams['~VALUE_REQUIRED_MODE']) && $arParams['~VALUE_REQUIRED_MODE'] === true,
		'ENABLE_LIVE_SEARCH' => isset($arParams['~ENABLE_LIVE_SEARCH']) && $arParams['~ENABLE_LIVE_SEARCH'] === true,
		'LIMITS' => $arParams['~LIMITS'] ?? null,
		'ENABLE_LABEL' => true,
		'ENABLE_ADDITIONAL_FILTERS' => true,
		'CONFIG' => $arParams['~CONFIG'] ?? null,
		'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
		'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (bool)($arParams['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? false),
		'RESTRICTED_FIELDS' => ($arParams['RESTRICTED_FIELDS'] ?? []),
	]);
}
else
{
	// for filters inside tabs
	$viewID = $arParams['~RENDER_INTO_VIEW'];
	$this->SetViewTarget($viewID, 0);
	?><div class="pagetitle-container pagetitle-flexible-space" style="overflow: hidden;"><?
	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.filter',
		'',
		[
			'GRID_ID' => $gridID,
			'FILTER_ID' => $filterID,
			'FILTER' => $arParams['~FILTER'],
			'FILTER_FIELDS' => $arParams['~FILTER_FIELDS'] ?? [],
			'FILTER_PRESETS' => $arParams['~FILTER_PRESETS'],
			'ENABLE_FIELDS_SEARCH' => (isset($arParams['~ENABLE_FIELDS_SEARCH']) && $arParams['~ENABLE_FIELDS_SEARCH'] === 'Y') ? 'Y' : 'N',
			'HEADERS_SECTIONS' => $arParams['~HEADERS_SECTIONS'] ?? [],
			'DISABLE_SEARCH' => isset($arParams['~DISABLE_SEARCH']) && $arParams['~DISABLE_SEARCH'] === true,
			'LAZY_LOAD' => $arParams['~LAZY_LOAD'] ?? null,
			'VALUE_REQUIRED_MODE' => isset($arParams['~VALUE_REQUIRED_MODE']) && $arParams['~VALUE_REQUIRED_MODE'] === true,
			'ENABLE_LIVE_SEARCH' => isset($arParams['~ENABLE_LIVE_SEARCH']) && $arParams['~ENABLE_LIVE_SEARCH'] === true,
			'LIMITS' => $arParams['~LIMITS'] ?? null,
			'ENABLE_LABEL' => true,
			'ENABLE_ADDITIONAL_FILTERS' => true,
			'CONFIG' => $arParams['~CONFIG'] ?? null,
			'THEME' => Bitrix\Main\UI\Filter\Theme::AIR,
			'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (bool)($arParams['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? false),
			'RESTRICTED_FIELDS' => ($arParams['RESTRICTED_FIELDS'] ?? []),
		],
		$component
	);
	?></div><?
	$this->EndViewTarget();
}
