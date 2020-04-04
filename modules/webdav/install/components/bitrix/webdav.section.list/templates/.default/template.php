<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS(BX_ROOT.'/components/bitrix/webdav/templates/.default/style.css');
	$GLOBALS['APPLICATION']->AddHeadScript(BX_ROOT.'/components/bitrix/webdav/templates/.default/script.js');
endif;

$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/panel/main/admin-public.css');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/utils.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/public_tools.js');
CJSCore::Init(array('access', 'dd', 'socnetlogdest'));
global $USER;
$isDiskNotInstall = $showUserBannerDisk = null;
$isLibToCurrentUser = !empty($arParams['OBJECT']->attributes['user_id']) && $USER->getId() == $arParams['OBJECT']->attributes['user_id'];
?>

<? if($isLibToCurrentUser && ($showUserBannerDisk = CWebDavTools::getShowOfferBannerForCurrentUser('disk')) && !CWebDavTools::isDesktopInstall()) {?>
<div>
	<a style="text-decoration: none" id="wd-banner-disk-install-offer" onclick="WDDownloadDesktop();" href="javascript:void(0)" class="wd-banner_section">
		<table class="wd-banner_table">
			<tr>
				<td class="wd-banner_table_<?= $isDiskNotInstall === true? 'two' : 'one' ?> <?= LANGUAGE_ID; ?>"><?= GetMessage('WD_OFFER_INSTALL_B24_TITLE'); ?></td>
				<td class="wd-banner_table_tree">
					<table>
						<tr>
							<td><?= GetMessage('WD_OFFER_INSTALL_B24_DESCR'); ?></td>
							<td><img src="/bitrix/components/bitrix/webdav.section.list/templates/.default/images/setup_<?= (in_array(LANGUAGE_ID, array('ru', 'en', 'de'))? LANGUAGE_ID : (LANGUAGE_ID == 'ua'? 'ua' : 'en')); ?>.png" alt=""></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<span class="wd-banner_close" onclick="WDCloseDiskBanner(this);"></span>
	</a>
</div>
<? } elseif($isLibToCurrentUser && $showUserBannerDisk && CWebDavTools::enableInVersion(15) && !CWebDavTools::isDesktopDiskInstall()) {?>
<div>
	<a style="text-decoration: none" id="wd-banner-disk-install-offer" onclick="WDCloseDiskBanner();" href="bx://openDiskTab" class="wd-banner_section">
		<table class="wd-banner_table">
			<tr>
				<td class="wd-banner_table_two <?= LANGUAGE_ID; ?>"><?= GetMessage('WD_OFFER_ENABLE_B24_TITLE'); ?></td>
				<td class="wd-banner_table_tree">
					<table>
						<tr>
							<td><?= GetMessage('WD_OFFER_INSTALL_B24_DESCR'); ?></td>
							<td><img src="/bitrix/components/bitrix/webdav.section.list/templates/.default/images/select_<?= (in_array(LANGUAGE_ID, array('ru', 'en', 'de'))? LANGUAGE_ID : (LANGUAGE_ID == 'ua'? 'ua' : 'en')); ?>.png" alt=""></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<span class="wd-banner_close" onclick="WDCloseDiskBanner(this); return false;"></span>
	</a>
</div>
<? } ?>
<?


