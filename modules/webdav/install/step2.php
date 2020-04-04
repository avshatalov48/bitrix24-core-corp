<?if (!CModule::IncludeModule("iblock"))
	return;
IncludeModuleLangFile(__FILE__);
$arError = array();
$iblock_type = "";
if($_REQUEST["install_public"] == "Y"):
	if (!check_bitrix_sessid())
	{
		$arError[] = array(
			"code" => "bad sessid",
			"title" => GetMessage("WD_ERROR_BAD_SESSID"));
	}
	if (empty($_REQUEST["IBLOCK_ID"]))
	{
		$arError[] = array(
			"code" => "empty iblock_id",
			"title" => GetMessage("WD_ERROR_EMPTY_IBLOCK_ID"));
	}
	else 
	{
		$db_res = CIBlock::GetByID($_REQUEST["IBLOCK_ID"]);
		if ($db_res && $res = $db_res->Fetch())
		{
			$iblock_type = $res["IBLOCK_TYPE_ID"];
		}
		else 
		{
			$arError[] = array(
				"code" => "iblock is not found",
				"title" => GetMessage("WD_ERROR_IBLOCK_IS_NOT_FOUND"));
		}
	}
	if (empty($_REQUEST["PATH"]))
	{
		$arError[] = array(
			"code" => "empty path",
			"title" => GetMessage("WD_ERROR_EMPTY_PATH"));
	}
	
	if (empty($arError)):
		
		$bRewrite = ($_REQUEST["REWRITE_PUBLIC"] == "Y" ? true : false);
		$res = array(
			"IBLOCK_TYPE" => $iblock_type,
			"IBLOCK_ID" => $_REQUEST["IBLOCK_ID"],
			"SEF_MODE" => ($_REQUEST["SEF_MODE"] == "Y" ? "Y" : "N"),
			"PATH" => $_REQUEST["PATH"]);
		$res["~PATH"] = preg_replace("/[\/\\\]+/", "/", "/".$res["PATH"]."/");
		$res["PATH"] = preg_replace("/[\/\\\]+/", "/", $_SERVER["DOCUMENT_ROOT"]."/".$res["PATH"]."/");
		CheckDirPath($res["PATH"]);
		$fileExistBefore = file_exists($res["PATH"]."index.php");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webdav/install/public/components/", 
			$res["PATH"], $bRewrite, true);
		if ((!$fileExistBefore || $bRewrite) && file_exists($res["PATH"]."index.php"))
		{
			if ($file = file_get_contents($res["PATH"]."index.php"))
			{
				$file = str_replace(
					array(
						"#IBLOCK_TYPE#",
						"#IBLOCK_ID#", 
						"#SEF_MODE#", 
						"#SEF_FOLDER#", 
						"#BASE_URL#"), 
					array(
						$res["IBLOCK_TYPE"],
						$res["IBLOCK_ID"], 
						$res["SEF_MODE"], 
						$res["~PATH"], 
						$res["~PATH"]),
					$file);
				if ($res["SEF_MODE"] == "Y")
				{
					$arFields = array(
						"CONDITION" => "#^".$res["~PATH"]."#",
						"RULE" => "",
						"ID" => "bitrix:webdav",
						"PATH" => $res["~PATH"]."index.php");
					CUrlRewriter::Add($arFields);
				}
				
			}
			
			if ($f = fopen($res["PATH"]."index.php", "w"))
			{
				@fwrite($f, $file);
				@fclose($f);
			}
			
			if ($file = file_get_contents($res["PATH"].".section.php"))
			{
				$file = str_replace(
					array(
						"#WD_SECTION_NAME#"), 
					array(
						GetMessage("WD_SECTION_NAME")),
					$file);
			}
			if ($f = fopen($res["PATH"].".section.php", "w"))
			{
				@fwrite($f, $file);
				@fclose($f);
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
window.location='/bitrix/admin/module_admin.php?step=3&lang=<?=LANGUAGE_ID?>&id=webdav&install=y&<?=bitrix_sessid_get()?>';
</script>
<?	
	}
elseif ($_POST["install"] == "Y"):
?>
<script>
window.location='/bitrix/admin/module_admin.php?step=3&lang=<?=LANGUAGE_ID?>&id=webdav&install=y&<?=bitrix_sessid_get()?>';
</script>
<?	
endif;

$iblock_id = (empty($_REQUEST["IBLOCK_ID"]) ? $_REQUEST["iblock"] : $_REQUEST["IBLOCK_ID"]);
if (!empty($_REQUEST["iblock"]) && empty($_POST["step"]))
{
	$_REQUEST["install_public"] = "Y";
}
$arIBlock = array(
	"reference" => array(),
	"reference_id" => array());
$rsIBlock = CIBlock::GetList(Array("IBLOCK_TYPE_ID" => "asc"), Array("ACTIVE"=>"Y"));
while($arr=$rsIBlock->Fetch())
{
	$arIBlock["reference"][] = "[".$arr["IBLOCK_TYPE_ID"]."] ".$arr["NAME"]." (".$arr["ID"].")";
	$arIBlock["reference_id"][] = $arr["ID"];
}
?>
<form action="<?=$APPLICATION->GetCurPage()?>" name="webdav_form" id="webdav_form" class="form-webdav" method="POST">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
	<input type="hidden" name="id" value="webdav">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="step" value="2">
<table class="list-table">
	<thead>
		<tr class="head">
			<td>
				<input type="checkbox" name="install_public" id="install_public" value="Y" onclick="CheckCreate(this);" <?
					?><?=($_REQUEST["install_public"] == "Y" ? " checked='checked'" : "")?>/> <label for="install_public"><?=GetMessage("WD_INSTALL_PUBLIC")?></label></td></tr>
	</thead>
	<tbody>
		<tr><td>
			<input type="checkbox" name="SEF_MODE" value="Y" id="SEF_MODE" <?=
				($_REQUEST["SEF_MODE"] == "Y" ? "checked='checked'" : "")?> />
			<label for="SEF_MODE"><?=GetMessage("WD_SEF_MODE")?></label><br /><br />
			<input type="checkbox" name="REWRITE_PUBLIC" value="Y" id="REWRITE_PUBLIC" <?=
				($_REQUEST["REWRITE_PUBLIC"] == "Y" ? "checked='checked'" : "")?> />
			<label for="REWRITE_PUBLIC"><?=GetMessage("WD_REWRITE_PUBLIC")?></label><br /><br />
			<span class="required">*</span><?=GetMessage("WD_IBLOCK")?>: 
				<br /><?=SelectBoxFromArray("IBLOCK_ID", $arIBlock, $iblock_id)?><br /><br />
			<span class="required">*</span><?=GetMessage("WD_PATH")?>: <br />
				<input type="text" name="PATH" value="<?=htmlspecialcharsEx($_REQUEST["PATH"])?>"  style='width:300px;' />
			</td></tr>
		
	</tbody>
	<tfoot>
		<tr><td><input type="submit" name="wd_next" value="<?=GetMessage("WD_INSTALL")?>" /></td></tr>
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
CheckCreate(document.getElementById('install_public'));
</script>

<style>
table.list-table select.typeselect{
	width:300px;}
</style>