<?
define("PUBLIC_AJAX_MODE", true);
define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

$SITE_ID = '';
if($_REQUEST["site_id"] <> '')
{
	$res = CSite::GetByID($_REQUEST["site_id"]);
	if($arSite = $res->Fetch())
		$SITE_ID = $arSite["ID"];
}

$iblockID = COption::GetOptionInt("intranet", "iblock_structure");

function AddDepartment($SITE_ID, $arFields)
{
	if (CModule::IncludeModule('iblock'))
	{
		global $iblockID;

		$arNewFields = array(
			"NAME" => $arFields["NAME"],
			"IBLOCK_SECTION_ID" => $arFields["IBLOCK_SECTION_ID"],
			"UF_HEAD" => $arFields["UF_HEAD"],
			"IBLOCK_ID" => $iblockID
		);

		$section = new CIBlockSection;
		$ID = $section->Add($arNewFields);
	}
	if(!$ID)
	{
		$arErrors = preg_split("/<br>/", $section->LAST_ERROR);
		return $arErrors;
	}
	else
	{
		return $ID;
	}
}

function EditDepartment($SITE_ID, $arFields)
{
	if (CModule::IncludeModule('iblock'))
	{
		global $iblockID;

		$arNewFields = array(
			"NAME" => $arFields["NAME"],
			"IBLOCK_SECTION_ID" => $arFields["IBLOCK_SECTION_ID"],
			"UF_HEAD" => $arFields["UF_HEAD"],
			"IBLOCK_ID" => $iblockID
		);

		$section = new CIBlockSection;
		$ID = $section->Update(intval($arFields['CURRENT_DEPARTMENT_ID']), $arNewFields);
	}
	if(!$ID)
	{
		$arErrors = preg_split("/<br>/", $section->LAST_ERROR);
		return $arErrors;
	}
	else
	{
		return $ID;
	}
}

function DeleteDepartment($arFields)
{
	$dpt = intval($arFields['dpt_id']);

	global $iblockID;

	$dbRes = CIBlockSection::GetList(
		array(),
		array("ID" => $dpt, "IBLOCK_ID" => $iblockID, "CHECK_PERMISSIONS" => "Y"),
		false,
		array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID')
	);
	if($arSection = $dbRes->Fetch())
	{
		if ($arSection['IBLOCK_SECTION_ID'] > 0)
		{
			$dbRes = CUser::GetList(
				$by,$order,
				array('UF_DEPARTMENT' => $dpt),
				array('SELECT' => array('ID'))
			);

			$GLOBALS['DB']->StartTransaction();

			$obUser = new CUser();
			while ($arRes = $dbRes->fetch())
				$obUser->update($arRes['ID'], array('UF_DEPARTMENT' => array($arSection['IBLOCK_SECTION_ID'])));

			$dbRes = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $iblockID, 'SECTION_ID' => $arSection['ID']), false, array('ID', 'IBLOCK_ID'));

			$obIBlockSection = new CIBlockSection();
			while ($arRes = $dbRes->Fetch())
			{
				$obIBlockSection->Update($arRes['ID'], array(
					'IBLOCK_SECTION_ID' => $arSection['IBLOCK_SECTION_ID'])
				);
			}

			if ($obIBlockSection->Delete($arSection['ID']))
			{
				$GLOBALS['DB']->Commit();
				echo "{id: '".$arSection['IBLOCK_SECTION_ID']."'}";
			}
			else
			{
				$GLOBALS['DB']->Rollback();
				$res = array('error' => trim(str_replace('<br>', "\n", $ob->LAST_ERROR)));
				echo "{errors: '".$res["error"]."'}";
			}
		}
	}
}

