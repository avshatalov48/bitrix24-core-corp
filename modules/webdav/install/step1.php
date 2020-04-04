<?if (!CModule::IncludeModule("iblock"))
	return 0;

IncludeModuleLangFile(__FILE__);
$arError = array();
if($_POST["iblock"] == "Y"):
	if (!check_bitrix_sessid()):
		$arError[] = array(
			"code" => "bad sessid",
			"title" => GetMessage("WD_ERROR_BAD_SESSID"));
	elseif ($GLOBALS["APPLICATION"]->GetGroupRight("iblock") < "W"):
		$arError[] = array(
			"code" => "bad permission",
			"title" => GetMessage("WD_ERROR_BAD_PERMISSION"));
	else:
		$arUGroupsEx = Array();
		$dbUGroups = CGroup::GetList($by = "c_sort", $order = "asc");
		while($arUGroups = $dbUGroups -> Fetch())
		{
			if ($arUGroups["ANONYMOUS"] == "Y")
				$arUGroupsEx[$arUGroups["ID"]] = "R";
		}
		if ($_REQUEST["create_iblock_type"] == "Y")
		{
			$arIBTLang = array(); $arLang = array();
			$l = CLanguage::GetList($lby="sort", $lorder="asc");
			while($ar = $l->ExtractFields("l_"))
				$arIBTLang[]=$ar;
			
			for($i=0; $i<count($arIBTLang); $i++)
				$arLang[$arIBTLang[$i]["LID"]] = array("NAME" => $_REQUEST["iblock_type_name"]);
			
			$arFields = array(
				"ID" => $_REQUEST["iblock_type_name"],
				"LANG" => $arLang,
				"SECTIONS" => "Y");

			$GLOBALS["DB"]->StartTransaction();
			$obBlocktype = new CIBlockType;
			$IBLOCK_TYPE_ID = $obBlocktype->Add($arFields);
			if (strLen($IBLOCK_TYPE_ID) <= 0)
			{
				$arError[] = array(
					"code" => "iblocktype is not added",
					"title" => $obBlocktype->LAST_ERROR);
			}
			else
			{
				$GLOBALS["DB"]->Commit();
				$_REQUEST["create_iblock_type"] = "N";
				$_REQUEST["iblock_type_name"] = "";
				$_REQUEST["iblock_type_id"] = $IBLOCK_TYPE_ID;
			}
		}
		
		$IBLOCK_TYPE_ID = $_REQUEST["iblock_type_id"];
		
		if ($IBLOCK_TYPE_ID)
		{
			$DB->StartTransaction();
			$arFields = Array(
				"ACTIVE"=>"Y",
				"NAME"=>$_REQUEST["iblock_name"],
				"IBLOCK_TYPE_ID"=>$IBLOCK_TYPE_ID,
				"LID"=>array());
			$ib = new CIBlock;
			$db_sites = CSite::GetList($lby="sort", $lorder="asc");
			while ($ar_sites = $db_sites->Fetch())
			{
				if ($ar_sites["ACTIVE"] == "Y")
					$arFields["LID"][] = $ar_sites["LID"];
				$arSites[] = $ar_sites;
			}
			
			if (empty($arFields["LID"]))
				$arFields["LID"][] = $ar_sites[0]["LID"];
			if (!empty($arUGroupsEx))
				$arFields["GROUP_ID"] = $arUGroupsEx;

			$ID = $ib->Add($arFields);
			if($ID <= 0)
			{
				$arError[] = array(
					"code" => "iblock is not added",
					"title" => $ib->LAST_ERROR);
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
				$_REQUEST["new_iblock_name"] = "";
				$_REQUEST["new_iblock"] = "created";
			}
		}
	endif;

	if (!empty($arError))
	{
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/webdav/include.php');
		$strWarning = WDShowError($arError);
		ShowError($strWarning);
	}
	else 
	{
?>
<script>
window.location='/bitrix/admin/module_admin.php?step=2&lang=<?=LANGUAGE_ID?>&id=webdav&install=y<?=($ID > 0 ? "&iblock=".$ID : "")?>&<?=bitrix_sessid_get()?>';
</script>
<?	
	}
