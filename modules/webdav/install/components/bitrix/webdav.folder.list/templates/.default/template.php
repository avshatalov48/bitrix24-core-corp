<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;

$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/utils.js');
CUtil::InitJSCore(array('dd'));

global $by, $order;
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
$arParams["BASE_URL"] = trim(str_replace(":443", "", $arParams["BASE_URL"]));
$arHeaders = array(
	array("id" => "NAME", "name" => GetMessage("WD_TITLE_NAME"), "editable"=>true, "sort" => "name", "default" => (in_array("NAME", $arParams["COLUMNS"]))), 
	array("id" => "TIMESTAMP_X", "name" => GetMessage("WD_TITLE_TIMESTAMP"), "sort" => "timestamp_x", "default" => (in_array("TIMESTAMP_X", $arParams["COLUMNS"]))), 
	array("id" => "FILE_SIZE", "name" => GetMessage("WD_TITLE_FILE_SIZE"), "sort" => "file_size", "default" => (in_array("FILE_SIZE", $arParams["COLUMNS"]))), 
); 
/********************************************************************
				/Input params
********************************************************************/
if (!empty($arResult["ERROR_MESSAGE"])):
	ShowError($arResult["ERROR_MESSAGE"]);
endif;

if (isset($_REQUEST["result"]))
{
	$msgid = false;

	switch ( $_REQUEST["result"] )
	{
		case "uploaded": 			$msgid = "WD_UPLOAD_DONE"; break;
		case "deleted": 			$msgid = "WD_DELETE_DONE"; break;
		case "section_deleted": 	$msgid = "WD_SECTION_DELETE_DONE"; break;
		case "empty_trash": 		$msgid = "WD_EMPTY_TRASH_DONE"; break;
		case "document_restored": 	$msgid = "WD_DOC_RESTORE_DONE"; break;
		case "section_restored": 	$msgid = "WD_SEC_RESTORE_DONE"; break;
		case "all_restored": 		$msgid = "WD_RESTORE_DONE"; break;
	}
	if ($msgid != false)
		ShowNote(GetMessage($msgid));
}

/********************************************************************
				FILTER
********************************************************************/
for ($i=0,$cnt=sizeof($arResult["FILTER"]); $i < $cnt; $i++)
{
	if ($arResult["FILTER"][$i]["type"] === "search")
	{
		$searchVal = (isset($arResult["FILTER_VALUE"]["content"]) && strlen($arResult["FILTER_VALUE"]["content"])>0 ? htmlspecialcharsbx($arResult["FILTER_VALUE"]["content"]) : '');
		$val = '<input type="text" style="width:98%;" autocomplete="off" id="wd_'.
			$arResult["FILTER"][$i]["id"].
			'" name="'.$arResult["FILTER"][$i]["id"].'" value="'.$searchVal.'" />'; 
		if (IsModuleInstalled("search"))
		{
			if (!isset($_REQUEST['ajax_call']))
			{
				ob_start();
			}
			$arSearchParams = Array(
				"NUM_CATEGORIES" => "1",
				"TOP_COUNT" => "10",
				"CHECK_DATES" => "N",
				"SHOW_OTHERS" => "N",
				"PAGE" => "#SITE_DIR#search/index.php",
				"SHOW_INPUT" => "N",
				"OBJECT" => $arParams['OBJECT'],
				"ELEMENT_EDIT_URL" => $arParams["ELEMENT_EDIT_URL"],
				"INPUT_ID" => "wd_".$arResult["FILTER"][$i]["id"],
				"CONTAINER_ID" => "sidebar",
			);
			
			$arSearchParams += Array(
				"CATEGORY_0_TITLE" => GetMessage("WD_DOCUMENTS"),
				"CATEGORY_0" => array(0 => "main"),
				"CATEGORY_0_main" => array(0 => $arParams["OBJECT"]->real_path),
			);
				
			$APPLICATION->IncludeComponent("bitrix:search.title", "wd-filter", $arSearchParams, $this->__component);

			if (!isset($_REQUEST['ajax_call']))
			{
				$val .= ob_get_clean();
			}
		}
		$arResult["FILTER"][$i]["type"] = "custom";
		$arResult["FILTER"][$i]["value"] = $val;
	}
}

/********************************************************************
				/ FILTER
********************************************************************/

$arResult["GRID_DATA"] = (is_array($arResult["GRID_DATA"]) ? $arResult["GRID_DATA"] : array()); 

