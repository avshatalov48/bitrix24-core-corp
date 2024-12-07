<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Imopenlines\Limit;

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 */

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.alerts',
	'access',
	'sidepanel',
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/table/style.css');

$APPLICATION->IncludeComponent('bitrix:ui.info.helper', '', []);

?>

<script>
	function openTrialInfoHelper(dialogId)
	{
		BX.UI.InfoHelper.show(dialogId);
	}
</script>
<form method="POST" action="<?=$arResult['ACTION_URI']?>" id="imol_permissions_edit_form">
	<div id="imol-permissions-edit">
	<input type="hidden" id="act" value="save" name="act">
	<?echo bitrix_sessid_post()?>
	<table class="table-blue-wrapper">
		<tr>
			<td>
				<table class="table-blue bx-vi-js-role-access-table">
					<tr>
						<td class="table-blue-td-title">&nbsp;</td>
						<td class="table-blue-td-title">&nbsp;</td>
						<td class="table-blue-td-title"><?=Loc::getMessage('IMOL_PERM_ROLE')?></td>
						<td class="table-blue-td-title"></td>
					</tr>
					<?foreach ($arResult['ROLE_ACCESS_CODES'] as $roleAccessCode):?>
						<tr data-access-code="<?=htmlspecialcharsbx($roleAccessCode['ACCESS_CODE'])?>" data-role-id="<?=htmlspecialcharsbx($roleAccessCode['ROLE_ID'])?>">
							<td class="table-blue-td-name"><?=htmlspecialcharsbx($roleAccessCode['ACCESS_PROVIDER'])?></td>
							<td class="table-blue-td-param"><?=htmlspecialcharsbx($roleAccessCode['ACCESS_NAME'])?></td>
							<td class="table-blue-td-select">
									<select class="bx-vi-js-select-role table-blue-select" name="PERMS[<?=htmlspecialcharsbx($roleAccessCode['ACCESS_CODE'])?>]" data-access-code="<?=htmlspecialcharsbx($roleAccessCode['ACCESS_CODE'])?>">
										<?foreach ($arResult['ROLES'] as $role):?>
											<option title="<?=htmlspecialcharsbx($role['NAME'])?>" value="<?=htmlspecialcharsbx($role['ID'])?>" <?=($role['ID'] == $roleAccessCode['ROLE_ID'] ? 'selected' : '')?>>
												<?=htmlspecialcharsbx($role['NAME'])?>
											</option>
										<?endforeach;?>
									</select>
							</td>
							<td class="table-blue-td-action">
								<span class="bx-vi-js-delete-access table-blue-delete" data-access-code="<?=htmlspecialcharsbx($roleAccessCode['ACCESS_CODE'])?>"></span>
							</td>
						</tr>
					<?endforeach;?>
					<tr class="bx-vi-js-access-table-last-row">
						<td colspan="4" class="table-blue-td-link">
								<a class="bx-vi-js-add-access table-blue-link" href="javascript:void(0);"><?=Loc::getMessage('IMOL_PERM_ADD_ACCESS_CODE')?></a>
						</td>
					</tr>
				</table>
			</td>
			<td>
				<table class="table-blue">
					<tr>
						<td colspan="2" class="table-blue-td-title"><?=Loc::getMessage('IMOL_PERM_ROLE_LIST')?>:</td>
					</tr>
					<?foreach ($arResult['ROLES'] as $role):?>
						<tr data-role-id="<?=htmlspecialcharsbx($role['ID'])?>">
							<td class="table-blue-td-name">
								<?=htmlspecialcharsbx($role['NAME'])?>
							</td>
							<td class="table-blue-td-action">
								<? if($arResult['IFRAME']): ?>
									<a class="table-blue-edit" href="javascript:void(0);" title="<?=Loc::getMessage('IMOL_PERM_EDIT')?>" onclick="BX.SidePanel.Instance.open('<?=$role['EDIT_URL']?>', {allowChangeHistory: false})"></a>
								<? else: ?>
									<a class="table-blue-edit" href="<?=$role['EDIT_URL']?>" title="<?=Loc::getMessage('IMOL_PERM_EDIT')?>"></a>
								<? endif; ?>
								<?if($arResult['CAN_EDIT']):?>
									<span class="table-blue-delete bx-vi-js-delete-role" title="<?=Loc::getMessage('IMOL_PERM_DELETE')?>" data-role-id="<?=htmlspecialcharsbx($role['ID'])?>"></span>
								<?endif?>
							</td>
						</tr>
					<?endforeach;?>
					<tr>
						<td colspan="2" class="table-blue-td-link">
							<? if($arResult['IFRAME']): ?>
								<a href="javascript:void(0);" onclick="BX.SidePanel.Instance.open('<?=$arResult['ADD_URL']?>', {allowChangeHistory: false})" class="table-blue-link"><?=Loc::getMessage('IMOL_PERM_ADD')?></a>
							<? else: ?>
								<a href="<?=$arResult['ADD_URL']?>" class="table-blue-link"><?=Loc::getMessage('IMOL_PERM_ADD')?></a>
							<? endif; ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</div>
	<?if($arResult['CAN_EDIT']):?>
		<?$APPLICATION->IncludeComponent(
			'bitrix:ui.button.panel',
			'',
			[
				'BUTTONS' => [
					[
						'TYPE' => 'save',
						'CAPTION' => Loc::getMessage('IMOL_PERM_SAVE'),

					],
					[
						'TYPE' => 'cancel',
						'LINK' =>  $arResult['INDEX_URL']
					]
				],
				'ALIGN' => 'center'
			],
			false
		);
		?>
	<?else:?>
		<?$APPLICATION->IncludeComponent(
			'bitrix:ui.button.panel',
			'',
			[
				'BUTTONS' => [
					[
						'TYPE' => 'custom',
						'LAYOUT' => '<span class="webform-small-button webform-small-button-accept" onclick="openTrialInfoHelper(\'' . Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_ACCESS_PERMISSIONS . '\');">
		' . Loc::getMessage('IMOL_PERM_SAVE') . '
		<div class="tariff-lock-holder-title"><div class="tariff-lock"></div></div>
		</span>'
					],
					[
						'TYPE' => 'cancel',
						'LINK' =>  $arResult['INDEX_URL']
					],
					[
						'TYPE' => 'custom',
						'LAYOUT' => '<div class="ui-alert ui-alert-warning" style="margin: 15px 0 0 0;">
			<span class="ui-alert-message"> ' . Loc::getMessage('IMOL_PERM_RESTRICTION_MSGVER_1') . '</span>
		</div>'
					],
				],
				'ALIGN' => 'center'
			],
			false
		);
		?>
	<?endif?>
