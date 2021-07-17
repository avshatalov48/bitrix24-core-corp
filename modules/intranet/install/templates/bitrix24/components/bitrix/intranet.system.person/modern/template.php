<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$this->addExternalCss(SITE_TEMPLATE_PATH."/css/employee.css");

$arUser = $arParams["USER"];
$name = CUser::FormatName(str_replace(array("#NOBR#", "#/NOBR#"), "", $arParams['NAME_TEMPLATE']), $arUser, $arResult["bUseLogin"], false);

$arUserData = array();
foreach ($arParams['USER_PROPERTY'] as $key)
{
	if ($arUser[$key])
	{
		$arUserData[$key] = $arUser[$key];
	}
}
$user_action_menu_number = rand();
?>
<tr id="tr_<?=$arUser["ID"]?>">
	<td class="employee-table-info">
		<div class="employee-info-block <? if (!in_array('PERSONAL_PHOTO', $arParams['USER_PROPERTY'])): ?> no-photo<? endif ?><? if (!$arUser['IS_ONLINE']): ?> employee-state-offline<? endif ?>">
			<? if (in_array('PERSONAL_PHOTO', $arParams['USER_PROPERTY'])): ?>
			<div class="user-avatar user-default-avatar" <?if ($arUser['PERSONAL_PHOTO_SOURCE']):?>style="background: url('<?=$arUser['PERSONAL_PHOTO_SOURCE']?>') no-repeat center center; background-size: cover;"<?endif;?>></div>
			<? endif ?>
			<div class="employee-name<?=($arUser["EXTRANET"] ? ' employee-name-extranet' : '')?>"><a class="employee-name-link" href="<?=$arUser['DETAIL_URL']?>"><?=$name?></a><?if ($arResult['CAN_EDIT_USER'] || $arUser["ACTIVITY_STATUS"] != "inactive"):?><span class="employee-user-action" onclick="user_action_menu<?=$user_action_menu_number?>(this,<?=$arUser['ID'].rand()?>, <?=$arUser['ID']?>, '<?=($arUser["EXTRANET"] ? "1" : "0")?>')"></span><?endif?></div>
			<div class="employee-post"><?=$arUser['WORK_POSITION']?></div>
			<div class="employee-state">
				<?=($arUser['IS_ONLINE'] ? GetMessage('INTR_ISP_IS_ONLINE') : GetMessage('INTR_ISP_IS_OFFLINE'));?><?if ($arUser['IS_ABSENT']):?> (<?=GetMessage('INTR_ISP_IS_ABSENT');?>)<?endif?>
			</div>
			<?if ($arParams["LIST_MODE"] == "all" && in_array($arUser["ACTIVITY_STATUS"], array("fired", "extranet", "inactive"))):?>
			<span class="employee-dept-post employee-dept-<?=$arUser["ACTIVITY_STATUS"]?>"><?=GetMessage("INTR_USER_".$arUser["ACTIVITY_STATUS"])?></span>
			<?elseif ($arUser["ADMIN"] && $arUser["ACTIVITY_STATUS"] == "active"):?>
				<span class="employee-dept-post employee-dept-admin"><?=GetMessage("INTR_IS_ADMIN")?></span>
			<?elseif ($arUser["ACTIVITY_STATUS"] == "integrator"):?>
				<span class="employee-dept-post employee-dept-<?=$arUser["ACTIVITY_STATUS"]?>"><?=GetMessage("INTR_USER_".$arUser["ACTIVITY_STATUS"])?></span>
			<?endif?>
		</div>
	</td>
	<?if ($arResult['CAN_EDIT_USER']):?>
	<td>
		<? if (!in_array($arUser['EXTERNAL_AUTH_ID'], \Bitrix\Main\UserTable::getExternalUserTypes())): ?>
			<div style="margin-right: 40px;">
				<a href="<?=CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_EDIT'], array("user_id" => $arUser['ID']))?>"><?=GetMessage("INTR_ISP_EDIT_LINK")?></a>
			</div>
		<? endif ?>
	</td>
	<?endif?>

	<td class="employee-table-phone">
		<?
		/*if (isset($arUser["PERSONAL_MOBILE"]))
			echo  GetMessage("ISL_PERSONAL_PHONE").": <a href='callto:".$arUser["PERSONAL_MOBILE"]."'>".$arUser["PERSONAL_MOBILE"]."</a><br/>";
		if (isset($arUser["UF_SKYPE"]))
			echo  GetMessage("ISL_PERSONAL_SKYPE").": <a href='callto:".$arUser["UF_SKYPE"]."'>".$arUser["UF_SKYPE"]."</a><br/>";
		if (isset($arUser["EMAIL"]))
			echo  GetMessage("ISL_PERSONAL_EMAIL").": <a href='mailto:".$arUser["EMAIL"]."'>".$arUser["EMAIL"]."</a><br/>"; */
		foreach ($arUserData as $key => $value)
		{
			if (in_array($key, array('PERSONAL_PHOTO')))
				continue;
			echo $arParams['USER_PROP'][$key] ? $arParams['USER_PROP'][$key] : GetMessage('ISL_'.$key); ?>:
			<? switch($key)
			{
				case 'EMAIL':
					echo '<a href="mailto:',urlencode($value),'">',htmlspecialcharsbx($value),'</a>';
					break;

				case 'PERSONAL_WWW':
					echo '<a href="http://',urlencode($value),'" target="_blank">',htmlspecialcharsbx($value),'</a>';
					break;

				case 'PERSONAL_PHONE':
				case 'WORK_PHONE':
				case 'PERSONAL_MOBILE':
					$value_encoded = preg_replace('/[^\d\+]+/', '', $value);
					echo '<a href="callto:',$value_encoded,'">',htmlspecialcharsbx($value),'</a>';
					break;

				case 'PERSONAL_GENDER':
					echo $value == 'F' ? GetMessage('INTR_ISP_GENDER_F') : ($value == 'M' ? GetMessage('INTR_ISP_GENDER_M') : '');
					break;

				case 'PERSONAL_BIRTHDAY':
					echo FormatDateEx(
						$value,
						false,
						$arParams['DATE_FORMAT'.(($arParams['SHOW_YEAR'] == 'N' || $arParams['SHOW_YEAR'] == 'M' && $arUser['PERSONAL_GENDER'] == 'F') ? '_NO_YEAR' : '')]
					);

					break;

				case 'DATE_REGISTER':
					echo FormatDateEx(
						$value,
						false,
						$arParams['DATE_TIME_FORMAT']
					);

					break;

				case 'UF_DEPARTMENT':
					$bFirst = true;
					if (is_array($value) && count($value) > 0)
					{
						foreach ($value as $dept_id => $dept_name)
						{
							if (!$bFirst && $dept_name) echo ', ';
							else $bFirst = false;

							if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
								echo htmlspecialcharsbx($dept_name);
							else
							{
								if (trim($arParams["PATH_TO_CONPANY_DEPARTMENT"]) <> '')
									echo '<a href="',CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_CONPANY_DEPARTMENT"], array("ID" => $dept_id)),'">',htmlspecialcharsbx($dept_name),'</a>';
								else
									echo '<a href="',$arParams['STRUCTURE_PAGE'].'?set_filter_',$arParams['STRUCTURE_FILTER'],'=Y&',$arParams['STRUCTURE_FILTER'],'_UF_DEPARTMENT=',$dept_id,'">',htmlspecialcharsbx($dept_name),'</a>';
							}

						}
					}
					break;

				default:
					if (mb_substr($key, 0, 3) == 'UF_' && is_array($arResult['USER_PROP'][$key]))
					{
						$arResult['USER_PROP'][$key]['VALUE'] = $value;
						$APPLICATION->IncludeComponent(
							'bitrix:system.field.view',
							$arResult['USER_PROP'][$key]['USER_TYPE_ID'],
							array(
								'arUserField' => $arResult['USER_PROP'][$key],
							)
						);
					}
					else
						echo htmlspecialcharsbx($arParams["~USER"][$key]);

					break;
			} ?>
			<br />
		<? } ?>
	</td>
	<td class="employee-table-dept">
		<?
		if (empty($arUser['DEP_HEAD']) || !is_array($arUser['DEP_HEAD']))
		{
			$arUser['DEP_HEAD'] = array();
		}
		if (empty($arUser['UF_DEPARTMENT']) || !is_array($arUser['UF_DEPARTMENT']))
		{
			$arUser['UF_DEPARTMENT'] = array();
		}
		foreach ($arUser["UF_DEPARTMENT"] as $dep_id => $dep_name)
		{
			$dep_name = htmlspecialcharsbx($dep_name);
			if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
				echo $dep_name."<br>";
			else
			{
				if (trim($arParams["PATH_TO_CONPANY_DEPARTMENT"]) <> '')
					echo '<a href="',CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_CONPANY_DEPARTMENT"], array("ID" => $dep_id)),'">',$dep_name,'</a>';
				else
					echo '<a href="',$arParams['STRUCTURE_PAGE'].'?set_filter_',$arParams['STRUCTURE_FILTER'],'=Y&',$arParams['STRUCTURE_FILTER'],'_UF_DEPARTMENT=',$dep_id,'">',$dep_name,'</a>';
				echo "<br>";
			}
			if (array_key_exists($dep_id, $arUser["DEP_HEAD"])):?>
			<span class="employee-dept-post"><?=GetMessage("INTR_IS_HEAD")?></span><br />
			<?endif;
		}
		$arHead = array_diff_key($arUser["DEP_HEAD"], $arUser["UF_DEPARTMENT"]);
		foreach ($arHead as $dep_id => $dep_name)
		{
			$dep_name = htmlspecialcharsbx($dep_name);
			if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
				echo $dep_name."<br>";
			else
			{
				if (trim($arParams["PATH_TO_CONPANY_DEPARTMENT"]) <> '')
					echo '<a href="',CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_CONPANY_DEPARTMENT"], array("ID" => $dep_id)),'">',$dep_name,'</a>';
				else
					echo '<a href="',$arParams['STRUCTURE_PAGE'].'?set_filter_',$arParams['STRUCTURE_FILTER'],'=Y&',$arParams['STRUCTURE_FILTER'],'_UF_DEPARTMENT=',$dep_id,'">',$dep_name,'</a>';
				echo "<br>";
			}
			?>
			<span class="employee-dept-post"><?=GetMessage("INTR_IS_HEAD")?></span><br /> <?
		}
		?>
	</td>
