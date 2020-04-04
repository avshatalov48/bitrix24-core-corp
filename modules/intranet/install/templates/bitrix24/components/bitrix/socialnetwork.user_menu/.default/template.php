<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
CUtil::InitJSCore(array("popup"));

if (
	!isset($arResult["User"]["ID"])
	|| (
		$USER->IsAuthorized()
		&& $arResult["User"]["ID"] == $USER->GetID()
		&& $arParams["PAGE_ID"] != "user"
	)
)
{
	return;
}

$this->addExternalCss(SITE_TEMPLATE_PATH."/css/profile_menu.css");
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."profile-menu-mode");

$this->SetViewTarget("above_pagetitle", 100);
$className = '';
if ($arResult["User"]["TYPE"] == 'extranet')
{
	$className = ' profile-menu-user-info-extranet';
}
elseif ($arResult["User"]["TYPE"] == 'email')
{
	$className = ' profile-menu-user-info-email';
}
/*
elseif ($arResult["User"]["TYPE"] == 'imconnector')
{
	$className = ' profile-menu-user-info-imconnector';
}
elseif ($arResult["User"]["TYPE"] == 'bot')
{
	$className = ' profile-menu-user-info-bot';
}
elseif ($arResult["User"]["TYPE"] == 'replica')
{
	$className = ' profile-menu-user-info-replica';
}
*/
elseif ($arResult["User"]["IS_EXTRANET"] == 'Y')
{
	$className = ' profile-menu-user-info-extranet';
}
?>

<div class="profile-menu">
	<div class="profile-menu-inner">
		<?
			$isOffline = !array_key_exists("IS_ONLINE", $arResult) || !$arResult["IS_ONLINE"];
			$avatar = $arResult["User"]["PersonalPhotoFile"]["src"];
		?>
		<a
			href="<?=htmlspecialcharsbx($arResult['Urls']['main']) ?>"
			class="profile-menu-avatar user-default-avatar<?if ($isOffline):?> profile-menu-avatar-offline<?endif?>"
			<?if (strlen($avatar) > 0):?>
				style="background:url('<?=$avatar?>') no-repeat center center; background-size: cover;"
			<?endif;?>
			><i></i>
		</a>
		<div class="profile-menu-right">
			<div class="profile-menu-info<?=$className?>">
				<a href="<?=htmlspecialcharsbx($arResult['Urls']['main']) ?>" class="profile-menu-name"><?=$arResult["User"]["NAME_FORMATTED"]?></a><?if (array_key_exists("IS_ABSENT", $arResult) && $arResult["IS_ABSENT"]):?><span class="profile-menu-status"><?=GetMessage("SONET_UM_ABSENT")?></span><?endif;
				if (
					isset($arResult["User"]["ID"])
					&& (
						$arResult["CAN_MESSAGE"]
						|| $arResult["CAN_MESSAGE_HISTORY"]
					)
				)
				{
					?><span class="profile-menu-user-menu" onclick="openProfileMenuPopup(this);"></span><?
				}
				if(strlen($arResult["User"]["WORK_POSITION"]) > 0):?><span class="profile-menu-description"><?=$arResult["User"]["WORK_POSITION"]?></span><?endif?><?if(array_key_exists("IS_BIRTHDAY", $arResult) && $arResult["IS_BIRTHDAY"]):?><span
				class="profile-menu-birthday-icon" title="<?=GetMessage("SONET_UM_BIRTHDAY")?>"></span><?endif?><?if(array_key_exists("IS_HONOURED", $arResult) && $arResult["IS_HONOURED"]):?><span class="profile-menu-leaderboard-icon" title="<?=GetMessage("SONET_UM_HONOUR")?>"></span><?endif?>
			</div>

			<div id="profile-menu-filter" class="profile-menu-items"><?
				?><a href="<?=htmlspecialcharsbx($arResult['Urls']['main']) ?>" class="profile-menu-item<?if ($arParams["PAGE_ID"] == "user"):?> profile-menu-item-active<?endif?>"><?=GetMessage("SONET_UM_GENERAL")?></a><?
				if (is_array($arResult["CanView"]))
				{
					foreach ($arResult["CanView"] as $key => $val)
					{
						if (!$val)
						{
							continue;
						}
						?><a
							href="<?=htmlspecialcharsbx($arResult['Urls'][$key]) ?>"
							class="profile-menu-item
							<?if ($arParams["PAGE_ID"] == "user_".$key):?>
								profile-menu-item-active<?endif?>
							"><?=$arResult["Title"][$key]?>
						</a><?
					}
				}
				?>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
