<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($_SERVER['REQUEST_METHOD'] == 'POST'):
	$APPLICATION->RestartBuffer();
	?>
	<script type="text/javascript">
	top.location.href = '<?=CUtil::JSEscape($arResult['EVENT_PAGE'])?>';
	top.BX.WindowManager.Get().Close();
	</script>
	<?
	die();
endif;

if(strlen($arResult['ERROR_MESSAGE'])>0):
	?>
	<div class="crm-errors">
		<div class="crm-error-text">
			<?=$arResult['ERROR_MESSAGE']?>
		</div>
	</div>
	<?
	return;
endif;
?>
<form action="/bitrix/components/bitrix/crm.activity.subscribe.add/component.ajax.php" target="add_sbscribe" name="load_form" method="post" enctype="multipart/form-data">
<?=bitrix_sessid_post()?>
<input type="hidden" name="FORM_TYPE" value="<?=$arResult['FORM_TYPE']?>"/>
<input type="hidden" name="FORM_ENTITY_TYPE" value="<?=$arResult['FORM_ENTITY_TYPE']?>"/>
<input type="hidden" name="FORM_ENTITY_ID" value="<?=$arResult['FORM_ENTITY_ID']?>"/>
<input type="hidden" name="ENTITY_TYPE" value="<?=$arResult['ENTITY_TYPE']?>"/>
<?
if (is_array($arResult['ENTITY_ID'])):
	foreach($arResult['ENTITY_ID'] as $iEntitiID):
	?><input type="hidden" name="ENTITY_ID[]" value="<?=$iEntitiID?>"/><?
	endforeach;
else:
	?><input type="hidden" name="ENTITY_ID" value="<?=$arResult['ENTITY_ID']?>"/><?
endif;
?>
<input type="hidden" name="EVENT_PAGE" value="" id="EVENT_PAGE" />
<input type="hidden" name="add_sbscribe" value="Y"/>
<table cellspacing="0" cellpadding="0" border="0" width="100%"  class="bx-edit-table">
<tr class="bx-after-heading">
	<td class="bx-field-value bx-padding" style="width: 96px">
		<span class="required">*</span><?=GetMessage('CRM_SUBSCRIBE_FROM')?>:
	</td>
	<td class="bx-field-value bx-padding" style="">
		<input type="text" value="<?=htmlspecialcharsbx($arResult['EMAIL_FROM'])?>" name=FROM id="FROM" style="width:350px"  />
	</td>
</tr>
<? if (!empty($arResult['EMAIL_LIST']) || !empty($arResult['EMAIL'])) :?>
<tr>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<span class="required">*</span><?=GetMessage('CRM_SUBSCRIBE_TO')?>:
	</td>
	<td class="bx-field-value bx-padding" style="padding-top: 11px!important">
		<?
		if (!empty($arResult['EMAIL'])):
			echo SelectBoxFromArray('TO', $arResult['EMAIL']);
		else:
		?>
		<a href="javascript:void(0)" onclick="crm_show_to(this)"><?=GetMessage("CRM_SUBSCRIBE_TO_SHOW")?></a>
		<textarea cols="95" rows="3" name="TO" id="TO" style="display: none"><?=$arResult['EMAIL_LIST']?></textarea>
		<?
		endif;
		?>
	</td>
</tr>
<?endif;?>
<tr class="bx-after-heading">
	<td class="bx-field-value bx-padding" style="width: 96px">
		<span class="required">*</span><?=GetMessage('CRM_SUBSCRIBE_TITLE')?>:
	</td>
	<td class="bx-field-value bx-padding" style="">
		<input type="text" value="" name="TITLE" id="TITLE" style="width:780px" />
	</td>
</tr>
<tr>
	<td id="subscribe_comments" class="bx-field-value bx-padding event-desc" colspan="2">
	<?
$ar = array(
	'width' => '100%',
	'height' => '250px',
	'inputName' => 'COMMENTS',
	'inputId' => 'COMMENTS',
	'jsObjName' => 'pLEditorCrmSubscribeAdd',
	'content' => isset($arResult['COMMENTS']) ? htmlspecialcharsback($arResult['COMMENTS']) : '',
	'bUseFileDialogs' => false,
	'bFloatingToolbar' => false,
	'bArisingToolbar' => false,
	'bResizable' => false,
	'bSaveOnBlur' => true,
	'toolbarConfig' => array(
		'Bold', 'Italic', 'Underline', 'Strike',
		'BackColor', 'ForeColor',
		'CreateLink', 'DeleteLink',
		'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
	)
);
$LHE = new CLightHTMLEditor;
$LHE->Show($ar);
?>
	</td>
</tr>
<tr>
	<td class="bx-field-value bx-padding subscribe-file" id="subscribeFileBox" colspan="2">
		<div class="subscribe-file-title"><?=GetMessage('CRM_SUBSCRIBE_ADD_FILE')?>:</div>
		<div><input type="file" name="ATTACH[]" onchange="subscribeAddFileInput(this)" /></div>
	</td>
</tr>
</form>
<script type="text/javascript">

	function subscribeAddFileInput(el)
	{
		tableObject = BX('subscribeFileBox');
		divObject = document.createElement("div");
		divObject.innerHTML = el.parentNode.innerHTML;
		tableObject.appendChild(divObject);
		BX.WindowManager.Get().adjustSizeEx();
	}

	function crm_show_to(el)
	{
		el.text = BX('TO').style.display == 'block' ? '<?=CUtil::JSEscape(GetMessage("CRM_SUBSCRIBE_TO_SHOW"))?>' : '<?=CUtil::JSEscape(GetMessage("CRM_SUBSCRIBE_TO_HIDE"))?>';
		BX('TO').style.display = (BX('TO').style.display == 'block' ? 'none' : 'block');
	}

	BX('EVENT_PAGE').value = window.location.href;

	BX.WindowManager.Get().SetTitle('<?=GetMessage('CRM_NEW_TITLE')?>');

	var _BTN = [
		{
			'title': "<?=GetMessage('CRM_SUBSCRIBE_ADD_BUTTON');?>",
			'id': 'crm_event_add',
			'action': function () {
				var bSubmit = true;
				if (document.forms.load_form['TITLE'].value == '')
				{
					document.forms.load_form['TITLE'].className = 'border_red';
					bSubmit = false;
				}
				else
					document.forms.load_form['TITLE'].className = '';

				if (document.forms.load_form['FROM'].value == '')
				{
					document.forms.load_form['FROM'].className = 'border_red';
					bSubmit = false;
				}
				else
					document.forms.load_form['FROM'].className = '';

				if (window.pLEditorCrmSubscribeAdd.GetContent() == '')
				{
					BX('subscribe_comments').className = 'border_red';
					bSubmit = false;
				}
				else
					BX('subscribe_comments').className = '';
				if (bSubmit)
				{
					BX('crm_event_add').disabled = true;
					document.forms.load_form.submit();
					BX.showWait();
				}
			}
		},
		BX.CDialog.btnCancel
	];

	BX.WindowManager.Get().ClearButtons();
	BX.WindowManager.Get().SetButtons(_BTN);
</script>
<iframe name="add_sbscribe" style="display: none"></iframe>