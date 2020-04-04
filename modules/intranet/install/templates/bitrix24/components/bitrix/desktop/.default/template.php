<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/pubstyles.css');

if(!defined("BX_GADGET_DEFAULT"))
{
	define("BX_GADGET_DEFAULT", true);
	?><script type="text/javascript">
		var updateURL = '<?=CUtil::JSEscape(htmlspecialcharsback($arResult['UPD_URL']))?>';
		var bxsessid = '<?=CUtil::JSEscape(bitrix_sessid())?>';
		var langGDError1 = '<?=CUtil::JSEscape(GetMessage("CMDESKTOP_TDEF_ERR1"))?>';
		var langGDError2 = '<?=CUtil::JSEscape(GetMessage("CMDESKTOP_TDEF_ERR2"))?>';
		var langGDConfirm1 = '<?=CUtil::JSEscape(GetMessage("CMDESKTOP_TDEF_CONF"))?>';
		var langGDConfirmUser = '<?=CUtil::JSEscape(GetMessage("CMDESKTOP_TDEF_CONF_USER"))?>';
		var langGDConfirmGroup = '<?=CUtil::JSEscape(GetMessage("CMDESKTOP_TDEF_CONF_GROUP"))?>';
		var langGDClearConfirm = '<?=CUtil::JSEscape(GetMessage("CMDESKTOP_TDEF_CLEAR_CONF"))?>';
		var langGDCancel = "<?echo CUtil::JSEscape(GetMessage("CMDESKTOP_TDEF_CANCEL"))?>";
	</script><?
	if($arResult["PERMISSION"]>"R")
	{
		?><script type="text/javascript" src="/bitrix/components/bitrix/desktop/script.js?v=<?=filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/desktop/script.js');?>"></script><?
	}
}

$gadgetButtons = "";

if($arResult["PERMISSION"]>"R")
{
	$allGD = Array();
	foreach($arResult['ALL_GADGETS'] as $gd)
	{
		$allGD[] = Array(
			'ID' => $gd["ID"],
			'TEXT' =>
				'<div style="text-align: left;">'.($gd['ICON1']?'<img src="'.($gd['ICON']).'" align="left">':'').
				'<b>'.(htmlspecialcharsbx($gd['NAME'])).'</b><br>'.(htmlspecialcharsbx($gd['DESCRIPTION'])).'</div>',
			);
	}

	?><script type="text/javascript">
		arGDGroups = <?=CUtil::PhpToJSObject($arResult["GROUPS"])?>;
		BX.ready(function() {
			new BXGadget('<?=$arResult["ID"]?>', <?=CUtil::PhpToJSObject($allGD)?>);
		});
	</script><?

	$gadgetButtons = '
	<div class="sidebar-buttons">
		<a href="" class="sidebar-button" onclick="getGadgetHolder(\''.AddSlashes($arResult["ID"]).'\').ShowAddGDMenu(this);return false;">
			<span class="sidebar-button-top"><span class="corner left"></span><span class="corner right"></span></span>
			<span class="sidebar-button-content"><span class="sidebar-button-content-inner"><i class="sidebar-button-create"></i><b>'.GetMessage("CMDESKTOP_TDEF_ADD").'</b></span></span>
			<span class="sidebar-button-bottom"><span class="corner left"></span><span class="corner right"></span></span></a>';


	if($arResult["PERMISSION"]>"W")
	{
		if ($arParams["MODE"] == "SU")
		{
			$mode = "'SU'";
		}
		elseif ($arParams["MODE"] == "SG")
		{
			$mode = "'SG'";
		}
		else
		{
			$mode = "";
		}

		$gadgetButtons .= '<a href="" class="sidebar-button" onclick="getGadgetHolder(\''.AddSlashes($arResult["ID"]).'\').SetForAll('.$mode.'); return false;">
			<span class="sidebar-button-top"><span class="corner left"></span><span class="corner right"></span></span>
			<span class="sidebar-button-content"><span class="sidebar-button-content-inner"><i class="sidebar-button-accept"></i><b>'.GetMessage("CMDESKTOP_TDEF_SET").'</b></span></span>
			<span class="sidebar-button-bottom"><span class="corner left"></span><span class="corner right"></span></span></a>';
	}

	$gadgetButtons .= '<a href="" class="sidebar-button" onclick="getGadgetHolder(\''.AddSlashes($arResult["ID"]).'\').ClearUserSettings(); return false;">
		<span class="sidebar-button-top"><span class="corner left"></span><span class="corner right"></span></span>
		<span class="sidebar-button-content"><span class="sidebar-button-content-inner"><i class="sidebar-button-delete"></i><b>'.GetMessage("CMDESKTOP_TDEF_CLEAR").'</b></span></span>
		<span class="sidebar-button-bottom"><span class="corner left"></span><span class="corner right"></span></span></a>';

	$gadgetButtons .= '</div>';
}

