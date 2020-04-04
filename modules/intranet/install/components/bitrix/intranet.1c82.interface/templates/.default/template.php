<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<?CUtil::InitJSCore(Array('ajax','window'));?>

<script>
function OpenInFrame(button)
{
	BX('1c_frame').src="<?=$arResult['AUTH_URL']?>";
	BX('1c_frame').style.display='block';
	button.value='<?=GetMessage('1C_RESTART')?>';
}
	
function OpenInNewWindow(button)
{
	window.open("<?=$arResult['AUTH_URL']?>",'new','width=1000,height=600, top=100 left=100 toolbar=1 scrollbars=yes');
	button.value='<?=GetMessage('1C_RESTART')?>';
}

</script>

<?
if ($arParams['1C_URL']==''):
	echo '<div style="color:red;">'.GetMessage('URL_EMPTY_ERROR').'</div>';
else:
if ($arParams['BLANK_MODE']!='Y'):?>
<input class="but" type="button" value="<?=getMessage('1C_START') ?>" onclick="OpenInFrame(this)">
<?else:?>
<input class="but" type="button" value="<?=getMessage('1C_START') ?>" onclick="OpenInNewWindow(this)">
<?endif;?>

<iframe id="1c_frame" style="display:none; border:none" src="" height="100%" width="100%"></iframe>
<?endif;?>

<script>
BX('1c_frame').height=document.body.clientHeight;
</script>