</tr>
<?
if ($arUser["ACTIVITY_STATUS"] == "fired") $userActive = "Y"; elseif($arUser["ACTIVITY_STATUS"] == "inactive")  $userActive = "D"; else $userActive = "N";
$userActionHref = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_EDIT'], array("user_id" => $arUser['ID']))."?ACTIVE=".$userActive;
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
			<?if (CBXFeatures::IsFeatureEnabled("Tasks")):?>
				{ text : "<?=GetMessage("INTR_ISP_TASK")?>", onclick : function() { this.popupWindow.close(); taskIFramePopup.add({RESPONSIBLE_ID: user_id});}},
			<?endif?>
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
		<?if ($arUser["ACTIVITY_STATUS"] != "inactive" && CBXFeatures::IsFeatureEnabled("WebMessenger") && $arUser["ID"] != $USER->GetID()):?>
			{ text : "<?=GetMessage("INTR_ISP_MESSAGE_HISTORY")?>", onclick : function() { this.popupWindow.close(); BXIM.openHistory(user_id);} },
		<?endif;?>

		<?if ($arResult['CAN_EDIT_USER'] && $arUser["ID"] != $USER->GetID() && !in_array($arUser['EXTERNAL_AUTH_ID'], \Bitrix\Main\UserTable::getExternalUserTypes())):?>
			{ text : "<?=$userActionMessage?>", onclick : function() {
				BX.showWait(button.parentNode.parentNode);
				if (confirmUser("<?=$arUser["ACTIVITY_STATUS"]?>"))
				{
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
									content: "<p>"+json.error+"</p>",
									offsetLeft:-10,
									offsetTop:7,
									autoHide:true
								});

								DeleteErrorPopup.show();
							}
							else
							{
								<?if ($arUser["SHOW_USER"] != "all"):?>
								BX('tr_'+user_id).style.display = 'none';
								<?else:?>
								window.location.reload();
								<?endif;?>
							}
						}
					});
				}
				else
				{
					BX.closeWait();
				}
				if(this.popupWindow)
				{
					this.popupWindow.close();
				}
				return false;
			} },
		<?endif;?>
		<?if ($arResult['CAN_EDIT_USER'] || $arUser["ID"] == $USER->GetID() and !in_array($arUser['EXTERNAL_AUTH_ID'], \Bitrix\Main\UserTable::getExternalUserTypes())):?>
			{ text : "<?=GetMessage("INTR_ISP_EDIT_USER")?>", href : "<?=CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_EDIT'], array("user_id" => $arUser['ID']))?>" }
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