if(!CModule::IncludeModule('iblock'))
{
	echo GetMessage("INTR_STRUCTURE_BITRIX24_MODULE");
}
else
{
	$ID = 1;
	if($_SERVER["REQUEST_METHOD"] === "POST" && check_bitrix_sessid())
	{
		if (isset($_POST['CURRENT_DEPARTMENT_ID']) && CIBlockSectionRights::UserHasRightTo($iblockID, intval($_POST['CURRENT_DEPARTMENT_ID']), 'section_edit'))
		{
			$ID = EditDepartment($SITE_ID, $_POST);
		}
		elseif (!isset($_POST['CURRENT_DEPARTMENT_ID']) && CIBlockSectionRights::UserHasRightTo($iblockID, intval($_POST["IBLOCK_SECTION_ID"]), "section_section_bind"))
		{
			$ID = AddDepartment($SITE_ID, $_POST);
		}
		else
		{
			die('error:<li>'.GetMessage('INTR_USER_ERR_NO_RIGHT').'</li>');
		}

		if(is_array($ID))
		{
			$arErrors = $ID;
			foreach ($arErrors as $key => $val) {if (strlen($val) <= 0) unset($arErrors[$key]);}
			$ID = 0;
			die('error:<li>'.implode('</li><li>', $arErrors)).'</li>';
		}
		elseif (isset($_POST['CURRENT_DEPARTMENT_ID']))
			die("close");
	}

	if($_SERVER["REQUEST_METHOD"] === "GET" && check_bitrix_sessid() && $_GET["action"] = "delete_department" && CIBlockSectionRights::UserHasRightTo($iblockID, intval($_GET['dpt_id']), 'section_delete'))
	{
		DeleteDepartment($_GET);
		return;
	}
?>
<div style="width:450px; "><?
	if ($ID>1)
	{
	?>
	<form method="POST" action="<?echo BX_ROOT."/tools/intranet_structure.php?site_id=".$SITE_ID."&IBLOCK_SECTION_ID=".intval($_POST["IBLOCK_SECTION_ID"])?>" id="STRUCTURE_FORM">
		<p><?=GetMessage("INTR_STRUCTURE_SUCCESS")?></p>
		<input type="hidden" name="reload" value="Y">
	</form><?
	}
	else
	{
		if (isset($arParams["UF_DEPARTMENT_ID"]))  //data for department's editing
		{
			$rsSection = CIBlockSection::GetList(array(), array("ID" => intval($arParams["UF_DEPARTMENT_ID"]), "IBLOCK_ID" => $iblockID),false, array('UF_HEAD'));
			$arSection = $rsSection->Fetch();
		}?>
	<form method="POST" action="<?echo BX_ROOT."/tools/intranet_structure.php"?>" id="STRUCTURE_FORM"><?
		if (isset($_POST['CURRENT_DEPARTMENT_ID']) || isset($arSection["ID"])):?>
		<input type="hidden" value="<?=(isset($_POST['CURRENT_DEPARTMENT_ID'])) ? htmlspecialcharsbx($_POST['CURRENT_DEPARTMENT_ID']) : $arSection['ID']?>" name="CURRENT_DEPARTMENT_ID"><?
		endif;?>
		<table width="100%" cellpadding="5">
			<tr valign="bottom">
				<td>
					<div style="font-size:14px;font-weight:bold;padding-bottom:8px"><label for="NAME"><?echo GetMessage("INTR_STRUCTURE_NAME")?></label></div>
					<input type="text" value="<?if (isset($_POST['NAME'])) echo htmlspecialcharsbx($_POST['NAME']); elseif (isset($arSection["NAME"])) echo htmlspecialcharsbx($arSection["NAME"])?>" name="NAME" id="NAME" style="width:100%;font-size:14px;border:1px #c8c8c8 solid;">
				</td>
			</tr>
			<?if (!(isset($arParams["UF_DEPARTMENT_ID"]) && empty($arSection["IBLOCK_SECTION_ID"])))://for top department no parent department?>
			<tr valign="bottom">
				<td>
					<div style="font-size:14px;font-weight:bold;padding-bottom:8px"><label for="IBLOCK_SECTION_ID"><?echo GetMessage("INTR_STRUCTURE_DEPARTMENT")?></label></div>
					<select name="IBLOCK_SECTION_ID" id="IBLOCK_SECTION_ID" style="width:100%;font-size:14px;border:1px #c8c8c8 solid;">
						<?
						$rsDepartments = CIBlockSection::GetTreeList(array(
							"IBLOCK_ID"=>intval(COption::GetOptionInt('intranet', 'iblock_structure', false)),
						));
						$CurrentIblockSectionID = "";
						if (isset($arSection["IBLOCK_SECTION_ID"]) && intval($arSection["IBLOCK_SECTION_ID"]) > 0)
							$CurrentIblockSectionID = intval($arSection["IBLOCK_SECTION_ID"]);
						elseif (isset($_POST['IBLOCK_SECTION_ID']) && intval($_POST['IBLOCK_SECTION_ID']) > 0)
							$CurrentIblockSectionID = intval($_POST['IBLOCK_SECTION_ID']);
						elseif (!isset($arParams["UF_DEPARTMENT_ID"]) && isset($arParams['IBLOCK_SECTION_ID']) && intval($arParams['IBLOCK_SECTION_ID']) > 0)
							$CurrentIblockSectionID = intval($arParams['IBLOCK_SECTION_ID']);
						elseif (isset($_GET["IBLOCK_SECTION_ID"]) && intval($_GET["IBLOCK_SECTION_ID"]) > 0)
							$CurrentIblockSectionID = intval($_GET["IBLOCK_SECTION_ID"]);
						while($arDepartment = $rsDepartments->GetNext()):
							?><option value="<?echo $arDepartment["ID"]?>" <?if($CurrentIblockSectionID==$arDepartment["ID"]) echo "selected"?>><?echo str_repeat("&nbsp;.&nbsp;", $arDepartment["DEPTH_LEVEL"])?><?echo $arDepartment["NAME"]?></option><?
						endwhile;
						?>
					</select>
				</td>
			</tr>
			<?endif;?>
			<tr valign="bottom">
				<td colspan="2">
					<div style="font-size:14px;font-weight:bold;padding-bottom:8px"><label for="UF_HEAD"><?echo GetMessage("INTR_STRUCTURE_HEAD")?></label></div>
					<?
					$UF_HeadName = "";
					if (isset($_POST['UF_HEAD']))
					{
						$dbUser = CUser::GetList($b="", $o="", array("ID" => intval($_POST['UF_HEAD'])));
						if ($arUser = $dbUser->GetNext())
							$UF_HeadName = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true, false);
					}
					elseif (isset($arSection["UF_HEAD"]))
					{
						$dbUser = CUser::GetList($b="", $o="", array("ID" => intval($arSection["UF_HEAD"])));
						if ($arUser = $dbUser->GetNext())
							$UF_HeadName = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true, false);
					}

					$controlName = "Single_" . RandString(6);
					?>
					<input type="hidden" id="uf_head" value="<?if (isset($_POST['UF_HEAD'])) echo htmlspecialcharsbx($_POST['UF_HEAD']); elseif (isset($arSection["UF_HEAD"]))  echo htmlspecialcharsbx($arSection["UF_HEAD"])?>" name="UF_HEAD" style="width:35px;font-size:14px;border:1px #c8c8c8 solid;">
					<div id="structure-department-head-div" style="margin-bottom: 5px;"<?if ($UF_HeadName == ""):?>style="display:none;"<?endif;?>>
						<span id="uf_head_name" style="margin-right:5px;"><?=$UF_HeadName?></span>
						<span id="structure-department-head" class="structure-department-head" <?if ($UF_HeadName != ""):?>style="visibility:visible;"<?endif;?> onclick='BX("uf_head").value = ""; BX("uf_head_name").innerHTML = ""; BX("structure-department-head").style.visibility="hidden"; BX("structure-department-head-div").style.display="none"'></span>
					</div>
					<a href="javascript:void(0)" id="single-user-choice"><?=GetMessage("INTR_UF_HEAD_CHOOSE")?></a>
					<?CUtil::InitJSCore(array('popup'));?>

					<script type="text/javascript" src="/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js"></script>
					<script type="text/javascript">BX.loadCSS('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/style.css');</script>
					<script>// user_selector:
						var multiPopup, singlePopup, taskIFramePopup;

						function onSingleSelect(arUser)
						{
							BX("uf_head").value = arUser.id;
							BX("uf_head_name").innerHTML = BX.util.htmlspecialchars(arUser.name);
							BX("structure-department-head").style.visibility="visible";
							BX("structure-department-head-div").style.display="block";
							singlePopup.close();
						}

						function ShowSingleSelector(e)
						{

							if(!e) e = window.event;

							if (!singlePopup)
							{
								singlePopup = new BX.PopupWindow("single-employee-popup", this, {
									offsetTop : 1,
									autoHide : true,
									content : BX("<?=CUtil::JSEscape($controlName)?>_selector_content"),
									zIndex: 3000
								});
							}
							else
							{
								singlePopup.setContent(BX("<?=CUtil::JSEscape($controlName)?>_selector_content"));
								singlePopup.setBindElement(this);
							}

							if (singlePopup.popupContainer.style.display != "block")
							{
								singlePopup.show();
							}

							return BX.PreventDefault(e);
						}

						function Clear()
						{
							O_<?=CUtil::JSEscape($controlName)?>.setSelected();
						}

						BX.ready(function() {
							BX.bind(BX("single-user-choice"), "click", ShowSingleSelector);
							BX.bind(BX("clear-user-choice"), "click", Clear);
						});
					</script>
					<?$name = $APPLICATION->IncludeComponent(
							"bitrix:intranet.user.selector.new", ".default", array(
								"MULTIPLE" => "N",
								"NAME" => $controlName,
								"VALUE" => 1,
								"POPUP" => "Y",
								"ON_SELECT" => "onSingleSelect",
								"SITE_ID" => SITE_ID,
								"SHOW_EXTRANET_USERS" => "NONE",
							), null, array("HIDE_ICONS" => "Y")
						);?>
				</td>
			</tr>
		</table>
		<?echo bitrix_sessid_post()?>
		<input type="hidden" name="site_id" value="<?echo htmlspecialcharsbx($SITE_ID)?>">
	</form>
