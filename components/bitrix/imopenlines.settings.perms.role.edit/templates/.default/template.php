<?
use \Bitrix\Main\UI\Extension,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Imopenlines\Limit;

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 */
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$this->addExternalCss('/bitrix/css/main/table/style.css');

if($arResult['ERRORS'] && $arResult['ERRORS'] instanceof \Bitrix\Main\ErrorCollection)
{
	foreach ($arResult['ERRORS']->toArray() as $error)
	{
		ShowError($error);
	}
}

\CJSCore::init('sidepanel');
$APPLICATION->IncludeComponent('bitrix:ui.info.helper', '', []);
Extension::load('ui.alerts');
?>
<script type="text/javascript">
	function openTrialInfoHelper(dialogId)
	{
		BX.UI.InfoHelper.show(dialogId);
	}
</script>
<form method="POST" action="<?=$arResult['ACTION_URI']?>" id="imol-role-form">
	<input type="hidden" name="act" value="save">
	<input type="hidden" name="ID" value="<?=htmlspecialcharsbx($arResult['ID'])?>">
	<?echo bitrix_sessid_post()?>
	<label for="form-input-name"><?=Loc::getMessage('IMOL_ROLE_LABEL')?>:</label>
	<input id="form-input-name" name="NAME" value="<?=htmlspecialcharsbx($arResult['NAME'])?>">
	<br>
	<br>
	<table class="table-blue-wrapper">
		<tr>
			<td>
				<table class="table-blue">
					<tr>
						<th class="table-blue-td-title"><?=Loc::getMessage('IMOL_ROLE_ENTITY')?></th>
						<th class="table-blue-td-title"><?=Loc::getMessage('IMOL_ROLE_ACTION')?></th>
						<th class="table-blue-td-title"><?=Loc::getMessage('IMOL_ROLE_PERMISSION')?></th>
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
										<?if (
												$entity == 'VOTE_HEAD' &&
												(
														!\Bitrix\Imopenlines\Limit::canUseVoteHead() ||
														\Bitrix\Imopenlines\Limit::isDemoLicense()
												)
										):?>
											<span class="tariff-lock-holder-select" title="<?=Loc::getMessage("IMOL_ROLE_LOCK_ALT")?>"><span onclick="openTrialInfoHelper('<?=Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_BOSS_RATE?>');" class="tariff-lock"></span></span>
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
		<input type="submit" class="webform-small-button webform-small-button-accept" value="<?=Loc::getMessage('IMOL_ROLE_SAVE')?>">
	<?else:?>
		<span class="webform-small-button webform-small-button-accept" onclick="openTrialInfoHelper('<?=Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_ACCESS_PERMISSIONS?>');">
			<?=Loc::getMessage('IMOL_ROLE_SAVE')?>
			<div class="tariff-lock-holder-title"><div class="tariff-lock"></div></div>
		</span>
	<?endif?>
	<a class="webform-small-button"
		<? if($arResult['IFRAME']): ?>
			onclick="BX.SidePanel.Instance.close()"
		<? else: ?>
			href="<?=$arResult['PERMISSIONS_URL']?>"
		<? endif; ?>
	>
		<?=Loc::getMessage('IMOL_ROLE_CANCEL')?>
	</a>
	<?if(!$arResult['CAN_EDIT']):?>
	<div class="ui-alert ui-alert-warning" style="margin: 15px 0 0 0;">
		<span class="ui-alert-message"><?=Loc::getMessage('IMOL_PERM_RESTRICTION')?></span>
	</div>
	<?endif?>
</form>
<?
if(!$arResult['CAN_EDIT'])
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