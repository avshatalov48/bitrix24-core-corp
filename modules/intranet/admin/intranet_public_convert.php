<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/include.php");

IncludeModuleLangFile(__FILE__);

if($REQUEST_METHOD=="POST" && check_bitrix_sessid())
{
	if (isset($_POST["skip_convert_button"]))
	{
		COption::SetOptionString("intranet", "intranet_public_converted", "N");
	}
	else if (isset($_POST["convert_button"]))
	{
		$extranetSiteId = COption::GetOptionString("extranet", "extranet_site", false);

		$by = 'sort'; $order='asc';
		$rsSite = CSite::GetList($by,$order);
		while ($site = $rsSite->Fetch())
		{
			if ($extranetSiteId && $site["ID"] == $extranetSiteId)
			{
				$lang = in_array(LANGUAGE_ID, array("ru", "en", "de")) ? LANGUAGE_ID : \Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID);
				if (file_exists($_SERVER['DOCUMENT_ROOT']."/bitrix/wizards/bitrix/extranet/site/public/".$lang."/"))
				{
					CopyDirFiles(
						$_SERVER['DOCUMENT_ROOT']."/bitrix/wizards/bitrix/extranet/site/public/".$lang."/.top.menu_ext.php",
						$_SERVER["DOCUMENT_ROOT"].$site['DIR'].".top.menu_ext.php",
						true,
						true
					);
				}

				continue;
			}

			if (isset($_POST["int_convert_public_section"]))
			{
				require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/wizard_util.php");

				/*======== timeman ====*/
				if (file_exists($_SERVER['DOCUMENT_ROOT']."/bitrix/wizards/bitrix/portal/site/public/timeman/"))
				{
					CopyDirFiles(
						$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/bitrix/portal/site/public/timeman/",
						$_SERVER["DOCUMENT_ROOT"].$site['DIR']."timeman/",
						true,
						true
					);

					if ($_SERVER["DOCUMENT_ROOT"].$site['DIR']."company/work_report.php")
					{
						CopyDirFiles(
							$_SERVER["DOCUMENT_ROOT"].$site['DIR']."company/work_report.php",
							$_SERVER["DOCUMENT_ROOT"].$site['DIR']."timeman/work_report.php",
							true,
							true,
							true // delete after copy
						);
					}

					if ($_SERVER["DOCUMENT_ROOT"].$site['DIR']."company/timeman.php")
					{
						CopyDirFiles(
							$_SERVER["DOCUMENT_ROOT"].$site['DIR']."company/timeman.php",
							$_SERVER["DOCUMENT_ROOT"].$site['DIR']."timeman/timeman.php",
							true,
							true,
							true // delete after copy
						);
					}

					if ($_SERVER["DOCUMENT_ROOT"].$site['DIR']."services/meeting/")
					{
						CopyDirFiles(
							$_SERVER["DOCUMENT_ROOT"].$site['DIR']."services/meeting/",
							$_SERVER["DOCUMENT_ROOT"].$site['DIR']."timeman/meeting/",
							true,
							true,
							true // delete after copy
						);

						$content = file_get_contents($_SERVER["DOCUMENT_ROOT"].$site['DIR']."timeman/meeting/index.php");

						if ($content && strpos($content, "services/meeting/") !== false)
						{
							$content = str_replace("services/meeting/", "timeman/meeting/", $content);
							file_put_contents($_SERVER["DOCUMENT_ROOT"].$site['DIR']."timeman/meeting/index.php", $content);
						}
					}

					CWizardUtil::ReplaceMacrosRecursive($_SERVER["DOCUMENT_ROOT"].$site['DIR'].'timeman/', array("SITE_DIR" => $site['DIR']));

					CUrlRewriter::Update(array("ID" => 'bitrix:meetings'), array(
						'CONDITION' => '#^'.$site['DIR'].'timeman/meeting/#',
						'RULE' => '',
						'ID' => 'bitrix:meetings',
						'PATH' => $site['DIR'].'timeman/meeting/index.php'
					));

					if (\Bitrix\Main\Loader::includeModule("fileman"))
					{
						$menu = CFileMan::GetMenuArray($_SERVER["DOCUMENT_ROOT"].$site['DIR']."company/.left.menu.php");
						if (is_array($menu))
						{
							foreach($menu["aMenuLinks"] as $key => $item)
							{
								if (preg_match("/((work_report|absence|timeman)\\.php)|(\/company\/personal\/processes\/)/i", $item[1]))
								{
									unset($menu["aMenuLinks"][$key]);
								}
							}

							CFileMan::SaveMenu($site['DIR']."company/.left.menu.php", $menu["aMenuLinks"]);
						}
					}
				}

				/*======== bizproc ====*/
				if (file_exists($_SERVER['DOCUMENT_ROOT']."/bitrix/wizards/bitrix/portal/site/public/bizproc/"))
				{
					CopyDirFiles(
						$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/bitrix/portal/site/public/bizproc/",
						$_SERVER["DOCUMENT_ROOT"].$site['DIR']."bizproc/",
						true,
						true
					);

					CWizardUtil::ReplaceMacrosRecursive($_SERVER["DOCUMENT_ROOT"].$site['DIR'].'bizproc/', array("SITE_DIR" => $site['DIR']));

					CUrlRewriter::Update(array("ID" => 'bitrix:lists', "CONDITION" => '#^'.$site['DIR'].'services/processes/#'), array(
						'CONDITION' => '#^'.$site['DIR'].'bizproc/processes/#',
						'RULE' => '',
						'ID' => 'bitrix:lists',
						'PATH' => $site['DIR'].'bizproc/processes/index.php'
					));

					\Bitrix\Main\Config\Option::set("lists", "livefeed_url", "/bizproc/processes/");

					if (\Bitrix\Main\Loader::includeModule("fileman"))
					{
						$menu = CFileMan::GetMenuArray($_SERVER["DOCUMENT_ROOT"].$site['DIR']."services/.left.menu.php");
						if (is_array($menu))
						{
							foreach($menu["aMenuLinks"] as $key => $item)
							{
								if (preg_match("/(meeting|processes)/i", $item[1]))
								{
									unset($menu["aMenuLinks"][$key]);
								}
							}

							CFileMan::SaveMenu($site['DIR']."services/.left.menu.php", $menu["aMenuLinks"]);
						}
					}
				}

				/*======== calendar ====*/
				if (file_exists($_SERVER['DOCUMENT_ROOT']."/bitrix/wizards/bitrix/portal/site/public/calendar/"))
				{
					CopyDirFiles(
						$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/bitrix/portal/site/public/calendar/",
						$_SERVER["DOCUMENT_ROOT"].$site['DIR']."calendar/",
						true,
						true
					);

					if ($_SERVER["DOCUMENT_ROOT"].$site['DIR']."about/calendar.php")
					{
						CopyDirFiles(
							$_SERVER["DOCUMENT_ROOT"].$site['DIR']."about/calendar.php",
							$_SERVER["DOCUMENT_ROOT"].$site['DIR']."calendar/index.php",
							true,
							true,
							true // delete after copy
						);
					}

					if (\Bitrix\Main\Loader::includeModule("fileman"))
					{
						$menu = CFileMan::GetMenuArray($_SERVER["DOCUMENT_ROOT"].$site['DIR']."about/.left.menu.php");
						if (is_array($menu))
						{
							foreach($menu["aMenuLinks"] as $key => $item)
							{
								if (preg_match("/calendar\\.php/i", $item[1]))
								{
									unset($menu["aMenuLinks"][$key]);
								}
							}

							CFileMan::SaveMenu($site['DIR']."about/.left.menu.php", $menu["aMenuLinks"]);
						}
					}

					CWizardUtil::ReplaceMacrosRecursive($_SERVER["DOCUMENT_ROOT"].$site['DIR'].'calendar/', array("SITE_DIR" => $site['DIR']));
				}

				/*======== disk ====*/
				if (file_exists($_SERVER['DOCUMENT_ROOT']."/bitrix/wizards/bitrix/portal/site/public/docs/"))
				{
					CopyDirFiles(
						$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/bitrix/portal/site/public/docs/.left.menu_ext.php",
						$_SERVER["DOCUMENT_ROOT"].$site['DIR']."docs/.left.menu_ext.php",
						true,
						true
					);
				}

				CopyDirFiles(
					$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/bitrix/portal/site/public/company/personal/.left.menu_ext.php",
					$_SERVER["DOCUMENT_ROOT"].$site['DIR']."company/personal/.left.menu_ext.php",
					true,
					true
				);
			}

			if (isset($_POST["int_convert_public_index"]))
			{
				//change index.php
				$indexPath = $_SERVER["DOCUMENT_ROOT"].$site['DIR']."index.php";

				if (file_exists($indexPath))
				{
					$content = file_get_contents($indexPath);

					if ($content && strpos($content, "bitrix:socialnetwork.log.ex") !== false)
					{
						CopyDirFiles(
							$_SERVER['DOCUMENT_ROOT'].$site['DIR']."index.php",
							$_SERVER["DOCUMENT_ROOT"].$site['DIR']."stream/index.php",
							false,
							true
						);

						CopyDirFiles(
							$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/bitrix/portal/site/public/index_b24.php",
							$_SERVER['DOCUMENT_ROOT'].$site['DIR'].'index.php',
							true,
							true
						);
					}
				}
			}
		}

		$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
		CBitrixComponent::clearComponentCache("bitrix:menu");
		COption::SetOptionString("intranet", "intranet_public_converted", "Y");
	}

	CAdminNotify::DeleteByTag("INTRANET_PUBLIC_CONVERT");
	LocalRedirect($APPLICATION->GetCurPage());
}

