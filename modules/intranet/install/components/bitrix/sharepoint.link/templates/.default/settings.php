<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die(); 

__IncludeLang($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/lang/'.LANGUAGE_ID.'/template.php');
?>
<table class="bx-width100">
	<tr class="section">
		<td colspan="2"><?=GetMessage('SL_SETTINGS_SECTION_CONN')?></td>
	</tr>
	<tr>
		<td class="bx-width50 bx-popup-label"><?=GetMessage('SL_SETTINGS_CONN_SERVER')?>: </td>
		<td><a href="<?=htmlspecialcharsbx($arResult['SERVICE']['SP_URL'])?>" target="_blank"><?=htmlspecialcharsex($arResult['SERVICE']['SP_URL'])?></a></td>
	</tr>
	<tr>
		<td class="bx-popup-label"><?=GetMessage('SL_SETTINGS_CONN_USER')?>: </td>
		<td><?=htmlspecialcharsbx($arResult['SERVICE']['SP_AUTH_USER'])?></td>
	</tr>
	<tr>
		<td class="bx-popup-label"><?=GetMessage('SL_SETTINGS_CONN_PASS')?>: </td>
		<td>********</td>
	</tr>
	<tr class="section">
		<td colspan="2"><?=GetMessage('SL_SETTINGS_SECTION_SYNC')?></td>
	</tr>
	<tr>
		<td class="bx-popup-label"><?=GetMessage('SL_SETTINGS_SYNC_LIST_ID')?>: </td>
		<td><?=CIntranetUtils::makeGUID($arResult['SERVICE']['SP_LIST_ID'])?></td>
	</tr>
	<tr>
		<td class="bx-popup-label"><?=GetMessage('SL_FORM_LIST_INTERVAL')?>: </td>
		<td><?=GetMessage('SL_FORM_LIST_INTERVAL_'.intval($arResult['SERVICE']['SYNC_PERIOD']))?></td>
	</tr>
	<tr>
		<td class="bx-popup-label"><?=GetMessage('SL_FORM_LIST_PRIORITY')?>: </td>
		<td><?=GetMessage('SL_FORM_LIST_PRIORITY_'.$arResult['SERVICE']['PRIORITY'])?></td>
	</tr>
	<tr class="section">
		<td colspan="2"><?=GetMessage('SL_SETTINGS_SECTION_FIELDS')?></td>
	</tr>
<?
	foreach ($arResult['SERVICE']['FIELDS'] as $fld):
?>
	<tr>
		<td class="bx-popup-label">
			<?=htmlspecialcharsbx($fld['SP_FIELD'])?> (<?=htmlspecialcharsbx($fld['SP_FIELD_TYPE'])?>): 
		</td>
		<td>
			<?=(substr($fld['FIELD_ID'], 0, 9) == 'PROPERTY_' ? GetMessage('SL_SETTINGS_FIELD_PROP') : GetMessage('SL_SETTINGS_FIELD_FLD'));?> &quot;<?=htmlspecialcharsbx($arResult['TYPES'][$fld['FIELD_ID']])?>&quot;
		</td>
<?
	endforeach;
?>
</table>
<script type="text/javascript">
</script>
<script type="text/javascript">
var wnd = BX.WindowManager.Get();
wnd.btnEdit = new BX.CWindowButton({
	title: '<?=CUtil::JSEscape(GetMessage('SL_SETTINGS_BTN_CAPTION_EDIT'))?>',
	action: function () {
		wnd.Close();
		<?=$APPLICATION->GetPopupLink(array('URL' => $arResult['SELF'].'?mode=edit&ID='.$arParams['IBLOCK_ID'].'&'.bitrix_sessid_get()));?>
	}
});

wnd.btnResync = new BX.CWindowButton({
	title: '<?=CUtil::JSEscape(GetMessage('SL_SETTINGS_BTN_CAPTION_RESYNC'))?>',
	action: function () {
		if (confirm('<?=CUtil::JSEscape(GetMessage('SL_SETTINGS_BTN_CONFIRM_RESYNC'))?>'))
		{
			wnd.Close();
			BXSPSync(0, this, '<?=CUtil::JSEscape($arResult['SELF'])?>?mode=sync&ID=<?=$arParams['IBLOCK_ID']?>', 1);
		}
	}
});

wnd.SetTitle('<?=CUtil::JSEscape(GetMessage('SL_SETTINGS_STEP_TITLE'))?>');
wnd.SetButtons([wnd.btnEdit, wnd.btnResync, wnd.btnCancel]);
</script>
