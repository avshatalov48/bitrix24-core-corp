<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load(
	[
		'ui.dialogs.messagebox',
		'crm_common',
		'crm.restriction.filter-fields',
	]
);

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-hidden no-background");
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

echo $arResult['ACTIVITY_FIELD_RESTRICTIONS'] ?? '';
?>

<script>
	BX.ready(function() {
		BX.message(<?=\Bitrix\Main\Web\Json::encode($messages)?>);
		var params = <?=CUtil::PhpToJSObject($arResult['jsParams'], false, false, true);?>;
		params.errorTextContainer = document.getElementById('crm-type-item-list-error-text-container');
		(new BX.Crm.ItemListComponent(params)).init();

		<?php if (isset($arResult['RESTRICTED_VALUE_CLICK_CALLBACK'])):?>
		BX.addCustomEvent(window, 'onCrmRestrictedValueClick', function() {
			<?=$arResult['RESTRICTED_VALUE_CLICK_CALLBACK'];?>
		});
		<?php endif;?>
	});
</script>