global $by, $order;
/********************************************************************
				Input params
********************************************************************/
/*************** Ratings *********************************************/
if ($arParams["SHOW_RATING"] == 'Y'):
	$arFileId = array();
	foreach($arResult["GRID_DATA"] as $data)
		$arFileId[] = $data['data']['ID'];


	if (!empty($arFileId))
		$arResult['RATING'] = CRatings::GetRatingVoteResult('IBLOCK_ELEMENT', $arFileId);

	if ($arParams["RATING_TYPE"] == "")
	{
		$sRatingVoteType = COption::GetOptionString("main", "rating_vote_type", "standart");
		if ($sRatingVoteType == "like_graphic")
			$arParams["RATING_TYPE"] = "like";
		else if ($sRatingVoteType == "standart")
			$arParams["RATING_TYPE"] = "standart_text";
	}
	else
	{
		if ($arParams["RATING_TYPE"] == "like_graphic")
			$arParams["RATING_TYPE"] = "like";
		else if ($arParams["RATING_TYPE"] == "standart")
			$arParams["RATING_TYPE"] = "standart_text";
	}
	foreach($arResult["GRID_DATA"] as $id => $data)
	{
		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:rating.vote", $arParams["RATING_TYPE"],
			$ar111 = Array(
				"ENTITY_TYPE_ID" => "IBLOCK_ELEMENT",
				"ENTITY_ID" => $data["data"]["ID"],
				"OWNER_ID" => $data["data"]["CREATED_BY"]["ID"],
				"USER_VOTE" => $arResult["RATING"][$data["data"]["ID"]]["USER_VOTE"],
				"USER_HAS_VOTED" => $arResult["RATING"][$data["data"]["ID"]]["USER_HAS_VOTED"],
				"TOTAL_VOTES" => $arResult["RATING"][$data["data"]["ID"]]["TOTAL_VOTES"],
				"TOTAL_POSITIVE_VOTES" => $arResult["RATING"][$data["data"]["ID"]]["TOTAL_POSITIVE_VOTES"],
				"TOTAL_NEGATIVE_VOTES" => $arResult["RATING"][$data["data"]["ID"]]["TOTAL_NEGATIVE_VOTES"],
				"TOTAL_VALUE" => $arResult["RATING"][$data["data"]["ID"]]["TOTAL_VALUE"],
				"PATH_TO_USER_PROFILE" => $arParams["USER_VIEW_URL"],
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		
		$text = "";
		if( array_key_exists("EXT_LINKS_HASH_ARRAY", $arResult)
			&& array_key_exists("BASE_URL_FOR_EXT_LINK", $data["data"]) 
			&& array_key_exists("URL_FOR_EXT_LINK", $data["data"]) 
			&& $data["data"]["BASE_URL_FOR_EXT_LINK"] != null
			&& $data["data"]["URL_FOR_EXT_LINK"] != null
		)
		{
			$currHash = md5($data["data"]["BASE_URL_FOR_EXT_LINK"] . $data["data"]["URL_FOR_EXT_LINK"]);
			if(array_key_exists($currHash, $arResult["EXT_LINKS_HASH_ARRAY"]) && $arResult["EXT_LINKS_HASH_ARRAY"][$currHash] > 0){
				//$arResult["EXT_LINKS_HASH_ARRAY"][$currHash]
				
				$urlT = CWebDavExtLinks::GetFullURL($data["data"]["BASE_URL_FOR_EXT_LINK"] . $data["data"]["URL_FOR_EXT_LINK"]) . "?" . bitrix_sessid_get();
				$text = '<div class="ext-link-clip" onclick="ShowExtLinkDialog(\'' . $urlT . '&GetExtLink=1\',\'' . $urlT . '&GetDialogDiv=1\')" title="' . GetMessage("WD_COPY_EXT_LINK_TITLE") . '"></div>';
			}
			
		}
		
		$arResult["GRID_DATA"][$id]['columns']['NAME'] = str_replace(CWebDavExtLinks::$icoRepStr, $text, $arResult["GRID_DATA"][$id]['columns']['NAME']);
		/*$arResult["GRID_DATA"][$id]['columns']['NAME'] = CWebDavExtLinks::InsertListIcon($arResult["GRID_DATA"][$id]['columns']['NAME'] ,$data);*/
		$arResult["GRID_DATA"][$id]['columns']['NAME'] = str_replace("#RATING#", '<div class="rating_vote_text">'.$sVal.'</div>', $arResult["GRID_DATA"][$id]['columns']['NAME']);
	}
endif;
/***************** BASE ********************************************/
$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
$arParams["BASE_URL"] = trim(str_replace(":443", "", $arParams["BASE_URL"]));
$arHeaders = array(
	array("id" => "ID", "name" => "ID", "sort" => "id", "default" => (in_array("ID", $arParams["COLUMNS"]))),
	array("id" => "ACTIVE", "name" => GetMessage("WD_TITLE_ACTIVE"), "sort" => "active", "default" => (in_array("SORT", $arParams["COLUMNS"]))),
	array("id" => "SORT", "name" => GetMessage("WD_TITLE_SORT"), "sort" => "sort", "default" => (in_array("SORT", $arParams["COLUMNS"]))),
	array("id" => "CODE", "name" => GetMessage("WD_TITLE_CODE"), "sort" => "code", "default" => (in_array("CODE", $arParams["COLUMNS"]))),
	array("id" => "EXTERNAL_ID", "name" => GetMessage("WD_TITLE_EXTCODE"), "sort" => "external_id", "default" => (in_array("EXTERNAL_ID", $arParams["COLUMNS"]))),
	array("id" => "NAME", "name" => GetMessage("WD_TITLE_NAME"), "sort" => "name", "editable" => true, "default" => (in_array("NAME", $arParams["COLUMNS"]))),
);

if ($arParams["PERMISSION"] >= "U")
{
	$arHeaders = array_merge($arHeaders, array(
	array("id" => "WF_STATUS_ID", "name" => GetMessage("WD_TITLE_STATUS"), "sort" => false, "default" => ($arParams["WORKFLOW"] == "workflow" && $arParams["SHOW_WORKFLOW"] != "N")),
	array("id" => "BP_PUBLISHED", "name" => GetMessage("WD_TITLE_PUBLIC"), "sort" => false, "default" => ($arParams["WORKFLOW"] == "bizproc" && $arParams['OBJECT']->workflow == 'bizproc')),
	));
}
$arHeaders = array_merge($arHeaders, array(
	array("id" => "FILE_SIZE", "name" => GetMessage("WD_TITLE_FILE_SIZE"), "sort" => "property_webdav_size", "order"=>"desc", "default" => (in_array("FILE_SIZE", $arParams["COLUMNS"])), "align" => "right"),
	array("id" => "USER_NAME", "name" => GetMessage("WD_TITLE_MODIFIED_BY"), "sort" => "modified_by", "default" => (in_array("USER_NAME", $arParams["COLUMNS"]))),
	array("id" => "TIMESTAMP_X", "name" => GetMessage("WD_TITLE_TIMESTAMP"), "sort" => "timestamp_x","order"=>"desc",  "default" => (in_array("TIMESTAMP_X", $arParams["COLUMNS"]))),
	array("id" => "ELEMENT_CNT", "name" => GetMessage("WD_TITLE_ELS"), "sort" => false, "default" => (in_array("ELEMENT_CNT", $arParams["COLUMNS"]))),
	array("id" => "SECTION_CNT", "name" => GetMessage("WD_TITLE_SECS"), "sort" => false, "default" => (in_array("SECTION_CNT", $arParams["COLUMNS"]))),
	array("id" => "DATE_CREATE", "name" => GetMessage("WD_TITLE_ADMIN_DCREATE"), "sort" => "created", "order"=>"desc",	"default" => (in_array("DATE_CREATE", $arParams["COLUMNS"]))),
	array("id" => "CREATED_USER_NAME", "name" => GetMessage("WD_TITLE_ADMIN_WCREATE2"), "sort" => "created_by", "default" => (in_array("CREATED_USER_NAME", $arParams["COLUMNS"]))),
//	array("id" => "SHOW_COUNTER", "name" => GetMessage("WD_TITLE_EXTERNAL_SHOWS"), "sort" => "show_counter", "default" => (in_array("SHOW_COUNTER", $arParams["COLUMNS"]))),
	array("id" => "PREVIEW_TEXT", "name" => GetMessage("WD_TITLE_EXTERNAL_PREV_TEXT"), "sort" => false, "default" => (in_array("PREVIEW_TEXT", $arParams["COLUMNS"]))),
	array("id" => "DETAIL_TEXT", "name" => GetMessage("WD_TITLE_EXTERNAL_DET_TEXT"), "sort" => false, "default" => (in_array("DETAIL_TEXT", $arParams["COLUMNS"]))),
	array("id" => "TAGS", "name" => GetMessage("WD_TITLE_TAGS"), "sort" => "tags", "default" => (in_array("TAGS", $arParams["COLUMNS"]))),
));

$arProps = $arParams['OBJECT']->GetProperties(); // cached

foreach ($arProps as $code => &$arIBProp)
{
	if (in_array($code, array("WEBDAV_SIZE", "WEBDAV_INFO", "FILE")))
		continue;

	$arHeaders[] = array(
		"id" => "PROPERTY_".$code,
		"name" => trim(empty($arIBProp["NAME"]) ? $code : $arIBProp["NAME"]),
		"sort" => strtolower("PROPERTY_".$code),
		"default" => (in_array("PROPERTY_".$code, $arParams["COLUMNS"]))
	);
}

if ($arParams["PERMISSION"] >= "U")
{
	$arHeaders = array_merge($arHeaders, array(
	//array("id" => "WF_STATUS_ID", "name" => GetMessage("WD_TITLE_STATUS"), "sort" => false, "default" => ($arParams["WORKFLOW"] == "workflow" && $arParams["SHOW_WORKFLOW"] != "N")),
	//array("id" => "BP_PUBLISHED", "name" => GetMessage("WD_TITLE_PUBLIC"), "sort" => false, "default" => ($arParams["WORKFLOW"] == "bizproc" || CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "BIZPROC") != "N")),
	array("id" => "WF_NEW", "name" => GetMessage("WD_TITLE_EXTERNAL_WFNEW"), "sort" => false, "default" => (in_array("WF_NEW", $arParams["COLUMNS"]))),
	array("id" => "LOCK_STATUS", "name" => GetMessage("WD_TITLE_EXTERNAL_LOCK"), "sort" => false, "default" => (in_array("LOCK_STATUS", $arParams["COLUMNS"]))),
	array("id" => "LOCKED_USER_NAME", "name" => GetMessage("WD_TITLE_EXTERNAL_LOCK_BY"), "sort" => false, "default" => (in_array("LOCKED_USER_NAME", $arParams["COLUMNS"]))),
	array("id" => "WF_DATE_LOCK", "name" => GetMessage("WD_TITLE_EXTERNAL_LOCK_WHEN"), "sort" => false, "default" => (in_array("WF_DATE_LOCK", $arParams["COLUMNS"]))),
	array("id" => "WF_COMMENTS", "name" => GetMessage("WD_TITLE_EXTERNAL_COM"), "sort" => false, "default" => (in_array("WF_COMMENTS", $arParams["COLUMNS"]))),
	array("id" => "BIZPROC", "name" => GetMessage("IBLIST_A_BP_H"), "sort" => false, "default" => (in_array("BIZPROC", $arParams["COLUMNS"]))),
	//array("id" => "BP_VERSIONS", "name" => GetMessage("WD_VERSIONS"), "sort" => false, "default" => ($arParams["WORKFLOW"] == "bizproc")),
	));
}

if (isset($arResult['USER_FIELDS'])
	&& ! empty($arResult['USER_FIELDS'])
)
{
	foreach ($arResult['USER_FIELDS'] as $fieldCode => $field)
	{
		$arHeaders[] = array(
			"id" => $fieldCode,
			"name" => htmlspecialcharsbx($field['EDIT_FORM_LABEL']),
			"sort" => false,
			"default" => false,
		);
	}
}

$arPresets = array(
	"filter_today"=>array("name"=>GetMessage("WD_PRESET_TODAY"), "fields"=>array("timestamp_datesel"=>"today")),
	"filter_yesterday"=>array("name"=>GetMessage("WD_PRESET_YESTERDAY"), "fields"=>array("timestamp_datesel"=>"yesterday")),
	"filter_my"=>array("name"=>GetMessage("WD_PRESET_MY"), "fields"=>array( "user_name"=>__format_user4search(), "user[]"=>$GLOBALS['USER']->GetID())),
	"filter_documents"=>array("name"=>GetMessage("WD_PRESET_DOCUMENTS"), "fields"=>array("doctype"=>"acf489b4")),
	"filter_images"=>array("name"=>GetMessage("WD_PRESET_IMAGES"), "fields"=>array("doctype"=>"5b53cf82")),
);
/********************************************************************
				Filter
********************************************************************/
for ($i=0, $cnt=sizeof($arResult["FILTER"]); $i < $cnt; $i++)
{
	if ($arResult["FILTER"][$i]["type"] === "user")
	{
		$userID = (isset($_REQUEST[$arResult["FILTER"][$i]["id"]]))?intval($_REQUEST[$arResult["FILTER"][$i]["id"]][0]):0;
		if ($userID === 0 || (isset($_REQUEST["clear_filter"]) && $_REQUEST["clear_filter"] == "Y"))
		{
			$userID = "";
			$userName = "";
		}
		else
		{
			$userName = __format_user4search($userID, $arParams["NAME_TEMPLATE"]);
		}
		ob_start();

		$APPLICATION->IncludeComponent(
			"bitrix:intranet.user.selector.new", ".default", array(
				"MULTIPLE" => "N",
				"NAME" => $arResult["FILTER"][$i]["id"],
				"VALUE" => $userID,
				"POPUP" => "Y",
				"ON_CHANGE" => "BXWDOnFilterAuthorSelect",
				"SITE_ID" => SITE_ID
			), $component, array("HIDE_ICONS" => "Y")
		);

		$val = ob_get_clean();

		$arResult["FILTER"][$i]["type"] = "custom";
		$val .= "
			<div id=\"wd_filter_author_name\"></div>
			<div id=\"user_selector_control\" >$val</div>
			<a href=\"javascript:void(0);\" class=\"webform-field-action-link\" onclick=\"BXWDFilterAuthorSelect(this);\">".GetMessage('WD_SELECT_USER')."</a>";
		$val .= <<<EOS
			<script>
				function BXWDFilterAuthorSelect(el)
				{
					fld = 'author';
					if (!window['BXFilterAuthorSelector_' + fld])
					{
						window['BXFilterAuthorSelector_' + fld] = BX.PopupWindowManager.create("filter-"+fld+"-popup", el, {
							offsetTop : 1,
							autoHide : true,
							content : BX("user_selector_content")
						});
					}

					if (window['BXFilterAuthorSelector_' + fld].popupContainer.style.display != "block")
					{
						window['BXFilterAuthorSelector_' + fld].show();
					}

					BX("user_selector_content").innerHTML = '';
					return false;
				}

				function BXWDOnFilterAuthorSelect(users)
				{
					fld = 'author';
					if (users)
					{
						var u = BX.util.array_values(users);
						if (u.length > 0)
						{
							BX('wd_filter_author_name').innerHTML = '\
<span class="wd-name">\
<input type="hidden" value="'+u[0].id+'" name="user[]" />\
<a class="wd-user-selected">'+BX.util.htmlspecialchars(u[0].name)+'</a>\
<span onclick="O_user.unselect(this.parentNode.firstChild.value);" class="wd-user-selected-del-icon"></span>\
</span>';

							if (window['BXFilterAuthorSelector_'+fld])
								window['BXFilterAuthorSelector_'+fld].close();

							return;
						}
					}

					if (!!window['BXFilterAuthorSelector_' + fld])
					{
						BX('wd_filter_author_name').innerHTML = '';
					}
				}
			</script>
EOS;

		$arResult["FILTER"][$i]["value"] = $val;
	}
	elseif ($arResult["FILTER"][$i]["type"] === "tags")
	{
		$tags = (isset($_REQUEST[$arResult["FILTER"][$i]["id"]]))?trim(urldecode($_REQUEST[$arResult["FILTER"][$i]["id"]])):'';

		if (IsModuleInstalled("search"))
		{
			ob_start();
			$APPLICATION->IncludeComponent(
				"bitrix:search.tags.input",
				"",
				array(
					"VALUE" => $tags,
					"NAME" => $arResult["FILTER"][$i]["id"]),
				null,
				array("HIDE_ICONS" => "Y"));
			$val = ob_get_clean();
		}
		else
		{
			$val = '<input type="text" name="'.$arResult["FILTER"][$i]["id"].'" value="'.$tags.'" />';
		}
		$arResult["FILTER"][$i]["type"] = "custom";
		$arResult["FILTER"][$i]["value"] = $val;
	}
	elseif ($arResult["FILTER"][$i]["type"] === "search")
	{
		$searchVal = (isset($arResult["FILTER_VALUE"]["content"]) && strlen($arResult["FILTER_VALUE"]["content"])>0 ? htmlspecialcharsbx($arResult["FILTER_VALUE"]["content"]) : '');
		$val = '<input type="text" style="width:98%;" autocomplete="off" id="wd_'.
			$arResult["FILTER"][$i]["id"].
			'" name="'.$arResult["FILTER"][$i]["id"].'" value="'.$searchVal.'" />';
		if (IsModuleInstalled("search"))
		{
			if (!isset($_REQUEST['ajax_call']))
				ob_start();
			$arSearchParams = Array(
				"NUM_CATEGORIES" => "1",
				"TOP_COUNT" => "10",
				"CHECK_DATES" => "N",
				"SHOW_OTHERS" => "N",
				"PAGE" => "#SITE_DIR#search/index.php",
				"SHOW_INPUT" => "N",
				"OBJECT" => $arParams['OBJECT'],
				"INPUT_ID" => "wd_".$arResult["FILTER"][$i]["id"],
				"CONTAINER_ID" => "sidebar|flt_content_".$arParams["GRID_ID"],
			);
			if ($arParams['OBJECT']->arRootSection)
			{
				if (isset($arParams["OBJECT"]->attributes['user_id']))
				{
					$arSearchParams += Array(
						"CATEGORY_0_TITLE" => GetMessage("WD_DOCUMENTS"),
						"CATEGORY_0" => array(0 => "socialnetwork_user"),
						"CATEGORY_0_socialnetwork_user" => $arParams["OBJECT"]->attributes["user_id"],
					);
				}
				elseif (isset($arParams["OBJECT"]->attributes['group_id']))
				{
					$arSearchParams += Array(
						"CATEGORY_0_TITLE" => GetMessage("WD_DOCUMENTS"),
						"CATEGORY_0" => array(0 => "socialnetwork"),
						"CATEGORY_0_socialnetwork" => array($arParams["OBJECT"]->attributes["group_id"]),
					);
				}
			}
			else
			{
				$arSearchParams += Array(
					"CATEGORY_0_TITLE" => GetMessage("WD_DOCUMENTS"),
					"CATEGORY_0" => array(0 => "iblock_".$arParams["OBJECT"]->IBLOCK_TYPE,),
					"CATEGORY_0_iblock_".$arParams["OBJECT"]->IBLOCK_TYPE => array(0 => $arParams["IBLOCK_ID"],),
				);
			}
			$APPLICATION->IncludeComponent("bitrix:search.title", "wd-filter", $arSearchParams,
				$this->__component
			);

			if (!isset($_REQUEST['ajax_call']))
				$val .= ob_get_clean();
		}
		$arResult["FILTER"][$i]["type"] = "custom";
		$arResult["FILTER"][$i]["value"] = $val;

	}

	if ($arResult["FILTER"][$i]["id"] === "FILE_SIZE")
	{
		$fltSizeFrom = (isset($_REQUEST["FILE_SIZE_from"]) && $_REQUEST["FILE_SIZE_from"] !== '') ? intval($_REQUEST["FILE_SIZE_from"]) : '';
		$fltSizeTo = (isset($_REQUEST["FILE_SIZE_to"]) && $_REQUEST["FILE_SIZE_to"] !== '') ? intval($_REQUEST["FILE_SIZE_to"]): '';
		$fltSizeMultiply = isset($_REQUEST["FILE_SIZE_multiply"]) ? $_REQUEST["FILE_SIZE_multiply"] : 'b';
		if (!in_array($fltSizeMultiply, array('b', 'kb', 'mb', 'gb'))) $fltSizeMultiply = 'b';

		$val = "
<input type=\"text\" size=\"3\" value=\"".$fltSizeFrom."\" name=\"FILE_SIZE_from\">
...
<input type=\"text\" size=\"3\" value=\"".$fltSizeTo."\" name=\"FILE_SIZE_to\">
<select name=\"FILE_SIZE_multiply\">
	<option value=\"b\" ".(($fltSizeMultiply === 'b')?'selected="selected"':'').">".GetMessage("WD_BYTE")."</option>
	<option value=\"kb\" ".(($fltSizeMultiply === 'kb')?'selected="selected"':'').">".GetMessage("WD_KBYTE")."</option>
	<option value=\"mb\" ".(($fltSizeMultiply === 'mb')?'selected="selected"':'').">".GetMessage("WD_MBYTE")."</option>
	<option value=\"gb\" ".(($fltSizeMultiply === 'gb')?'selected="selected"':'').">".GetMessage("WD_GBYTE")."</option>
</select>
";
		$arResult["FILTER"][$i]["type"] = "custom";
		$arResult["FILTER"][$i]["value"] = $val;
	}
}

/********************************************************************
				/ Filter
********************************************************************/
/********************************************************************
				/Input params
********************************************************************/
if (!empty($arResult["ERROR_MESSAGE"]))
{
	ShowError($arResult["ERROR_MESSAGE"]);
}
if (isset($_REQUEST["result"]))
{
	$msgid = false;

	switch ( $_REQUEST["result"] )
	{
		case "uploaded":			$msgid = "WD_UPLOAD_DONE"; break;
		case "deleted":				$msgid = "WD_DELETE_DONE"; break;
		case "section_deleted":		$msgid = "WD_SECTION_DELETE_DONE"; break;
		case "empty_trash":			$msgid = "WD_EMPTY_TRASH_DONE"; break;
		case "document_restored":	$msgid = "WD_DOC_RESTORE_DONE"; break;
		case "section_restored":	$msgid = "WD_SEC_RESTORE_DONE"; break;
		case "all_restored":		$msgid = "WD_RESTORE_DONE"; break;
	}
	if ($msgid != false)
		ShowNote(GetMessage($msgid));
}
$arResult["GRID_DATA"] = (is_array($arResult["GRID_DATA"]) ? $arResult["GRID_DATA"] : array());
if (isset($_REQUEST['result']))
{
	unset($_REQUEST['result']);
	unset($_GET['result']);
}

if ($arParams['OBJECT']->e_rights)
{
	$bEditable = false;
	foreach ($arResult['GRID_DATA'] as $row)
	{
		if ($row['editable'])
		{
			$bEditable = true;
			break;
		}
	}
}
else
{
	$bEditable = ! (
		($arParams["PERMISSION"] <= "U")
		||
		(
			($arParams["PERMISSION"] == "W")
			&& ($arParams["CHECK_CREATOR"] == "Y")
		)
	);
}

if (! $bEditable)
{
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.grid",
		"",
		array(
			"GRID_ID" => $arParams["GRID_ID"],
			"HEADERS" => $arHeaders ,
			"FILTER" => $arResult["FILTER"],
			"FILTER_PRESETS" => $arPresets,
			"SORT" => array($by => $order),
			"ROWS" => $arResult["GRID_DATA"],
			"FOOTER" => array(array("title" => GetMessage("WD_ALL"), "value" => $arResult["GRID_DATA_COUNT"])),
			"EDITABLE" => false,
			"ACTIONS" => false,
			"ACTION_ALL_ROWS" => false,
			"NAV_OBJECT" => $arResult["NAV_RESULT"],
			"AJAX_MODE" => "N",
		),
		($this->__component->__parent ? $this->__component->__parent : $component)
	);
}
else
{
	if (!empty($arResult["SECTION_LIST"]) && $arParams['OBJECT']->meta_state != 'TRASH' )
	{
		$custom_html = GetMessage("WD_MOVE_TO").'<select name="IBLOCK_SECTION_ID">'.
			'<option value="0"' . ($arParams["SECTION_ID"] == 0 ? "selected" : "").'>'.GetMessage("WD_CONTENT").'</option>';
		foreach ($arResult["SECTION_LIST"] as $res)
		{
			$custom_html .= '<option value="'.$res["ID"].'" '.($arParams["SECTION_ID"] == $res["ID"] ? "selected=\"selected\"" : "").'>'.
				str_repeat(".", $res["DEPTH_LEVEL"]).$res["NAME"].'</option>';
		}
		$custom_html .= '</select>';
	}
	else
	{
		$custom_html = '';
	}
	if ($arParams['OBJECT']->meta_state == 'TRASH' && $arParams['PERMISSION'] > "W")
	{
?>
<script>
BX(function() { // 'undelete' group action button
	var del_button = BX.findChild(BX('<?=CUtil::JSEscape($arParams['GRID_ID'])?>').parentNode, {'tag':'a', 'class':'action-delete-button-dis'}, true);
	var restore_button = BX.create('a', {
		'attrs':{
			'class':'context-button icon action-restore-button-dis',
			'title':'<?=CUtil::JSEscape(GetMessage('WD_CONFIRM_RESTORE_TITLE'))?>',
			'href':'javascript:void(0);'
		},
		'props':{
			'id':'restore_button_<?=$arParams['GRID_ID']?>'
		},
		'events':{
			'click':function()
				{
					if (bxGrid_<?=$arParams['GRID_ID']?>.IsActionEnabled())
						WDConfirm("<?=CUtil::JSEscape(GetMessage("WD_CONFIRM_RESTORE_TITLE"))?>",
							"<?=CUtil::JSEscape(GetMessage("WD_CONFIRM_RESTORE"))?>",
							function()
							{
								bxGrid_<?=$arParams['GRID_ID']?>.ActionRestore();
							});
				}
		}
	});
	var td_restore_button = BX.create('td', {
		'children': [restore_button]
	});
	del_button.parentNode.parentNode.appendChild(td_restore_button);

	bxGrid_<?=$arParams['GRID_ID']?>._wd_EnableActions = bxGrid_<?=$arParams['GRID_ID']?>.EnableActions;
	bxGrid_<?=$arParams['GRID_ID']?>.EnableActions = function()
	{
		this._wd_EnableActions();
		var bEnabled = this.IsActionEnabled();
		var b = document.getElementById('restore_button_'+this.table_id);
		if(b) b.className = 'context-button icon action-restore-button'+(bEnabled? '':'-dis');
	}

	bxGrid_<?=$arParams['GRID_ID']?>.ActionRestore = function()
	{
		var form = document.forms['form_'+this.table_id];
		if(!form)
			return;

		form.elements['action_button_'+this.table_id].value = 'undelete';

		if(form.onsubmit)
			form.onsubmit();
		form.submit();
	}
});
</script>
<?
	}
?><?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID" => $arParams["GRID_ID"],
		"HEADERS" => $arHeaders,
		"FILTER" => $arResult["FILTER"],
		"FILTER_PRESETS" => $arPresets,
		"SORT" => array($by => $order),
		"ROWS" => $arResult["GRID_DATA"],
		"FOOTER" => array(array("title" => GetMessage("WD_ALL"), "value" => $arResult["GRID_DATA_COUNT"])),
		"EDITABLE" =>!($arParams["OBJECT"]->meta_state == 'TRASH' && $arParams["OBJECT"]->permission < "X") ,
		"ACTIONS" => (
					($arParams["OBJECT"]->meta_state == 'TRASH' && $arParams["OBJECT"]->permission < "X") ?
					array() :
					array("delete" => true, "custom_html" => $custom_html)
				),
		"ACTION_ALL_ROWS" => true,
		"NAV_OBJECT" => $arResult["NAV_RESULT"],
		"AJAX_MODE" => "N",
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);?><?
}

