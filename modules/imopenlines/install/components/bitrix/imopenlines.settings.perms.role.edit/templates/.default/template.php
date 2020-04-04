<?
use \Bitrix\Main\Localization\Loc as Loc;

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->addExternalCss('/bitrix/css/main/table/style.css');

if($arResult['ERRORS'] && $arResult['ERRORS'] instanceof \Bitrix\Main\ErrorCollection)
{
	foreach ($arResult['ERRORS']->toArray() as $error)
	{
		ShowError($error);
	}
}
if (\Bitrix\Main\Loader::includeModule('bitrix24'))
	\CBitrix24::initLicenseInfoPopupJS();

\CJSCore::init('sidepanel');
?>
<script type="text/javascript">
	function imolOpenTrialPopup(dialogId, text)
	{
		if (typeof(B24) != 'undefined' && typeof(B24.licenseInfoPopup) != 'undefined')
		{
			B24.licenseInfoPopup.show(dialogId, "<?=CUtil::JSEscape(Loc::getMessage("IMOL_ROLE_POPUP_LIMITED_TITLE"))?>", text);
		}
		else
		{
			alert(text);
		}
	}
</script>
<form method="POST" action="<?=$arResult['ACTION_URI']?>" id="imol-role-form">
	<input type="hidden" name="act" value="save">
	<input type="hidden" name="ID" value="<?=htmlspecialcharsbx($arResult['ID'])?>">
	<?echo bitrix_sessid_post()?>
	<label for="form-input-name"><?=GetMessage('IMOL_ROLE_LABEL')?>:</label>
	<input id="form-input-name" name="NAME" value="<?=htmlspecialcharsbx($arResult['NAME'])?>">
	<br>
	<br>
	<table class="table-blue-wrapper">
		<tr>
			<td>
				<table class="table-blue">
					<tr>
						<th class="table-blue-td-title"><?=GetMessage('IMOL_ROLE_ENTITY')?></th>
						<th class="table-blue-td-title"><?=GetMessage('IMOL_ROLE_ACTION')?></th>
						<th class="table-blue-td-title"><?=GetMessage('IMOL_ROLE_PERMISSION')?></th>
					</tr>
					<?foreach ($arResult['PERMISSION_MAP'] as $entity => $actions)
					{
						$firstAction = true;
						foreach ($actions as $action => $availablePermissions)
						{
							?>
								<tr class="<?=($firstAction ? 'tr-first' : '')?>">
									<td class="table-blue-td-name">
										<?=($firstAction ? htmlspecialcharsbx(\Bitrix\ImOpenlines\Security\Permissions::getEntityName($entity)) : '&nbsp;')?>
										<?if ($entity == 'VOTE_HEAD' && (!\Bitrix\Imopenlines\Limit::canUseVoteHead() || \Bitrix\Imopenlines\Limit::isDemoLicense())):?>
											<span class="tel-lock-holder-select" title="<?=GetMessage("IMOL_ROLE_LOCK_ALT")?>"><span onclick='imolOpenTrialPopup("imol_queue_all", "<?=CUtil::JSEscape(Loc::getMessage("IMOL_ROLE_POPUP_LIMITED_VOTE_HEAD"))?>")' class="tel-lock <?=(\Bitrix\Imopenlines\Limit::isDemoLicense()? 'tel-lock-half': '')?>"></span></span>
										<?endif;?>
									</td>
									<td class="table-blue-td-param">
										<?=htmlspecialcharsbx(\Bitrix\ImOpenlines\Security\Permissions::getActionName($action))?>
									</td>
									<td class="table-blue-td-select">
										<select name="PERMISSIONS[<?=$entity?>][<?=$action?>]" class="table-blue-select" <?=($entity == 'VOTE_HEAD' && !\Bitrix\Imopenlines\Limit::canUseVoteHead()? 'disabled': '')?>>
											<?foreach ($availablePermissions as $permission):?>
												<option value="<?=$permission?>" <?=($permission === $arResult['PERMISSIONS'][$entity][$action] ? 'selected' : '')?>>
													<?=htmlspecialcharsbx(\Bitrix\ImOpenlines\Security\Permissions::getPermissionName($permission))?>
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
	<?if($arResult['CAN_EDIT']):?>
		<input type="submit" class="webform-small-button webform-small-button-accept" value="<?=GetMessage('IMOL_ROLE_SAVE')?>">
	<?else:?>
		<span class="webform-small-button webform-small-button-accept" onclick="viOpenTrialPopup('vi_role')">
			<?=GetMessage('IMOL_ROLE_SAVE')?>
			<div class="tel-lock-holder-title"><div class="tel-lock"></div></div></span>
	<?endif?>
	<a class="webform-small-button"
		<? if($arResult['IFRAME']): ?>
			onclick="BX.SidePanel.Instance.close()"
		<? else: ?>
			href="<?=$arResult['PERMISSIONS_URL']?>"
		<? endif; ?>
	>
		<?=GetMessage('IMOL_ROLE_CANCEL')?>
	</a>
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
else
{
	?>
	<script>
		BX.ready(function()
		{
			var sendSidePanelMessage =  function imolSendSidePanelMessage() {
				BX.SidePanel.Instance.postMessage(BX.SidePanel.Instance.getSliderByWindow(window), 'ImOpenLines:reloadRoles', {});
			};
			BX.bind(BX('imol-role-form'), 'submit', sendSidePanelMessage);
		});
	</script>
	<?
}