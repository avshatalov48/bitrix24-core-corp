<?
/**
 * @var array $arResult
 * @var CMain $APPLICATION
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/table/style.css');

CUtil::InitJSCore(Array('access'));
?>

<div id="vi-permissions-edit">
<form method="POST" action="<?=$arResult['ACTION_URI']?>">
	<input type="hidden" id="act" value="save" name="act">
	<?echo bitrix_sessid_post()?>
	<table class="table-blue-wrapper">
		<tr>
			<td>
				<table class="table-blue bx-vi-js-role-access-table">
					<tr>
						<td class="table-blue-td-title">&nbsp;</td>
						<td class="table-blue-td-title">&nbsp;</td>
						<td class="table-blue-td-title"><?=GetMessage('IMOL_PERM_ROLE')?></td>
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
								<a class="bx-vi-js-add-access table-blue-link" href="javascript:void(0);"><?=GetMessage('IMOL_PERM_ADD_ACCESS_CODE')?></a>
						</td>
					</tr>
				</table>
			</td>
			<td>
				<table class="table-blue">
					<tr>
						<td colspan="2" class="table-blue-td-title"><?=GetMessage('IMOL_PERM_ROLE_LIST')?>:</td>
					</tr>
					<?foreach ($arResult['ROLES'] as $role):?>
						<tr data-role-id="<?=htmlspecialcharsbx($role['ID'])?>">
							<td class="table-blue-td-name">
								<?=htmlspecialcharsbx($role['NAME'])?>
							</td>
							<td class="table-blue-td-action">
								<a class="table-blue-edit" title="<?=GetMessage('IMOL_PERM_EDIT')?>" href="<?=$role['EDIT_URL']?>"></a>
								<?if($arResult['CAN_EDIT']):?>
									<span class="table-blue-delete bx-vi-js-delete-role" title="<?=GetMessage('IMOL_PERM_DELETE')?>" data-role-id="<?=htmlspecialcharsbx($role['ID'])?>"></span>
								<?endif?>
							</td>
						</tr>
					<?endforeach;?>
					<tr>
						<td colspan="2" class="table-blue-td-link">
							<a href="<?=$arResult['ADD_URL']?>" class="table-blue-link"><?=GetMessage('IMOL_PERM_ADD')?></a>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<?if($arResult['CAN_EDIT']):?>
		<input type="submit" class="webform-small-button webform-small-button-accept" value="<?=GetMessage('IMOL_PERM_SAVE')?>">
	<?else:?>
		<span class="webform-small-button webform-small-button-accept" onclick="viOpenTrialPopup('vi_crm_source')">
			<?=GetMessage('IMOL_PERM_SAVE')?>
			<div class="tel-lock-holder-title"><div class="tel-lock"></div></div></span>
	<?endif?>
</form>
</div>
<script>
	(function()
	{
		var permissions = new BX.ViPermissionEdit(BX("vi-permissions-edit"));
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

<?
if(!$arResult['CAN_EDIT'])
{
	CBitrix24::initLicenseInfoPopupJS();
	?>
	<script type="text/javascript">
		function viOpenTrialPopup(dialogId)
		{
			B24.licenseInfoPopup.show(dialogId, "<?=CUtil::JSEscape($arResult["TRIAL"]['TITLE'])?>", "<?=CUtil::JSEscape($arResult["TRIAL"]['TEXT'])?>");
		}
		BX.ready(function()
		{
			viOpenTrialPopup('permissions');
		});
	</script>
	<?
}

