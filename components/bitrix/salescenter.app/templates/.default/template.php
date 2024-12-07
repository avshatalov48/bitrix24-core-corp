<?php

use Bitrix\SalesCenter\Integration\Bitrix24Manager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/components/bitrix/ui.image.input/templates/.default/script.js');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/ui.image.input/templates/.default/style.css');

\Bitrix\Main\UI\Extension::load([
	'salescenter.app',
	'ui.common',
	'currency',
	'fileinput',
	'ui.entity-editor',
	'ui.hint',
	'bitrix24.phoneverify',
]);

if (\Bitrix\Main\Loader::includeModule('location'))
{
	\Bitrix\Main\UI\Extension::load(['location.core', 'location.widget',]);
}
if (\Bitrix\Main\Loader::includeModule('crm'))
{
	\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
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
	<script>
		BX.ready(
			function()
			{
				BX.UI.EntityEditorUserSelector.users =  <?=CUtil::PhpToJSObject($socialNetworkData['ITEMS']['USERS'] ?? []);?>;
				BX.UI.EntityEditorUserSelector.department = <?=CUtil::PhpToJSObject($socialNetworkData['ITEMS']['DEPARTMENT'] ?? []);?>;
				BX.UI.EntityEditorUserSelector.departmentRelation = <?=CUtil::PhpToJSObject($socialNetworkData['ITEMS']['DEPARTMENT_RELATION'] ?? []);?>;
				BX.UI.EntityEditorUserSelector.last = <?=CUtil::PhpToJSObject(array_change_key_case($socialNetworkData['ITEMS_LAST'], CASE_LOWER))?>;
			}
		);
	</script>
	<?php
}

$this->SetViewTarget('pagetitle');
?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<?php
		if ($arResult['mode'] && $arResult['mode'] === 'terminal_payment')
		{
			Bitrix24Manager::getInstance()->renderFeedbackTerminalOfferButton();
		}
		else
		{
			Bitrix24Manager::getInstance()->renderIntegrationRequestButton(
				[
					Bitrix24Manager::ANALYTICS_SENDER_PAGE => Bitrix24Manager::ANALYTICS_LABEL_SALESHUB_RECEIVING_PAYMENT
				]
			);
			Bitrix24Manager::getInstance()->renderFeedbackButton();
		}
		?>
	</div>
<?php
$this->EndViewTarget();

$this->SetViewTarget('below_pagetitle');
?>
	<div id="salescenter-app-order-selector" class="salescenter-app-order-selector is-hidden">
		<span class="salescenter-app-order-selector-text" data-hint="" data-hint-no-icon></span>
	</div>
<?php
$this->EndViewTarget();

// todo a bit later
//Bitrix24Manager::getInstance()->addFeedbackButtonToToolbar();

if (!empty($arResult['CURRENCIES']))
{
	?>
	<script>
		BX.Currency.setCurrencies(<?=CUtil::PhpToJSObject($arResult['CURRENCIES'])?>);
	</script>
	<?php
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
			var options = <?=CUtil::PhpToJSObject($arResult, false, false, true)?>;
			new BX.Salescenter.App(options);
			BX.Salescenter.Manager.init(options);
		});
	</script>
	<?php
	if($arResult['facebookSettingsPath'])
	{
		?>
		<script>
			BX.ready(function()
			{
				BX.ready(function () {
					BX.SidePanel.Instance.open('<?=CUtil::JSEscape($arResult['facebookSettingsPath'])?>');
				});
			});
		</script>
		<?php
	}
	?>