if (!empty($arParams["SHOW_NOTE"])):
?>
<br />
<div class="wd-help-list selected" id="wd_list_note"><?=$arParams["~SHOW_NOTE"]?></div>
<?
endif;

if ($arParams["WORKFLOW"] == "workflow" && $arParams["PERMISSION"] >= "U" && $arParams["SHOW_WORKFLOW"] != "N"):?>
<br />
<div class="wd-help-list selected">
<?
if ($arParams["PERMISSION"] >= "W" && CWorkflow::IsAdmin()):
?><?=GetMessage("WD_WF_COMMENT1")?><br /><?
elseif (!in_array(2, $arResult["WF_STATUSES_PERMISSION"])):
?><?=GetMessage("WD_WF_COMMENT2")?><br /><?
else:
	foreach ($arResult["WF_STATUSES_PERMISSION"] as $key => $val):
		if ($val == 2):
			$arr[] = $arResult["WF_STATUSES"][$key];
		endif;
	endforeach;

	if (count($arr) == 1):
	?><?=str_replace("#STATUS#", $arr[0], GetMessage("WD_WF_ATTENTION2"))?><br /><?
	else:
	?><?=str_replace("#STATUS#", $arr[0], GetMessage("WD_WF_ATTENTION3"))?><br /><?
	endif;