<?
	}
?>
	<script type="text/javascript">
		var myBX;
		if(window.BX)
			myBX = window.BX;
		else if (window.top.BX)
			myBX = window.top.BX;
		else
			myBX = null;

		var myPopup = myBX.IntranetStructure.popup;
		var myButton = myPopup.buttons[0];
		<?if(isset($arParams["UF_DEPARTMENT_ID"]) || $_POST['CURRENT_DEPARTMENT_ID']):?>
			myButton.setName('<?=\CUtil::jsEscape(getMessage('INTR_STRUCTURE_EDIT')) ?>');
			myPopup.setTitleBar('<?=\CUtil::jsEscape(getMessage('INTR_EDIT_TITLE')) ?>');
		<?elseif ($ID > 1):?>
			myButton.setName('<?=\CUtil::jsEscape(getMessage('INTR_STRUCTURE_ADD_MORE')) ?>');
			myPopup.setTitleBar('<?=\CUtil::jsEscape(getMessage('INTR_ADD_TITLE')) ?>');
		<?else:?>
			myButton.setName('<?=\CUtil::jsEscape(getMessage('INTR_STRUCTURE_ADD')) ?>');
			myPopup.setTitleBar('<?=\CUtil::jsEscape(getMessage('INTR_ADD_TITLE')) ?>');
		<?endif?>

		myPopup = null;
		myButton = null;
		myBX = null;
	</script>
<?
}
?>
</div>
<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>
