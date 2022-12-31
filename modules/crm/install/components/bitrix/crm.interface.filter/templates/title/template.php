<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\EntityList\GridId;
use Bitrix\Crm\Integration\Intranet\BindingMenu\SectionCode;
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
	?><script type="text/javascript">
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
		?><script type="text/javascript"><?
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

//region Filter Navigation Bar
$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';
$navigationBarId = htmlspecialcharsbx("{$filterIDLc}_nav_bar");
$navigationBar = new NavigationBar($arParams);
$viewList = $navigationBar->getSwitchViewList();

Extension::load(['crm.toolbar-component', 'ui.fonts.opensans']);


$belowPageTitleFilled = false;

// switch view panel region
if($isBitrix24Template)
{
	$this->SetViewTarget('below_pagetitle', 100);
}
?>

<?php if (!empty($viewList['items'])):
	$belowPageTitleFilled = true;
?>
	<div id="<?=$navigationBarId?>" class="crm-view-switcher"></div>
	<script type="text/javascript">
		BX.ready(function() {
			// init navigation bar panel
			(new BX.Crm.NavigationBar({
				id: "<?= $navigationBarId ?>",
				items: <?= CUtil::PhpToJSObject($viewList['items']) ?>,
				binding: <?= CUtil::PhpToJSObject($viewList['binding']) ?>,
			})).init();
		});
	</script>
<?php endif; ?>

<?php
//  binding menu/automation region
if($isBitrix24Template)
{
	$this->EndViewTarget();
	$this->SetViewTarget('below_pagetitle', 10000);
}

?>
<div class="crm-view-switcher-buttons pagetitle-align-right-container">
<?php
	if (
		Loader::includeModule('intranet')
		&& $navigationBar->isEnabled()
		&& preg_match(NavigationBar::BINDING_MENU_MASK, $arParams['GRID_ID'], $bindingMenuMatches)
		&& mb_stripos($arParams['GRID_ID'], GridId::DEFAULT_GRID_MY_COMPANY_SUFFIX) === false
	)
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

	if ($arResult['SHOW_AUTOMATION_VIEW'])
	{
		echo $navigationBar->getAutomationView();
	}
?>
</div>

<?php

if($isBitrix24Template)
{
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
			'THEME' => Bitrix\Main\UI\Filter\Theme::LIGHT,
		],
		$component
	);
	?></div><?
	$this->EndViewTarget();
}