endif;

if ($arParams["PERMISSION"] >= "W"):
?><?=GetMessage("WD_WF_ATTENTION1")?><br /><?
endif;
?>
</div>
<?endif;?>
<?if ($arParams["PERMISSION"] >= "U"):?>
<script>
BX(function() {
	var moffice = WDCheckOfficeEdit();
	if (moffice)
		var officetitle = WDEditOfficeTitle();

	for (row in bxGrid_<?=$arParams["GRID_ID"]?>.oActions)
	{
		for (popup_cell in bxGrid_<?=$arParams["GRID_ID"]?>.oActions[row])
		{
			if (bxGrid_<?=$arParams["GRID_ID"]?>.oActions[row][popup_cell].OFFICECHECK && (! bxGrid_<?=$arParams["GRID_ID"]?>.oActions[row][popup_cell].DISABLED))
			{
				if (moffice)
				{
					if (officetitle)
						bxGrid_<?=$arParams["GRID_ID"]?>.oActions[row][popup_cell].TEXT = officetitle;
					bxGrid_<?=$arParams["GRID_ID"]?>.oActions[row][popup_cell].DESCRIPTION = '';
				}
				else
				{
					bxGrid_<?=$arParams["GRID_ID"]?>.oActions[row][popup_cell].DISABLED = true;
				}
			}
		}
	}
});
</script>
<?
endif;
?>


