<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$GLOBALS['APPLICATION']->RestartBuffer();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$popupWindow = new CJSPopup('', '');
$popupWindow->ShowTitlebar(GetMessage("WD_CONNECTION_TITLE"));
$popupWindow->StartContent();
$serverParams = $arResult['serverParams'];
?>
<script src="/bitrix/js/webdav/imgshw.js"></script>
<?
if ($serverParams['CLIENT_OS'] == 'Windows XP')
{


	// WebFolder
?>
	<p><?=GetMessage("WD_USEADDRESS");?></p>
	<input type="text" class="wd-connection-line" onclick="this.select();" value="<?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?>" />
	<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_winxp_wfolder_help'));" class="ajax"><?=GetMessage('WD_WEBFOLDER_TITLE');?></a></p>
	<input id="WDMappingButton" type="button" value="<?=GetMessage("WD_CONNECT");?>" />
	<div id="wd_winxp_wfolder_help" class="wd-collapseable">
		<?= GetMessage('WD_CONNECTOR_HELP_WEBFOLDERS', array('#TEMPLATEFOLDER#' => $templateFolder, '#URL_HELP#' => $arResult["URL"]["HELP"])); ?>
		?>
	</div>
<?
	// Network Drive
	if ($serverParams['SECURE'] != true)
	{
?>
		<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_winxp_ndrive_help'));" class="ajax"><?=GetMessage("WD_SHAREDDRIVE_TITLE");?></a></p>
		<div class="wd-collapseable" id="wd_winxp_ndrive_help">
			<?= GetMessage('WD_CONNECTOR_HELP_MAPDRIVE', array('#TEMPLATEFOLDER#' => $templateFolder)); ?>
		</div>
<?
		if ($serverParams['AUTH_MODE'] == 'BASIC')
		{
?>
			<table class="wd-tip">
			<tr><td class="wd-alert-icon"><div>&nbsp;</div></td><td>
			<?=GetMessage("WD_REGISTERPATCH", array("#LINK#" => "/bitrix/webdav/xp.reg"));?>
			</td></tr></table>
<?
		}
	} else {
	}
}
elseif (in_array($serverParams['CLIENT_OS'], array('Windows 2000', 'Windows 2003')))
{
	// WebFolder
?>
	<p><?=GetMessage("WD_USEADDRESS");?></p>
	<input type="text" class="wd-connection-line" onclick="this.select();" value="<?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?>" />
	<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_win2k_wfolder_help'));" class="ajax"><?=GetMessage('WD_WEBFOLDER_TITLE');?></a></p>

	<table class="wd-tip">
	<tr><td class="wd-alert-icon"><div>&nbsp;</div></td><td>
	<?=GetMessage("WD_NOTINSTALLED", array("#LINK#" => "http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64"));?>
	</td></tr></table>

	<input id="WDMappingButton" type="button" value="<?=GetMessage("WD_CONNECT");?>" />
	<div id="wd_win2k_wfolder_help" class="wd-collapseable">
		<?= GetMessage('WD_CONNECTOR_HELP_WEBFOLDERS', array('#TEMPLATEFOLDER#' => $templateFolder, '#URL_HELP#' => $arResult["URL"]["HELP"])); ?>
	</div>
<?
	// Network Drive
	if ($serverParams['SECURE'] != true)
	{
?>
		<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_win2k_ndrive_help'));" class="ajax"><?=GetMessage("WD_SHAREDDRIVE_TITLE");?></a></p>
		<div class="wd-collapseable" id="wd_win2k_ndrive_help">
			<?= GetMessage('WD_CONNECTOR_HELP_MAPDRIVE', array('#TEMPLATEFOLDER#' => $templateFolder)); ?>
		</div>
<?
		if ($serverParams['AUTH_MODE'] == 'BASIC')
		{
?>
			<table class="wd-tip">
			<tr><td class="wd-alert-icon"><div>&nbsp;</div></td><td>
			<?=GetMessage("WD_REGISTERPATCH", array("#LINK#" => "/bitrix/webdav/xp.reg"));?>
			</td></tr></table>
<?
		}
	} else {
	}
}
elseif ($serverParams['CLIENT_OS'] == 'Windows Vista')
{
	// WebFolder
?>
	<p><?=GetMessage("WD_USEADDRESS");?></p>
	<input type="text" class="wd-connection-line" onclick="this.select();" value="<?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?>" />

<?if(false):?>
	<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_winvista_wfolder_help'));" class="ajax"><?=GetMessage('WD_WEBFOLDER_TITLE');?></a></p>
<?endif?>
<br><br>	<input id="WDMappingButton" type="button" value="<?=GetMessage("WD_CONNECT");?>" />
	<div id="wd_winvista_wfolder_help" class="wd-collapseable">
		<?= GetMessage('WD_CONNECTOR_HELP_WEBFOLDERS', array('#TEMPLATEFOLDER#' => $templateFolder, '#URL_HELP#' => $arResult["URL"]["HELP"])); ?>
	</div>
<?
	// Network Drive
?>
	<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_winvista_ndrive_help'));" class="ajax"><?=GetMessage("WD_SHAREDDRIVE_TITLE");?></a></p>
	<div class="wd-collapseable" id="wd_winvista_ndrive_help">
		<?= GetMessage('WD_CONNECTOR_HELP_MAPDRIVE', array('#TEMPLATEFOLDER#' => $templateFolder)); ?>
	</div>
<?
	if ($serverParams['AUTH_MODE'] == 'BASIC')
	{
?>
		<table class="wd-tip">
		<tr><td class="wd-alert-icon"><div>&nbsp;</div></td><td>
		<?=GetMessage("WD_REGISTERPATCH", array("#LINK#" => "/bitrix/webdav/vista.reg"));?>
		</td></tr></table>
<?	} ?>
	<table class="wd-tip">
	<tr><td class="wd-alert-icon"><div>&nbsp;</div></td><td>
	<?=GetMessage("WD_TIP_FOR_2008", array("#LINK#" => htmlspecialcharsbx($arParams['HELP_URL'])));?>
	</td></tr></table>
<?
}
elseif ($serverParams['CLIENT_OS'] == 'Windows 2008')
{
	// WebFolder
?>
	<p><?=GetMessage("WD_USEADDRESS");?></p>
	<input type="text" class="wd-connection-line" onclick="this.select();" value="<?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?>" />
	<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_win2k8_wfolder_help'));" class="ajax"><?=GetMessage('WD_WEBFOLDER_TITLE');?></a></p>
<?// TODO: fix link to manual - already installed but need to enable? ?>

	<table class="wd-tip">
	<tr><td class="wd-alert-icon"><div>&nbsp;</div></td><td>
	<?=GetMessage("WD_NOTINSTALLED", array("#LINK#" => "http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64"));?>
	</td></tr></table>

	<input id="WDMappingButton" type="button" value="<?=GetMessage("WD_CONNECT");?>" />
	<div id="wd_win2k8_wfolder_help" class="wd-collapseable">
		<?= GetMessage('WD_CONNECTOR_HELP_WEBFOLDERS', array('#TEMPLATEFOLDER#' => $templateFolder, '#URL_HELP#' => $arResult["URL"]["HELP"])); ?>
	</div>
<?
	// Network Drive
?>
	<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_win2k8_ndrive_help'));" class="ajax"><?=GetMessage("WD_SHAREDDRIVE_TITLE");?></a></p>
	<div class="wd-collapseable" id="wd_win2k8_ndrive_help">
		<?= GetMessage('WD_CONNECTOR_HELP_MAPDRIVE', array('#TEMPLATEFOLDER#' => $templateFolder)); ?>
	</div>
<?
	if ($serverParams['AUTH_MODE'] == 'BASIC')
	{
?>
		<table class="wd-tip">
		<tr><td class="wd-alert-icon"><div>&nbsp;</div></td><td>
		<?=GetMessage("WD_REGISTERPATCH", array("#LINK#" => "/bitrix/webdav/vista.reg"));?>
		</td></tr></table>
<?
	}
}
elseif ($serverParams['CLIENT_OS'] == 'Windows 7')
{
	// WebFolder
	if ($serverParams['SECURE'] != true)
	{
?>
		<p><?=GetMessage("WD_USEADDRESS");?></p>
		<input type="text" class="wd-connection-line" onclick="this.select();" value="<?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?>" />

<?if(false):?>
		<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_win7_wfolder_help'));" class="ajax"><?=GetMessage('WD_WEBFOLDER_TITLE');?></a></p>
<?endif?>
<br><br>
		<input id="WDMappingButton" type="button" value="<?=GetMessage("WD_CONNECT");?>" />
		<div id="wd_win7_wfolder_help" class="wd-collapseable">
			<?= GetMessage('WD_CONNECTOR_HELP_WEBFOLDERS', array('#TEMPLATEFOLDER#' => $templateFolder, '#URL_HELP#' => $arResult["URL"]["HELP"])); ?>
		</div>
<?
	}
	// Network Drive
	if ($serverParams['SECURE'] == true)
	{
?>
		<p><?=GetMessage("WD_USECOMMANDLINE");?></p>
		<input type="text" class="wd-connection-line" onclick="this.select();" value="net use z: <?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?> /user:<?=$GLOBALS['USER']->GetLogin()?> *" />
<?
	} else {
?>
		<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_win7_ndrive_help'));" class="ajax"><?=GetMessage("WD_SHAREDDRIVE_TITLE");?></a></p>
		<div class="wd-collapseable" id="wd_win7_ndrive_help">
			<?= GetMessage('WD_CONNECTOR_HELP_MAPDRIVE', array('#TEMPLATEFOLDER#' => $templateFolder)); ?>
		</div>
<?
	}
?>
<?
	if ($serverParams['AUTH_MODE'] == 'BASIC')
	{
?>
		<table class="wd-tip">
		<tr><td class="wd-alert-icon"><div>&nbsp;</div></td><td>
		<?=GetMessage("WD_REGISTERPATCH", array("#LINK#" => "/bitrix/webdav/vista.reg"));?>
		</td></tr></table>
<?
	}
}
elseif ($serverParams['CLIENT_OS'] == 'Windows 8')
{
	// WebFolder
	if ($serverParams['SECURE'] != true)
	{
		?>
	<p><?=GetMessage("WD_USEADDRESS");?></p>
	<input type="text" class="wd-connection-line" onclick="this.select();" value="<?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?>" />

	<?if(false):?>
	<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_win7_wfolder_help'));" class="ajax"><?=GetMessage('WD_WEBFOLDER_TITLE');?></a></p>
	<?endif?>
	<br><br>
	<input id="WDMappingButton" type="button" value="<?=GetMessage("WD_CONNECT");?>" />
	<div id="wd_win7_wfolder_help" class="wd-collapseable">
		<?= GetMessage('WD_CONNECTOR_HELP_WEBFOLDERS', array('#TEMPLATEFOLDER#' => $templateFolder, '#URL_HELP#' => $arResult["URL"]["HELP"])); ?>
	</div>
	<?
	}
	// Network Drive
	if ($serverParams['SECURE'] == true)
	{
		?>
	<p><?=GetMessage("WD_USECOMMANDLINE");?></p>
	<input type="text" class="wd-connection-line" onclick="this.select();" value="net use z: <?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?> /user:<?=$GLOBALS['USER']->GetLogin()?> *" />
	<?
	} else {
		?>
	<p class="wd-collapse-toggler wd-collapse-collapsed"><a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_win7_ndrive_help'));" class="ajax"><?=GetMessage("WD_SHAREDDRIVE_TITLE");?></a></p>
	<div class="wd-collapseable" id="wd_win7_ndrive_help">
		<?= GetMessage('WD_CONNECTOR_HELP_MAPDRIVE', array('#TEMPLATEFOLDER#' => $templateFolder)); ?>
	</div>
	<?
	}
	?>
<?
	if ($serverParams['AUTH_MODE'] == 'BASIC')
	{
		?>
	<table class="wd-tip">
		<tr><td class="wd-alert-icon"><div>&nbsp;</div></td><td>
			<?=GetMessage("WD_REGISTERPATCH", array("#LINK#" => "/bitrix/webdav/vista.reg"));?>
		</td></tr></table>
	<?
	}
}
elseif ($serverParams['CLIENT_OS'] == 'Linux')
{
?>
	<p><?=GetMessage("WD_USEADDRESS");?></p>
	<input type="text" class="wd-connection-line" onclick="this.select();" value="<?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?>" />
<?
}
elseif ($serverParams['CLIENT_OS'] == 'Mac')
{
?>
<!-- TODO: its not a link -->
	<p><?=GetMessage("WD_USEADDRESS");?></p>
	<input type="text" class="wd-connection-line" onclick="this.select();" value="<?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?>" />
	<p class="wd-collapse-toggler wd-collapse-collapsed">
		<a href="javascript:void(0);" onclick="WDToggleCollapseable(this.parentNode, BX('wd_macos_help'));" class="ajax"><?=GetMessage("WD_MACOS_TITLE");?></a>
	</p>
	<div class="wd-collapseable" id="wd_macos_help">
			<?= GetMessage('WD_CONNECTOR_HELP_OSX', array('#TEMPLATEFOLDER#' => $templateFolder)); ?>
	</div>
<?
}
elseif ($serverParams['CLIENT_OS'] == 'Windows')
{
?>
	<p><?=GetMessage("WD_USEADDRESS");?></p>
	<input type="text" class="wd-connection-line" onclick="this.select();" value="<?=htmlspecialcharsbx(str_replace(":443", "", $arParams["BASE_URL"]))?>" />
<?
}
else
{
}