$APPLICATION->SetTitle(GetMessage("INTRANET_PUBLIC_CONVERT_TITLE"));

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("INTRANET_PUBLIC_CONVERT_TAB"), "TITLE"=>GetMessage("INTRANET_PUBLIC_CONVERT_TAB_TITLE")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$isCOnverted = COption::GetOptionString("intranet", "intranet_public_converted", "");
if ($isCOnverted)
{
	echo GetMessage($isCOnverted == "Y" ? "INTRANET_PUBLIC_CONVERT_FINISH" : "INTRANET_PUBLIC_SKIP_CONVERT_FINISH");
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
				<?=GetMessage("INTRANET_PUBLIC_CONVERT_DESC")?>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div style="margin: 20px 0 10px 0">
					<b><?=GetMessage("INTRANET_PUBLIC_CONVERT_DESC_TITLE")?></b>
				</div>
				<?=GetMessage("INTRANET_PUBLIC_CONVERT_DESC2")?>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div style="margin: 10px 0 10px 0">
					<b><?=GetMessage("INTRANET_PUBLIC_CONVERT_OPTIONS_TITLE")?></b>
					<div style="margin-top:5px"><?=GetMessage("INTRANET_PUBLIC_CONVERT_OPTIONS_DESC")?></div>
				</div>
			</td>
		</tr>
		<tr>
			<td width="3%"><input type="checkbox" name="int_convert_public_section" id="int_convert_public_section" value="Y" checked></td>
			<td width="97%"><label for="int_convert_public_section"><?echo GetMessage("INTRANET_PUBLIC_CONVERT_SECTIONS")?></label></td>

		</tr>
		<tr>
			<td width="3%"><input type="checkbox" name="int_convert_public_index" id="int_convert_public_index" value="Y" checked></td>
			<td width="97%"><label for="int_convert_public_index"><?echo GetMessage("INTRANET_PUBLIC_CONVERT_INDEX")?></label></td>
		</tr>
		<?
		$tabControl->Buttons();
		?>
		<input type="submit" id="convert_button" name="convert_button" value="<? echo GetMessage("INTRANET_PUBLIC_CONVERT_BUTTON") ?>" class="adm-btn-save">
		<input type="submit" id="skip_convert_button" name="skip_convert_button" value="<? echo GetMessage("INTRANET_PUBLIC_SKIP_CONVERT_BUTTON") ?>">
		<?
		$tabControl->End();
		?>
	</form>
	<?
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>