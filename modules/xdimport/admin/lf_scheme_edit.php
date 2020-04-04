<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

use Bitrix\Main\Text\HtmlFilter;

if(!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

if(
	!CModule::IncludeModule('xdimport')
	|| !CModule::IncludeModule('socialnetwork')
)
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

IncludeModuleLangFile(__FILE__);

$arSocNetAllowedSubscribeEntityTypes = CSocNetAllowed::GetAllowedEntityTypes();
$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();
$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();
$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();

$arEntityTypes = array();
$arEvents = array();

foreach ($arSocNetAllowedSubscribeEntityTypesDesc as $entity_type => $arEntityTypeTmp)
{
	if (
		!array_key_exists("XDIMPORT_ALLOWED", $arEntityTypeTmp)
		|| $arEntityTypeTmp["XDIMPORT_ALLOWED"] != "Y"
	)
	{
		continue;
	}

	$arEntityTypes[$entity_type] = strtolower((array_key_exists("TITLE_ENTITY_XDI", $arEntityTypeTmp) ? $arEntityTypeTmp["TITLE_ENTITY_XDI"] : $arEntityTypeTmp["TITLE_ENTITY"]));
}	

foreach ($arSocNetLogEvents as $event_id => $arEventTmp)
{
	if (
		!$arEventTmp["HIDDEN"] 
		&& array_key_exists("ENTITIES", $arEventTmp) 
		&& is_array($arEventTmp["ENTITIES"])
	)
	{
		foreach ($arEventTmp["ENTITIES"] as $entity_type => $arEventEntityTmp)
		{
			$arEvents[$entity_type][$event_id] = array(
				"ALLOWED" => (array_key_exists("XDIMPORT_ALLOWED", $arEventTmp) && $arEventTmp["XDIMPORT_ALLOWED"] == "Y" ? "Y" : "N"),
				"TITLE" => $arEventEntityTmp["TITLE"]
			);
		}
	}
}

foreach ($arSocNetFeaturesSettings as $feature_id => $arFeatureTmp)
{
	if (
		array_key_exists("subscribe_events", $arFeatureTmp) 
		&& is_array($arFeatureTmp)
	)
	{
		foreach ($arFeatureTmp["subscribe_events"] as $event_id => $arEventTmp)
		{
			if (
				!$arEventTmp["HIDDEN"] 
				&& array_key_exists("ENTITIES", $arEventTmp) 
				&& is_array($arEventTmp["ENTITIES"])
			)
			{
				foreach ($arEventTmp["ENTITIES"] as $entity_type => $arEventEntityTmp)
				{
					$arEvents[$entity_type][$event_id] = array(
						"ALLOWED" => (array_key_exists("XDIMPORT_ALLOWED", $arEventEntityTmp) && $arEventEntityTmp["XDIMPORT_ALLOWED"] == "Y" ? "Y" : "N"),
						"TITLE" => $arEventEntityTmp["TITLE"]
					);
				}
			}
		}
	}
}

$arRights = array(
	SONET_SUBSCRIBE_ENTITY_GROUP => array(
		SONET_ROLES_OWNER => GetMessage("LFP_SCHEME_EDIT_RIGHTS_G_OWNER"),
		SONET_ROLES_MODERATOR => GetMessage("LFP_SCHEME_EDIT_RIGHTS_G_MODERATOR"),
		SONET_ROLES_USER => GetMessage("LFP_SCHEME_EDIT_RIGHTS_G_MEMBER"),
		SONET_ROLES_AUTHORIZED => GetMessage("LFP_SCHEME_EDIT_RIGHTS_G_AUTHORIZED"),
		SONET_ROLES_ALL => GetMessage("LFP_SCHEME_EDIT_RIGHTS_G_ALL")
	),
	SONET_SUBSCRIBE_ENTITY_USER => array(
		SONET_RELATIONS_TYPE_NONE => GetMessage("LFP_SCHEME_EDIT_RIGHTS_U_OWNER")
	),	
);

if (COption::GetOptionString("socialnetwork", "allow_frields", "Y") == "Y")
{
	$arRights["U"][SONET_RELATIONS_TYPE_FRIENDS] =  GetMessage("LFP_SCHEME_EDIT_RIGHTS_U_FRIENDS");
	$arRights["U"][SONET_RELATIONS_TYPE_FRIENDS2] =  GetMessage("LFP_SCHEME_EDIT_RIGHTS_U_FRIENDS2");
}

$arRights["U"][SONET_RELATIONS_TYPE_AUTHORIZED] =  GetMessage("LFP_SCHEME_EDIT_RIGHTS_U_AUTHORIZED");
$arRights["U"][SONET_RELATIONS_TYPE_ALL] =  GetMessage("LFP_SCHEME_EDIT_RIGHTS_U_ALL");

$ID = intval($_REQUEST["ID"]); // Id of the edited record
$arError = false;
$bVarsFromForm = false;
$message = /*.(CAdminMessage).*/null;

$arSites = array();
$rsSite = CSite::GetList(($by="sort"), ($order="asc"));
while($arSite = $rsSite->Fetch())
	$arSites[] = $arSite["LID"];

if (in_array(SONET_SUBSCRIBE_ENTITY_NEWS, $arSocNetAllowedSubscribeEntityTypes))
{
	$arIBlockTmp = array();
	$rsIBlock = CIBlock::GetList(
		array("ID"=>"ASC"),
		array("ACTIVE" => "Y", "TYPE" => "news")
	);
	while($arIBlock = $rsIBlock->Fetch())
	{
		$rsIBlockSite = CIBlock::GetSite($arIBlock["ID"]);
		while($arIBlockSite = $rsIBlockSite->Fetch())
		{
			if (!array_key_exists($arIBlockSite["LID"], $arIBlockTmp))
			{
				$arIBlockTmp[$arIBlockSite["LID"]] = array("REFERENCE" => array(), "REFERENCE_ID" => array());
			}

			$arIBlockTmp[$arIBlockSite["LID"]]["REFERENCE"][] = "[".$arIBlock["ID"]."] ".$arIBlock["NAME"];
			$arIBlockTmp[$arIBlockSite["LID"]]["REFERENCE_ID"][] = $arIBlock["ID"];
		}
	}
}

$arSocNetGroupTmp = array();
$rsSocNetGroups = CSocNetGroup::GetList(
	array("ID" => "ASC"),
	array("ACTIVE" => "Y"),
	false,
	false,
	array("ID", "NAME", "SITE_ID")
);
while($arSocNetGroups = $rsSocNetGroups->Fetch())
{
	if (!array_key_exists($arSocNetGroups["SITE_ID"], $arSocNetGroupTmp))
		$arSocNetGroupTmp[$arSocNetGroups["SITE_ID"]] = array("REFERENCE" => array(), "REFERENCE_ID" => array());

	$arSocNetGroupTmp[$arSocNetGroups["SITE_ID"]]["REFERENCE"][] = "[".$arSocNetGroups["ID"]."] ".$arSocNetGroups["NAME"];
	$arSocNetGroupTmp[$arSocNetGroups["SITE_ID"]]["REFERENCE_ID"][] = $arSocNetGroups["ID"];
}

if($_SERVER["REQUEST_METHOD"] === "POST" && check_bitrix_sessid())
{
	if (
		isset($_REQUEST["save"])
		|| isset($_REQUEST["apply"])
	)
	{
		$arUserRights = $arFields = array();
		$res = false;

		if ($_POST["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER)
		{
			if ($_POST["RIGHTS_USER_TYPE"] == "UN")
			{
				$arUserRights = array("UN" => array());
			}
			elseif ($_POST["RIGHTS_USER_TYPE"] == "UA")
			{
				$arUserRights = array("UA" => array());
			}
			elseif (
				is_array($_POST["RIGHTS_USER_ID"])
				&& !empty($_POST["RIGHTS_USER_ID"])
			)
			{
				foreach($_POST["RIGHTS_USER_ID"] as $key => $value)
				{
					if (intval($value) <= 0)
					{
						unset($_POST["RIGHTS_USER_ID"][$key]);
					}
				}
				if (!empty($_POST["RIGHTS_USER_ID"]))
				{
					$arUserRights = array("U" => $_POST["RIGHTS_USER_ID"]);
				}
			}

			if (empty($arUserRights))
			{
				$arError[] = array(
					"id" => "XDIMPORT_RIGHTS",
					"text" => GetMessage("LFP_SCHEME_EDIT_RIGHTS_ERROR")
				);
			}
		}

		if (!$arError)
		{
			$ob = new CXDILFScheme();
			$arFields = array(
				"ACTIVE" => $_POST["ACTIVE"] === "Y"? "Y": "N",
				"ENABLE_COMMENTS" => $_POST["ENABLE_COMMENTS"] === "Y"? "Y": "N",
				"SORT" => $_POST["SORT"],
				"NAME" => $_POST["NAME"],
				"TYPE" => $_POST["TYPE"],
				"LID" => $_POST["LID"],
				"DAYS_OF_MONTH" => $_POST["DAYS_OF_MONTH"],
				"DAYS_OF_WEEK" => (is_array($_POST["DAYS_OF_WEEK"])?implode(",", $_POST["DAYS_OF_WEEK"]):""),
				"TIMES_OF_DAY" => $_POST["TIMES_OF_DAY"],
				"LAST_EXECUTED" => $_POST["LAST_EXECUTED"],
				"METHOD" => $_POST["METHOD"],
				"IS_HTML" => $_POST["IS_HTML"],
				"PARAMS" => $_POST["PARAMS"],
				"LOGIN" => $_POST["LOGIN"],
				"PASSWORD" => $_POST["PASSWORD"],
				"ENTITY_TYPE" => $_POST["ENTITY_TYPE"],
				"EVENT_ID" => ($_POST["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER ? "data" : $_POST["EVENT_ID_".$_POST["ENTITY_TYPE"]])
			);

			if (in_array($_POST["PREDEFINED"], array("stat", "sale")))
			{
				$arFields["HOST"] = $_POST["HOST"];
				$arFields["PAGE"] = $_POST["PAGE"];
			}
			else
			{
				$arFields["URI"] = $_POST["URI"];
			}

			if ($arFields["TYPE"] == "POST")
			{
				$arFields["HASH"] = $_POST["HASH"];
			}

			if ($_POST["ENTITY_TYPE"] != SONET_SUBSCRIBE_ENTITY_PROVIDER)
			{
				if ($_POST["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
				{
					$arFields["ENTITY_ID"] = $_POST["ENTITY_ID_GROUP_".$_POST["LID"]];
				}
				elseif ($_POST["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER)
				{
					$arFields["ENTITY_ID"] = $_POST["ENTITY_ID_USER"];
				}
				elseif (
					$_POST["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_NEWS
					&& in_array(SONET_SUBSCRIBE_ENTITY_NEWS, $arSocNetAllowedSubscribeEntityTypes)
				)
				{
					$arFields["ENTITY_ID"] = $_POST["ENTITY_ID_NEWS_".$_POST["LID"]];
				}
				else
				{
					$arFields["ENTITY_ID"] = $_POST["ENTITY_ID"];
				}
			}

			if ($ID > 0)
			{
				$res = $ob->Update($ID, $arFields);
			}
			else
			{
				$res = $ob->Add($arFields);
			}

			if (
				$res
				&& $_POST["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER
			)
			{
				$ob->Update($res, array("ENTITY_ID" => $res));
			}
		}
		else
		{
			$e = new CAdminException($arError);
			$APPLICATION->ThrowException($e);
		}

		if($res)
		{
			if (
				$_POST["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER 
				&& !empty($arUserRights)
			)
			{
				$obSchemeRights = new CXDILFSchemeRights();
				$obSchemeRights->Set(
					$res, 
					$arUserRights, 
					array(
						"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_PROVIDER,
						"ENTITY_ID" => $res,
						"EVENT_ID" => $arFields["EVENT_ID"]
					)
				);
			}
			elseif(in_array($_POST["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER)))
			{
				CXDILFSchemeRights::DeleteBySchemeID($res);
			}

			if(isset($_REQUEST["apply"]))
				LocalRedirect("/bitrix/admin/xdi_lf_scheme_edit.php?ID=".$res."&lang=".LANGUAGE_ID);
			else
				LocalRedirect("/bitrix/admin/xdi_lf_scheme_list.php?lang=".LANGUAGE_ID);
		}
		else
		{
			$e = $APPLICATION->GetException();
			if(is_object($e))
				$message = new CAdminMessage(GetMessage("LFP_SCHEME_EDIT_SAVE_ERROR"), $e);
			$bVarsFromForm = true;
		}
	}
	elseif(isset($_REQUEST["delete"]))
	{
		$ob = new CXDILFScheme();
		if($ob->Delete($ID))
			LocalRedirect("/bitrix/admin/xdi_lf_scheme_list.php?lang=".LANGUAGE_ID);
		else
			$bVarsFromForm = true;
	}
}

if($ID > 0)
{
	$rs = CXDILFScheme::getByID($ID);
	if ($arRes = $rs->fetch())
	{
		$scheme_type = $arRes["TYPE"];
		if ($arRes["TYPE"] == "XML")
		{
			if ($arRes["PAGE"] == "/bitrix/tools/stat_gadget.php" && $arRes["METHOD"] == "GetLiveFeedData")
			{
				$predefined = "stat";
			}
			if ($arRes["PAGE"] == "/bitrix/tools/sale_gadget.php" && $arRes["METHOD"] == "GetLiveFeedData")
			{
				$predefined = "sale";
			}
		}
		if (strlen($arRes["URI"]) <= 0)
		{
			$arRes["URI"] = "http://".$arRes["HOST"].(intval($arRes["PORT"]) > 0 ? ":".$arRes["PORT"] : "").$arRes["PAGE"].($arRes["TYPE"] == "RSS" && strlen($arRes["PARAMS"]) > 0 ? "?".$arRes["PARAMS"] : "");
		}

		$DAYS_OF_WEEK = explode(",", $arRes["DAYS_OF_WEEK"]);
		$rsSchemeRights = CXDILFSchemeRights::GetList(array("GROUP_CODE" => "ASC"), array("SCHEME_ID" => $ID));

		while($arSchemeRights = $rsSchemeRights->Fetch())
		{
			if (substr($arSchemeRights["GROUP_CODE"], 0, 1) == "U")
			{
				if (substr($arSchemeRights["GROUP_CODE"], 1) == "A")
				{
					$arRes["RIGHTS_USER_ID"][] = "UA";
					break;
				}
				elseif(substr($arSchemeRights["GROUP_CODE"], 1) == "N")
				{
					$arRes["RIGHTS_USER_ID"][] = "UN";
					break;
				}
				elseif(intval(substr($arSchemeRights["GROUP_CODE"], 1)) > 0)
				{
					$arRes["RIGHTS_USER_ID"][] = substr($arSchemeRights["GROUP_CODE"], 1);
				}
			}
		}
	}
}
else
{
	$DAYS_OF_WEEK = array();
	$arRes = array(
		"LID" => $arSites[0],
		"SORT" => 500,
		"NAME" => "",
		"ACTIVE" => "Y",
		"ENABLE_COMMENTS" => "Y",
		"DAYS_OF_MONTH" => "",
		"DAYS_OF_WEEK" => "",
		"TIMES_OF_DAY" => "",
		"LAST_EXECUTED" => "",
		"URI" => "",
		"METHOD" => "",
		"IS_HTML" => "N",
		"PARAMS" => "",
		"LOGIN" => "",
		"PASSWORD" => "",
		"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_PROVIDER,
		"ENTITY_ID" => 0,
		"EVENT_ID" => "",
		"HASH" => ""
	);
}

if (!$scheme_type)
{
	$scheme_type = $_REQUEST["TYPE"];
}

if($bVarsFromForm)
{
	$arRes = array(
		"ACTIVE" => $_POST["ACTIVE"] === "Y"? "Y": "N",
		"ENABLE_COMMENTS" => $_POST["ENABLE_COMMENTS"] === "Y"? "Y": "N",
		"SORT" => $_POST["SORT"],
		"NAME" => $_POST["NAME"],
		"TYPE" => $_POST["TYPE"],
		"LID" => $_POST["LID"],
		"DAYS_OF_MONTH" => $_POST["DAYS_OF_MONTH"],
		"DAYS_OF_WEEK" => (is_array($_POST["DAYS_OF_WEEK"])?implode(",", $_POST["DAYS_OF_WEEK"]):""),
		"TIMES_OF_DAY" => $_POST["TIMES_OF_DAY"],
		"LAST_EXECUTED" => $_POST["LAST_EXECUTED"],
		"URI" => $_POST["URI"],
		"METHOD" => $_POST["METHOD"],
		"IS_HTML" => $_POST["IS_HTML"] === "Y"? "Y": "N",
		"PARAMS" => $_POST["PARAMS"],
		"LOGIN" => $_POST["LOGIN"],
		"PASSWORD" => $_POST["PASSWORD"],
		"ENTITY_TYPE" => $_POST["ENTITY_TYPE"],
		"ENTITY_ID" => $_POST["ENTITY_ID"],
		"EVENT_ID" => $_POST["EVENT_ID_".$_POST["ENTITY_TYPE"]],
		"RIGHTS_USER_ID" => $_POST["RIGHTS_USER_ID"],
		"HASH" => $_POST["HASH"]
	);
	$DAYS_OF_WEEK = explode(",", $arRes["DAYS_OF_WEEK"]);
}

$APPLICATION->SetTitle(($ID > 0? GetMessage("LFP_SCHEME_EDIT_EDIT_TITLE") : GetMessage("LFP_SCHEME_EDIT_ADD_TITLE")));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => GetMessage("LFP_SCHEME_EDIT_TAB1"),
		"TITLE" => GetMessage("LFP_SCHEME_EDIT_TAB1_TITLE"),
	)
);
	
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$aMenu = array(
	array(
		"TEXT" => GetMessage("LFP_SCHEME_EDIT_MENU_LIST"),
		"TITLE" => GetMessage("LFP_SCHEME_EDIT_MENU_LIST_TITLE"),
		"LINK" => "xdi_lf_scheme_list.php?lang=".LANGUAGE_ID,
		"ICON" => "btn_list",
	)
);
$context = new CAdminContextMenu($aMenu);
$context->Show();

if(is_object($message))
{
	echo $message->Show();
}
?><style type="text/css">
	input.xdimport-finduser-input { margin: 0; width: 85px;}
</style>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>"  enctype="multipart/form-data" name="editform" id="editform"><?

$tabControl->Begin();
$tabControl->BeginNextTab();

	if($ID > 0)
	{
		?>
		<tr>
			<td><?=GetMessage("LFP_SCHEME_EDIT_ID")?>:</td>
			<td><?=$ID;?></td>
		</tr>
		<?
	}
	?>
	<tr>
		<td width="40%"><?=GetMessage("LFP_SCHEME_EDIT_TYPE")?>:</td>
		<td width="60%"><?=HtmlFilter::encode($scheme_type)?></td>
	</tr>
	<tr>
		<td><?=GetMessage("LFP_SCHEME_EDIT_ACTIVE")?>:</td>
		<td><input type="checkbox" name="ACTIVE" value="Y"<?if($arRes["ACTIVE"] === "Y") echo " checked"?>></td>
	</tr>
	<tr id="LID_ROW">
		<td><?=GetMessage("LFP_SCHEME_EDIT_SITE")?>:</td>
		<td>
			<?=CLang::SelectBox("LID", $arRes["LID"])?>
			<script type="text/javascript">
				BX.ready(function()
					{
						var LIDInput = BX.findChild(BX('LID_ROW'), { 'tag': 'select'}, true);
						BX.bind(LIDInput, 'change', function() {
							var
								arGroupSiteSpan = null,
								i = null;

							if (BX('LF_ENTITY_ID_GROUP_ROW'))
							{
								arGroupSiteSpan = BX.findChildren(BX('LF_ENTITY_ID_GROUP_ROW'), {'tag':'span'}, true);
								for (i = 0; i < arGroupSiteSpan.length; i++)
								{
									arGroupSiteSpan[i].style.display = 'none';
								}
							}
							if (BX('LF_ENTITY_ID_GROUP_' + LIDInput.options[LIDInput.selectedIndex].value + '_ROW'))
								BX('LF_ENTITY_ID_GROUP_' + LIDInput.options[LIDInput.selectedIndex].value + '_ROW').style.display = "inline-block";

							if (BX('LF_ENTITY_ID_NEWS_ROW'))
							{
								arGroupSiteSpan = BX.findChildren(BX('LF_ENTITY_ID_NEWS_ROW'), {'tag':'span'}, true);
								for (i = 0; i < arGroupSiteSpan.length; i++)
								{
									arGroupSiteSpan[i].style.display = 'none';
								}
							}

							if (BX('LF_ENTITY_ID_NEWS_' + LIDInput.options[LIDInput.selectedIndex].value + '_ROW'))
							{
								BX('LF_ENTITY_ID_NEWS_' + LIDInput.options[LIDInput.selectedIndex].value + '_ROW').style.display = "inline-block";
							}
						});
					}
				);
			</script>
		</td>
	</tr>	
	<tr class="adm-detail-required-field">
		<td><?=GetMessage("LFP_SCHEME_EDIT_NAME")?>:</td>
		<td><input type="text" size="50" maxlength="100" name="NAME" value="<?=HtmlFilter::encode($arRes["NAME"])?>"></td>
	</tr>
	<tr>
		<td><?=GetMessage("LFP_SCHEME_EDIT_SORT")?>:</td>
		<td><input type="text" size="6" name="SORT" value="<?=intval($arRes["SORT"])?>"></td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?=GetMessage("LFP_SCHEME_EDIT_DESTINATION")?></td>
	</tr>
	<tr class="adm-detail-required-field">
		<td><?=GetMessage("LFP_SCHEME_EDIT_ENTITY_TYPE")?>:</td>
		<td>
		<select name="ENTITY_TYPE" id="LF_ENTITY_TYPE" onchange="__LFPSchemeChangeEntityType(this)"><?
			foreach ($arEntityTypes as $entity_type => $entity_type_title):
				?><option value="<?=$entity_type?>"<?=($entity_type == $arRes["ENTITY_TYPE"] ? " selected" : "")?>><?=$entity_type_title?></option><?
			endforeach;
		?></select>
		<script type="text/javascript">
			var arSchemeEventTypes = [];
			var arSchemeEvents = [];
			var arEventsCnt = [];
			<?
			$arEventsCnt = array();
			foreach ($arEvents as $entity_type => $arTmp)
			{
				echo "if(!BX.util.in_array('".$entity_type."', arSchemeEventTypes)) arSchemeEventTypes[arSchemeEventTypes.length] = '".$entity_type."';";
				echo "arSchemeEvents['". $entity_type."'] = [];\n";

				$tmpCnt = 0;
				foreach ($arTmp as $event_id => $arEventTmp)
				{
					if (
						($arEventTmp["ALLOWED"] == "Y")
						|| ($event_id == $arRes["EVENT_ID"])
					)
					{
						echo "arSchemeEvents['". $entity_type."'][arSchemeEvents['". $entity_type."'].length] = '".$arEventTmp["TITLE"]."';\n";
						$tmpCnt++;
					}
				}
				$arEventsCnt[$entity_type] = $tmpCnt;
				echo "arEventsCnt['". $entity_type."'] = ".$tmpCnt.";\n";
			}
			?>
			if ('__LFPSchemeChangeEntityType' != typeof window.noFunc) 
			{
				function __LFPSchemeChangeEntityType(el)
				{
					var number = el.selectedIndex;
					var arEventsDiv = BX.findChildren(BX('lfp_events_container'), {'tag':'div'}, true);
					var arRightsDiv = BX.findChildren(BX('lfp_rights_container'), {'tag':'div'}, true);
					var i = null;

					if (el.options[number].value == '<?=SONET_SUBSCRIBE_ENTITY_PROVIDER?>')
					{
						BX('LF_ENTITY_ID_ROW').style.display = 'none';
						BX('LF_EVENT_ID_ROW').style.display = 'none';
						BX('LF_ENTITY_ID_GROUP_ROW').style.display = 'none';
						BX('LF_ENTITY_ID_USER_ROW').style.display = 'none';
						if (BX('LF_ENTITY_ID_NEWS_ROW'))
						{
							BX('LF_ENTITY_ID_NEWS_ROW').style.display = 'none';
						}
						BX('LF_ENABLE_COMMENTS_ROW').style.display = 'table-row';
					}
					else
					{
						if (el.options[number].value == '<?=SONET_SUBSCRIBE_ENTITY_USER?>')
						{
							BX('LF_ENTITY_ID_ROW').style.display = 'none';
							BX('LF_ENTITY_ID_GROUP_ROW').style.display = 'none';
							if (BX('LF_ENTITY_ID_NEWS_ROW'))
								BX('LF_ENTITY_ID_NEWS_ROW').style.display = 'none';
							BX('LF_ENTITY_ID_USER_ROW').style.display = 'table-row';
						}
						else if (el.options[number].value == '<?=SONET_SUBSCRIBE_ENTITY_GROUP?>')
						{
							BX('LF_ENTITY_ID_ROW').style.display = 'none';
							BX('LF_ENTITY_ID_USER_ROW').style.display = 'none';
							if (BX('LF_ENTITY_ID_NEWS_ROW'))
								BX('LF_ENTITY_ID_NEWS_ROW').style.display = 'none';
							BX('LF_ENTITY_ID_GROUP_ROW').style.display = 'table-row';
						}
						else if (el.options[number].value == '<?=SONET_SUBSCRIBE_ENTITY_NEWS?>')
						{
							BX('LF_ENTITY_ID_ROW').style.display = 'none';
							BX('LF_ENTITY_ID_USER_ROW').style.display = 'none';
							BX('LF_ENTITY_ID_GROUP_ROW').style.display = 'none';
							if (BX('LF_ENTITY_ID_NEWS_ROW'))
								BX('LF_ENTITY_ID_NEWS_ROW').style.display = 'table-row';
						}
						else if (el.options[number].value == '')
						{
							BX('LF_ENTITY_ID_ROW').style.display = 'none';
							BX('LF_ENTITY_ID_USER_ROW').style.display = 'none';
							BX('LF_ENTITY_ID_GROUP_ROW').style.display = 'none';
							if (BX('LF_ENTITY_ID_NEWS_ROW'))
								BX('LF_ENTITY_ID_NEWS_ROW').style.display = 'none';
						}
						else
						{
							BX('LF_ENTITY_ID_GROUP_ROW').style.display = 'none';
							BX('LF_ENTITY_ID_USER_ROW').style.display = 'none';
							if (BX('LF_ENTITY_ID_NEWS_ROW'))
							{
								BX('LF_ENTITY_ID_NEWS_ROW').style.display = 'none';
							}
							BX('LF_ENTITY_ID_ROW').style.display = 'table-row';

							if (BX('LF_ENTITY_ID')) __RecalcEntityDesc();
						}

						if (arEventsCnt[el.options[number].value] > 1)
						{
							BX('LF_EVENT_ID_ROW').style.display = 'table-row';
						}

						for (i = 0; i < arEventsDiv.length; i++)
						{
							arEventsDiv[i].style.display = 'none';
						}

						if (BX('LF_EVENT_ID_' + el.options[number].value + '_DIV'))
						{
							BX('LF_EVENT_ID_' + el.options[number].value + '_DIV').style.display = 'block';
						}

						BX('LF_ENABLE_COMMENTS_ROW').style.display = 'none';
					}
					
					for (i = 0; i < arRightsDiv.length; i++)
					{
						arRightsDiv[i].style.display = 'none';
					}

					if (BX('LF_RIGHTS_' + el.options[number].value + '_DIV'))
					{
						BX('LF_RIGHTS_' + el.options[number].value + '_DIV').style.display = 'block';
						BX('LF_RIGHTS_ROW').style.display = 'table-row'
					}
					else
					{
						BX('LF_RIGHTS_ROW').style.display = 'none'
					}
				}
			}
		</script>
		</td>
	</tr>
	<tr id="LF_ENTITY_ID_ROW" style="display: <?=(in_array($arRes["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER, SONET_SUBSCRIBE_ENTITY_NEWS, SONET_SUBSCRIBE_ENTITY_PROVIDER)) ? "none" : "table-row")?>;">
		<td><?=GetMessage("LFP_SCHEME_EDIT_ENTITY_ID")?>:</td>
		<td>
		<input type="text" size="3" name="ENTITY_ID" id="LF_ENTITY_ID" value="<?=intval($arRes["ENTITY_ID"])?>">
		<script type="text/javascript">

			function __RecalcEntityDesc(e)
			{
				if(!e)
				{
					e = window.event;
				}

				var entity_type = BX("LF_ENTITY_TYPE").value;
				var entity_id = BX("LF_ENTITY_ID").value;
				var node_div = BX("LF_ENTITY_DESC");
				if (parseInt(entity_id) > 0)
				{
					BX.ajax.post(
						"xdi_lf_scheme_getentity.php",
						"entity_type="+entity_type+"&entity_id="+entity_id,
						function(result)
						{
							node_div.innerHTML = result;
						}
					);
				}
			}

			BX.ready(function()
				{
					if (BX('LF_ENTITY_ID'))
					{
						BX.bind(BX('LF_ENTITY_ID'), 'keyup', __RecalcEntityDesc);
						BX.bind(BX('LF_ENTITY_ID'), 'blur', __RecalcEntityDesc);
					}
				}
			);
		</script>
		<? if (intval($arRes["ENTITY_ID"]) > 0 && !in_array($arRes["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER, SONET_SUBSCRIBE_ENTITY_NEWS, SONET_SUBSCRIBE_ENTITY_PROVIDER))):?>
			<script type="text/javascript">
				BX.ready(function()
					{
						if (BX('LF_ENTITY_ID')) __RecalcEntityDesc();
					}
				);
			</script>
		<? endif;?>
		<span id="LF_ENTITY_DESC" style="display: inline-block;"></span>
		</td>
	</tr>
	<tr id="LF_ENTITY_ID_USER_ROW" style="display: <?=(in_array($arRes["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_USER)) ? "table-row" : "none")?>;">
		<td><?=GetMessage("LFP_SCHEME_EDIT_ENTITY_ID_USER")?>:</td>
		<td><?=FindUserID("ENTITY_ID_USER", $arRes["ENTITY_ID"], "", "editform", "10", "", "...", "", "");?></td>
	</tr>
	<tr id="LF_ENTITY_ID_GROUP_ROW" style="display: <?=(in_array($arRes["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP)) ? "table-row" : "none")?>;">
		<td><?=GetMessage("LFP_SCHEME_EDIT_ENTITY_ID_GROUP")?>:</td>
		<td><? 
		foreach($arSites as $site_id_tmp)
		{
			if (array_key_exists($site_id_tmp, $arSocNetGroupTmp) && count($arSocNetGroupTmp[$site_id_tmp]["REFERENCE_ID"]) > 0)
			{
				?><span id="LF_ENTITY_ID_GROUP_<?=$site_id_tmp?>_ROW" style="display: <?=($arRes["LID"] == $site_id_tmp ? "inline-block" : "none")?>;"><?
					echo SelectBoxFromArray("ENTITY_ID_GROUP_".$site_id_tmp, $arSocNetGroupTmp[$site_id_tmp], intval($arRes["ENTITY_ID"]), "", "", false, "editform");
				?></span><?
			}
		}
		?></td>
	</tr>
	<?
	if (in_array(SONET_SUBSCRIBE_ENTITY_NEWS, $arSocNetAllowedSubscribeEntityTypes))
	{
		?>
		<tr id="LF_ENTITY_ID_NEWS_ROW" style="display: <?=(in_array($arRes["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_NEWS)) ? "table-row" : "none")?>;">
			<td><?=GetMessage("LFP_SCHEME_EDIT_ENTITY_ID_NEWS")?>:</td>
			<td><? 
			foreach($arSites as $site_id_tmp)
			{
				if (array_key_exists($site_id_tmp, $arIBlockTmp) && count($arIBlockTmp[$site_id_tmp]["REFERENCE_ID"]) > 0)
				{
					?><span id="LF_ENTITY_ID_NEWS_<?=$site_id_tmp?>_ROW" style="display: <?=($arRes["LID"] == $site_id_tmp ? "inline-block" : "none")?>;"><?
						echo SelectBoxFromArray("ENTITY_ID_NEWS_".$site_id_tmp, $arIBlockTmp[$site_id_tmp], intval($arRes["ENTITY_ID"]), "", "", false, "editform");
					?></span><?
				}
			}
			?></td>
		</tr>
		<?
	}
	?>
	<tr id="LF_EVENT_ID_ROW" style="display: <?=($arEventsCnt[$arRes["ENTITY_TYPE"]] != 1 && in_array($arRes["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER)) ? "table-row" : "none")?>;">
		<td class="adm-detail-required-field"><?=GetMessage("LFP_SCHEME_EDIT_EVENT_ID")?>:</td>
		<td id="lfp_events_container"><?
		foreach ($arEntityTypes as $entity_type => $entity_type_title)
		{
			?><div id="LF_EVENT_ID_<?=$entity_type?>_DIV" style="display: <?=($entity_type == $arRes["ENTITY_TYPE"] ? "block" : "none")?>;"><?
				if ($arEventsCnt[$entity_type] == 1)
				{
						$tmpVal = "";
						foreach ($arEvents[$entity_type] as $event_id => $arEventTmp)
						{
							if (
								($arEventTmp["ALLOWED"] == "Y")
								|| ($event_id == $arRes["EVENT_ID"])
							)
							{
								$tmpVal = $event_id;
								break;
							}
						}
					?><input type="hidden" name="EVENT_ID_<?=$entity_type?>" value="<?=$tmpVal?>"><?
				}
				else
				{
					?><select name="EVENT_ID_<?=$entity_type?>">
						<option value=""><?=GetMessage("LFP_SCHEME_EDIT_SELECT_EMPTY")?></option><?
						foreach ($arEvents[$entity_type] as $event_id => $arEventTmp)
						{
							if (
								($arEventTmp["ALLOWED"] == "Y")
								|| ($event_id == $arRes["EVENT_ID"])
							)
							{
								?><option value="<?=$event_id?>"<?=($event_id == $arRes["EVENT_ID"] ? " selected" : "")?>>[<?=$event_id?>] <?=$arEventTmp["TITLE"]?></option><?
							}
						}
					?></select><?
				}
			?></div><?
		}
		?></td>
	</tr>
	<tr id="LF_RIGHTS_ROW" style="display: <?=($arRes["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER ? "table-row" : "none")?>;" class="adm-detail-required-field">
		<td class="adm-detail-valign-top"><?=GetMessage("LFP_SCHEME_EDIT_RIGHTS")?>:</td>
		<td id="lfp_rights_container">
		<?
		foreach ($arEntityTypes as $entity_type => $entity_type_title):
			if ($entity_type == SONET_SUBSCRIBE_ENTITY_PROVIDER):
				?>
				<div id="LF_RIGHTS_<?=$entity_type?>_DIV" style="display: <?=($entity_type == $arRes["ENTITY_TYPE"] ? "block" : "none")?>;">
					<select name="RIGHTS_USER_TYPE"  onchange="__LFPSchemeChangeUserRightsType(this)">
						<option value="UN" <?=(is_array($arRes["RIGHTS_USER_ID"]) && $arRes["RIGHTS_USER_ID"][0] == "UN" ? "selected" : "")?>><?=GetMessage("LFP_SCHEME_EDIT_RIGHTS_P_ANONYMOUS")?></option>
						<option value="UA" <?=(is_array($arRes["RIGHTS_USER_ID"]) && $arRes["RIGHTS_USER_ID"][0] == "UA" ? "selected" : "")?>><?=GetMessage("LFP_SCHEME_EDIT_RIGHTS_P_AUTHORIZED")?></option>
						<option value="US" <?=(is_array($arRes["RIGHTS_USER_ID"]) && !in_array($arRes["RIGHTS_USER_ID"][0], array("UA", "UN")) ? "selected" : "")?>><?=GetMessage("LFP_SCHEME_EDIT_RIGHTS_P_USERS_SELECTED")?></option>
					</select>

					<script type="text/javascript">
						if ('__LFPSchemeChangeUserRightsType' != typeof window.noFunc) 
						{
							function __LFPSchemeChangeUserRightsType(el)
							{
								if (BX("LF_RIGHTS_USER_US"))
									BX("LF_RIGHTS_USER_US").style.display = (el.options[el.selectedIndex].value == "US" ? "block" : "none");
							}
						}
					</script>

					<span id="LF_RIGHTS_USER_US" style="display: <?=(is_array($arRes["RIGHTS_USER_ID"]) && !in_array($arRes["RIGHTS_USER_ID"][0], array("UA", "UN")) ? "block" : "none")?>;">
						<table>
						<tbody>
						<?
						if (
							is_array($arRes["RIGHTS_USER_ID"])
							&& !empty($arRes["RIGHTS_USER_ID"])
						)
						{
							$key = false;
							foreach($arRes["RIGHTS_USER_ID"] as $key=>$user_id_tmp)
							{
								?><tr>
									<td>
										<a href="javascript:void(0)" onClick="lfpDropRow(this)" style="display: inline-block; vertical-align: middle; cursor: pointer;"><img border="0" width="20" height="20" src="/bitrix/themes/.default/images/actions/delete_button.gif"></a><?
										$sUser = "";
										if($user_id_tmp > 0)
										{
											$rsUser = CUser::GetByID($user_id_tmp);
											$arUser = $rsUser->GetNext();
											if($arUser)
												$sUser = "[<a href=\"user_edit.php?ID=".$arUser["ID"]."&amp;lang=".LANG."\">".$arUser["ID"]."</a>] (".$arUser["LOGIN"].") ".$arUser["NAME"]." ".$arUser["LAST_NAME"];
										}
										echo FindUserID("RIGHTS_USER_ID_".$key, ($user_id_tmp > 0 ? $user_id_tmp : ""), $sUser, "editform", "10", "", "...", "xdimport-finduser-input", "");
										?>
										<script type="text/javascript">
											BX.ready(function() { 
												setTimeout(function(){
													if (BX('RIGHTS_USER_ID_<?=$key?>')) BX.adjust(BX('RIGHTS_USER_ID_<?=$key?>'), { props: {'name': 'RIGHTS_USER_ID[]'} }); 
												}, 3500);
											});
										</script>
									</td>
								</tr><?
							}
							$max_key = $key;
						}
						else
						{
							?><tr>
								<td>
									<span style="display: inline-block; width: 20px; height: 20px;"></span><?
									echo FindUserID("RIGHTS_USER_ID_0", "", "", "editform", "10", "", "...", "xdimport-finduser-input", "");?>
									<script type="text/javascript">
										BX.ready(function() { 
											setTimeout(function(){
												if (BX('RIGHTS_USER_ID_0')) BX.adjust(BX('RIGHTS_USER_ID_0'), { props: {'name': 'RIGHTS_USER_ID[]'} }); 
											}, 3500);
										});
									</script>
								</td>
							</tr><?
							$max_key = 0;
						}
						?>
						<tr>
							<td><a href="javascript:void(0)" onclick="lfpAddUser(this)" hidefocus="true" class="bx-action-href"><?=GetMessage("LFP_SCHEME_EDIT_RIGHTS_P_ADD")?></a></td>
						</tr>
						</tbody>
						</table>
					</span>
					<script type="text/javascript">
						BX.CRightsUserRow = function(arParams) 
						{
							this.row = arParams.row;
						};

						BX.CRightsUserRow.prototype.RecalcRightsUserDesc = function()
						{
							var userInput = BX.findChild(this.row, { 'tag': 'input'}, true);
							var entity_id = userInput.value;
							var node_div = BX.findChild(this.row, { 'tag': 'span'}, true);

							if (parseInt(entity_id) > 0)
							{
								BX.ajax.post("xdi_lf_scheme_getentity.php", "entity_type=U&entity_id="+entity_id, function(result)
								{
									node_div.innerHTML = result;
								})
							}
							else
							{
								node_div.innerHTML = "";
							}
						};

						function lfpAddUser(a)
						{
							var row = BX.findParent(a, { 'tag': 'tr'});
							var tbl = row.parentNode;

							var userInput = BX.findChild(tbl.rows[row.rowIndex-1], { 'tag': 'input'}, true);
							var iTmp = userInput.id.substr(15);
							iTmp++;

							var q = function () {
								var tv_xxx= '';
								var DV_xxx = BX("div_RIGHTS_USER_ID_" + iTmp);
								if (tv_xxx != BX('RIGHTS_USER_ID_' + iTmp).value)
								{
									tv_xxx = BX('RIGHTS_USER_ID_' + iTmp).value;
									if (tv_xxx != '')
									{
										DV_xxx.innerHTML = '<i><?=GetMessage("LFP_SCHEME_EDIT_RIGHTS_WAIT")?></i>';
										BX("hiddenframeRIGHTS_USER_ID_" + iTmp).src='/bitrix/admin/get_user.php?ID=' + tv_xxx+'&strName=RIGHTS_USER_ID_' + iTmp + '&lang=<?=LANGUAGE_ID?>&admin_section=Y';
									}
									else
									{
										DV_xxx.innerHTML = '';
									}
								}
							};

							var tableRow = BX.create('tr', {
								children: [
									BX.create('td', {
										children: [
											BX.create('a', {
												style: {
													'display': 'inline-block',
													'verticalAlign': 'middle',
													'cursor': 'pointer'
												},
												events: {
													'click': function() { lfpDropRow(this); }
												},
												children: [
													BX.create('img', {
														attrs: {
															'border': '0',
															'width': '20',
															'height': '20',
															'src': '/bitrix/themes/.default/images/actions/delete_button.gif'
														}
													})
												]
											}),
											BX.create('input', {
												attrs: {
													'size': 10
												},
												props: {
													id: 'RIGHTS_USER_ID_' + iTmp,
													name: 'RIGHTS_USER_ID[]',
													type: 'text',
													className: 'xdimport-finduser-input'
												},
												events: {
													'keyup': q,
													'change': q
												},
												style: {
													'marginLeft': '4px',
													'marginRight': '8px'
												}
											}),
											BX.create('iframe', {
												props: {
													id: 'hiddenframeRIGHTS_USER_ID_' + iTmp
												},
												style: {
													'width': '0px',
													'height': '0px',
													'border': '0px'
												},
												children: [
													BX.create('html', {
														children: [
															BX.create('head', {}),
															BX.create('body', {html: '&nbsp;'})
														]
													})
												]
											}),
											BX.create('input', {
												props: {
													id: 'FindUser',
													type: 'button',
													value: '...'
												},
												events: {
													'click': function() { window.open('/bitrix/admin/user_search.php?lang=<?=LANGUAGE_ID?>&FN=editform&FC=RIGHTS_USER_ID_' + iTmp, '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5)); }
												}
											}),
											BX.create('span', {
												html: ' '
											}),
											BX.create('span', {
												props: {
													id: 'div_RIGHTS_USER_ID_' + iTmp,
													className: 'adm-filter-text-search'
												}
											})
										]
									})
								]
							});
							tbl.insertBefore(tableRow, row);
						}

						function lfpDropRow(a)
						{
							BX.remove(BX.findParent(a, {'tag': 'tr'}));
						}

					</script>
				</div>
				<?
			endif;
		endforeach;?>
		</td>
	</tr>
	<tr id="LF_ENABLE_COMMENTS_ROW" style="display: <?=(in_array($arRes["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_PROVIDER)) ? "table-row" : "none")?>;">
		<td><?=GetMessage("LFP_SCHEME_EDIT_ENABLE_COMMENTS")?>:</td>
		<td width="60%"><input type="checkbox" name="ENABLE_COMMENTS" value="Y"<?=($arRes["ENABLE_COMMENTS"] === "Y" ? " checked" : "")?>></td>
	</tr>
	<?
	if (in_array($scheme_type, array("XML", "RSS")))
	{
		?>
		<tr class="heading">
			<td colspan="2"><?=GetMessage("LFP_SCHEME_EDIT_SOURCE")?></td>
		</tr>
		<?
		if ($scheme_type == "XML")
		{
			?>
			<tr>
				<td><?=GetMessage("LFP_SCHEME_EDIT_XML_PREDEFINED")?></td>
				<td>
					<script type="text/javascript">
						if ('___LFPChangePredefined' != typeof window.noFunc) 
						{
							function ___LFPChangePredefined(el)
							{
								if (
									el.options[el.selectedIndex].value == "stat"
									|| el.options[el.selectedIndex].value == "sale"
								)
								{
									BX("LF_URI_ROW").style.display = "none";
									BX("LF_METHOD_ROW").style.display = "none";
									BX("LF_HOST_ROW").style.display = "table-row";

									BX("LF_METHOD").value = "GetLiveFeedData";
									if (BX("LF_PARAMS").value.length == 0)
										BX("LF_PARAMS").value = "lang=<?echo LANGUAGE_ID?>";
								
									if (el.options[el.selectedIndex].value == "stat")
										BX("LF_PAGE").value = "/bitrix/tools/stat_gadget.php";
									else
										BX("LF_PAGE").value = "/bitrix/tools/sale_gadget.php";
								}
								else
								{
									BX("LF_URI_ROW").style.display = "table-row";
									BX("LF_METHOD_ROW").style.display = "table-row";
									BX("LF_HOST_ROW").style.display = "none";
								}
							}
						}
					</script>
					<select onchange="___LFPChangePredefined(this)" name="PREDEFINED">
						<option value=""><?=GetMessage("LFP_SCHEME_EDIT_SELECT_NONE")?></option>
						<option value="stat" <?=($predefined == "stat" ? "selected" : "")?>><?=GetMessage("LFP_SCHEME_EDIT_XML_PREDEFINED_STAT")?></option>
						<option value="sale" <?=($predefined == "sale" ? "selected" : "")?>><?=GetMessage("LFP_SCHEME_EDIT_XML_PREDEFINED_SALE")?></option>
					</select>
				</td>
			</tr>
			<?
		}
		?>
		<tr id="LF_URI_ROW" style="display: <?=(strlen($predefined) > 0 ? "none" : "table-row")?>;" class="adm-detail-required-field">
			<td><?echo GetMessage("LFP_SCHEME_EDIT_URI")?>:</td>
			<td><input id="LF_URI" type="text" size="50" name="URI" value="<?=HtmlFilter::encode($arRes["URI"])?>"></td>
		</tr>
		<tr id="LF_HOST_ROW" style="display: <?=(strlen($predefined) > 0 ? "table-row" : "none")?>;" class="adm-detail-required-field">
			<td><?echo GetMessage("LFP_SCHEME_EDIT_HOST")?>:</td>
			<td>
				<input id="LF_HOST" type="text" size="50" name="HOST" value="<?=HtmlFilter::encode($arRes["HOST"]).(intval($arRes["PORT"]) > 0 ? ":".HtmlFilter::encode($arRes["PORT"]) : "")?>">
				<input id="LF_PAGE" type="hidden" name="PAGE" value="<?=HtmlFilter::encode($arRes["PAGE"])?>">
			</td>
		</tr>
		<?
		if ($scheme_type == "XML")
		{
			?><tr id="LF_METHOD_ROW" style="display: <?=(strlen($predefined) > 0 ? "none" : "table-row")?>;">
				<td><?=GetMessage("LFP_SCHEME_EDIT_METHOD")?>:</td>
				<td><input type="text" id="LF_METHOD" size="50" name="METHOD" value="<?=HtmlFilter::encode($arRes["METHOD"])?>"></td>
			</tr>
			<tr>
				<td><?=GetMessage("LFP_SCHEME_EDIT_METHOD_PARAMS")?>:</td>
				<td><input type="text" id="LF_PARAMS" size="50" name="PARAMS" value="<?=HtmlFilter::encode($arRes["PARAMS"])?>"></td>
			</tr>
			<tr>
				<td><?=GetMessage("LFP_SCHEME_EDIT_LOGIN")?>:</td>
				<td><input type="text" size="20" name="LOGIN" value="<?=HtmlFilter::encode($arRes["LOGIN"])?>"></td>
			</tr>
			<tr>
				<td><?=GetMessage("LFP_SCHEME_EDIT_PASSWORD")?>:</td>
				<td><input type="password" size="20" name="PASSWORD" value="<?=HtmlFilter::encode($arRes["PASSWORD"])?>"></td>
			</tr><?
		}

		if (in_array($scheme_type, array("RSS")))
		{
			?><tr id="LF_IS_HTML_ROW">
				<td><?=GetMessage("LFP_SCHEME_EDIT_IS_HTML_".$scheme_type)?>:</td>
				<td><input type="checkbox" id="IS_HTML" name="IS_HTML" value="Y"<?if($arRes["IS_HTML"] === "Y") echo " checked"?>></td>
			</tr><?
		}
	}
	elseif (in_array($scheme_type, array("POST")))
	{
		?>
		<tr class="heading">
			<td colspan="2"><?=GetMessage("LFP_SCHEME_EDIT_POINT_PARAMS")?></td>
		</tr>
		<tr>
			<td><?=GetMessage("LFP_SCHEME_EDIT_POINT_PAGE")?>:</td>
			<td><?
				$server_name = COption::GetOptionString("main", "server_name", $GLOBALS["SERVER_NAME"]);
				$protocol = (CMain::IsHTTPS() ? "https" : "http");
				$point_page = $protocol."://".$server_name.COption::GetOptionString("xdimport", "point_page", "/bitrix/tools/xdi_livefeed.php");
				?><?=$point_page?></td>
		</tr>
		<tr>
			<td class="adm-detail-valign-top"><?=GetMessage("LFP_SCHEME_EDIT_POINT_POST_PARAMS")?>:</td>
			<td>
				<table cellspacing="0" cellpadding="2" border="0" class="edit-table">
				<tbody>
					<tr>
						<td><b>hash</b></td>
						<td>
							<script type="text/javascript">
							if ('__LFPSchemeClearHash' != typeof window.noFunc) 
							{
								function __LFPSchemeClearHash(el)
								{
									ShowWaitWindow();
									BX.ajax.post(
										'xdi_lf_scheme_changehash.php',
										{
											'scheme_id': '<?=$ID?>', 
											'sessid': BX.bitrix_sessid()
										},
										function(result){
											BX('LF_HASH').value = result;
											BX('LF_HASH_SPAN').innerHTML = result;
											CloseWaitWindow();
										}
									);
								}
							}
							</script>
							<?
							if (intval($ID) > 0)
							{
								?>
								<span id="LF_HASH_SPAN"><?=HtmlFilter::encode($arRes["HASH"])?></span>
								<input type="hidden" id="LF_HASH" name="HASH" value="<?=HtmlFilter::encode($arRes["HASH"])?>">
								<a href="#" onclick="__LFPSchemeClearHash(); return false;"><?=GetMessage("LFP_SCHEME_EDIT_POINT_HASH_CHANGE")?></a>
								<?
							}
							else
							{
								echo GetMessage("LFP_SCHEME_EDIT_POINT_NO_HASH");
							}
						?></td>
					</tr>
					<tr>
						<td><b>title</b></td>
						<td><?=GetMessage("LFP_SCHEME_EDIT_POINT_TITLE")?></td>
					</tr>
					<tr>
						<td><b>message</b></td>
						<td><?=GetMessage("LFP_SCHEME_EDIT_POINT_MESSAGE")?></td>
					</tr>
					<tr id="LF_IS_HTML_ROW">
						<td><input type="checkbox" id="IS_HTML" name="IS_HTML" value="Y"<?if($arRes["IS_HTML"] === "Y") echo " checked"?>></td>
						<td><?=GetMessage("LFP_SCHEME_EDIT_IS_HTML_".$scheme_type)?></td>
					</tr>
					<tr>
						<td><b>text_message</b></td>
						<td><?=GetMessage("LFP_SCHEME_EDIT_POINT_TEXT_MESSAGE")?></td>
					</tr>
					<tr>
						<td><b>url</b></td>
						<td><?=GetMessage("LFP_SCHEME_EDIT_POINT_URL")?></td>
					</tr>
				</tbody>
				</table>
			</td>
		</tr>
		<?
	}
	?>
