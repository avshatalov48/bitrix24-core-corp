<?php

use Bitrix\SalesCenter\Integration\Bitrix24Manager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/components/bitrix/ui.image.input/templates/.default/script.js');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/ui.image.input/templates/.default/style.css');

\Bitrix\Main\UI\Extension::load(['salescenter.app', 'ui.common', 'currency', 'fileinput']);

if (\Bitrix\Main\Loader::includeModule('location'))
{
	\Bitrix\Main\UI\Extension::load(['location.core', 'location.widget',]);
}

if (\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('IFRAME') == 'Y')
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-all-paddings no-background no-hidden');
}

CUtil::InitJSCore(array('window'));

/**
 * User selector data
 */
if (\Bitrix\Main\Loader::includeModule('socialnetwork'))
{
	Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/entity-editor/js/field-selector.js');

	$socialNetworkData = \Bitrix\Socialnetwork\Integration\Main\UISelector\Entities::getData(
		[
			'enableDepartments' => 'Y',
			'context' => \Bitrix\Crm\Entity\EntityEditor::getUserSelectorContext(),
		]);

	\CJSCore::init(array('socnetlogdest'));
	?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.Crm.EntityEditorUserSelector.users =  <?=CUtil::PhpToJSObject($socialNetworkData['ITEMS']['USERS'])?>;
				BX.Crm.EntityEditorUserSelector.department = <?=CUtil::PhpToJSObject($socialNetworkData['ITEMS']['DEPARTMENT'])?>;
				BX.Crm.EntityEditorUserSelector.departmentRelation = <?=CUtil::PhpToJSObject($socialNetworkData['ITEMS']['DEPARTMENT_RELATION'])?>;
				BX.Crm.EntityEditorUserSelector.last = <?=CUtil::PhpToJSObject(array_change_key_case($socialNetworkData['ITEMS_LAST'], CASE_LOWER))?>;
			}
		);
	</script>
	<?
}

$this->SetViewTarget('pagetitle');
?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<?php
		Bitrix24Manager::getInstance()->renderFeedbackButton();
		?>
	</div>
<?
$this->EndViewTarget();

// todo a bit later
//Bitrix24Manager::getInstance()->addFeedbackButtonToToolbar();

if (!empty($arResult['CURRENCIES']))
{
	?>
	<script>
		BX.Currency.setCurrencies(<?=CUtil::PhpToJSObject($arResult['CURRENCIES'])?>);
	</script>
	<?
}
?>
	<div id="salescenter-app-root"></div>
	<?php
	if($arResult['isPaymentsLimitReached'])
	{
		?>
		<div id="salescenter-payment-limit-container" style="display: none;">
			<?php
			$APPLICATION->includeComponent('bitrix:salescenter.feature', '', ['FEATURE' => 'salescenterPaymentsLimit']);
			?>
		</div>
		<?php
	}
	?>
	<script>
		BX.ready(function()
		{
			var options = <?=CUtil::PhpToJSObject($arResult)?>;
			new BX.Salescenter.App(options);
			BX.Salescenter.Manager.init(options);
		});
	</script>
<?