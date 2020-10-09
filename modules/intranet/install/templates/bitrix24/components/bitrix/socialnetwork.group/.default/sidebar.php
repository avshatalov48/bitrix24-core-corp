<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

global $DB, $USER;

use Bitrix\Main\Localization\Loc;

if ($_REQUEST['BLOCK_RELOAD'] != 'Y')
{
	?><div id="socialnetwork-group-sidebar-block"><?
}

?><div class="socialnetwork-group-sidebar-block">
	<div class="socialnetwork-group-sidebar-block-inner">
	<? if ($arResult["CanView"]["content_search"]):?>
		<div class="socialnetwork-group-search">
			<div class="socialnetwork-group-search-item">
				<form class="socialnetwork-group-search-form" action="<?=$arResult["Urls"]["content_search"]?>">
					<input
						class="socialnetwork-group-search-field"
						type="text"
						name="q"
						placeholder="<?=GetMessage($arResult['Group']['PROJECT'] == 'Y' ? "SONET_UM_SEARCH_BUTTON_TITLE_PROJECT" : "SONET_UM_SEARCH_BUTTON_TITLE")?>"
					>
					<span class="socialnetwork-group-search-icon"></span>
				</form>
			</div>
		</div>
	<? endif ?>
	
	<? if ($arResult["Owner"]):
		$userName = \CUser::FormatName(
			str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]),
			array(
				"ID" => $arResult["Owner"]["USER_ID"],
				"NAME" => htmlspecialcharsback($arResult["Owner"]["USER_NAME"]),
				"LAST_NAME" => htmlspecialcharsback($arResult["Owner"]["USER_LAST_NAME"]),
				"SECOND_NAME" => htmlspecialcharsback($arResult["Owner"]["USER_SECOND_NAME"]),
				"LOGIN" => htmlspecialcharsback($arResult["Owner"]["USER_LOGIN"])
			),
			$arParams["SHOW_LOGIN"] != "N"
		);
		
		$avatar = $arResult["Owner"]["USER_PERSONAL_PHOTO_FILE"]["SRC"]; 
		?>
		<div class="socialnetwork-group-users">
			<div class="socialnetwork-group-users-inner socialnetwork-group-owner">
				<div class="socialnetwork-group-title"><?=GetMessage($arResult['Group']['PROJECT'] == 'Y' ? "SONET_C6_OWNERN_PROJECT" : "SONET_C6_OWNERN")?></div>
				<div class="socialnetwork-group-users-list">
					<div class="socialnetwork-group-user socialnetwork-group-owner-user">
						<a class="ui-icon ui-icon-common-user socialnetwork-group-user-avatar user-default-avatar" href="<?=htmlspecialcharsback($arResult["Owner"]["USER_PROFILE_URL"])?>">
							<i <? if ($avatar):?>
								style="background: url('<?=$avatar?>'); background-size: cover"
							<? endif ?>></i>
						</a>
						<div class="socialnetwork-group-user-info">
							<div class="socialnetwork-group-user-name
								<?=($arResult["Owner"]["USER_IS_EXTRANET"] == "Y" ?
								" socialnetwork-group-user-name-extranet" : "")?>">
								<a href="<?=htmlspecialcharsback($arResult["Owner"]["USER_PROFILE_URL"])?>"><?=$userName?></a>
							</div>
							<div class="socialnetwork-group-user-position"><?
								if (IsModuleInstalled("intranet") && $arResult["Owner"]["USER_WORK_POSITION"] <> ''):
									?><?=$arResult["Owner"]["USER_WORK_POSITION"]?><?
								elseif ($arResult["Owner"]["USER_IS_EXTRANET"] == "Y"):
									?><?=GetMessage("SONET_C6_USER_IS_EXTRANET")?><?
								else:
									?>&nbsp;<?
								endif;
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?endif; ?>
	<table cellspacing="0" class="socialnetwork-group-layout">
		<tr class="socialnetwork-group-layout-row">
			<td class="socialnetwork-group-layout-left-column"><?=GetMessage("SONET_C6_CREATED")?>:</td>
			<td class="socialnetwork-group-layout-right-column"><?
				echo \CComponentUtil::getDateTimeFormatted([
					'TIMESTAMP' => MakeTimeStamp($arResult["Group"]["DATE_CREATE"]),
					'TZ_OFFSET' => \CTimeZone::getOffset()
				]);
			?>
			</td>
		</tr>
		<tr class="socialnetwork-group-layout-row">
			<td class="socialnetwork-group-layout-left-column"><?=GetMessage("SONET_C6_NMEM")?>:</td>
			<td class="socialnetwork-group-layout-right-column"><?=$arResult["Group"]["NUMBER_OF_MEMBERS"]?></td>
		</tr>
		<tr class="socialnetwork-group-layout-row">
			<td class="socialnetwork-group-layout-left-column"><?=Loc::getMessage($arResult['Group']['PROJECT'] == 'Y' ? "SONET_C6_TYPE_PROJECT" : "SONET_C6_TYPE")?>:</td>
			<td class="socialnetwork-group-layout-right-column"><?=$arResult['Group']['Type']['NAME']?></td>
		</tr><?

		if ($arResult['Group']['PROJECT'] == 'Y')
		{
			if (!empty($arResult['Group']['PROJECT_DATE_START']))
			{
				?><tr class="socialnetwork-group-layout-row">
					<td class="socialnetwork-group-layout-left-column"><?=Loc::getMessage("SONET_C6_PROJECT_DATE_START")?>:</td>
					<td class="socialnetwork-group-layout-right-column"><?=FormatDateFromDB($arResult["Group"]["PROJECT_DATE_START"], $arParams["DATE_FORMAT"], true)?></td>
				</tr><?
			}
			if (!empty($arResult['Group']['PROJECT_DATE_FINISH']))
			{
				?><tr class="socialnetwork-group-layout-row">
				<td class="socialnetwork-group-layout-left-column"><?=Loc::getMessage("SONET_C6_PROJECT_DATE_FINISH")?>:</td>
				<td class="socialnetwork-group-layout-right-column"><?=FormatDateFromDB($arResult["Group"]["PROJECT_DATE_FINISH"], $arParams["DATE_FORMAT"], true)?></td>
				</tr><?
			}
		}

		if (!empty($arResult["GroupDepartments"]))
		{
			?><tr class="socialnetwork-group-layout-row">
				<td class="socialnetwork-group-layout-left-column"><?=GetMessage("SONET_C6_DEPARTMENTS")?>:</td>
				<td class="socialnetwork-group-layout-right-column"><?
					$arDepartmentFormatted = array();
					foreach($arResult["GroupDepartments"] as $arDepartment)
					{
						$arDepartmentFormatted[] = '<a href="'.$arDepartment["URL"].'">'.htmlspecialcharsEx($arDepartment["NAME"]).'</a>';
					}
					echo implode(', ', $arDepartmentFormatted);
				?></td>
			</tr><?
		}

		if ($arResult["GroupProperties"]["SHOW"] === "Y")
		{
			foreach ($arResult["GroupProperties"]["DATA"] as $fieldName => $arUserField)
			{
				if (
					(is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0) ||
					(!is_array($arUserField["VALUE"]) && $arUserField["VALUE"] <> '')
				)
				{
					?><tr class="socialnetwork-group-layout-row">
						<td class="socialnetwork-group-layout-left-column"><?=$arUserField["EDIT_FORM_LABEL"]?>:</td>
						<td class="socialnetwork-group-layout-right-column"><?
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

		if (
			is_array($arResult["Group"]["KEYWORDS_LIST"])
			&& !empty($arResult["Group"]["KEYWORDS_LIST"])
		)
		{
			?><tr class="socialnetwork-group-layout-row">
				<td class="socialnetwork-group-layout-left-column"><?=Loc::getMessage("SONET_C6_TAGS")?>:</td>
				<td class="socialnetwork-group-layout-right-column">
					<div class="socialnetwork-group-sidebar-tag-box"><?
					foreach($arResult["Group"]["KEYWORDS_LIST"] as $keyword)
					{
						?><a bx-tag-value="<?=$keyword?>" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_TAG"], array('tag' => $keyword));?>" class="socialnetwork-group-sidebar-tag"><?=$keyword?></a><?
					}
					?></div>
				</td>
			</tr><?
		}

	?></table><?

	if (
		$arResult["Group"]["DESCRIPTION"] <> '' &&
		$arResult["Group"]["DESCRIPTION"] !== GetMessage("SONET_GCE_T_DESCR") &&
		!$arResult["bUserCanRequestGroup"]
	)
	{
		$desc = $arResult["Group"]["DESCRIPTION"];
		$descEnding = "";
		$maxLength = 250;
		if (mb_strlen($desc) > $maxLength)
		{
			$descEnding = mb_substr($desc, $maxLength);
			$desc = mb_substr($desc, 0, $maxLength);
		}

		?><div class="socialnetwork-group-desc-box">
			<div class="socialnetwork-group-title"><?=GetMessage("SONET_C6_DESCR")?></div>
			<div class="socialnetwork-group-desc-text"><?
				echo $desc;
				if ($descEnding <> ''):
					?><span class="socialnetwork-group-desc-more">...
						<span
							class="socialnetwork-group-desc-more-link"
							onclick="BX.addClass(this.parentNode.parentNode, 'socialnetwork-group-desc-open')"
						>
							<?=GetMessage("SONET_C6_MORE")?>
						</span>
					</span><span class="socialnetwork-group-desc-full"><?=$descEnding?></span>
				<? endif ?>
			</div>
		</div><?
	}

	if ($arResult["Moderators"]["List"]):
		$itemsLimit = 3;
		?>
		<div class="socialnetwork-group-users socialnetwork-group-moderator">
			<div class="socialnetwork-group-users-inner">
				<div class="socialnetwork-group-title"><?=GetMessage($arResult['Group']['PROJECT'] == 'Y' ? "SONET_C6_ACT_MODN_PROJECT" : "SONET_C6_ACT_MODN")?>
					<? if (count($arResult["Moderators"]["List"]) > $itemsLimit):?>
						(<a
							href="<?=htmlspecialcharsback($arResult["Urls"]["GroupMods"])?>"><?
							echo $arResult["Group"]["NUMBER_OF_MODERATORS"]
							?></a>)
					<? endif ?>

					<? if (
						$arResult["CurrentUserPerms"]["UserCanModifyGroup"] &&
						$USER->IsAuthorized() &&
						!$arResult["HideArchiveLinks"]
					):?>
						<a
							class="socialnetwork-group-title-link dashed"
							href="<?=htmlspecialcharsback($arResult["Urls"]["GroupMods"])?>"><?
								echo GetMessage("SONET_C6_ACT_MODN_ACTION")?>
						</a>
					<? endif ?>
				</div>
				<div class="socialnetwork-group-users-list">
					<? foreach ($arResult["Moderators"]["List"] as $friend):

						if (!$itemsLimit--)
						{
							break;
						}

						$userName = CUser::FormatName(
							str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]),
							array(
								"ID" => $friend["USER_ID"],
								"NAME" => htmlspecialcharsback($friend["USER_NAME"]),
								"LAST_NAME" => htmlspecialcharsback($friend["USER_LAST_NAME"]),
								"SECOND_NAME" => htmlspecialcharsback($friend["USER_SECOND_NAME"]),
								"LOGIN" => htmlspecialcharsback($friend["USER_LOGIN"])
							),
							$arParams["SHOW_LOGIN"] != "N"
						);

						$avatar = $friend["USER_PERSONAL_PHOTO_FILE"]["SRC"];
					?>
						<div class="socialnetwork-group-user">
							<a
								class="socialnetwork-group-user-avatar user-default-avatar"
								href="<?=htmlspecialcharsback($friend["USER_PROFILE_URL"])?>"
								<? if ($avatar): ?>
									style="background: url('<?=$avatar?>'); background-size: cover"
								<? endif ?>
							>
							</a>
							<div class="socialnetwork-group-user-info">
								<div class="socialnetwork-group-user-name
									<?=($friend["USER_IS_EXTRANET"] === "Y" ?
									" socialnetwork-group-user-name-extranet" : "")?>
								">
									<a href="<?=htmlspecialcharsback($friend["USER_PROFILE_URL"])?>"><?=$userName?></a>
								</div>
								<div class="socialnetwork-group-user-position"><?
									if (IsModuleInstalled("intranet") && $friend["USER_WORK_POSITION"] <> ''):
										?><?=$friend["USER_WORK_POSITION"]?><?
									elseif ($friend["USER_IS_EXTRANET"] == "Y"):
										?><?=GetMessage("SONET_C6_USER_IS_EXTRANET")?><?
									else:
										?>&nbsp;<?
									endif;
									?>
								</div>
							</div>
						</div>
					<? endforeach ?>
				</div>
			</div>
		</div><?
	endif;

	if ($arResult["Members"]["List"]): ?>
		<div class="socialnetwork-group-users socialnetwork-group-member">
			<div class="socialnetwork-group-users-inner">
				<div class="socialnetwork-group-title">
					<?=GetMessage("SONET_C6_ACT_USER1")?>
					(<a
						href="<?=htmlspecialcharsback($arResult["Urls"]["GroupUsers"])?>"><?
							echo $arResult["Group"]["NUMBER_OF_MEMBERS"]
					?></a>)<?

					if (
						$USER->IsAuthorized() &&
						$arResult["CurrentUserPerms"]["UserCanInitiate"] &&
						!$arResult["HideArchiveLinks"]
					)
					{
						?><a
							class="socialnetwork-group-title-link dashed"
							href="<?=htmlspecialcharsbx(
									!empty($arResult["Urls"]["Invite"])
										? $arResult["Urls"]["Invite"]
										: $arResult["Urls"]["Edit"].(mb_strpos($arResult["Urls"]["Edit"], "?") !== false ? "&" : '?')."tab=invite"
							)?>"
							><?=Loc::getMessage($arResult['Group']['PROJECT'] == 'Y' ? "SONET_C6_ACT_REQU_PROJECT" : "SONET_C6_ACT_REQU")?></a><?
					}
				?></div>
				<div class="socialnetwork-group-users-list"><?
				foreach ($arResult["Members"]["List"] as $friend):

					$userName = CUser::FormatName(
						str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]),
						array(
							"ID" => $friend["USER_ID"],
							"NAME" => htmlspecialcharsback($friend["USER_NAME"]),
							"LAST_NAME" => htmlspecialcharsback($friend["USER_LAST_NAME"]),
							"SECOND_NAME" => htmlspecialcharsback($friend["USER_SECOND_NAME"]),
							"LOGIN" => htmlspecialcharsback($friend["USER_LOGIN"])
						),
						$arParams["SHOW_LOGIN"] != "N"
					);

					$avatar = $friend["USER_PERSONAL_PHOTO_FILE"]["SRC"];
					?>
					<div
						class="socialnetwork-group-user socialnetwork-group-member-user
						<?=($friend["USER_IS_EXTRANET"] === "Y" ? "socialnetwork-group-extranet-user" : "")?>">
						<a href="<?=htmlspecialcharsback($friend["USER_PROFILE_URL"])?>" class="ui-icon ui-icon-common-user socialnetwork-group-member-avatar" title="<?=$userName?>">
							<i <? if ($avatar): ?>
								style="background: url('<?=$avatar?>'); background-size: cover"
							<? endif ?>></i>
						</a>
					</div><?
					endforeach;
					?>
				</div>
			</div>
		</div><?
	endif ?>
	</div>
