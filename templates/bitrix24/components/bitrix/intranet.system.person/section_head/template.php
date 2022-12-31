<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arUser = $arParams['~USER'];
$name = CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser, $arResult["bUseLogin"]);

$user_action_menu_number = rand();
$avatar = !empty($arUser["PERSONAL_PHOTO"]) ? $arUser["PERSONAL_PHOTO"] : false;
?>
<div class="department-manager">
	<div class="department-manager-title"><?=GetMessage("INTR_STR_UF_HEAD")?></div>
		<span class="department-manager-info-block">
			<a
				class="department-manager-avatar user-default-avatar"
				href="<?=$arUser['DETAIL_URL']?>"
				<?if ($avatar):?>
					style="background: url('<?=$avatar?>') no-repeat; background-size: cover;"
				<?endif?>>
			</a>
			<span class="department-manager-name-block">
				<div class="department-manager-name">
					<a href="<?=$arUser['DETAIL_URL']?>" class="department-manager-name-link"><?=$arUser["FORMATTED_NAME"]?></a><?if ($arResult['CAN_EDIT_USER'] || $arUser["ACTIVITY_STATUS"] == "active"):?><span onclick="user_action_menu<?=$user_action_menu_number?>(this,<?=$arUser['ID'].rand()?>,<?=$arUser["ID"]?>)" class="employee-user-action"></span><?endif?>
				</div>
				<div class="department-manager-post"><?=$arUser["WORK_POSITION"]?></div>
			</span><span class="department-manager-info">
				<?if ($arUser["PERSONAL_MOBILE"]):?><div class="department-manager-tel"><?=GetMessage("ISL_PERSONAL_PHONE")?>: <?=$arUser["PERSONAL_MOBILE"]?></div><?endif?>
				<?if ($arUser["UF_SKYPE"]):?><div class="department-manager-tel"><?=GetMessage("ISL_PERSONAL_SKYPE")?>: <a href="callto:<?=$arUser["UF_SKYPE"]?>"><?=$arUser["UF_SKYPE"]?></a></div><?endif?>
				<?if ($arUser["EMAIL"]):?><div class="department-manager-tel"><?=GetMessage("ISL_PERSONAL_EMAIL")?>: <a href="mailto:<?=$arUser["EMAIL"]?>"><?=$arUser["EMAIL"]?></a></div><?endif?>
			</span>
		</span>
</div>

<?
if ($arUser["ACTIVITY_STATUS"] == "fired") $userActive = "Y"; elseif($arUser["ACTIVITY_STATUS"] == "inactive")  $userActive = "D"; else $userActive = "N"; 
$userActionHref = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_EDIT'], array("user_id" => $arUser["ID"]))."?ACTIVE=".$userActive;
if ($arUser["ACTIVITY_STATUS"] == "fired") 
	$userActionMessage = GetMessage('INTR_ISP_RESTORE_USER'); 
elseif ($arUser["ACTIVITY_STATUS"] == "inactive") 
	$userActionMessage = GetMessage('INTR_ISP_DELETE_USER'); 
else 
	$userActionMessage = GetMessage('INTR_ISP_DEACTIVATE_USER');	
