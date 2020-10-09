<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Main\UI;
use Bitrix\Main\Loader;

CUtil::InitJSCore(array("popup", "ajax"));
\Bitrix\Main\UI\Extension::load(["socialnetwork.common", "ui.icons.b24", "ui.buttons"]);

if (Loader::includeModule('bitrix24'))
{
	\CBitrix24::initLicenseInfoPopupJS();
}

$this->addExternalCss(SITE_TEMPLATE_PATH."/css/profile_menu.css");
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."profile-menu-mode");

$this->SetViewTarget("above_pagetitle", 100);

if (!empty($arResult["bShowRequestSentMessage"]))
{
	if (
		$arResult["bShowRequestSentMessage"] == UserToGroupTable::INITIATED_BY_USER
		&& !CSocNetUser::isCurrentUserModuleAdmin()
	)
	{
		?><script>
			BX.ready(function() {
				BX.SocialnetworkUICommon.showRecallJoinRequestPopup({
					RELATION_ID: <?=intval($arResult["UserRelationId"])?>,
					URL_REJECT_OUTGOING_REQUEST: '<?=CUtil::JSEscape($arResult["Urls"]["UserRequests"])?>',
					URL_GROUPS_LIST: '<?=CUtil::JSEscape($arResult["Urls"]["GroupsList"])?>',
					PROJECT: <?=($arResult["Group"]["PROJECT"] == "Y" ? 'true' : 'false')?>
				});
			});
		</script><?
	}
	elseif ($arResult["bShowRequestSentMessage"] == UserToGroupTable::INITIATED_BY_GROUP)
	{

	}
}

?><script>
	BX.ready(function() {
		BX.message({
			SGMPathToRequestUser: '<?=CUtil::JSUrlEscape(
				!empty($arResult["Urls"]["Invite"])
					? $arResult["Urls"]["Invite"]
					: $arResult["Urls"]["Edit"].(mb_strpos($arResult["Urls"]["Edit"], "?") !== false ? "&" : '?')."tab=invite"
			)?>',
			SGMPathToUserRequestGroup: '<?=CUtil::JSUrlEscape($arResult["Urls"]["UserRequestGroup"])?>',
			SGMPathToUserLeaveGroup: '<?=CUtil::JSUrlEscape($arResult["Urls"]["UserLeaveGroup"])?>',
			SGMPathToRequests: '<?=CUtil::JSUrlEscape($arResult["Urls"]["GroupRequests"])?>',
			SGMPathToRequestsOut: '<?=CUtil::JSUrlEscape($arResult["Urls"]["GroupRequestsOut"])?>',
			SGMPathToMembers: '<?=CUtil::JSUrlEscape($arResult["Urls"]["GroupUsers"])?>',
			SGMPathToEdit: '<?=CUtil::JSUrlEscape($arResult["Urls"]["Edit"].(mb_strpos($arResult["Urls"]["Edit"], "?") !== false ? "&" : '?')."tab=edit")?>',
			SGMPathToDelete: '<?=CUtil::JSUrlEscape($arResult["Urls"]["Delete"])?>',
			SGMPathToFeatures: '<?=CUtil::JSUrlEscape($arResult["Urls"]["Features"])?>',
			SGMPathToCopy: '<?=CUtil::JSUrlEscape($arResult["Urls"]["Copy"])?>'
		});

		BX.BXSGM24.init({
			currentUserId: BX.message('USER_ID'),
			groupId: <?=intval($arResult["Group"]["ID"])?>,
			groupType: '<?=CUtil::JSEscape($arResult['Group']['TypeCode'])?>',
			isProject: <?=($arResult["Group"]["PROJECT"] == "Y" ? 'true' : 'false')?>,
			isOpened: <?=($arResult["Group"]["OPENED"] == "Y" ? 'true' : 'false')?>,
			favoritesValue: <?=($arResult["FAVORITES"] ? 'true' : 'false')?>,
			canInitiate: <?=($arResult["CurrentUserPerms"]["UserCanInitiate"] && !$arResult["HideArchiveLinks"] ? 'true' : 'false')?>,
			canProcessRequestsIn: <?=($arResult["CurrentUserPerms"]["UserCanProcessRequestsIn"] && !$arResult["HideArchiveLinks"] ? 'true' : 'false')?>,
			canModify: <?=($arResult["CurrentUserPerms"]["UserCanModifyGroup"] && !$arResult["HideArchiveLinks"] ? 'true' : 'false')?>,
			userRole: '<?=$arResult["CurrentUserPerms"]["UserRole"]?>',
			userIsMember: <?=($arResult["CurrentUserPerms"]["UserIsMember"] ? 'true' : 'false')?>,
			userIsAutoMember: <?=(isset($arResult["CurrentUserPerms"]["UserIsAutoMember"]) && $arResult["CurrentUserPerms"]["UserIsAutoMember"] ? 'true' : 'false')?>,
			editFeaturesAllowed: <?=(\Bitrix\Socialnetwork\Item\Workgroup::getEditFeaturesAvailability() ? 'true' : 'false')?>,
			urls: {
				group: '<?=CUtil::JSEscape(!empty($arResult["Urls"]["General"]) ? $arResult["Urls"]["General"] : $arResult["Urls"]["View"])?>',
				groupsList: '<?=CUtil::JSEscape($arResult["Urls"]["GroupsList"])?>'
			}
		});
	});