elseif ($_REQUEST["install"] == "Y"):
?>
<script>
window.location='/bitrix/admin/module_admin.php?step=2&lang=<?=LANGUAGE_ID?>&id=webdav&install=y&<?=bitrix_sessid_get()?>';
</script>
<?	
endif;	
?>
<form action="<?=$APPLICATION->GetCurPage()?>" name="webdav_form" id="webdav_form" class="form-webdav" method="POST">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
	<input type="hidden" name="id" value="webdav">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="step" value="1">
<table class="list-table">
	<thead>
		<tr class="head">
			<td colspan="2">
				<input type="checkbox" name="iblock" id="iblock" value="Y" onclick="CheckCreate(this);" <?
					?><?=($_REQUEST["iblock"] == "Y" ? " checked='checked'" : "")?>/> <label for="iblock"><?=GetMessage("WD_CREATE_NEW_IBLOCK")?></label></td></tr>
	</thead>
	<tbody>
		<tr><td width="10%" style="white-space:nowrap;">
			<span class="required">*</span><?=GetMessage("WD_CREATE_NEW_IBLOCK_NAME")?>: </td>
			<td width="90%"><input type="text" name="iblock_name" value="<?=htmlspecialcharsbx($_REQUEST["iblock_name"])?>" /></td></tr>
		<tr><td><span class="required">*</span><?=GetMessage("WD_CREATE_NEW_IBLOCK_TYPE")?>: </td>
			<td><input onclick="ChangeStatus(this)" type="radio" name="create_iblock_type" id="create_iblock_type_n" value="N" <?
					?><?=($_REQUEST["create_iblock_type"] != "Y" ? " checked=\"checked\"" : "")?> />
				<label for="create_iblock_type_n"><?=GetMessage("WD_SELECT")?>: </label> 
				<select name="iblock_type_id" <?=($_REQUEST["create_iblock_type"] == "Y" ? "disabled='disabled'" : "")?>><?
				$arIBlockType = array();
				$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
				while ($arr=$rsIBlockType->GetNext())
				{
					if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
					{
						?><option value="<?=$arr["ID"]?>" <?=($_REQUEST["iblock_type_id"] == $arr["ID"] ? " selected='selected'" : "")?>><?="[".$arr["ID"]."] ".$ar["NAME"]?></option><?
					}
				}
			?></select><br />
			<input onclick="ChangeStatus(this)" type="radio" name="create_iblock_type" id="create_iblock_type_y" value="Y" <?
				?><?=($_REQUEST["create_iblock_type"] == "Y" ? " checked=\"checked\"" : "")?> /> 
			<label for="create_iblock_type_y"><?=GetMessage("WD_CREATE")?>: </label> 
			<span class="required">*</span><?=GetMessage("WD_ID")?> (ID): 
			<input type="text" name="iblock_type_name" value="<?=htmlspecialcharsbx($_REQUEST["iblock_type_name"])?>" <?
				?><?=($_REQUEST["create_iblock_type"] != "Y" ? "disabled='disabled'" : "")?>/><br />
		</td></tr>
	</tbody>
	<tfoot>
		<tr><td colspan="2"><input type="submit" name="wd_next" value="<?=GetMessage("WD_INSTALL")?>" /></td></tr>
	</tfoot>
</table>
</form>
<script>
function ChangeStatus(pointer)
{
	if (typeof pointer != "object" || (document.forms['webdav_form'] == null))
		return false;
	document.forms['webdav_form'].elements['iblock_type_id'].disabled = (pointer.id == 'create_iblock_type_y');
	document.forms['webdav_form'].elements['iblock_type_name'].disabled = !(pointer.id == 'create_iblock_type_y');
}

function CheckCreate(pointer)
{
	if (!pointer || typeof pointer != "object" || !pointer.form)
		return false;
	var form = pointer.form;
	for (var ii=0; ii<form.elements.length; ii++)
	{
		if (form.elements[ii].name != 'wd_next' && form.elements[ii].name != pointer.name && form.elements[ii].type != "hidden")
		{
			form.elements[ii].disabled = (!pointer.checked);
		}
		else
		{
			form.elements[ii].disabled = false;
		}
	}
}
CheckCreate(document.getElementById('iblock'));
</script>