?>
<script>
function user_action_menu<?=$user_action_menu_number?> (button, number, user_id, is_extranet) {

	var popupUserMenuItems = [
		<?if ($arUser["ACTIVITY_STATUS"] == "active" || $arUser["ACTIVITY_STATUS"] == "extranet"):?>
			{ text : "<?=GetMessage("INTR_ISP_TASK")?>", onclick : function() { this.popupWindow.close(); taskIFramePopup.add({RESPONSIBLE_ID: user_id});}},
			<?if ($arResult["CAN_MESSAGE"]):?>
			{ text : "<?=GetMessage("INTR_ISP_PM")?>", onclick : function() {if (BX.IM) { BXIM.openMessenger(user_id); return false; } else { window.open('<?echo $url ?>', '', 'status=no,scrollbars=yes,resizable=yes,width=700,height=550,top='+Math.floor((screen.height - 550)/2-14)+',left='+Math.floor((screen.width - 700)/2-5)); return false; }}},
			<?endif?>
		<?elseif ($arUser["ACTIVITY_STATUS"] == "inactive" && (!IsModuleInstalled("bitrix24") && $USER->CanDoOperation('edit_all_users') || $USER->CanDoOperation('bitrix24_invite') && CModule::IncludeModule('bitrix24'))):?>
		{ text : "<?=GetMessage("INTR_ISP_INVITE")?>", onclick : function() {
			var myBX = (window.BX? window.BX: (window.top.BX? window.top.BX: null));
			var user_reinvite = "reinvite_user_id_";
			if (is_extranet == "1")  user_reinvite = user_reinvite + "extranet_";
			BX.ajax.post(
					'/bitrix/tools/intranet_invite_dialog.php',
					{
						lang: BX.message('LANGUAGE_ID'),
						site_id: BX.message('SITE_ID') || '',
						reinvite: user_reinvite+user_id,
						sessid: BX.bitrix_sessid()
					},
					BX.delegate(function(result)
							{
								this.popupWindow.close();

								var InviteAccessPopup = BX.PopupWindowManager.create('invite_access'+number, button, {
									content: "<p><?=GetMessageJS("INTR_ISP_INVITE_ACCESS")?></p>",
									offsetLeft:-10,
									offsetTop:7,
									autoHide:true
								});

								InviteAccessPopup.show();
							},
							this)
			);
			return false;
		}},
		<?endif;?>
		<?if ($arResult['CAN_EDIT_USER'] && !in_array($arUser['EXTERNAL_AUTH_ID'], \Bitrix\Main\UserTable::getExternalUserTypes())):?>
			<?if ($arUser["ID"] != $USER->GetID()):?>
				{ text : "<?=$userActionMessage?>", onclick : function() {
					if (confirmUser("<?=$arUser["ACTIVITY_STATUS"]?>"))
					{
						BX.showWait(button.parentNode.parentNode);
						BX.ajax({
							method: 'POST',
							dataType: 'json',
							url: '<?=$this->GetFolder()."/ajax.php"?>',
							data:
							{
								user_id : '<?=$arUser["ID"]?>',
								active : '<?=$userActive?>',
								sessid: BX.bitrix_sessid(),
								site_id: '<?=SITE_ID?>'
							},
							onsuccess: function(json)
							{
								BX.closeWait();
								if (json.error)
								{
									if(this.popupWindow)
									{
										this.popupWindow.close();
									}

									var DeleteErrorPopup = BX.PopupWindowManager.create('delete_error'+number, button, {
										content: "<p><?=GetMessageJS("INTR_ISP_DELETE_ERROR")?></p>",
										offsetLeft:-10,
										offsetTop:7,
										autoHide:true
									});

									DeleteErrorPopup.show();
								}
								else
								{
									window.location.reload();
								}
							}
						});
					}
					this.popupWindow.close();
					return false;
				} },
			<?endif;?>
		{ text : "<?=GetMessage("INTR_ISP_EDIT_USER")?>", href : "<?=CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_EDIT'], array("user_id" => $arUser["ID"]))?>" }
		<?endif;?>
	];
	<?if ($arResult['CAN_VIDEO_CALL']):?>
		if (BXIM && BXIM.checkCallSupport())
		{
			popupUserMenuItems.push({ text : "<?=GetMessage("INTR_ISP_VIDEO_CALL")?>", onclick : function() {if (BXIM) { BXIM.callTo(user_id); return false;}}});
		}
	<?endif?>
	BX.PopupMenu.show('more-action-menu'+number, button, popupUserMenuItems,
		{
		offsetTop:7,
		offsetLeft:6,
		angle : true
		}
	);
}

function confirmUser(activity_status) 
{
	var  confirmMess = "";
	if (activity_status == "fired")
		confirmMess = "<?=GetMessage('INTR_CONFIRM_RESTORE')?>";
	else if (activity_status == "inactive")
		confirmMess = "<?=GetMessage('INTR_CONFIRM_DELETE')?>";
	else if (activity_status == "active" || activity_status == "extranet")
		confirmMess = "<?=GetMessage('INTR_CONFIRM_FIRE')?>";
	if (confirm(confirmMess)) 
		return true; 
	else
		return false;
}
</script>