<?
if (in_array($scheme_type, array("XML", "RSS")))
{
	?>
		<tr class="heading">
			<td colspan="2"><?=GetMessage("LFP_SCHEME_EDIT_SCHEDULE")?></td>
		</tr>
		<tr class="adm-detail-required-field">
			<td width="40%"><?=GetMessage("LFP_SCHEME_EDIT_LAST_EXECUTED").":"?></td>
			<td width="60%"><?=CalendarDate("LAST_EXECUTED", $arRes["LAST_EXECUTED"], "editform", "20")?></td>
		</tr>
		<tr class="adm-detail-required-field">
			<td class="adm-detail-valign-top adm-detail-content-cell-l"><?=GetMessage("LFP_SCHEME_EDIT_DAYS")?></td>
			<td class="adm-detail-content-cell-r"><table cellspacing=0 cellpadding=0 border=0><?
			?><tr>
				<td><div style="margin-bottom: 10px;"><?=GetMessage("LFP_SCHEME_EDIT_DOM")?></div><input class="typeinput" type="text" name="DAYS_OF_MONTH" value="<?=HtmlFilter::encode($arRes["DAYS_OF_MONTH"])?>" size="30" maxlength="100"></td>
			</tr>
			<tr>
				<td><div style="margin: 10px 0 10px 0;"><?=GetMessage("LFP_SCHEME_EDIT_DOW")?></div><table cellspacing=1 cellpadding=0 border=0 class="internal"><?
					$arDoW = array(
						"1" => GetMessage("LFP_SCHEME_EDIT_MON"),
						"2" => GetMessage("LFP_SCHEME_EDIT_TUE"),
						"3" => GetMessage("LFP_SCHEME_EDIT_WED"),
						"4" => GetMessage("LFP_SCHEME_EDIT_THU"),
						"5" => GetMessage("LFP_SCHEME_EDIT_FRI"),
						"6" => GetMessage("LFP_SCHEME_EDIT_SAT"),
						"7" => GetMessage("LFP_SCHEME_EDIT_SUN")
					);
					?><tr class="heading"><?foreach($arDoW as $strVal=>$strDoW):
						?><td><?=$strDoW?></td><?
					endforeach;?></tr>
					<tr><?
					foreach($arDoW as $strVal=>$strDoW):
						?><td style="text-align: center;"><input type="checkbox" name="DAYS_OF_WEEK[]" value="<?=$strVal?>"<?if(array_search($strVal, $DAYS_OF_WEEK) !== false) echo " checked"?>></td><?
					endforeach;
					?></tr>
				</table></td>
			</tr><?
			?></table></td>
		</tr>
		<tr class="adm-detail-required-field">
			<td><?=GetMessage("LFP_SCHEME_EDIT_TOD")?></td>
			<td><input type="text" name="TIMES_OF_DAY" value="<?=HtmlFilter::encode($arRes["TIMES_OF_DAY"])?>" size="30" maxlength="255"></td>
		</tr>
	<?
}
$tabControl->Buttons(
	array(
		"back_url"=>"xdi_lf_scheme_list.php?lang=".LANGUAGE_ID,
	)
);
?>
<?echo bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
<input type="hidden" name="ID" value="<?=$ID?>">
<input type="hidden" name="TYPE" value="<?=HtmlFilter::encode($scheme_type)?>">
<?
$tabControl->End();
?>
</form>

<?
$tabControl->ShowWarnings("editform", $message);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>