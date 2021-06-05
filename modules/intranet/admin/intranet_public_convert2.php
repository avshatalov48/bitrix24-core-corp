<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/include.php");

IncludeModuleLangFile(__FILE__);

if($_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid())
{
	if (isset($_POST["skip_convert_button"]))
	{
		COption::SetOptionString("intranet", "intranet_public_converted2", "N");
	}
	else if (isset($_POST["convert_button"]))
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/wizard_util.php");

		$extranetSiteId = COption::GetOptionString("extranet", "extranet_site", false);

		$rsSite = CSite::GetList();
		while ($site = $rsSite->Fetch())
		{
			if ($extranetSiteId && $site["ID"] == $extranetSiteId)
			{
				CopyDirFiles(
					$_SERVER['DOCUMENT_ROOT']."/bitrix/wizards/bitrix/extranet/site/public/en/contacts/index.php",
					$_SERVER["DOCUMENT_ROOT"].$site['DIR']."contacts/index.php",
					true,
					false
				);
				CopyDirFiles(
					$_SERVER['DOCUMENT_ROOT']."/bitrix/wizards/bitrix/extranet/site/public/en/contacts/employees.php",
					$_SERVER["DOCUMENT_ROOT"].$site['DIR']."contacts/employees.php",
					true,
					false
				);
				DeleteDirFilesEx($site['DIR']."contacts/.left.menu.php");
				CWizardUtil::ReplaceMacros($_SERVER["DOCUMENT_ROOT"].$site['DIR'].'contacts/employees.php', array("SITE_DIR" => $site['DIR']));
			}
			else
			{
				if (file_exists($_SERVER['DOCUMENT_ROOT']."/bitrix/wizards/bitrix/portal/site/public/company/index.php"))
				{
					CopyDirFiles(
						$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/bitrix/portal/site/public/company/index.php",
						$_SERVER["DOCUMENT_ROOT"].$site['DIR']."company/index.php",
						true,
						false
					);

					CWizardUtil::ReplaceMacros($_SERVER["DOCUMENT_ROOT"].$site['DIR'].'company/index.php', array("SITE_DIR" => $site['DIR']));
				}
			}
		}

		$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
		CBitrixComponent::clearComponentCache("bitrix:menu");
		COption::SetOptionString("intranet", "intranet_public_converted2", "Y");
	}

	CAdminNotify::DeleteByTag("INTRANET_PUBLIC_CONVERT2");
	LocalRedirect($APPLICATION->GetCurPage());
}

$APPLICATION->SetTitle(GetMessage("INTRANET_PUBLIC_CONVERT2_TITLE"));

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("INTRANET_PUBLIC_CONVERT2_TAB"), "TITLE"=>GetMessage("INTRANET_PUBLIC_CONVERT2_TAB_TITLE")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$isCOnverted = COption::GetOptionString("intranet", "intranet_public_converted2", "");
if ($isCOnverted)
{
	echo GetMessage($isCOnverted == "Y" ? "INTRANET_PUBLIC_CONVERT2_FINISH" : "INTRANET_PUBLIC_CONVERT2_SKIP_FINISH");
}
else
{
	?>
	<form method="post" name="intr_convert_form" action="<? echo $APPLICATION->GetCurPage() ?>?lang=<? echo LANGUAGE_ID ?>">
		<? echo bitrix_sessid_post(); ?>
		<?
		$tabControl->Begin();
		$tabControl->BeginNextTab();
		?>
		<tr>
			<td colspan="2">
				<?=GetMessage("INTRANET_PUBLIC_CONVERT2_DESC")?>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div style="margin: 20px 0 10px 0">
					<b><?=GetMessage("INTRANET_PUBLIC_CONVERT2_DESC_TITLE")?></b>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div style="margin: 10px 0 10px 0">
					<b><?=GetMessage("INTRANET_PUBLIC_CONVERT2_OPTIONS_TITLE")?></b>
					<div style="margin-top:5px"><?=GetMessage("INTRANET_PUBLIC_CONVERT2_OPTIONS_DESC")?></div>
				</div>
			</td>
		</tr>
		<?
		$tabControl->Buttons();
		?>
		<input type="submit" id="convert_button" name="convert_button" value="<? echo GetMessage("INTRANET_PUBLIC_CONVERT2_BUTTON") ?>" class="adm-btn-save">
		<input type="submit" id="skip_convert_button" name="skip_convert_button" value="<? echo GetMessage("INTRANET_PUBLIC_CONVERT2_SKIP_BUTTON") ?>">
		<?
		$tabControl->End();
		?>
	</form>
	<?
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>