</script><?

?><div class="profile-menu profile-menu-group">
	<div class="profile-menu-inner">
		<div class="profile-menu-top">
			<a href="<?=$arResult["Urls"]["View"]?>" class="ui-icon ui-icon-common-user-group profile-menu-avatar">
				<i <?if ($arResult["Group"]["IMAGE_FILE"]["src"] <> ''):?>
					style="background:url('<?=$arResult["Group"]["IMAGE_FILE"]["src"]?>') no-repeat center center; background-size: cover"
				<?endif;?>></i>
			</a>
			<div class="profile-menu-info<?=($arResult["Group"]["IS_EXTRANET"] == "Y" ? " profile-menu-group-info-extranet" : "")?>">
				<a href="<?=$arResult["Urls"]["View"]?>" class="profile-menu-name"><?=$arResult["Group"]["NAME"]?></a>
				<div class="profile-menu-type">
					<span class="profile-menu-type-name">
						<span class="profile-menu-type-name-item"><?=(is_array($arResult['Group']['Type']) && !empty($arResult['Group']['Type']) && !empty($arResult['Group']['Type']['NAME']) ? (LANGUAGE_ID == 'de'? $arResult['Group']['Type']['NAME'] : mb_strtolower($arResult['Group']['Type']['NAME'])) : '')?></span><?
						if ($arResult["CurrentUserPerms"]["UserCanModifyGroup"])
						{
							?><a href="<?=htmlspecialcharsbx($arResult["Urls"]["Edit"].(mb_strpos($arResult["Urls"]["Edit"], "?") !== false ? "&" : '?')."tab=edit")?>" class="profile-menu-type-icon"></a><?
						}
					?></span>
				</div><?

				if ($arResult["Group"]["CLOSED"] == "Y")
				{
					?><span class="profile-menu-description"><?=Loc::getMessage("SONET_UM_ARCHIVE_GROUP")?></span><?
				}

				?><span class="profile-menu-links"><?
					?><a href="<?=$arResult["Urls"]["Card"]?>" class="profile-menu-links-item"><?=Loc::getMessage($arResult["Group"]["PROJECT"] == "Y" ? "SONET_SGM_T_LINKS_ABOUT_PROJECT" : "SONET_SGM_T_LINKS_ABOUT")?></a><?

					?><a href="<?=$arResult["Urls"]["GroupUsers"]?>" class="profile-menu-links-item"><?
						if (intval($arResult['Group']['NUMBER_OF_MEMBERS']) > 0)
						{
							echo Loc::getMessage("SONET_SGM_T_MEMBERS2", array('#NUM#' => intval($arResult['Group']['NUMBER_OF_MEMBERS'])));
						}
						else
                        {
							echo Loc::getMessage("SONET_SGM_T_MEMBERS");
                        }
					?></a><?

					if (
						$arResult["CurrentUserPerms"]["UserCanProcessRequestsIn"]
						&& !$arResult["HideArchiveLinks"]
						&& intval($arResult['Group']['NUMBER_OF_REQUESTS']) > 0
					)
					{
						?><a href="<?=$arResult["Urls"]["GroupRequests"]?>" class="profile-menu-links-count">+<?=intval($arResult['Group']['NUMBER_OF_REQUESTS'])?></a><?
					}

					if (
						$arResult["CurrentUserPerms"]["UserCanModifyGroup"]
						|| $arResult["CurrentUserPerms"]["UserIsMember"]
					)
					{
						?><a id="bx-group-menu-settings" href="javascript:void(0);" class="profile-menu-links-item"><?=Loc::getMessage("SONET_UM_ACTIONS_BUTTON")?></a><?
					}
				?></span><?

				if ($arResult["bUserCanRequestGroup"])
				{
					?><span id="bx-group-menu-join-cont" style="padding-left: 10px;"><?

						if (
							$arResult['Group']['OPENED'] == 'Y'
							|| (
								$arResult["CurrentUserPerms"]["UserRole"] == UserToGroupTable::ROLE_REQUEST
								&& $arResult["CurrentUserPerms"]["InitiatedByType"] == UserToGroupTable::INITIATED_BY_GROUP
							)
						)
						{
							?><button class="ui-btn ui-btn-sm ui-btn-primary" id="bx-group-menu-join" bx-request-url="<?=$arResult["Urls"]["UserRequestGroup"]?>"><?=Loc::getMessage('SONET_SGM_T_BUTTON_JOIN')?></button><?
						}
						else
						{
							?><a class="ui-btn ui-btn-sm ui-btn-primary" href="<?=$arResult["Urls"]["UserRequestGroup"]?>"><?=Loc::getMessage('SONET_SGM_T_BUTTON_JOIN')?></a><?
						}

					?></span><?
				}

			?></div>
		</div>
		<div class="profile-menu-bottom">
			<div class="profile-menu-items-new"><?

				$menuItems = array();

				foreach ($arResult["CanView"] as $key => $val)
				{
					if (
						!$val
						|| $key == "content_search")
					{
						continue;
					}

					if ($key == 'general')
					{
						$menuItems[] = array(
							"TEXT" => GetMessage("SONET_UM_GENERAL"),
							"URL" => ($arResult["Urls"]["General"] ? $arResult["Urls"]["General"] : $arResult["Urls"]["View"]),
							"ID" => "general",
							"IS_ACTIVE" => in_array($arParams["PAGE_ID"], array("group", "group_general")),
						);
					}
					else
					{
						$item = array(
							"TEXT" => $arResult["Title"][$key],
							"ID" => $key,
							"IS_ACTIVE" => ($arParams["PAGE_ID"] == "group_".$key),
						);

						if (
							!empty($arResult["OnClicks"])
							&& !empty($arResult["OnClicks"][$key])
						)
						{
							$item["ON_CLICK"] = $arResult["OnClicks"][$key];
						}
						else
						{
							$item["URL"] = $arResult["Urls"][$key];
						}

						$menuItems[] = $item;
					}
				}

				$APPLICATION->IncludeComponent(
					"bitrix:main.interface.buttons",
					"",
					array(
						"ID" => $arResult["menuId"],
						"ITEMS" => $menuItems,
					)
				);

			?></div>
		</div>
	</div>
</div>
<?


$this->EndViewTarget();?>
