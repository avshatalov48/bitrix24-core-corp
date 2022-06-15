<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @param array $arParams
 * @param array $arResult
 * @param CBitrixComponentTemplate $this
 */

use Bitrix\Main\Localization\Loc;

Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.forms',
	'ui.entity-selector',
	'ui.notification',
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
<form id="<?=$listDomIds['formId']?>" class="biconnector-key-edit-wrapper" method="post" action="<?=$formAction?>">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="ID" value="<?=$arResult['FORM_DATA']['ID']?>">
	<input type="hidden" name="ACCESS_KEY" value="<?=$arResult['FORM_DATA']['ACCESS_KEY']?>">
	<input type="hidden" name="USERS" id="USERS" value="<?=htmlspecialcharsbx(implode(',', $arResult['FORM_DATA']['USERS']))?>">
	<div class="biconnector-key-edit-wrapper-form-inner">
		<div class="biconnector-key-edit-wrapper-form-row" style="margin-bottom:16px">
			<label class="ui-ctl ui-ctl-checkbox">
				<input type="checkbox" class="ui-ctl-element" name="ACTIVE" value="Y" <?=$arResult['FORM_DATA']['ACTIVE'] == 'Y' ? 'checked="checked"' : ''?>>
				<div class="ui-ctl-label-text" style="margin-right: 12px;"><?=Loc::getMessage('CT_BBKE_ACTIVE')?></div>
			</label>
		</div>

		<?php if (count($arResult['CONNECTIONS']) > 1):?>
		<div class="biconnector-key-edit-wrapper-form-row">
			<div class="ui-ctl-label-text"><?=Loc::getMessage('CT_BBKE_CONNECTION')?></div>
			<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				<select name="CONNECTION" class="ui-ctl-element">
					<?php foreach ($arResult['CONNECTIONS'] as $name => $value):?>
						<option value="<?=htmlspecialcharsbx($name)?>" <?=$arResult['FORM_DATA']['CONNECTION'] == $value ? 'selected' : ''?>><?=htmlspecialcharsbx($value)?></option>
					<?php endforeach?>
				</select>
			</div>
		</div>
		<?php endif?>

		<div class="biconnector-key-edit-wrapper-form-row">
			<div class="ui-ctl-label-text"><?=Loc::getMessage('CT_BBKE_ACCESS_KEY')?></div>
			<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
				<input type="password" class="ui-ctl-element biconnector-key-edit-access-key" readonly value="<?=htmlspecialcharsbx($arResult['FORM_DATA']['ACCESS_KEY'] . LANGUAGE_ID)?>" id="ACCESS_KEY">
			</div>
			<button class="biconnector-key-edit-action-link" onclick="return showText(this, '<?=CUtil::JSEscape(Loc::getMessage('CT_BBKE_KEY_SHOW'))?>', '<?=CUtil::JSEscape(Loc::getMessage('CT_BBKE_KEY_HIDE'))?>')"><?=Loc::getMessage('CT_BBKE_KEY_SHOW')?></button>
			<button class="biconnector-key-edit-action-link" onclick="return copyText()"><?=Loc::getMessage('CT_BBKE_KEY_COPY')?></button>
		</div>
		<?php if (!$arResult['FORM_DATA']['APP_ID']):?>
		<div class="biconnector-key-edit-wrapper-form-row">
			<div class="ui-ctl-label-text"><?=Loc::getMessage('CT_BBKE_USERS')?></div>
			<div id="user-selector"></div>
		</div>
		<?php endif;?>
		<div>
			<?php
			$buttons = [];
			if (\Bitrix\BIConnector\LimitManager::getInstance()->checkLimit())
			{
				$buttons[] = [ 'TYPE' => 'save' ];
			}
			$buttons[] = [ 'TYPE' => 'cancel' ];

			$APPLICATION->includeComponent('bitrix:ui.button.panel', '', [
				'BUTTONS' => $buttons,
			]);
			?>
		</div>
	</div>
</form>
<?php if (!$arResult['FORM_DATA']['APP_ID']):?>
<script>

function copyText()
{
	const textarea = document.createElement('textarea');
	textarea.value = BX('ACCESS_KEY').value;
	textarea.setAttribute('readonly', '');
	textarea.style.position = 'absolute';
	textarea.style.left = '-9999px';
	document.body.appendChild(textarea);
	textarea.select();

	try {
		document.execCommand('copy');
		BX.UI.Notification.Center.notify({
			content: "<?=Loc::getMessage('CT_BBKE_KEY_COPIED')?>",
			autoHideDelay: 2000,
		});
	}
	catch(err)
	{
		BX.UI.Notification.Center.notify({
			content: 'Oops, unable to copy',
			autoHideDelay: 2000,
		});
	}

	textarea.remove();

	return false;
}

function showText(btn, showText, hideText)
{
	if (BX('ACCESS_KEY').type == 'password')
	{
		BX('ACCESS_KEY').type = 'text';
		btn.firstChild.data = hideText;
	}
	else
	{
		BX('ACCESS_KEY').type = 'password';
		btn.firstChild.data = showText;
	}

	return false;
}

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
<?php endif;?>
