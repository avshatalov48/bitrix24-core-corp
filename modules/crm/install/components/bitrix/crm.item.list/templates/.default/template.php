<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Filter\HeaderSections;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

Extension::load([
	'ui.dialogs.messagebox',
	'crm_common',
	'crm.settings-button-extender',
	'crm.entity-list.panel',
	'crm.activity.grid-activities-manager',
	'crm.badge',
	'ui.design-tokens',
]);

$assets = Asset::getInstance();
$assets->addJs('/bitrix/js/crm/progress_control.js');
$assets->addJs('/bitrix/js/crm/dialog.js');
$assets->addCss('/bitrix/themes/.default/crm-entity-show.css');
$assets->addJs('/bitrix/js/crm/interface_grid.js');

if ($this->getComponent()->getErrors())
{
	foreach($this->getComponent()->getErrors() as $error)
	{
		/** @var \Bitrix\Main\Error $error */
		?>
		<div class="ui-alert ui-alert-danger">
			<span class="ui-alert-message"><?=$error->getMessage();?></span>
		</div>
		<?php
	}

	return;
}
echo CCrmViewHelper::RenderItemStatusSettings($arParams['entityTypeId'], ($arParams['categoryId'] ?? null));
/** @see \Bitrix\Crm\Component\Base::addTopPanel() */
$this->getComponent()->addTopPanel($this);

/** @see \Bitrix\Crm\Component\Base::addToolbar() */
$this->getComponent()->addToolbar($this);

echo \Bitrix\Crm\Tour\Permissions\AutomatedSolution::getInstance()
	->setEntityTypeId($arParams['entityTypeId'])
	->build()
;
?>

<div class="ui-alert ui-alert-danger" style="display: none;">
	<span class="ui-alert-message" id="crm-type-item-list-error-text-container"></span>
	<span class="ui-alert-close-btn" onclick="this.parentNode.style.display = 'none';"></span>
</div>

<div class="crm-type-item-list-wrapper" id="crm-type-item-list-wrapper">
	<div class="crm-type-item-list-container<?php
		if ($arResult['grid'])
		{
			echo ' crm-type-item-list-grid';
		}
		?>" id="crm-type-item-list-container">
		<?php
		if ($arResult['grid'])
		{
			echo '<div id="crm-type-item-list-progress-bar-container"></div>';

			if (!empty($arResult['interfaceToolbar']))
			{
				$APPLICATION->IncludeComponent(
					'bitrix:crm.interface.toolbar',
					'',
					[
						'TOOLBAR_ID' => $arResult['interfaceToolbar']['id'],
						'BUTTONS' => $arResult['interfaceToolbar']['buttons'],
					]
				);
			}

			$navigationHtml = '';
			if(isset($arResult['pagination']) && is_array($arResult['pagination']))
			{
				ob_start();
				$APPLICATION->IncludeComponent(
					'bitrix:crm.pagenavigation',
					'',
					$arResult['pagination']
				);
				$navigationHtml = ob_get_contents();
				ob_end_clean();
			}

			$arResult['grid']['NAV_STRING'] = $navigationHtml;
			$arResult['grid']['HEADERS_SECTIONS'] = HeaderSections::getInstance()
				->filterGridSupportedSections($arResult['grid']['HEADERS_SECTIONS'] ?? []);

			$APPLICATION->IncludeComponent(
				"bitrix:main.ui.grid",
				"",
				$arResult['grid']
			);
		}
		?>
	</div>
</div>

<?php

$messages = array_merge(Container::getInstance()->getLocalization()->loadMessages(), Loc::loadLanguageFile(__FILE__));

if (!empty($arResult['restrictedFieldsEngine']))
{
	Extension::load(['crm.restriction.filter-fields']);

	echo $arResult['restrictedFieldsEngine'];
}
?>

<script>
	BX.ready(function() {
		BX.message(<?=Json::encode($messages)?>);

		let params = <?=CUtil::PhpToJSObject($arResult['jsParams'], false, false, true);?>;
		params.errorTextContainer = document.getElementById('crm-type-item-list-error-text-container');

		params.progressBarContainerId = 'crm-type-item-list-progress-bar-container';

		(new BX.Crm.ItemListComponent(params)).init();

		<?php if (isset($arResult['RESTRICTED_VALUE_CLICK_CALLBACK'])):?>
		BX.addCustomEvent(window, 'onCrmRestrictedValueClick', function() {
			<?= $arResult['RESTRICTED_VALUE_CLICK_CALLBACK']; ?>
		});
		<?php endif;?>
	});
</script>
