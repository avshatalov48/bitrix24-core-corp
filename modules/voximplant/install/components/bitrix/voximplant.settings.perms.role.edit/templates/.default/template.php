<?
/**
 * @var array $arResult
 * @var CMain $APPLICATION
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CJSCore::Init(['voximplant.common']);
$this->addExternalCss('/bitrix/css/main/table/style.css');

if($arResult['ERRORS'] && $arResult['ERRORS'] instanceof \Bitrix\Main\ErrorCollection)
{
	foreach ($arResult['ERRORS']->toArray() as $error)
	{
		ShowError($error);
	}
}

?>
<form method="POST">
	<input type="hidden" name="act" value="save">
	<input type="hidden" name="ID" value="<?=htmlspecialcharsbx($arResult['ID'])?>">
	<?echo bitrix_sessid_post()?>
	<div style="display: flex; align-items:center; margin-left: 20px; height: 40px;">
		<label for="form-input-name"><?=GetMessage('VOXIMPLANT_ROLE_LABEL')?>:</label>
		<input id="form-input-name" name="NAME" value="<?=htmlspecialcharsbx($arResult['NAME'])?>">
	</div>
	<table class="table-blue-wrapper">
		<tr>
			<td>
				<table class="table-blue">
					<tr>
						<th class="table-blue-td-title"><?=GetMessage('VOXIMPLANT_ROLE_ENTITY')?></th>
						<th class="table-blue-td-title"><?=GetMessage('VOXIMPLANT_ROLE_ACTION')?></th>
						<th class="table-blue-td-title"><?=GetMessage('VOXIMPLANT_ROLE_PERMISSION')?></th>
					</tr>
					<?foreach ($arResult['PERMISSION_MAP'] as $entity => $actions)
					{
						$firstAction = true;
						foreach ($actions as $action => $availablePermissions)
						{
							?>
								<tr class="<?=($firstAction ? 'tr-first' : '')?>">
									<td class="table-blue-td-name"><?=($firstAction ? htmlspecialcharsbx(\Bitrix\Voximplant\Security\Permissions::getEntityName($entity)) : '&nbsp;')?></td>
									<td class="table-blue-td-param"><?=htmlspecialcharsbx(\Bitrix\Voximplant\Security\Permissions::getActionName($action))?></td>
									<td class="table-blue-td-select">
										<select name="PERMISSIONS[<?=$entity?>][<?=$action?>]" class="table-blue-select">
											<?foreach ($availablePermissions as $permission):?>
												<option value="<?=$permission?>" <?=($permission === $arResult['PERMISSIONS'][$entity][$action] ? 'selected' : '')?>>
													<?=htmlspecialcharsbx(\Bitrix\Voximplant\Security\Permissions::getPermissionName($permission))?>
												</option>
											<?endforeach;?>
										</select>
									</td>

								</tr>
							<?
							$firstAction = false;
						}
					}
					?>
				</table>
			</td>
		</tr>
	</table>

	<?

	$buttons = [];

	if($arResult['CAN_EDIT'])
	{
		$buttons[] = 'save';
	}
	$buttons['cancel'] = $arResult['PERMISSIONS_URL'];

	$APPLICATION->IncludeComponent(
		'bitrix:ui.button.panel',
		'',
		['BUTTONS' => $buttons],
		false
	); ?>
</form>
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