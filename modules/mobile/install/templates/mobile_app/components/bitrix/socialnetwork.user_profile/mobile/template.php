<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "employee-card");
$GLOBALS["APPLICATION"]->AddHeadString('<link href="' . CUtil::GetAdditionalFileURL($templateFolder . "/style.css") . '" type="text / css" rel="stylesheet" />');

if (!empty($arResult["FatalError"]))
{
	echo $arResult["FatalError"];
	return;
}

global $USER;
$arUser = $arResult["User"];
$userJson = CUtil::PhpToJSObject(array_change_key_case($arUser, CASE_LOWER));

$userColor = '#404f5d';
if (CModule::IncludeModule('im'))
{
	$userColor = Bitrix\Im\Color::getColorByNumber($arUser['ID']);
	$arOnline = CIMStatus::GetList(Array('ID' => $arUser['ID']));
	if (isset($arOnline['users'][$arUser['ID']]['color']))
	{
		$userColor = $arOnline['users'][$arUser['ID']]['color'];
	}
}
?><script type="text/javascript">

	BX.message(<?=CUtil::PhpToJSObject(array(
		"SONET_MESSAGE" => GetMessage("SONET_MESSAGE"),
		"SONET_AUDIO_CALL" => GetMessage("SONET_AUDIO_CALL"),
		"SONET_VIDEO_CALL" => GetMessage("SONET_VIDEO_CALL"),
		"SONET_TITLE" => GetMessage("SONET_TITLE"),
		"MB_CALL" => GetMessage("MB_CALL"),
		"STATUS_ONLINE" => GetMessage("STATUS_ONLINE"),
		"STATUS_OFFLINE" => GetMessage("STATUS_OFFLINE"),
		"MB_CANCEL" => GetMessage("MB_CANCEL"),
		"PULL_TEXT" => GetMessage("PULL_TEXT"),
		"DOWN_TEXT" => GetMessage("DOWN_TEXT"),
		"LOAD_TEXT" => GetMessage("LOAD_TEXT"),
		"USER_LF" => GetMessage("MB_LF_AT_SOCNET_USER_CPT_MENU_ITEM_LIST"),
		"USER_TASKS" => GetMessage("MB_TASKS_AT_SOCNET_USER_CPT_MENU_ITEM_LIST"),
		"USER_FILES" => GetMessage("MB_FILES_AT_SOCNET_USER_CPT_MENU_ITEM_LIST"),
		"REINVITE" => GetMessage("MB_REINVITE_USER_CPT_MENU_ITEM_LIST"),
		"INVITE_MESSAGE" => GetMessage("MB_INVITE_MESSAGE"),
	))?>);

	BX.Mobile.Profile.init({
		isWebRTCSupported: <?=(CMobile::getInstance()->isWebRtcSupported() ? "true" : "false")?>,
		userPhotoUrl: <?=$arResult["USER_PERSONAL_PHOTO_SRC"] ? "\"".CUtil::JSEscape($arResult["USER_PERSONAL_PHOTO_SRC"]["src"])."\"" : "false" ?>,
		pullDown: {
			enable: true,
			pulltext: BX.message("PULL_TEXT"),
			downtext: BX.message("DOWN_TEXT"),
			loadtext: BX.message("LOAD_TEXT")
		},
		user: <?=$userJson?>,
		menu: [
			{
				name: BX.message("USER_LF"),
				image: "/bitrix/templates/mobile_app/images/lenta/menu/blog.png",
				action: function () {

					app.loadPageBlank({
						url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?blog=Y&created_by_id=<?php echo (int) $arResult["User"]['ID']; ?>",
						cache: false,
						bx24ModernStyle: true
					});

				}
			},
			{
				name: BX.message("USER_TASKS"),
				icon: "check",
				action: function () {
					var path = '<?=CUtil::JSEscape($arParams['PATH_TO_TASKS_SNM_ROUTER'])?>';
					path = path
						.replace('__ROUTE_PAGE__', 'roles')
						.replace('#USER_ID#', <?php echo (int) $arResult["User"]['ID']; ?>);

					BXMobileApp.PageManager.loadPageUnique({url: path, bx24ModernStyle: true});
				}
			},
			{
				name: BX.message("USER_FILES"),
				icon: "file",
				action: function () {
					app.openBXTable({
						url: '<?=CUtil::JSEscape($arResult["PATH_TO_FILES"])?>',
						table_settings: {
							type: "files",
							useTagsInSearch: "NO"
						}
					});
				}
			},
			<?if (IsModuleInstalled("bitrix24") && $USER->CanDoOperation('bitrix24_invite') && $arResult["User"]["ACTIVITY_STATUS"] == "invited") :?>
			{
				name: BX.message("REINVITE"),
				icon: "adduser",
				action: function ()
				{
					app.showPopupLoader({test: ""});
					BX.ajax.post(
						"<?=SITE_DIR?>mobile/users/invite.php",
						{
							user_id: "<?=$arUser["ID"]?>",
							reinvite: "Y",
							sessid: BX.bitrix_sessid()
						},
						function (result)
						{
							app.hidePopupLoader();
							app.alert({text: BX.message("INVITE_MESSAGE")});
						}
					);
				}
			}
			<?endif?>
		]
	});
	window.pageColor = '<?=$userColor?>';
	BX.addCustomEvent("onOpenPageAfter", function(){
		app.exec("setTopBarColors",{background: window.pageColor, titleText:"#ffffff", titleDetailText:"#f0f0f0"});
	});
	app.exec("setTopBarColors",{background: window.pageColor, titleText:"#ffffff", titleDetailText:"#f0f0f0"});
