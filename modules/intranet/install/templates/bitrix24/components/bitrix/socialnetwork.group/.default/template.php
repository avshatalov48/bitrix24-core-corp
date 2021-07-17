<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$component = $this->getComponent();

use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\UI;

if ($arResult["FatalError"] <> '')
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:socialnetwork.entity.error',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'ENTITY' => 'SONET_GROUP',
			],
		]
	);

	return;
}

CUtil::InitJSCore(array("ajax", "tooltip"));
UI\Extension::load([
	'ui.buttons',
	'ui.alerts',
	'ui.icons.b24',
	'socialnetwork.common'
]);

$frameMode = (\Bitrix\Main\Context::getCurrent()->getRequest()->getQuery('IFRAME') === 'Y');

if ($arResult["ErrorMessage"] <> '')
{
	?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br/><br/><?
}

if ($arResult["bShowRequestSentMessage"] === "U")
{
	?><div class="socialnetwork-group-join-request-sent">
		<?=GetMessage("SONET_C6_ACT_JOIN_REQUEST_SENT")?>
	</div><?
}
elseif ($arResult["bShowRequestSentMessage"] === "G")
{
	?><div class="socialnetwork-group-join-request-sent">
		<?=str_replace(
			"#LINK#",
			str_replace(
				"#user_id#",
				$USER->GetID(),
				COption::GetOptionString("socialnetwork", "user_request_page", (IsModuleInstalled("intranet")) ? "/company/personal/user/#user_id#/requests/" : "/club/user/#user_id#/requests/", SITE_ID)
			),
			GetMessage("SONET_C6_ACT_JOIN_REQUEST_SENT_BY_GROUP")
	)?></div><?
}
elseif ($arResult["bUserCanRequestGroup"])
{
	?><script>
		BX.message({
			SONET_C6_T_AJAX_ERROR: '<?=GetMessageJS('SONET_C6_T_AJAX_ERROR')?>'
		});
	</script><?

	?><div class="socialnetwork-group-description-wrapper">
	<table width="100%" cellspacing="0" id="bx_group_description" class="socialnetwork-group-description-table<?if (!$arResult["bDescriptionOpen"]):?> socialnetwork-group-description-hide-table<?endif?>">
		<tr>
			<td valign="top">
				<table width="100%" cellspacing="0"><?
					if ($arResult["Group"]["CLOSED"] === "Y")
					{
						?><tr>
							<td colspan="2" class="socialnetwork-group-description"><b><?=GetMessage("SONET_C39_ARCHIVE_GROUP")?></b></td>
						</tr><?
					}

					if ($arResult["Group"]["DESCRIPTION"] <> '')
					{
						?><tr class="ext-header-center-row">
							<td class="socialnetwork-group-description-left-col"><?=GetMessage("SONET_C6_DESCR")?>:</td>
							<td class="socialnetwork-group-description"><?=nl2br($arResult["Group"]["DESCRIPTION"])?></td>
						</tr><?
					}

					if ($arResult["GroupProperties"]["SHOW"] === "Y")
					{
						foreach ($arResult["GroupProperties"]["DATA"] as $fieldName => $arUserField)
						{
							if (
								(
									is_array($arUserField["VALUE"])
									&& !empty($arUserField["VALUE"])
								)
								|| (
									!is_array($arUserField["VALUE"])
									&& $arUserField["VALUE"] <> ''
								)
							)
							{
								?><tr class="ext-header-center-row">
									<td class="socialnetwork-group-description-left-col"><?=$arUserField["EDIT_FORM_LABEL"]?>:</td>
									<td class="socialnetwork-group-description"><?
										$APPLICATION->IncludeComponent(
											"bitrix:system.field.view",
											$arUserField["USER_TYPE"]["USER_TYPE_ID"],
											array("arUserField" => $arUserField),
											null,
											array("HIDE_ICONS"=>"Y")
										);
									?></td>
								</tr><?
							}
						}
					}

					?><tr>
						<td class="socialnetwork-group-description-left-col"><nobr><?= Loc::getMessage($arResult['Group']['PROJECT'] === 'Y' ? "SONET_C6_TYPE_PROJECT" : "SONET_C6_TYPE") ?>:</nobr></td>
						<td class="socialnetwork-group-description"><?

							?><?= $arResult['Group']['Type']['NAME'] ?><br /><?;
							?><?= ($arResult['Group']['Type']['DESCRIPTION']) ?><br /><?

							$joinButtonTitle = (
								$arResult['Group']['OPENED'] === 'Y'
									? Loc::getMessage($arResult['Group']['PROJECT'] === 'Y' ? "SONET_C6_ACT_JOIN_PROJECT" : "SONET_C6_ACT_JOIN")
									: Loc::getMessage($arResult['Group']['PROJECT'] === 'Y' ? "SONET_C6_ACT_JOIN2_PROJECT" : "SONET_C6_ACT_JOIN2")
							);

							?><div id="bx-group-join-form" class="sonet-group-user-request-form"><?
								?><div id="bx-group-join-error" class="sonet-ui-form-error-block-invisible ui-alert ui-alert-danger"></div><?

								if ($arResult['Group']['OPENED'] !== 'Y')
								{
									?><div class="sonet-group-user-request-join-message-cont">
										<textarea class="sonet-group-user-request-join-message-text" id="bx-group-join-message"></textarea>
									</div><?
								}

								?><span class="sonet-ui-btn-cont"><?
									?><button class="ui-btn ui-btn-success" id="bx-group-join-submit" bx-request-url="<?=$arResult["Urls"]["UserRequestGroup"]?>"><?=$joinButtonTitle?></button><?
									?><a class="ui-btn ui-btn-light-border" href="<?=$arResult["Urls"]["GroupsList"]?>"><?= Loc::getMessage("SONET_C6_ACT_RETURN_TO_LIST")?></a><?
								?></span>

							</div><?

						?></td>
					</tr><?
				?></table>
			</td>
		</tr>
	</table>
	</div><?
}
?>

	<div class="sonet-group-log">
		<div id="log_external_container"></div>
		<?
		if (
			!empty($arResult["ActiveFeatures"])
			&& array_key_exists('blog', $arResult["ActiveFeatures"])
		)
		{
			$APPLICATION->IncludeComponent(
				"bitrix:socialnetwork.log.ex",
				"",
				[
					"ENTITY_TYPE" => "",
					"GROUP_ID" => $arParams["GROUP_ID"],
					"USER_VAR" => $arParams["VARIABLE_ALIASES"]["user_id"],
					"GROUP_VAR" => $arParams["VARIABLE_ALIASES"]["group_id"],
					"PATH_TO_USER" => $arParams["PATH_TO_USER"],
					"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
					'SET_TITLE' => 'Y',
					"AUTH" => "Y",
					"SET_NAV_CHAIN" => "N",
					"PATH_TO_MESSAGES_CHAT" => $arParams["PM_URL"],
					"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
					"PATH_TO_USER_BLOG_POST_IMPORTANT" => $arParams["PATH_TO_USER_BLOG_POST_IMPORTANT"],
					"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
					"PATH_TO_GROUP_PHOTO_SECTION" => $arParams["PARENT_COMPONENT_RESULT"]["PATH_TO_GROUP_PHOTO_SECTION"],
					"PATH_TO_SEARCH_TAG" => $arParams["PATH_TO_SEARCH_TAG"],
					"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
					"SHOW_YEAR" => $arParams["SHOW_YEAR"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					"SUBSCRIBE_ONLY" => "N",
					"SHOW_EVENT_ID_FILTER" => "Y",
					"SHOW_FOLLOW_FILTER" => "N",
					"USE_COMMENTS" => "Y",
					"PHOTO_THUMBNAIL_SIZE" => "48",
					"PAGE_ISDESC" => "N",
					"AJAX_MODE" => "N",
					"AJAX_OPTION_SHADOW" => "N",
					"AJAX_OPTION_HISTORY" => "N",
					"AJAX_OPTION_JUMP" => "N",
					"AJAX_OPTION_STYLE" => "Y",
					"CONTAINER_ID" => "log_external_container",
					"PAGE_SIZE" => 10,
					"SHOW_RATING" => $arParams["SHOW_RATING"],
					"RATING_TYPE" => $arParams["RATING_TYPE"],
					"SHOW_SETTINGS_LINK" => "Y",
					"AVATAR_SIZE" => $arParams["LOG_THUMBNAIL_SIZE"],
					"AVATAR_SIZE_COMMENT" => $arParams["LOG_COMMENT_THUMBNAIL_SIZE"],
					"NEW_TEMPLATE" => $arParams["LOG_NEW_TEMPLATE"],
					"SET_LOG_CACHE" => "Y",
				],
				$component,
				[ 'HIDE_ICONS' => 'Y' ]
			);
		}
		?>
	</div>

<?

if (
	$_SERVER['REQUEST_METHOD'] === "POST"
	&& $_REQUEST['BLOCK_RELOAD'] === 'Y'
	&& $_REQUEST['BLOCK_ID'] === 'socialnetwork-group-sidebar-block'
)
{
	ob_end_clean();
	$APPLICATION->RestartBuffer();

	include("sidebar.php");

	$strText = ob_get_clean();
	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo \Bitrix\Main\Web\Json::encode(array(
		"CONTENT" => $strText,
	));

	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	exit;
}

if (!$frameMode)
{
	$this->SetViewTarget("sidebar", 50);
	include("sidebar.php");
	$this->EndViewTarget();
}

$this->SetViewTarget("pagetitle", 1000);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."pagetitle-menu-visible");

include("title_buttons.php");
$this->EndViewTarget();
