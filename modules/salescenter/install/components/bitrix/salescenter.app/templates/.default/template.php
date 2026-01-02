<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @global \CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Loader::includeModule('ui');

$asset = Asset::getInstance();
$asset->addJs('/bitrix/components/bitrix/ui.image.input/templates/.default/script.js');
$asset->addCss('/bitrix/components/bitrix/ui.image.input/templates/.default/style.css');

Extension::load([
	'window',
	'salescenter.app',
	'ui.common',
	'currency',
	'fileinput',
	'ui.entity-editor',
	'ui.hint',
	'bitrix24.phoneverify',
]);

if (Loader::includeModule('location'))
{
	Extension::load([
		'location.core',
		'location.widget',
	]);
}
if (Loader::includeModule('crm'))
{
	$asset->addJs('/bitrix/js/crm/common.js');
}

if (Application::getInstance()->getContext()->getRequest()->get('IFRAME') === 'Y')
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-all-paddings no-background no-hidden');

	Toolbar::deleteFavoriteStar();
}

/**
 * User selector data
 */
if (Loader::includeModule('socialnetwork'))
{
	$asset->addJs('/bitrix/js/crm/entity-editor/js/field-selector.js');

	$socialNetworkData = \Bitrix\Socialnetwork\Integration\Main\UISelector\Entities::getData(
		[
			'enableDepartments' => 'Y',
			'context' => \Bitrix\Crm\Entity\EntityEditor::getUserSelectorContext(),
		]);

	Extension::load(['socnetlogdest']);
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

$bitrix24Manager = Bitrix24Manager::getInstance();

if (isset($arResult['mode']) && $arResult['mode'] === 'terminal_payment')
{
	$bitrix24Manager->addFeedbackTerminalOfferButtonToToolbar();
}
else
{
	$bitrix24Manager->addIntegrationRequestButtonToToolbar([
		Bitrix24Manager::ANALYTICS_SENDER_PAGE => Bitrix24Manager::ANALYTICS_LABEL_SALESHUB_RECEIVING_PAYMENT
	]);
	$bitrix24Manager->addFeedbackButtonToToolbar();
}

$this->SetViewTarget('below_pagetitle');
?>
	<div id="salescenter-app-order-selector" class="salescenter-app-order-selector is-hidden">
		<span class="salescenter-app-order-selector-text" data-hint="" data-hint-no-icon></span>
	</div>
<?php
$this->EndViewTarget();

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
