<?php

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var CAllMain $APPLICATION */
/** @var array $arResult */

if($this->getComponent()->getErrors())
{
	foreach($this->getComponent()->getErrors() as $error)
	{
		/** @var \Bitrix\Main\Error $error */
		?>
		<div><?=htmlspecialcharsbx($error->getMessage());?></div>
		<?php
	}

	return;
}
if (\Bitrix\Crm\Restriction\RestrictionManager::getQuotesRestriction()->hasPermission())
{
	/** @see \Bitrix\Crm\Component\Base::addTopPanel() */
	$this->getComponent()->addTopPanel($this);

	/** @see \Bitrix\Crm\Component\Base::addToolbar() */
	$this->getComponent()->addToolbar($this);
}
/** @see \Bitrix\Crm\Component\Base::addJsRouter() */
$this->getComponent()->addJsRouter($this);
?>
<div class="ui-alert ui-alert-danger" style="display: none;">
	<span class="ui-alert-message" id="crm-type-item-details-error-text-container"></span>
	<span class="ui-alert-close-btn" onclick="this.parentNode.style.display = 'none';"></span>
</div>
<?php

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/partial_entity_editor.js');

\Bitrix\Main\UI\Extension::load([
	'crm.item-details-component',
	'crm.conversion',
	'ui.layout-form',
	'ui.alerts',
	'bp_starter',
]);

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	$arResult['activityEditorParams'],
	$this->getComponent(),
	['HIDE_ICONS' => 'Y']
);

$messages = array_merge(Container::getInstance()->getLocalization()->loadMessages(), Loc::loadLanguageFile(__FILE__));
if(isset($arResult['jsParams']['messages']['crmTimelineHistoryStub']))
{
	$messages['CRM_TIMELINE_HISTORY_STUB'] = $arResult['jsParams']['messages']['crmTimelineHistoryStub'];
}
?>

<script>
	BX.ready(function() {
		BX.message(<?=\Bitrix\Main\Web\Json::encode($messages)?>);
		var params = <?=CUtil::PhpToJSObject($arResult['jsParams'], false, false, true);?>;
		params.errorTextContainer = document.getElementById('crm-type-item-details-error-text-container');

		if (params.conversion && params.conversion.lockScript)
		{
			params.conversion.lockScript = function()
			{
				<?php
					// Same as params.conversion.lockScript, but not escaped
					echo $arResult['jsParams']['conversion']['lockScript'] ?? null;
				?>
			};
		}

		(new BX.Crm.QuoteDetailsComponent(params)).init();
	});
</script>

<?php
$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details',
	'',
	$arResult['entityDetailsParams']
);