?>

<script>
BX.ready(function() {
	if (BX('WDMappingButton')) {
		BX('WDMappingButton').style.display = 'none';
		if (/*@cc_on ! @*/ false)
		{
			//try {
				//if (new ActiveXObject("SharePoint.OpenDocuments.2"))
				//{
					BX('WDMappingButton').style.display = 'block';
					BX.bind(BX('WDMappingButton'), 'click', function() {
						WDMappingDrive("<?=CUtil::JSEscape(str_replace(":443", "", CWebDavBase::get_request_url($arParams["BASE_URL"])))?>");
						BX.WindowManager.Get().Close();
					});
				//}
			//} catch(e) { }
		}
	}
});

var WDToggleCollapseable = function(link, div)
{
	if (div.style.display != 'block')
	{
		div.style.display = 'block';
		BX.addClass(link, 'wd-collapse-expanded');
		BX.removeClass(link, 'wd-collapse-collapsed');
	} else {
		div.style.display = 'none';
		BX.addClass(link, 'wd-collapse-collapsed');
		BX.removeClass(link, 'wd-collapse-expanded');
	}
	return false;
}

function WDMappingDrive(path)
{
	if (!jsUtils.IsIE())
	{
		return false;
	}
	if (!path || path.length <= 0)
	{
		alert('<?=GetMessageJS("WD_EMPTY_PATH")?>');
		return false;
	}
	var behaviorID = BX('WDMappingButton').addBehavior("#default#httpFolder");
	BX('WDMappingButton').navigate(path);
}
</script>
<?
$popupWindow->StartButtons();
$popupWindow->ShowStandardButtons(array('close'));
$popupWindow->EndButtons();
die();
?>
