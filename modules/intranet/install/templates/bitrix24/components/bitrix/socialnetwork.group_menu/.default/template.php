<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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

CUtil::InitJSCore([ 'popup', 'ajax', 'marketplace', 'clipboard' ]);

UI\Extension::load([
	'socialnetwork.common',
	'ui.icons.b24',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.notification',
]);

if (Loader::includeModule('bitrix24'))
{
	\CBitrix24::initLicenseInfoPopupJS();
}

$groupMember = in_array($arResult['CurrentUserPerms']['UserRole'], \Bitrix\Socialnetwork\UserToGroupTable::getRolesMember());

$this->addExternalCss(SITE_TEMPLATE_PATH."/css/profile_menu.css");
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."profile-menu-mode");

if (!$arResult['inIframe'] || $arResult['IS_CURRENT_PAGE_FIRST'])
{
	$this->SetViewTarget("above_pagetitle", 100);
}

if (
	!empty($arResult["bShowRequestSentMessage"])
	&& $arResult["bShowRequestSentMessage"] === UserToGroupTable::INITIATED_BY_USER
	&& !CSocNetUser::isCurrentUserModuleAdmin()
)
{
	?><script>
		BX.ready(function() {
			BX.SocialnetworkUICommon.showRecallJoinRequestPopup({
				RELATION_ID: <?= (int)$arResult["UserRelationId"] ?>,
				URL_REJECT_OUTGOING_REQUEST: '<?= CUtil::JSEscape($arResult["Urls"]["UserRequests"]) ?>',
				URL_GROUPS_LIST: '<?= CUtil::JSEscape($arResult["Urls"]["GroupsList"]) ?>',
				PROJECT: <?= ($arResult["Group"]["PROJECT"] === "Y" ? 'true' : 'false') ?>
			});
		});
	</script><?
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
			SGMPathToCopy: '<?=CUtil::JSUrlEscape($arResult["Urls"]["Copy"])?>',
			SONET_SGM_T_CONTROL_NOTIFICATION_COPYURL: '<?= htmlspecialcharsbx(Loc::getMessage('SONET_SGM_T_CONTROL_NOTIFICATION_COPYURL')) ?>',
			SONET_SGM_T_CONTROL_HINT_FAVORITES_ADD: '<?= htmlspecialcharsbx(Loc::getMessage('SONET_SGM_T_CONTROL_HINT_FAVORITES_ADD')) ?>',
			SONET_SGM_T_CONTROL_HINT_FAVORITES_REMOVE: '<?= htmlspecialcharsbx(Loc::getMessage('SONET_SGM_T_CONTROL_HINT_FAVORITES_REMOVE')) ?>',
		});

		BX.BXSGM24.init({
			currentUserId: BX.message('USER_ID'),
			groupId: <?=(int)$arResult["Group"]["ID"]?>,
			groupType: '<?=CUtil::JSEscape($arResult['Group']['TypeCode'])?>',
			isProject: <?=($arResult["Group"]["PROJECT"] === "Y" ? 'true' : 'false')?>,
			isOpened: <?=($arResult["Group"]["OPENED"] === "Y" ? 'true' : 'false')?>,
			favoritesValue: <?=($arResult["FAVORITES"] ? 'true' : 'false')?>,
			canInitiate: <?=($arResult["CurrentUserPerms"]["UserCanInitiate"] && !$arResult["HideArchiveLinks"] ? 'true' : 'false')?>,
			canProcessRequestsIn: <?=($arResult["CurrentUserPerms"]["UserCanProcessRequestsIn"] && !$arResult["HideArchiveLinks"] ? 'true' : 'false')?>,
			canModify: <?=($arResult["CurrentUserPerms"]["UserCanModifyGroup"] ? 'true' : 'false')?>,
			userRole: '<?=$arResult["CurrentUserPerms"]["UserRole"]?>',
			userIsMember: <?=($arResult["CurrentUserPerms"]["UserIsMember"] ? 'true' : 'false')?>,
			userIsAutoMember: <?=(isset($arResult["CurrentUserPerms"]["UserIsAutoMember"]) && $arResult["CurrentUserPerms"]["UserIsAutoMember"] ? 'true' : 'false')?>,
			editFeaturesAllowed: <?=(\Bitrix\Socialnetwork\Item\Workgroup::getEditFeaturesAvailability() ? 'true' : 'false')?>,
			canPickTheme: <?= (
				$arResult['inIframe']
				&& \Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker::isAvailable()
				&& $arResult['CurrentUserPerms']['UserCanModifyGroup']
				&& !$arResult['HideArchiveLinks']
					? 'true'
					: 'false'
			) ?>,
			urls: <?= CUtil::PhpToJSObject($arResult['Urls']) ?>,
			pageId: '<?= $arParams['PAGE_ID'] ?>'
		});
	});