</script>

<div class="emp-profile" id="emp-profile" style="background-color: <?=$userColor?>; <?if ($arResult["USER_PERSONAL_PHOTO_SRC"]): ?>background-image: url('<?= $arResult["USER_PERSONAL_PHOTO_SRC"]["src"] ?>'); background-size: auto 100%; background-position: center;<? endif ?>">
	<div class="emp-profile-info">
		<div class="emp-profile-name"><?=$arResult["USER_FULL_NAME"]?></div>
		<div class="emp-profile-position"><?=$arUser["WORK_POSITION"]?></div>
	</div>

	<div class="emp-profile-buttons" id="emp-profile-buttons"></div>
	<div class="emp-profile-status" id="emp-profile-status"></div>
</div>

<div class="emp-info-block">
	<div class="emp-info-title"><?= GetMessage("SONET_CONTACT_TITLE") ?></div><?

	//Contact INFO
	if (is_array($arResult["UserFieldsContact"]["DATA"]))
	{
		foreach ($arResult["UserFieldsContact"]["DATA"] as $field => $arUserField)
		{
			if (
				(
					is_array($arUserField["VALUE"])
					&& count($arUserField["VALUE"]) > 0
				)
				|| (
					!is_array($arUserField["VALUE"])
					&& StrLen($arUserField["VALUE"]) > 0
				)
			)
			{
				?><span class="emp-info-cell"><?= $arUserField["NAME"] . ":" ?></span><span class="emp-info-cell"><?
				switch ($field)
				{
					case "PERSONAL_MOBILE":
					case "WORK_PHONE":
						?><a href="javascript:" onclick="BX.MobileTools.phoneTo('<?=$arUser[$field]?>')"><?= $arUser[$field] ?></a><?
						break;
					default:
						echo $arUserField["VALUE"];
				}
				?></span><?
			}
		}
	}

	if (is_array($arResult["UserPropertiesContact"]["DATA"]))
	{
		foreach ($arResult["UserPropertiesContact"]["DATA"] as $field => $arUserField)
		{
			if (
				(
					is_array($arUserField["VALUE"])
					&& count($arUserField["VALUE"]) > 0
				)
				|| (
					!is_array($arUserField["VALUE"])
					&& StrLen($arUserField["VALUE"]) > 0
				)
			)
			{
				?><span class="emp-info-cell"><?= $arUserField["EDIT_FORM_LABEL"] . ":" ?></span><span
				class="emp-info-cell"><?
				$value = htmlspecialcharsbx($arUserField["VALUE"]);
				switch ($field)
				{
					case "UF_FACEBOOK":
					case "UF_LINKEDIN":
					case "UF_XING":
						$href = ((strpos($arUserField["VALUE"], "http") === false) ? "http://" : "") . htmlspecialcharsbx($arUserField["VALUE"]);
						$isLink = preg_match('#^https?://\w#D', $href) > 0;
						if ($isLink)
						{
							?><a href="<?= $href ?>"><?= $value ?></a><?
						}
						else
						{
							echo $value;
						}
						break;
					case "UF_TWITTER":
						?>
						<a href="http://twitter.com/<?= $value ?>"><?= $value ?></a><?
						break;
					case "UF_SKYPE":
						echo $value;
						break;
					default:
						$GLOBALS["APPLICATION"]->IncludeComponent(
							"bitrix:system.field.view",
							$arUserField["USER_TYPE"]["USER_TYPE_ID"],
							array(
								"arUserField" => $arUserField,
								"inChain" => "N",
								"MOBILE" => "Y"
							),
							null,
							array("HIDE_ICONS" => "Y")
						);
				}
				?></span><?
			}
		}
	}