if ($arResult["PERMISSION"] > "W")
{
	$gadgetButtons = '<table id="tdesktop-actions" class="data-table-gadget" style="margin-bottom:0;"><tr><td><div class="gdparent">
		<div class="gdheader" style="cursor:move;" onmousedown="return getGadgetHolder(\''.$arResult["ID"].'\').DragStart(\'desktop-actions\', event)"><div class="gdheader-title">'.GetMessage("CMDESKTOP_DESC_NAME").'</div></div>
	<div class="ghheader-underline"></div>
	<div class="gdcontent" id="dgddesktop-actions">'.$gadgetButtons.'</div>
	</div>';

	$gadgetButtons .= '<script type="text/javascript">
		BX.ready(function() {
			new BXGadget(\'desktop-actions\', {});
		});
	</script>';

	if ($USER->IsAdmin() && COption::GetOptionString("main", "wizard_clear_exec", "N", SITE_ID) != "Y" && $APPLICATION->GetCurPage(true) == SITE_DIR."index.php")
	{
		$gadgetButtons .= '
			<div class="sidebar-block sidebar-help-block">
				<b class="r2"></b><b class="r1"></b><b class="r0"></b>
				<div class="sidebar-block-inner">
					<div class="sidebar-block-title">'.GetMessage("CMDESKTOP_DEMO_DATA_BLOCK_TITLE").'</div>
					<div class="sidebar-help-content">'.GetMessage("CMDESKTOP_DEMO_DATA_BLOCK_DESC", Array("#LINK_TO_WIZARD#" => "/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardSiteID=".SITE_ID."&wizardName=bitrix:portal_clear&".bitrix_sessid_get())).'</div>
				</div>
				<i class="r0"></i><i class="r1"></i><i class="r2"></i>
			</div>';
	}

	$gadgetButtons .= '</td></tr></table><div style="display:none; border:1px #404040 dashed; margin-bottom:8px;" id="ddesktop-actions"></div>';
}

?><form action="<?=POST_FORM_ACTION_URI?>" method="POST" id="GDHolderForm_<?=$arResult["ID"]?>">
<?=bitrix_sessid_post()?>
<input type="hidden" name="holderid" value="<?=$arResult["ID"]?>">
<input type="hidden" name="gid" value="0">
<input type="hidden" name="action" value="">
</form>

<table class="gadgetholder gadgetholder-<?=$arResult["ID"]?>" cellspacing="0" cellpadding="0" width="100%" id="GDHolder_<?=$arResult["ID"]?>">
	<tbody>
	<tr><?
		for($i=0; $i<$arResult["COLS"]; $i++)
		{
			if($i==0)
			{
				?><td class="gd-page-column<?=$i?>" valign="top" width="<?=$arResult["COLUMN_WIDTH"][$i]?>" id="s0"><?
				if ($arResult["COLS"] == 1)
				{
					$this->SetViewTarget("sidebar", 100);
					?><?=$gadgetButtons?><?
					$this->EndViewTarget();
				}
			}
			elseif($i==$arResult["COLS"]-1)
			{
				?><td width="10">
					<div style="WIDTH: 10px"></div>
					<br />
				</td>
				<td class="gd-page-column<?=$i?>" valign="top" width="<?=$arResult["COLUMN_WIDTH"][$i]?>" id="s2"><?
					$this->SetViewTarget("sidebar", 100);
					?><?=$gadgetButtons?><?
					$this->EndViewTarget();
			}
			else
			{
				?><td width="10">
					<div style="WIDTH: 10px"></div>
					<br />
				</td>
				<td class="gd-page-column<?=$i?>" valign="top"  width="<?=$arResult["COLUMN_WIDTH"][$i]?>" id="s1"><?
			}
				
			foreach($arResult["GADGETS"][$i] as $arGadget)
			{
				$bChangable = true;
				if (
					!$GLOBALS["USER"]->IsAdmin() 
					&& array_key_exists("GADGETS_FIXED", $arParams) 
					&& is_array($arParams["GADGETS_FIXED"]) 
					&& in_array($arGadget["GADGET_ID"], $arParams["GADGETS_FIXED"])
					&& array_key_exists("CAN_BE_FIXED", $arGadget)
					&& $arGadget["CAN_BE_FIXED"]
				)
					$bChangable = false;
				?>
				<table id="t<?=$arGadget["ID"]?>" class="data-table-gadget<?=($arGadget["HIDE"] == "Y" ?' gdhided':'')?>"><tr><td><div class="gdparent"><?
					if($arResult["PERMISSION"]>"R")
					{
						?><div class="gdheader" style="cursor:move;" onmousedown="return getGadgetHolder('<?=AddSlashes($arResult["ID"])?>').DragStart('<?=$arGadget["ID"]?>', event)" onmouseover="this.className=this.className.replace(/\s*gdheader-hover\s*/,'') + ' gdheader-hover';" onmouseout="this.className=this.className.replace(/\s*gdheader-hover\s*/,'')">
							<div class="gdheader-actions"><?
							if ($bChangable)
							{
								?><a class="gdsettings<?=($arGadget["NOPARAMS"]?' gdnoparams':'')?>" href="javascript:void(0)" onclick="return getGadgetHolder('<?=AddSlashes($arResult["ID"])?>').ShowSettings('<?=$arGadget["ID"]?>');" title="<?=GetMessage("CMDESKTOP_TDEF_SETTINGS")?>"></a><?
							}
							?><a class="gdhide" href="javascript:void(0)" onclick="return getGadgetHolder('<?=AddSlashes($arResult["ID"])?>').Hide('<?=$arGadget["ID"]?>', this);" title="<?=GetMessage("CMDESKTOP_TDEF_HIDE")?>"></a><?
							if ($bChangable)
							{
								?><a class="gdremove" href="javascript:void(0)" onclick="return getGadgetHolder('<?=AddSlashes($arResult["ID"])?>').Delete('<?=$arGadget["ID"]?>');" title="<?=GetMessage("CMDESKTOP_TDEF_DELETE")?>"></a><?
							}
							?>
							</div><?
					}
					else
					{
						?><div class="gdheader"><?
					}
					?><div class="gdheader-title"><?=$arGadget["TITLE"]?></div>
					</div>
					<div class="ghheader-underline"></div>
					<div class="gdoptions" style="display:none" id="dset<?=$arGadget["ID"]?>"></div>
					<div class="gdcontent" id="dgd<?=$arGadget["ID"]?>"><?=$arGadget["CONTENT"]?></div>
				</div></td></tr></table>
				<div style="display:none; border:1px #404040 dashed; margin-bottom:8px;" id="d<?=$arGadget["ID"]?>"></div><?
			}
			?></td><?
		}
	?></tr>
	</tbody>
</table>