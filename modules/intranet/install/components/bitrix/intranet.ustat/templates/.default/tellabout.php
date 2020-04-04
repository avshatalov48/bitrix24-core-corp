<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!isset($_REQUEST['WHAT']))
{
	return;
}

if (isset($_REQUEST['AJAX']))
{
	$APPLICATION->RestartBuffer();
}

if ($_REQUEST['WHAT'] == 'SOCNET')
{
	$title = GetMessage('INTRANET_USTAT_TELLABOUT_SOCNET_TITLE');
	$text = GetMessage('INTRANET_USTAT_TELLABOUT_SOCNET_TEXT');
}
elseif ($_REQUEST['WHAT'] == 'LIKES')
{
	$title = GetMessage('INTRANET_USTAT_TELLABOUT_LIKES_TITLE');
	$text = GetMessage('INTRANET_USTAT_TELLABOUT_LIKES_TEXT');
}
elseif ($_REQUEST['WHAT'] == 'TASKS')
{
	$title = GetMessage('INTRANET_USTAT_TELLABOUT_TASKS_TITLE');
	$text = GetMessage('INTRANET_USTAT_TELLABOUT_TASKS_TEXT');
}
elseif ($_REQUEST['WHAT'] == 'IM')
{
	$title = GetMessage('INTRANET_USTAT_TELLABOUT_IM_TITLE');
	$text = GetMessage('INTRANET_USTAT_TELLABOUT_IM_TEXT');
}
elseif ($_REQUEST['WHAT'] == 'DISK')
{
	$title = GetMessage('INTRANET_USTAT_TELLABOUT_DISK_TITLE');
	$text = GetMessage('INTRANET_USTAT_TELLABOUT_DISK_TEXT');
}
elseif ($_REQUEST['WHAT'] == 'MOBILE')
{
	$title = GetMessage('INTRANET_USTAT_TELLABOUT_MOBILE_TITLE');
	$text = GetMessage('INTRANET_USTAT_TELLABOUT_MOBILE_TEXT');
}
elseif ($_REQUEST['WHAT'] == 'CRM')
{
	$title = GetMessage('INTRANET_USTAT_TELLABOUT_CRM_TITLE');
	$text = GetMessage('INTRANET_USTAT_TELLABOUT_CRM_TEXT');
}
else
{
	return;
}

if (!isset($arParams["PATH_TO_POST"]))
{
	$arParams["PATH_TO_POST"] = "/company/personal/user/".$USER->getId()."/blog/edit/new/";
}

?>
<form <?=isset($_REQUEST['AJAX'])?'style="display: none"':''?> id="intranet-ustat-tell-about-form" action="/bitrix/urlrewrite.php?SEF_APPLICATION_CUR_PAGE_URL=<?=str_replace("%23", "#", urlencode($arParams["PATH_TO_POST"]))?>" method="POST">
<?=bitrix_sessid_post()?>
<input type="text" name="POST_TITLE" value="<?=htmlspecialcharsbx($title)?>">
<textarea name="POST_MESSAGE"><?=htmlspecialcharsbx($text)?></textarea>
<input type="hidden" name="changePostFormTab" value="important">
<input type="submit">
</form>

<!-- AJAX_EXECUTED_SUCCESSFULLY -->

<?
if (isset($_REQUEST['AJAX']))
	die();
?>