</script><?

?><div class="profile-menu profile-menu-group">
	<div class="profile-menu-inner">
		<div class="profile-menu-top<?= ($arResult['Group']['IS_EXTRANET'] === 'Y' ? ' profile-menu-top-extranet' : '') ?>"><?php

			$avatarStyle = (
				!empty($arResult['Group']['IMAGE_FILE']['src'])
					? 'style="background:url(\'' . $arResult['Group']['IMAGE_FILE']['src'] . '\') no-repeat center center; background-size: cover;"'
					: ''
			);

			if (!$arResult['inIframe'])
			{
				?><a href="<?= $arResult['Urls']['View'] ?>" class="ui-icon ui-icon-common-user-group profile-menu-avatar"><i <?= $avatarStyle ?>></i></a><?php
			}
			else
			{
				?><span class="ui-icon ui-icon-common-user-group profile-menu-avatar"><i <?= $avatarStyle ?>></i></span><?php
			}
			?><div class="profile-menu-group-info">
				<div class="profile-menu-name-box"><?php
					if (!$arResult['inIframe'])
					{
						?><a href="<?= $arResult['Urls']['View'] ?>" class="profile-menu-name"><?= $arResult['Group']['NAME'] ?></a><?php
					}
					else
					{
						?><span class="profile-menu-name"><?= $arResult['Group']['NAME'] ?></span><?php
					}
					?><div style="display: none;" class="profile-menu-type">
						<span class="profile-menu-type-name">
						<span class="profile-menu-type-name-item"><?=(is_array($arResult['Group']['Type']) && !empty($arResult['Group']['Type']) && !empty($arResult['Group']['Type']['NAME']) ? (LANGUAGE_ID === 'de' ? $arResult['Group']['Type']['NAME'] : mb_strtolower($arResult['Group']['Type']['NAME'])) : '')?></span><?php
						if ($arResult["CurrentUserPerms"]["UserCanModifyGroup"])
						{
							?><a href="<?= htmlspecialcharsbx($arResult["Urls"]["Edit"] . (mb_strpos($arResult["Urls"]["Edit"], "?") !== false ? "&" : '?') . "tab=edit")?>" class="profile-menu-type-icon"></a><?php
						}
						?></span>
					</div>
				</div>
				<?

				if ($arResult["Group"]["CLOSED"] === "Y")
				{
					?><span class="profile-menu-description"><?= Loc::getMessage('SONET_UM_ARCHIVE_GROUP') ?></span><?php
				}

				if (!$arResult['inIframe'])
				{
					?><span class="profile-menu-links"><?
						?><a href="<?=$arResult["Urls"]["Card"]?>" class="profile-menu-links-item"><?= Loc::getMessage("SONET_SGM_T_LINKS_ABOUT_PROJECT") ?></a><?

						?><a href="<?=$arResult["Urls"]["GroupUsers"]?>" class="profile-menu-links-item"><?
							if ((int)$arResult['Group']['NUMBER_OF_MEMBERS'] > 0)
							{
								echo Loc::getMessage("SONET_SGM_T_MEMBERS2", array('#NUM#' => (int)$arResult['Group']['NUMBER_OF_MEMBERS']));
							}
							else
							{
								echo Loc::getMessage("SONET_SGM_T_MEMBERS");
							}
						?></a><?

						if (
							$arResult["CurrentUserPerms"]["UserCanProcessRequestsIn"]
							&& !$arResult["HideArchiveLinks"]
							&& (int)$arResult['Group']['NUMBER_OF_REQUESTS'] > 0
						)
						{
							?><a href="<?= $arResult['Urls']['GroupRequests'] ?>" class="profile-menu-links-count">+<?= (int)$arResult['Group']['NUMBER_OF_REQUESTS'] ?></a><?
						}

						if (
							$arResult["CurrentUserPerms"]["UserCanModifyGroup"]
							|| $arResult["CurrentUserPerms"]["UserIsMember"]
						)
						{
							?><a id="bx-group-menu-settings" href="javascript:void(0);" class="profile-menu-links-item"><?=Loc::getMessage("SONET_UM_ACTIONS_BUTTON")?></a><?
						}

					?></span><?
				}

				if ($arResult['inIframe'])
				{

					?><span class="profile-menu-links"><?

						if ($arResult['bUserCanRequestGroup'])
						{
							if (
								$arResult['Group']['OPENED'] === 'Y'
								|| (
									$arResult['CurrentUserPerms']['UserRole'] === UserToGroupTable::ROLE_REQUEST
									&& $arResult['CurrentUserPerms']['InitiatedByType'] === UserToGroupTable::INITIATED_BY_GROUP
								)
							)
							{
								?><button class="ui-btn ui-btn-primary bx-group-menu-join-cont" id="bx-group-menu-join" bx-request-url="<?= $arResult["Urls"]["UserRequestGroup"] ?>"><?= Loc::getMessage('SONET_SGM_T_BUTTON_JOIN') ?></button><?php
							}
							else
							{
								?><a class="ui-btn ui-btn-primary bx-group-menu-join-cont" href="<?= $arResult["Urls"]["UserRequestGroup"] ?>"><?= Loc::getMessage('SONET_SGM_T_BUTTON_JOIN') ?></a><?php
							}
						}


						if ($groupMember)
						{
							$APPLICATION->includeComponent(
								'bitrix:intranet.binding.menu',
								'',
								[
									'SECTION_CODE' => 'socialnetwork',
									'MENU_CODE' => 'group_notifications',
									'CONTEXT' => [
										'GROUP_ID' => $arResult['Group']['ID']
									]
								]
							);
						}

						if ($arResult['CanView']['chat'])
						{
							?>
							<button id="group_menu_chat_button" class="ui-btn ui-btn-light-border ui-btn-icon-chat ui-btn-themes"
									title="<?= Loc::getMessage('SONET_SGM_T_CHAT_TITLE_PROJECT') ?>"
									onclick="top.BXIM.openMessenger('sg<?= (int)$arResult['Group']['ID'] ?>');"
							></button>
							<?
						}

						if ($groupMember)
						{
							?><button id="group_menu_subscribe_button" class="ui-btn ui-btn-light-border ui-btn-icon-follow ui-btn-themes
								<?= ($arResult['bSubscribed'] ? ' ui-btn-active' : '') ?>"
									  title="<?= Loc::getMessage('SONET_SGM_T_NOTIFY_TITLE_' . ($arResult['bSubscribed'] ? 'ON' : 'OFF')) ?>"
									  onclick="B24SGControl.getInstance().setSubscribe(event);"
							></button><?php
						}

						?><a href="<?=$arResult["Urls"]["Card"]?>" class="ui-btn ui-btn-light-border ui-btn-themes"><?= Loc::getMessage('SONET_SGM_T_LINKS_ABOUT_PROJECT') ?></a><?
						?><button id="bx-group-menu-settings" class="ui-btn ui-btn-light-border ui-btn-icon-dots ui-btn-themes"
							title="&hellip;"
							onclick=""
						></button><?

					?></span><?
				}

			?></div>
		</div>
		<div class="profile-menu-bottom">
			<div class="profile-menu-items-new"><?

				$menuItems = [];

				foreach ($arResult["CanView"] as $key => $val)
				{
					if (!$val || $key === "content_search")
					{
						continue;
					}

					if ($key === 'general')
					{
						$menuItems[] = [
							'TEXT' => Loc::getMessage('SONET_UM_NEWS'),
							'URL' => ($arResult['Urls']['General'] ?: $arResult['Urls']['View']),
							'ID' => 'general',
							'IS_ACTIVE' => in_array($arParams['PAGE_ID'], ['group', 'group_general'], true),
						];
					}
					else
					{
						$isDisabled = false;
						if (
							!in_array($key, ['general', 'tasks', 'calendar', 'files'], true)
						)
						{
							$isDisabled = true;
						}

						$item = [
							"TEXT" => $arResult["Title"][$key],
							"ID" => $key,
							"IS_ACTIVE" => ($arParams["PAGE_ID"] === "group_{$key}"),
							"IS_DISABLED" => $isDisabled,
						];

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

						if ($key !== 'tasks')
						{
							$menuItems[] = $item;
							continue;
						}

						// tasks by role
						$isActive = ($arParams["PAGE_ID"] === "group_{$key}");
						$defaultRoleId = $arResult['Tasks']['DefaultRoleId'];

						$item['URL'] = (new \Bitrix\Main\Web\Uri($arResult["Urls"][$key]))->addParams([
							'F_CANCEL' => 'Y',
							'F_STATE' => 'sR',
						])->getUri();
						$item['IS_ACTIVE'] = ($isActive && ($defaultRoleId === 'view_all' || !$defaultRoleId));
						$item['CLASS'] = 'tasks_role_link';
						$item['ID'] = 'view_all';
						$item['COUNTER'] = $arResult['Tasks']['Counters']['view_all'];
						$menuItems[] = $item;

						$defaultRoleId = $arResult['Tasks']['DefaultRoleId'];

						$menuItems[] = [
							'TEXT' => GetMessage("SONET_TASKS_PRESET_I_DO"),
							'URL' => (new \Bitrix\Main\Web\Uri($arResult["Urls"][$key]))->addParams([
								'F_CANCEL' => 'Y',
								'F_STATE' => 'sR400',
								'clear_filter' => 'Y',
							])->getUri(),
							'ID' => 'view_role_responsible',
							'CLASS' => 'tasks_role_link',
							'IS_ACTIVE' => ($isActive && $defaultRoleId === 'view_role_responsible'),
							'PARENT_ITEM_ID' => 'view_all',
							'COUNTER' => $arResult['Tasks']['Counters']['view_role_responsible'],
						];
						$menuItems[] = [
							'TEXT' => GetMessage("SONET_TASKS_PRESET_I_ACCOMPLICES"),
							'URL' => (new \Bitrix\Main\Web\Uri($arResult["Urls"][$key]))->addParams([
								'F_CANCEL' => 'Y',
								'F_STATE' => 'sR800',
								'clear_filter' => 'Y',
							])->getUri(),
							'ID' => 'view_role_accomplice',
							'CLASS' => 'tasks_role_link',
							'IS_ACTIVE' => ($isActive && $defaultRoleId === 'view_role_accomplice'),
							'PARENT_ITEM_ID' => 'view_all',
							'COUNTER' => $arResult['Tasks']['Counters']['view_role_accomplice'],
						];
						$menuItems[] = [
							'TEXT' => GetMessage("SONET_TASKS_PRESET_I_ORIGINATOR"),
							'URL' => (new \Bitrix\Main\Web\Uri($arResult["Urls"][$key]))->addParams([
								'F_CANCEL' => 'Y',
								'F_STATE' => 'sRg00',
								'clear_filter' => 'Y',
							])->getUri(),
							'ID' => 'view_role_originator',
							'CLASS' => 'tasks_role_link',
							'IS_ACTIVE' => ($isActive && $defaultRoleId === 'view_role_originator'),
							'PARENT_ITEM_ID' => 'view_all',
							'COUNTER' => $arResult['Tasks']['Counters']['view_role_originator'],
						];
						$menuItems[] = [
							'TEXT' => GetMessage("SONET_TASKS_PRESET_I_AUDITOR"),
							'URL' => (new \Bitrix\Main\Web\Uri($arResult["Urls"][$key]))->addParams([
								'F_CANCEL' => 'Y',
								'F_STATE' => 'sRc00',
								'clear_filter' => 'Y',
							])->getUri(),
							'ID' => 'view_role_auditor',
							'CLASS' => 'tasks_role_link',
							'IS_ACTIVE' => ($isActive && $defaultRoleId === 'view_role_auditor'),
							'PARENT_ITEM_ID' => 'view_all',
							'COUNTER' => $arResult['Tasks']['Counters']['view_role_auditor'],
						];
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
</div><?

if (!$arResult['inIframe'] || $arResult['IS_CURRENT_PAGE_FIRST'])
{
	$this->EndViewTarget();
}