function openProfileMenuPopup(bindElement)
{
	BX.addClass(bindElement, "profile-menu-user-active");

	var menu = [];

	<?if ($arResult["CAN_MESSAGE"] && $arResult["User"]["ACTIVE"] != "N"):?>
		menu.push(
			{
				text : "<?=GetMessage("SONET_UM_SEND_MESSAGE")?>",

				onclick : function() {
					this.popupWindow.close();
					BXIM.openMessenger(<?=$arResult["User"]["ID"]?>);
				}
			}
		);
	<?endif;

	if ($arResult["CAN_MESSAGE_HISTORY"]):?>
		menu.push(
			{
				text : "<?=GetMessage("SONET_UM_MESSAGE_HISTORY")?>",
				onclick : function() {
					this.popupWindow.close();
					BXIM.openHistory(<?=$arResult["User"]["ID"]?>);
				}
			}
		);
	<?endif;

	if ($arResult["CurrentUserPerms"]["Operations"]["modifyuser"])
	{
		if ($arResult["CurrentUserPerms"]["Operations"]["modifyuser_main"])
		{
			?>
			menu.push(
				{
					text : "<?=GetMessage("SONET_UM_EDIT_PROFILE")?>",
					title: "<?=GetMessage("SONET_UM_EDIT_PROFILE")?>",
					href: "<?=CUtil::JSUrlEscape(CUtil::JsEscape($arResult["Urls"]["Edit"]))?>"
				}
			);
			<?
		}
		?>
		menu.push(
			{
				text : "<?=GetMessage("SONET_UM_REQUESTS")?>",
				title: "<?=GetMessage("SONET_UM_REQUESTS")?>",
				href: "<?=CUtil::JSUrlEscape($arResult["Urls"]["UserRequests"])?>"
			}
		);
		<?
	}

	if (
		($arResult["CurrentUserPerms"]["IsCurrentUser"] || $arResult["CurrentUserPerms"]["Operations"]["viewprofile"])
		&& !class_exists("CSocNetSubscription")
	):
		?>
		menu.push(
			{
				text : "<?=GetMessage("SONET_UM_SUBSCRIBE")?>",
				title: "<?=GetMessage("SONET_UM_SUBSCRIBE")?>",
				href: "<?=CUtil::JSUrlEscape($arResult["Urls"]["Subscribe"])?>"
			}
		);
	<?endif;

	if (IsModuleInstalled("bitrix24") && $arResult["CurrentUserPerms"]["Operations"]["modifyuser"]):?>
		menu.push(
			{
				text : "<?=GetMessage("SONET_TELEPHONY_HISTORY")?>",
				title: "<?=GetMessage("SONET_TELEPHONY_HISTORY")?>",
				href: "/telephony/detail.php?USER_ID=<?=$arResult["User"]["ID"]?>"
			}
		);
	<?endif;

	/*if ($arResult["CurrentUserPerms"]["Operations"]["videocall"] && $arParams['PATH_TO_VIDEO_CALL']):
		?>
		menu.push(
			{
				text : "<?=GetMessage("SONET_UM_VIDEO_CALL")?>",
				className : "profile-menu-videocall",
				onclick : function() {
					window.open('<?echo $arResult["Urls"]["VideoCall"] ?>', '', 'status=no,scrollbars=yes,resizable=yes,width=1000,height=600,top='+Math.floor((screen.height - 600)/2-14)+',left='+Math.floor((screen.width - 1000)/2-5));
					return false;
				}
			}
		);
		<?
	endif; */
	?>
	if (menu.length > 0)
	{
		BX.PopupMenu.show("user-menu-profile", bindElement, menu, {
			offsetTop: 5,
			offsetLeft : 12,
			angle : true,
			events : {
				onPopupClose : function() {
					BX.removeClass(this.bindElement, "profile-menu-user-active");
				}
			}
		});
	}
}
</script>
<?$this->EndViewTarget();?>