if ($arParams["PERMISSION"] <= "U" || $arParams["PERMISSION"] == "W" && $arParams["CHECK_CREATOR"] == "Y")
{
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.grid",
		"",
		array(
			"GRID_ID" => $arParams["GRID_ID"],
			"HEADERS" => $arHeaders , 
			"SORT" => array($by => $order),
			"ROWS" => $arResult["GRID_DATA"],
			"FOOTER" => array(array("title" => GetMessage("WD_ALL"), "value" => $arResult["GRID_DATA_COUNT"])),
			"FILTER" => $arResult["FILTER"],
			"EDITABLE" => true,
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

$actions = array("delete" => true);
if ($arParams['OBJECT']->meta_state != "TRASH")
{
	$actions["custom_html"] = GetMessage("WD_MOVE_TO").
		'<input type="text" name="IBLOCK_SECTION_ID" id="WD_IBLOCK_SECTION_ID" value="'.$arParams["SECTION_ID"].'" onclick="__wd_show_tree(this);" />';

	?>
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

}
if ($arParams['OBJECT']->meta_state == 'TRASH' && $arParams['PERMISSION'] > "W")
{
?>
<script>
BX(function() {
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
		"SORT" => array($by => $order),
		"ROWS" => $arResult["GRID_DATA"],
		"FOOTER" => array(array("title" => GetMessage("WD_ALL"), "value" => $arResult["GRID_DATA_COUNT"])),
		"EDITABLE" => true,
		"FILTER" => $arResult["FILTER"],
		"ACTIONS" => $actions,
		"ACTION_ALL_ROWS" => true,
		"NAV_OBJECT" => $arResult["NAV_RESULT"],
		"AJAX_MODE" => "N",
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);?>
<script>
function __wd_show_tree(obj)
{
	<?=$APPLICATION->GetPopupLink(Array(
		"URL"=>"/bitrix/components/bitrix/webdav/templates/.default/disk_sections_tree.php?lang=".
			LANGUAGE_ID."&site=".SITE_ID."&folder=".urlencode($arParams["FOLDER"])."&active=".urlencode($arParams["SECTION_ID"]),
		"PARAMS" => Array("width" => 450, "height" => 450)
		)
	)?>; 
}
function __wd_add_move_action()
{
	var form = document.forms.form_<?=$arParams["GRID_ID"]?>; 
	if (typeof form != "object" || form == null)
		return false; 
	if (form.apply)
		form.apply.onkeydown = form.apply.onmousedown = function(){document.forms.form_<?=$arParams["GRID_ID"]?>.action_button_<?=$arParams["GRID_ID"]?>.value = "MOVE";}
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
		var chCells = BX.findChild(BX(gridID), {'tag':'td', 'class':'bx-checkbox-col'}, true, true);
		for (var i=0;i<chCells.length;i++)
		{
			var cBox = BX.findChild(chCells[i], {'tag':'input'});
			if (BX.type.isDomNode(cBox) && cBox.checked)
			{
				cBox.checked = false;
				bxGrid.SelectRow(cBox);
			}
		}
		bxGrid.EnableActions();
	};
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
function WDUploadDroppedFiles(files)
{
	var url = "<?=CUtil::JSEscape($arResult['URL']['UPLOAD'].'?use_light_view=Y')?>";
	var dlg = new BX.CDialog({
		'content_url':url,
		'width':'600',
		'height':'200',
		'resizable':false
	});
	if(files && files.length)
	{
		BX.addCustomEvent(dlg, 'onUploadPopupReady', function() {
			dlg.updateListFiles(files);
		});
		dlg.Show();
	}
}
BX(function() {
	if (!!BX.DD) {
		var dropBoxNode = BX('<?=$arParams['GRID_ID']?>');
		var dropbox = new BX.DD.dropFiles(dropBoxNode);
		if (dropbox && dropbox.supported())
		{
			BX.addCustomEvent(dropbox, 'dropFiles', WDUploadDroppedFiles);
			//BX.addCustomEvent(dropbox, 'dragEnter', function() {BX.addClass(dropBoxNode, 'droptarget');});
			//BX.addCustomEvent(dropbox, 'dragLeave', function() {BX.removeClass(dropBoxNode, 'droptarget');});
		}
	}

	__wd_add_move_action();

	// dblclick on up arrow to move to parent folder
	var upControl = BX.findChild(document, {'class':'section-up'}, true);
	if (upControl)
		BX.bind(
			upControl.parentNode.parentNode,
			'dblclick',
			function() {
				jsUtils.Redirect([], '<?=CUtil::JSEscape($arResult["URL"]["UP"])?>');
			}
		);
});
</script>
<script type="text/javascript">
	BX.message({
		'wd_service_edit_doc_default': '<?= CUtil::JSEscape(CWebDavTools::getServiceEditDocForCurrentUser()) ?>'
	});
</script>
<?
}

if (!empty($arParams["SHOW_NOTE"])):
?>
<br />
<div class="wd-help-list selected" id="wd_list_note"><?=$arParams["~SHOW_NOTE"]?></div>
<?
endif;
?>