<script>
var wdGlobalRunDisconnectDialog = false;
	BX( function() {

		BX.addCustomEvent('OnUnshareInviteAllUsers', function(data){
			if(data && data.status == 'success')
			{
				if(wdGlobalSectionId)
				{
					var sectionToUnshare = BX('sec' + wdGlobalSectionId);
					if(sectionToUnshare)
					{
						var sectionIcon = BX.findChild(sectionToUnshare.parentNode, {className: 'shared-section-icon'});
						if(sectionIcon)
						{
							BX.addClass(sectionIcon, 'section-icon');
							BX.removeClass(sectionIcon, 'shared-section-icon');
						}
					}
				}
				wdGlobalSectionId = false;
			}
		});
		BX.addCustomEvent('OnShareInviteToUsers', function(data){
			if(data && data.status == 'success')
			{
				if(wdGlobalSectionId)
				{
					var sectionToUnshare = BX('sec' + wdGlobalSectionId);
					if(sectionToUnshare)
					{
						var sectionIcon = BX.findChild(sectionToUnshare.parentNode, {className: 'section-icon'});
						if(sectionIcon)
						{
							BX.addClass(sectionIcon, 'shared-section-icon');
							BX.removeClass(sectionIcon, 'section-icon');
						}
					}
				}
				wdGlobalSectionId = false;
			}
		});
		BX.addCustomEvent('OnConnectSharedSection', function(data){
			if(data && data.status == 'success')
			{
				if(wdGlobalSectionId)
				{
					var sectionToUnshare = BX('sec' + wdGlobalSectionId);
					if(sectionToUnshare)
					{
						var sectionIcon = BX.findChild(sectionToUnshare.parentNode, {className: 'section-icon'});
						if(sectionIcon)
						{
							BX.addClass(sectionIcon, 'shared-section-icon');
							BX.removeClass(sectionIcon, 'section-icon');
						}
					}
				}
				wdGlobalSectionId = false;
			}
		});
		BX.addCustomEvent('OnDisconnectSharedSection', function(data){
			if(data && data.status == 'success')
			{
				if(wdGlobalSectionId)
				{
					var sectionToUnshare = BX('sec' + wdGlobalSectionId);
					if(sectionToUnshare)
					{
						var sectionIcon = BX.findChild(sectionToUnshare.parentNode, {className: 'shared-section-icon'});
						if(sectionIcon)
						{
							BX.addClass(sectionIcon, 'section-icon');
							BX.removeClass(sectionIcon, 'shared-section-icon');
						}
					}
				}
				wdGlobalSectionId = false;
			}
		});

		<? if($arResult['preview']): ?>
			BX.viewElementBind(
				'<?=$arParams["GRID_ID"]?>',
				{showTitle: true},
				{attr: 'data-bx-viewer'}
			);
		<? endif; ?>

		var hilight_row = window.location.href.match(/[#=]((doc|sec)([0-9]+))/);
		if (hilight_row)
		{
			try {
				BX.addClass(BX(hilight_row[1]).parentNode.parentNode.parentNode.parentNode, 'bx-selected');
			} catch (e) {}
			try {
				BX.addClass(BX(hilight_row[1]).parentNode.parentNode.parentNode, 'bx-selected');
			} catch (e) {}
			if (BX(hilight_row[1]))
			{
				BX(hilight_row[1]).scrollIntoView();
				if(window.location.href.match(/[#]showInViewer/))
				{
					BX.fireEvent(BX(hilight_row[1]), 'click');
				}
				if(!wdGlobalRunDisconnectDialog && window.location.href.match(/[#]disconnect/))
				{
					var btnToShare = BX(hilight_row[1] + '-share');
					if(btnToShare)
					{
						BX.fireEvent(btnToShare, 'click');
					}
				}
				if(window.location.href.match(/[#]share/))
				{
					var btnToShare = BX(hilight_row[1] + '-share');
					if(btnToShare)
					{
						BX.fireEvent(btnToShare, 'click');
					}
				}
			}
		}

		// dblclick on up arrow to move to parent folder
		var upControl = BX.findChild(document, {'class':'section-up'}, true)
		if (upControl)
			BX.bind(
				upControl.parentNode.parentNode,
				'dblclick',
				function() {
					jsUtils.Redirect([], '<?=CUtil::JSEscape($arResult["URL"]["UP"])?>');
				}
			);

		var table = BX('<?=$arParams["GRID_ID"]?>');
		var trs = BX.findChild(table, {'tag': 'tr'}, true, true);
		for (var i in trs)
		{
			if (BX.hasClass(trs[i], 'bx-odd') || BX.hasClass(trs[i], 'bx-even'))
				if (trs[i].getAttribute('title'))
					trs[i].setAttribute('title', '');
		}
	});

	function WDShowVersionsPopup(itemId, bindElement)
	{
		if (versionPopup[itemId])
			BX.PopupMenu.show(itemId, bindElement, versionPopup[itemId], {});

		return false;
	}

	function WDRename(chbx, bxGrid, gridID)
	{
		if (chbx.checked !== true)
		{
			chbx.checked = true;
			bxGrid.SelectRow(chbx);
			bxGrid.EnableActions();
		}
		var tmp_oSaveData = {};
		for (row_id in bxGrid.oSaveData)
		{
			tmp_oSaveData[row_id] = {};
			for (col_id in bxGrid.oSaveData[row_id])
			{
				tmp_oSaveData[row_id][col_id] = bxGrid.oSaveData[row_id][col_id];
			}
		}
		bxGrid.ActionEdit();
		for (row_id in tmp_oSaveData)
			for (col_id in tmp_oSaveData[row_id])
				bxGrid.oSaveData[row_id][col_id] = tmp_oSaveData[row_id][col_id];

		var btnCancel = BX.findChild(BX('bx_grid_'+gridID+'_action_buttons'), {'tag':'input', 'attr':{'type':'button'}});
		btnCancel.onclick = function() {
			bxGrid.ActionCancel();
			var checkAll = BX(gridID+'_check_all');
			checkAll.checked = false;
			bxGrid.SelectAllRows(checkAll);
			bxGrid.EnableActions();
		};
	}

	var WDSearchTag = function(tag)
	{
		jsUtils.Redirect({},"<?=WDAddPageParams($arResult["ELEMENT"]["URL"]["SECTION"], array("%3FTAGS" => "#tags#"));?>".replace("#tags#", BX.util.urlencode(tag)));
	}

	function WDCopyLinkDialog(url)
	{
		var wdc = new BX.CDialog({'title': '<?=CUtil::JSEscape(GetMessage('WD_COPY_LINK_TITLE'));?>', 'content':"<form><input type=\"text\" readonly=\"readonly\" style=\"width:482px\"><br /><p><?=CUtil::JSEscape(GetMessage("WD_COPY_LINK_HELP"));?></p></form>", 'width':520, 'height':120});

		wdc.SetButtons("<input type=\"button\" onClick=\"BX.WindowManager.Get().Close()\" value=\"<?=CUtil::JSEscape(GetMessage('MAIN_CLOSE'))?>\">");
		wdc.Show();

		var wdci = BX.findChild(wdc.GetForm(), {'tag':'input'})
		wdci.value = url.replace(/ /g, "%20");
		wdci.select();
	}

	function WDShareFolderInSharedDocs(dataUrl, id, sectionDisconnectUrl, sectionName)
	{
		//shame
		wdGlobalSectionDisconnectUrl = sectionDisconnectUrl || false;
		wdGlobalSectionName = sectionName || '';
		wdGlobalSectionId = id || false;
		showWebdavUserSharePopup(dataUrl, null, sectionDisconnectUrl);
	}

	function WDShareFolder(dataUrl, id, sectionDropUrl, sectionName)
	{
		//shame
		wdGlobalSectionDropUrl = sectionDropUrl || false;
		wdGlobalSectionName = sectionName || '';
		wdGlobalSectionId = id || false;
		showWebdavUserSharePopup(dataUrl, null, sectionDropUrl);
	}

	function showWebdavUserSharePopup(dataUrl, bindElement)
	{
		bindElement = bindElement || null;
		if(!dataUrl)
		{
			return false;
		}
		bindElement = bindElement || null;
		var popup = BX.PopupWindowManager.create(
			'bx_webdav_user_share_disk_popup',
			bindElement,
			{
				closeIcon : true,
				offsetTop: 5,
				autoHide: true,
				lightShadow:false,
				content: BX.create('div', {
					children: [
						BX.create('div', {
								style: {
									display: 'table',
									width: '30px',
									height: '30px'
								},
								children: [
									BX.create('div', {
										style: {
											display: 'table-cell',
											verticalAlign: 'middle',
											textAlign: 'center'
										},
										children: [
											BX.create('div', {
												props: {
													className: 'bx-viewer-wrap-loading-modal'
												}
											}),
											BX.create('span', {
												text: ''
											})
										]
									})
								]
							}
						)
					]
				}),
				closeByEsc: true,
				events : {
					'onPopupClose': function()
					{
						if(BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog())
						{
							BX.SocNetLogDestination.closeDialog()
						}

						this.destroy();
					}
				}
			}
		);
		popup.show();

		BX.ajax({
			'method': 'POST',
			'dataType': 'html',
			'url': dataUrl,
			'data': {
				sessid: BX.bitrix_sessid()
			},
			'onsuccess': function (data) {
				popup.setContent(BX.create('DIV', {html: data}));
				popup.adjustPosition();
			}
		});
	}

	function WDUploadDroppedFiles(files)
	{
<?
$arUrlParams = array('use_light_view'=>'Y');
if ($arResult['BP_PARAM_REQUIRED'] == 'Y')
	$arUrlParams['bp_param_required'] = 'Y';
$sUrl = '?';
foreach ($arUrlParams as $urlParam => $urlValue)
	$sUrl .= $urlParam.'='.$urlValue.'&';
?>
		var url = "<?=CUtil::JSEscape($arResult['URL']['UPLOAD'].$sUrl)?>";
		var dlg = new BX.CDialog({
			'content_url':url,
			'width':'600',
			'height':'200',
			'resizable':false
		});
		if (files)
		{
			BX.addCustomEvent(dlg, 'onUploadPopupReady', function() {
				dlg.updateListFiles(files);
			});
		}
		dlg.Show();
	}

<?	if (isset($_REQUEST['file_upload'])) { ?>
		BX(function() {
			WDUploadDroppedFiles();
		});
<?	} ?>

	BX(function() {
		var dropBoxNode = BX('<?=$arParams['GRID_ID']?>');
		var dropbox = new BX.DD.dropFiles(dropBoxNode);
		if (dropbox && dropbox.supported())
		{
			BX.addCustomEvent(dropbox, 'dropFiles', WDUploadDroppedFiles);
			//BX.addCustomEvent(dropbox, 'dragEnter', function() {BX.addClass(dropBoxNode, 'droptarget');});
			//BX.addCustomEvent(dropbox, 'dragLeave', function() {BX.removeClass(dropBoxNode, 'droptarget');});
		}
	});
</script>
<script type="text/javascript">
	BX.message({
		'wd_service_edit_doc_default': '<?= CUtil::JSEscape(CWebDavTools::getServiceEditDocForCurrentUser()) ?>'
	});
</script>
