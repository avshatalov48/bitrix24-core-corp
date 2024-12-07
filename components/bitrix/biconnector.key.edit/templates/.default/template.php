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
	'ui.notification',
	'ui.fonts.opensans',
	'ui.sidepanel-content',
	'ui.icon-set.main',
	'ui.icon-set.actions',
	'ui.alerts',
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
<form class="biconnector-key-edit__wrapper" method="post">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="ID" value="<?=$arResult['FORM_DATA']['ID']?>">
	<input type="hidden" name="USERS" id="USERS" value="<?=htmlspecialcharsbx(implode(',', $arResult['FORM_DATA']['USERS']))?>">
	<div class="biconnector-key-edit__wrapper_form-inner">
		<div class="biconnector-key-edit__wrapper_form-row" style="display: none;margin-bottom:16px">
			<label class="ui-ctl ui-ctl-checkbox">
				<input type="checkbox" class="ui-ctl-element" name="ACTIVE" value="Y" <?=$arResult['FORM_DATA']['ACTIVE'] == 'Y' ? 'checked="checked"' : ''?>>
				<div class="ui-ctl-label-text" style="margin-right: 12px;"><?=Loc::getMessage('CT_BBKE_ACTIVE')?></div>
			</label>
		</div>

		<?php if (count($arResult['CONNECTIONS']) > 1):?>
		<div class="biconnector-key-edit__wrapper_form-row">
			<div class="ui-slider-heading-4"><?=Loc::getMessage('CT_BBKE_CONNECTION')?></div>
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

		<div class="biconnector-key-edit__wrapper_form-row">
			<div class="ui-slider-heading-4"><?=Loc::getMessage('CT_BBKE_ACCESS_KEY')?></div>
			<div class="biconnector-key-edit__input_box">
				<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
					<input type="password" class="ui-ctl-element biconnector-key-edit-access-key" readonly value="<?=htmlspecialcharsbx($arResult['FORM_DATA']['ACCESS_KEY'] . LANGUAGE_ID)?>" id="ACCESS_KEY">
					<button class="biconnector-key-edit_btn" onclick="return showText(this, '<?= CUtil::JSEscape(Loc::getMessage('CT_BBKE_KEY_SHOW')) ?>', '<?= CUtil::JSEscape(Loc::getMessage('CT_BBKE_KEY_HIDE')) ?>')">
						<span class="ui-icon-set --crossed-eye"></span>
					</button>
				</div>
				<button class="ui-btn ui-btn-success biconnector-key-edit_btn" onclick="return copyText()">
					<span class="ui-icon-set --copy-plates"></span>
				</button>
			</div>
			<div class="biconnector-key-edit__info">
				<div class="biconnector-key-edit__info_logo"></div>
				<div class="biconnector-key-edit__info_content">
					<?= Loc::getMessage('CT_BBKE_ONBOARDING') ?>
					<a onclick="top.BX.Helper.show('redirect=detail&code=18017360')" class="biconnector-key-edit_link">
						<?= Loc::getMessage('CT_BBKE_ONBOARDING_MORE') ?>
					</a>
				</div>
			</div>
		</div>
		<?php if (!$arResult['FORM_DATA']['APP_ID']):?>
		<div class="biconnector-key-edit__wrapper_form-row">
			<div class="ui-slider-heading-4"><?=Loc::getMessage('CT_BBKE_USERS')?></div>
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

			$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
				'BUTTONS' => $buttons,
			]);
			?>
		</div>
	</div>
</form>
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

function showText()
{
	const icon = document.querySelector('.ui-icon-set');
	if (BX('ACCESS_KEY').type == 'password')
	{
		BX('ACCESS_KEY').type = 'text';
		icon.classList.remove('--crossed-eye');
		icon.classList.add('--opened-eye');
	}
	else
	{
		BX('ACCESS_KEY').type = 'password';
		icon.classList.remove('--opened-eye');
		icon.classList.add('--crossed-eye');
	}

	return false;
}

<?php if (!$arResult['FORM_DATA']['APP_ID']): ?>
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

<?php endif;?>
</script>
