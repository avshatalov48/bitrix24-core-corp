<?php
/**
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.forms',
	'ui.entity-selector',
]);

foreach ($arResult['ERRORS'] as $error)
{
	ShowError($error);
}

$users = [];
foreach ($arResult['FORM_DATA']['USERS'] as $userId)
{
	$users[] = ['user', $userId];
}
?>
<form class="biconnector-dashboard-edit-wrapper" method="post">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="ID" value="<?=$arResult['FORM_DATA']['ID']?>">
	<input type="hidden" name="USERS" id="USERS" value="<?=htmlspecialcharsbx(implode(',', $arResult['FORM_DATA']['USERS']))?>">
	<div class="biconnector-dashboard-edit-wrapper-form-inner">
		<div class="biconnector-dashboard-edit-wrapper-form-row">
			<div class="ui-ctl-label-text"><?=Loc::getMessage('CT_BBDE_NAME')?></div>
			<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
			<input type="text" class="ui-ctl-element" name="NAME" value="<?=htmlspecialcharsbx($arResult['FORM_DATA']['NAME'])?>">
		</div>
		</div>

		<div class="biconnector-dashboard-edit-wrapper-form-row">
			<div class="ui-ctl-label-text"><?=Loc::getMessage('CT_BBDE_URL')?></div>
			<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
			<input type="text" class="ui-ctl-element" name="URL" value="<?=htmlspecialcharsbx($arResult['FORM_DATA']['URL'])?>">
		</div>
		</div>

		<div class="biconnector-dashboard-edit-wrapper-form-row">
			<div class="ui-ctl-label-text"><?=Loc::getMessage('CT_BBKE_USERS')?></div>
			<div id="user-selector"></div>
		</div>

		<div>
			<?php
			$buttons = [];
			if (\Bitrix\BIConnector\LimitManager::getInstance()->checkLimit())
			{
				$buttons[] = [ 'TYPE' => 'save' ];
			}
			$buttons[] = [ 'TYPE' => 'cancel' ];

			$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
				'BUTTONS' => $buttons,
			]);
			?>
		</div>
	</div>
</form>
<script>
const tagSelector = new BX.UI.EntitySelector.TagSelector({
	id: 'user-selector',
	textBoxAutoHide: true,
	textBoxWidth: 350,
	maxHeight: 99,
	dialogOptions: {
		id: 'user-selector',
		preselectedItems: <?=\Bitrix\Main\Web\Json::encode($users)?>,
		events: {
			'Item:onSelect': function() {
				var selectedItems = tagSelector.getDialog().getSelectedItems();
				if (BX.type.isArray(selectedItems))
				{
					var result = [];
					selectedItems.forEach(function(item) {
						result.push(item.id);
					});
					BX('USERS').value = result.join(',');
				}
			},
			'Item:onDeselect': function() {
				var selectedItems = tagSelector.getDialog().getSelectedItems();
				if (BX.type.isArray(selectedItems))
				{
					var result = [];
					selectedItems.forEach(function(item) {
						result.push(item.id);
					});
					BX('USERS').value = result.join(',');
				}
			}
		},
		entities: [
			{
				id: 'user'
			}
		]
	}
});

BX.ready(function ()
{
	tagSelector.renderTo(document.getElementById('user-selector'));
});
</script>
