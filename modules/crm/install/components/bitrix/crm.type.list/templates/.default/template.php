<?php

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI;

/** @var array $arResult */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '') . 'no-all-paddings no-hidden');
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
if (!$arResult['isExternal'])
{
	print \Bitrix\Crm\Tour\ExternalDynamicTypes::getInstance()->build();

	/** @see \Bitrix\Crm\Component\Base::addTopPanel() */
	$this->getComponent()->addTopPanel($this);
}

/** @see \Bitrix\Crm\Component\Base::addToolbar() */
$this->getComponent()->addToolbar($this);

Extension::load([
	'ui.tilegrid',
	'popup',
	'ui.alerts',
	'ajax',
	'ui.dialogs.messagebox',
	'ui.fonts.opensans',
	'crm.integration.analytics',
	'ui.analytics',
]);
Main\Loader::includeModule('ui');

UI\Toolbar\Facade\Toolbar::addFilter($arResult['filter']);

?>

<div class="ui-alert ui-alert-danger" style="display: none;">
	<span class="ui-alert-message" id="crm-type-list-error-container"></span>
	<span class="ui-alert-close-btn" onclick="this.parentNode.style.display = 'none';"></span>
</div>
<div class="crm-type-list-wrapper" id="crm-type-list-wrapper">
	<div class="crm-type-list-container<?php
		if ($arResult['grid'])
		{
			echo ' crm-type-list-grid';
		}
		if ($arResult['isEmptyList'])
		{
			echo ' crm-type-list-grid-empty';
		}
		?>" id="crm-type-list-container">
		<?php
		if($arResult['grid'])
		{
			$APPLICATION->IncludeComponent(
				'bitrix:main.ui.grid',
				'',
				$arResult['grid']
			);
		}
		?>
		<?php $welcome = $arResult['welcome']; ?>
		<div class="crm-type-list-welcome" data-role="crm-type-list-welcome">
			<div class="crm-type-list-welcome-title">
				<?= htmlspecialcharsbx($welcome['title']) ?>
			</div>
			<div class="crm-type-list-welcome-text">
				<?= htmlspecialcharsbx($welcome['text']) ?>
			</div>
			<div
				class="crm-type-list-welcome-help"
				onclick="BX.Crm.Router.openHelper(null, <?= (int)$welcome['helpdeskCode'] ?>);"
			>
				<?= htmlspecialcharsbx($welcome['link']) ?>
			</div>
		</div>
	</div>
</div>
<?php $messages = Container::getInstance()->getLocalization()->loadMessages() ?>

<script>
    BX.ready(function()
    {
		BX.message(<?=Json::encode($messages)?>);

		var params = {
			gridId: '<?= CUtil::JSEscape($arResult['grid']['GRID_ID']); ?>',
			errorTextContainer: document.getElementById('crm-type-list-error-container'),
			welcomeMessageContainer: document.querySelector('[data-role="crm-type-list-welcome"]'),
			isExternal: <?= $arResult['isExternal'] ? 'true' : 'false' ?>,
		};
		(new BX.Crm.TypeListComponent(params)).init();
    });
</script>
</div>