</div>

<?
if (
	!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
	&& $arParams["SHOW_SEARCH_TAGS_CLOUD"] == 'Y'
)
{
	ob_start();

	global $arContentFilter;
	$arContentFilter = array(
		"!ITEM_ID" => "G".$arParams["GROUP_ID"],
		"PARAMS" => array("socnet_group" => $arParams["GROUP_ID"])
	);

	$tagsCnt = $APPLICATION->IncludeComponent(
		"bitrix:search.tags.cloud",
		"",
		Array(
			"PAGE_ELEMENTS" => $arParams["SEARCH_TAGS_PAGE_ELEMENTS"],
			"PERIOD" => $arParams["SEARCH_TAGS_PERIOD"],
			"URL_SEARCH" =>
				CComponentEngine::MakePathFromTemplate(
					$arParams["~PATH_TO_GROUP_CONTENT_SEARCH"],
					array("group_id" => $arParams["GROUP_ID"])
				),
			"FONT_MAX" => 30,
			"FONT_MIN" => 12,
			"COLOR_NEW" => $arParams["SEARCH_TAGS_COLOR_NEW"],
			"COLOR_OLD" => $arParams["SEARCH_TAGS_COLOR_NEW"],
			"WIDTH" => "100%",
			"SORT" => "NAME",
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"FILTER_NAME" => "arContentFilter",
		),
		false,
		array("HIDE_ICONS" => "Y")
	);

	$tagsCloud = "";
	if ($tagsCnt > 0)
	{
		$tagsCloud = ob_get_contents();
	}

	ob_end_clean();

	if ($tagsCloud <> '')
	{
		?><div class="socialnetwork-group-sidebar-block" style="margin-top: 10px;">
			<div class="socialnetwork-group-sidebar-block-inner"><?=$tagsCloud?></div>
		</div><?
	}
}

if ($_REQUEST['BLOCK_RELOAD'] != 'Y')
{
	?></div><?
}
?>
