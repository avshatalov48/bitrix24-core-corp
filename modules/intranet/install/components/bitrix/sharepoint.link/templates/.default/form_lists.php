<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die(); 

__IncludeLang($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/lang/'.LANGUAGE_ID.'/template.php');

if (count($arResult['LISTS']) > 0):
?>
<style type="text/css">
.bx-sp-lists div {
	margin: 1px;
	padding: 5px;
	border: 1px solid #CCCCCC;
	cursor: pointer;
}

.bx-sp-lists div.bx-sp-unavail {
	background-color: #FFFAFA;
	color: #CCCCCC;
}

.bx-sp-lists div.bx-sp-unavail a { color: #CCCCCC; }

.bx-sp-lists div.bx-sp-current {
	background-color: #EAF8DF;
}
</style>
<div class="bx-sp-lists">
<?
	foreach ($arResult['LISTS'] as $list):
		$ID_CLEAR = htmlspecialcharsbx(CIntranetUtils::checkGUID($list['ID']));
		
		$bExists = ($ID_CLEAR != $arResult['SERVICE']['SP_LIST_ID']) && in_array($ID_CLEAR, $arResult['LISTS_CONNECTED']);
		
		$url_img = $list['IMAGE'] ? $arResult['URL']['scheme'].'://'.$arResult['URL']['host'].$list['IMAGE'] : '';
		$url_list = $arResult['URL']['scheme'].'://'.$arResult['URL']['host'].$list['URL'];
?>
	<div id="line_<?=$ID_CLEAR?>" class="<?
echo $bExists ? 'bx-sp-unavail' : ''
?>" onclick="SLsetListValue(this)">
		<input type="radio" name="sp_list_id" value="<?=$ID_CLEAR?>" id="<?=$ID_CLEAR?>"<?=$bExists ? ' disabled="disabled"' : ''?> />
		<?if ($url_img):?><img src="<?=htmlspecialcharsbx($url_img)?>" border="0" />&nbsp;<?endif;?><a href="<?=htmlspecialcharsbx($url_list)?>" target="_blank"><?=htmlspecialcharsex($list['TITLE'])?></a>
		<?if (strlen($list['DESCRIPTION']) > 0):?><br /><small><?=htmlspecialcharsex($list['DESCRIPTION'])?></small><?endif;?>
	</div>
<?
	endforeach;
?>
</div>

<script type="text/javascript">
function SLsetListValue(row)
{
	var input = BX(row.id.substr(5));
	if (input.disabled) return;
	
	if (window.sp_list_id && window.sp_list_id != input.value)
		BX.removeClass(BX(window.sp_list_id).parentNode, 'bx-sp-current');

	input.checked = true;
	window.sp_list_id = input.value;
	BX.addClass(BX(input.value).parentNode, 'bx-sp-current');
	
	window.SLnextButton.enable();
}

var wnd = BX.WindowManager.Get();
wnd.SetTitle('<?=CUtil::JSEscape(GetMessage('SL_FORM_LISTS_STEP_TITLE'))?>');
wnd.SetHead('<?=CUtil::JSEscape(GetMessage('SL_FORM_LISTS_STEP_HEAD'))?>');

window.SLnextButton.disable();
<?
	if ($arResult['SERVICE']['SP_LIST_ID']):
?>
BX.ready(function() {
	var obInput = BX('<?=CUtil::JSEscape($arResult['SERVICE']['SP_LIST_ID'])?>');
	if (obInput)
	{
		var parent = obInput.parentNode.parentNode;
		parent.removeChild(obInput.parentNode);
		parent.insertBefore(obInput.parentNode, parent.firstChild);
		
		obInput.defaultChecked = obInput.checked = true;
		SLsetListValue(obInput.parentNode);
	}
});
<?
	endif;
?>
</script>
<?
else:
	ShowError(GetMessage('SL_FORM_LISTS_ERROR_NONE'));
?>
<script type="text/javascript">
var wnd = BX.WindowManager.Get();
wnd.ClearButtons();
wnd.SetHead('');
wnd.SetButtons(wnd.btnClose);
</script>
<?
endif;
?>