</form>
<script>
	(function()
	{
		var permissions = new BX.IMOLPermissionEdit(BX("imol-permissions-edit"));
	})();
</script>

<script type="text/template" id="bx-vi-new-access-row">
	<td class="table-blue-td-name">#PROVIDER#</td>
	<td class="table-blue-td-param">#NAME#</td>
	<td class="table-blue-td-select">
		<select class="bx-vi-js-select-role table-blue-select" name="PERMS[#ACCESS_CODE#]" data-access-code="#ACCESS_CODE#">
			<?foreach ($arResult['ROLES'] as $role):?>
				<option title="<?=htmlspecialcharsbx($role['NAME'])?>" value="<?=htmlspecialcharsbx($role['ID'])?>">
					<?=htmlspecialcharsbx($role['NAME'])?>
				</option>
			<?endforeach;?>
		</select>
	</td>
	<td class="table-blue-td-action">
		<span class="bx-vi-js-delete-access table-blue-delete" data-access-code="#ACCESS_CODE#"></span>
	</td>
</script>

<script>
	BX.message({
		IMOL_PERM_ERROR: '<?=GetMessageJS('IMOL_PERM_ERROR')?>',
		IMOL_PERM_ROLE_DELETE_ERROR: '<?=GetMessageJS('IMOL_PERM_ROLE_DELETE_ERROR')?>',
		IMOL_PERM_ROLE_DELETE: '<?=GetMessageJS('IMOL_PERM_ROLE_DELETE')?>',
		IMOL_PERM_ROLE_DELETE_CONFIRM: '<?=GetMessageJS('IMOL_PERM_ROLE_DELETE_CONFIRM')?>',
		IMOL_PERM_ROLE_OK: '<?=GetMessageJS('IMOL_PERM_ROLE_OK')?>',
		IMOL_PERM_ROLE_CANCEL: '<?=GetMessageJS('IMOL_PERM_ROLE_CANCEL')?>'
	});
</script>