?></div>
<div class="emp-info-block">
	<div class="emp-info-title"><?= GetMessage("SONET_COMMON_TITLE") ?></div><?
	//Common INFO
	if (is_array($arResult["UserFieldsMain"]["DATA"]))
	{
		foreach ($arResult["UserFieldsMain"]["DATA"] as $field => $arUserField)
		{
			if (
				(
					is_array($arUserField["VALUE"])
					&& count($arUserField["VALUE"]) > 0
				)
				|| (
					!is_array($arUserField["VALUE"])
					&& StrLen($arUserField["VALUE"]) > 0
				)
			)
			{
				?><span class="emp-info-cell"><?= $arUserField["NAME"] . ":" ?></span><span class="emp-info-cell"><?= $arUserField["VALUE"]; ?></span><?
			}
		}
	}

	if (is_array($arResult["UserPropertiesMain"]["DATA"]))
	{
		foreach ($arResult["UserPropertiesMain"]["DATA"] as $field => $arUserField)
		{
			if (
				(
					is_array($arUserField["VALUE"])
					&& count($arUserField["VALUE"]) > 0
				)
				|| (
					!is_array($arUserField["VALUE"])
					&& StrLen($arUserField["VALUE"]) > 0
				)
			)
			{
				?><span class="emp-info-cell"><?= $arUserField["EDIT_FORM_LABEL"] . ":" ?></span><span class="emp-info-cell"><?
				switch ($field)
				{
					case "UF_DEPARTMENT1":
						echo htmlspecialcharsbx($arUserField["VALUE"]);
						break;
					default:
						$bInChain = ($field == "UF_DEPARTMENT" ? "Y" : "N");
						$GLOBALS["APPLICATION"]->IncludeComponent(
							"bitrix:system.field.view",
							$arUserField["USER_TYPE"]["USER_TYPE_ID"],
							array("arUserField" => $arUserField, "inChain" => $bInChain),
							null,
							array("HIDE_ICONS" => "Y")
						);
				}
				?></span><?
			}
		}
	}
	if (
		is_array($arResult['MANAGERS'])
		&& count($arResult['MANAGERS']) > 0
	)
	{
		?><span class="emp-info-cell"><?= GetMessage("SONET_MANAGERS") . ":" ?></span><span class="emp-info-cell"><?$bFirst = true;
		foreach ($arResult['MANAGERS'] as $id => $sub_user)
		{
			if (!$bFirst)
			{
				echo ', ';
			}
			else
			{
				$bFirst = false;
			}
			$name = CUser::FormatName($arParams['NAME_TEMPLATE'], $sub_user, true, false);
			?><a class="user-profile-link" href="<?= CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER'], array("user_id" => $sub_user["ID"])) ?>"><?= $name ?></a><?
		}
		?></span><?
	}

	if (
		is_array($arResult['SUBORDINATE'])
		&& count($arResult['SUBORDINATE']) > 0
	)
	{
		?><span class="emp-info-cell"><?= GetMessage("SONET_SUBORDINATE") . ":" ?></span><span
		class="emp-info-cell"><?
		$bFirst = true;
		foreach ($arResult['SUBORDINATE'] as $id => $sub_user)
		{
			if (!$bFirst)
			{
				echo ', ';
			}
			else
			{
				$bFirst = false;
			}
			$name = CUser::FormatName($arParams['NAME_TEMPLATE'], $sub_user, true, false);
			?><a class="user-profile-link" href="<?= CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER'], array("user_id" => $sub_user["ID"])) ?>"><?= $name ?></a><?
		}
		?></span><?
	}

	if ($arResult["User"]["ACTIVITY_STATUS"] != "active")
	{
		?><span class="emp-info-cell"><?= GetMessage("SONET_ACTIVITY_STATUS") . ":" ?></span><span class="emp-info-cell"><?=GetMessage("SONET_USER_" . $arUser["ACTIVITY_STATUS"]) ?></span><?
	}
?></div><?

$additional = "";
if (is_array($arResult["UserFieldsPersonal"]["DATA"]))
{
	foreach ($arResult["UserFieldsPersonal"]["DATA"] as $field => $arUserField)
	{
		if (
			(
				is_array($arUserField["VALUE"])
				&& count($arUserField["VALUE"]) > 0
			)
			|| (
				!is_array($arUserField["VALUE"])
				&& StrLen($arUserField["VALUE"]) > 0)
			)
		{
			$additional .= '<span class="emp-info-cell">'.$arUserField["NAME"].':</span><span class="emp-info-cell">'.$arUserField["VALUE"].'</span>';
		}
	};
}

if (is_array($arResult["UserPropertiesPersonal"]["DATA"]))
{
	foreach ($arResult["UserPropertiesPersonal"]["DATA"] as $field => $arUserField)
	{
		if (
			(
				is_array($arUserField["VALUE"])
				&& count($arUserField["VALUE"]) > 0
			)
			|| (
				!is_array($arUserField["VALUE"])
				&& StrLen($arUserField["VALUE"]) > 0
			)
		)
		{
			$additional .= '<span class="emp-info-cell">' . $arUserField["EDIT_FORM_LABEL"] . ': '.'</span>';
			$additional .= '<span class="emp-info-cell">';

			ob_start();
			$GLOBALS["APPLICATION"]->IncludeComponent(
				"bitrix:system.field.view",
				$arUserField["USER_TYPE"]["USER_TYPE_ID"],
				array("arUserField" => $arUserField, "inChain" => $field == "UF_DEPARTMENT" ? "Y" : "N"),
				null,
				array("HIDE_ICONS" => "Y")
			);
			$additional .= ob_get_contents();
			ob_end_clean();
			$additional .= '</span>';
		}
	}
}

if (strlen($additional) > 0)
{
	?><div class="emp-info-block">
		<div class="emp-info-title"><?= GetMessage("SONET_ADDITIONAL_TITLE") ?></div>
		<?= $additional ?>
	</div><?
}
?>