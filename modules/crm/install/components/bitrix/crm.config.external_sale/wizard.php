<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");

CUtil::InitJSCore(array('window', 'popup', 'tooltip'));

?>
	<style type="text/css">
		.crm-field-action-link, .crm-field-action-link:link, .crm-field-action-link:visited, .crm-field-action-link:hover, .crm-field-action-link:active {
			border-bottom: 1px dashed #938F79;
			color: #757361;
			cursor: pointer;
			display: inline-block;
			font-size: 13px;
			outline: medium none;
			text-decoration: none;
		}
	</style>

<?
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/interface/prolog_jspopup_admin.php");

CUtil::JSPostUnescape();

if (!CModule::IncludeModule('crm'))
	die();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

function __ExtSaleWizardShowError($text)
{
	ShowError($text);

	echo '<script type="text/javascript">';
	echo 'BX.WindowManager.Get().SetButtons([BX.WindowManager.Get().btnClose]);';
	echo 'BX.WindowManager.Get().AdjustShadow();';
	echo '</script>';

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_after.php");

	exit();
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
	__ExtSaleWizardShowError(GetMessage('CRM_PERMISSION_DENIED'));

$id = intval($_REQUEST["id"]);

$currentStep = intval($_REQUEST["current_step"]);
if ($currentStep <= 0 || $currentStep > 6)
	$currentStep = 1;

$processActions = false;
if (isset($_REQUEST["wizard_action"]))
{
	if ($_REQUEST["wizard_action"] == "prev" && $currentStep > 1)
	{
		$currentStep--;
	}
	elseif ($_REQUEST["wizard_action"] == "next" && $currentStep < 6)
	{
		$processActions = true;
		$currentStep++;
	}
}

if ($processActions && !check_bitrix_sessid())
	__ExtSaleWizardShowError(GetMessage("BPWC_WNCW_SESS_ERR"));

if ($id > 0)
{
	$dbRecordsList = CCrmExternalSale::GetList(
		array(),
		array("ID" => $id)
	);
	$arRecord = $dbRecordsList->GetNext();
	if (!$arRecord)
		__ExtSaleWizardShowError(GetMessage("BPWC_WLC_WRONG_BP"));
}

$errorMessage = "";
if ($currentStep > 3 && $processActions && $id <= 0)
	$errorMessage .= GetMessage("BPWC_WLC_NO_RECORD")."<br />";

$modificationMode = (($id > 0) && ($_REQUEST["modification_mode"] == "Y"));

if ($currentStep == 1 && empty($errorMessage))
{
	$arLimitationSettings = CCrmExternalSale::GetLimitationSettings();
	if ($arLimitationSettings["MAX_SHOPS"] > 0 && $id <= 0)
	{
		$cnt = CCrmExternalSale::Count();
		if ($cnt >= $arLimitationSettings["MAX_SHOPS"])
			$errorMessage .= GetMessage("BPWC_WNC_MAX_SHOPS")."<br>";
	}
	if (!isset($_REQUEST["modification_mode"]))
		$modificationMode = ($id > 0);
}
if ($currentStep == 3 && $processActions && empty($errorMessage))
{
	$arLimitationSettings = CCrmExternalSale::GetLimitationSettings();
	if ($arLimitationSettings["MAX_SHOPS"] > 0 && $id <= 0)
	{
		$cnt = CCrmExternalSale::Count();
		if ($cnt >= $arLimitationSettings["MAX_SHOPS"])
			$errorMessage .= GetMessage("BPWC_WNC_MAX_SHOPS")."<br>";
	}

	$arFields = array(
		"NAME" => $_REQUEST["NAME"],
		"ACTIVE" => "Y",
		"LOGIN" => $_REQUEST["LOGIN"],
		"IMPORT_SIZE" => 10,
		"IMPORT_PREFIX" => $_REQUEST["IMPORT_PREFIX"],
		"IMPORT_ERRORS" => 0,
		"SCHEME" => $_REQUEST["SCHEME"],
		"SERVER" => $_REQUEST["SERVER"],
		"PORT" => $_REQUEST["PORT"],
		"COOKIE" => false,
	);
	if (strlen($_REQUEST["PASSWORD"]) > 0)
		$arFields["PASSWORD"] = $_REQUEST["PASSWORD"];

	if (strlen($_REQUEST["SERVER"]) > 0)
	{
		$arCrmUrl = parse_url($_REQUEST["SERVER"]);
		$crmUrlHost = $arCrmUrl["host"] ? $arCrmUrl["host"] : $arCrmUrl["path"];
		$crmUrlScheme = $arCrmUrl["scheme"] ? strtolower($arCrmUrl["scheme"]) : strtolower($_REQUEST["SCHEME"]);
		if (!in_array($crmUrlScheme, array('http', 'https')))
			$crmUrlScheme = 'http';
		$crmUrlPort = $arCrmUrl["port"] ? intval($arCrmUrl["port"]) : intval($_REQUEST["PORT"]);
		if ($crmUrlPort <= 0)
			$crmUrlPort = $crmUrlScheme == 'https' ? 443 : 80;
		$arFields["SCHEME"] = $crmUrlScheme;
		$arFields["SERVER"] = $crmUrlHost;
		$arFields["PORT"] = $crmUrlPort;
	}

	if (strlen($arFields["LOGIN"]) <= 0)
		$errorMessage .= GetMessage("BPWC_WNC_EMPTY_LOGIN")."<br>";
	if (strlen($arFields["SERVER"]) <= 0)
		$errorMessage .= GetMessage("BPWC_WNC_EMPTY_URL")."<br>";
	if (strlen($arFields["PASSWORD"]) <= 0 && $id <= 0)
		$errorMessage .= GetMessage("BPWC_WNC_EMPTY_PASSWORD")."<br>";

	if (empty($errorMessage))
	{
		if ($id > 0)
		{
			$res = CCrmExternalSale::Update($id, $arFields);
		}
		else
		{
			$arFields["IMPORT_PROBABILITY"] = 20;
			$arFields["IMPORT_PERIOD"] = 7;
			$arFields["IMPORT_PUBLIC"] = "Y";
			$res = CCrmExternalSale::Add($arFields);
		}

		if ($res)
		{
			$id = intval($res);

			$dbRecordsList = CCrmExternalSale::GetList(
				array(),
				array("ID" => $id)
			);
			$arRecord = $dbRecordsList->GetNext();
			if (!$arRecord)
				__ExtSaleWizardShowError(GetMessage("BPWC_WLC_WRONG_BP"));
		}
		else
		{
			if ($ex = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage .= $ex->GetString().".<br>";
			else
				$errorMessage .= "Unknown error."."<br>";
		}
	}

	if (empty($errorMessage))
	{
		$proxy = new CCrmExternalSaleProxy($id);
		if (!$proxy->IsInitialized())
		{
			$errorMessage .= GetMessage("CRM_EXT_SALE_C1NO_CONNECT")."<br>";
		}
		else
		{
			$request = array(
				"METHOD" => "GET",
				"PATH" => "/bitrix/admin/sale_order_new.php",
				"HEADERS" => array(),
				"BODY" => array()
			);

			$response = $proxy->Send($request);
			if ($response == null)
			{
				$errorMessage .= GetMessage("CRM_EXT_SALE_C1ERROR_CONNECT")."<br>";

				$message = '';
				$arErr = $proxy->GetErrors();
				foreach ($arErr as $err)
				{
					$message .= sprintf("[%s] %s<br/>", $err[0], htmlspecialcharsbx($err[1]));
				}
				$errorDetailContainerID = uniqid('crm_ext_sale_wizard_error_');
				$errorMessage .= '<a href="#" onclick="BX(\''.CUtil::JSEscape($errorDetailContainerID).'\').style.display=\'\'; this.style.display=\'none\'; return false;">'.GetMessage("CRM_EXT_SALE_ERROR_DETAILS").'...</a>';
				$errorMessage .= '<div id="'.htmlspecialcharsbx($errorDetailContainerID).'" style="display:none;">'.CUtil::JSEscape($message).'</div>';
			}
			elseif ($response["STATUS"]["CODE"] != 200)
			{
				$errorMessage .= sprintf(GetMessage("CRM_EXT_SALE_C1STATUS")."<br>", $response["STATUS"]["CODE"], $response["STATUS"]["PHRASE"]);
			}
			elseif (strpos($response["BODY"], "form_auth") !== false)
			{
				$errorMessage .= GetMessage("CRM_EXT_SALE_C1NO_AUTH")."<br>";
			}
			elseif (strpos($response["BODY"], "/bitrix/") === false)
			{
				$errorMessage .= GetMessage("CRM_EXT_SALE_C1NO_BITRIX")."<br>";
			}
		}
	}
}

if ($currentStep == 4 && $processActions && empty($errorMessage))
{
	$importPeriod = intval($_REQUEST["IMPORT_PERIOD"]);
	$arLimitationSettings = CCrmExternalSale::GetLimitationSettings();
	if ($arLimitationSettings["MAX_DAYS"] > 0 && $importPeriod > $arLimitationSettings["MAX_DAYS"])
		$importPeriod = $arLimitationSettings["MAX_DAYS"];

	$arFields = array(
		"IMPORT_PERIOD" => $importPeriod,
		"IMPORT_PROBABILITY" => $_REQUEST["IMPORT_PROBABILITY"],
		"IMPORT_RESPONSIBLE" => $_REQUEST["IMPORT_RESPONSIBLE"],
		"IMPORT_PUBLIC" => $_REQUEST["IMPORT_PUBLIC"],
	);

	if (empty($errorMessage))
	{
		$res = CCrmExternalSale::Update($id, $arFields);
		if (!$res)
		{
			if ($ex = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage .= $ex->GetString().".<br>";
			else
				$errorMessage .= "Unknown error."."<br>";
		}
	}
}
if ($currentStep == 6 && $processActions && empty($errorMessage))
{
	$arFields = array(
		"IMPORT_PROBABILITY" => $_REQUEST["IMPORT_PROBABILITY"],
		"IMPORT_RESPONSIBLE" => $_REQUEST["IMPORT_RESPONSIBLE"],
		"IMPORT_PUBLIC" => $_REQUEST["IMPORT_PUBLIC"],
		"IMPORT_GROUP_ID" => $_REQUEST["IMPORT_GROUP_ID"],
	);

	if (empty($errorMessage))
	{
		$res = CCrmExternalSale::Update($id, $arFields);
		if (!$res)
		{
			if ($ex = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage .= $ex->GetString().".<br>";
			else
				$errorMessage .= "Unknown error."."<br>";
		}
	}

	if (empty($errorMessage))
	{
		$dataSyncPeriod = intval($_REQUEST["DATA_SYNC_PERIOD"]);

		$dbAgents = CAgent::GetList(array(), array("NAME" => "CCrmExternalSaleImport::DataSync(".$id.");", "MODULE_ID" => "crm"));
		if ($arAgent = $dbAgents->Fetch())
		{
			if ($dataSyncPeriod > 0)
			{
				if ($arAgent["ACTIVE"] != "Y" || intval($arAgent["AGENT_INTERVAL"] / 60) != $dataSyncPeriod)
					CAgent::Update($arAgent["ID"], array("ACTIVE" => "Y", "AGENT_INTERVAL" => 60 * $dataSyncPeriod));
			}
			else
			{
				CAgent::RemoveAgent("CCrmExternalSaleImport::DataSync(".$id.");", "crm");
			}
		}
		else
		{
			if ($dataSyncPeriod > 0)
				CAgent::AddAgent("CCrmExternalSaleImport::DataSync(".$id.");", "crm", "N", 60 * $dataSyncPeriod);
		}
	}
}

if ($processActions && !empty($errorMessage))
	$currentStep--;

?>

<script>
	top.BX.WindowManager.Get().SetTitle('<?
	$stepTitle = GetMessageJS("BPWC_WNCW_TITLE_".$currentStep);
	if (empty($stepTitle))
		$stepTitle = GetMessageJS("BPWC_WNCW_TITLE", array("#STEP#" => $currentStep));
	echo $stepTitle;
	?>');
	top.BX.WindowManager.Get().SetHead('<?= GetMessageJS("CRM_CES_HEAD_".$currentStep."_".(($id > 0) ? "1" : "0")) ?>');
	function __CrmConfigExtSaleReload()
	{
		var search = window.location.search;
		if (search)
		{
			search = search.replace("&do_show_wizard=Y", "");
			search = search.replace("do_show_wizard=Y&", "");
			window.location.search = search.replace("do_show_wizard=Y", "");
		}
		else
			window.location.reload(true);
	}

	var btns = [];
	<?
	if ($currentStep > 1)
	{
		?>
		btns.push({
			'title': "  <  <?= GetMessageJS("BPWC_WNCW_BACK") ?>  ",
			'id': 'btn-prev',
			'name': 'btn-prev',
			'action': function(){ bxExtSaleWizard.Send("prev"); }
		});
		<?
	}
	if ($currentStep < 6)
	{
		?>
		btns.push({
			'title': "      <?= GetMessageJS("BPWC_WNCW_NEXT") ?>    >      ",
			'id': 'btn-next',
			'name': 'btn-next',
			'action': function(){ bxExtSaleWizard.Send("next"); }
		});
		btns.push({
			'title': "<?= GetMessageJS("BPWC_WNCW_CANCEL") ?>",
			'id': 'btn-cancel',
			'name': 'btn-cancel',
			'action': function(){ this.parentWindow.Close(); __CrmConfigExtSaleReload(); }
		});
		<?
	}
	else
	{
		?>
		btns.push({
			'title': "      <?= GetMessageJS("BPWC_WNCW_CLOSE") ?>      ",
			'id': 'btn-next',
			'name': 'btn-next',
			'action': function(){ this.parentWindow.Close(); __CrmConfigExtSaleReload(); }
		});
		<?
	}
	?>
	top.BX.WindowManager.Get().ClearButtons();
	top.BX.WindowManager.Get().SetButtons(btns);
</script>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
<?if (!empty($errorMessage)):?>
<tr><td colspan="2">
	<?CAdminMessage::ShowMessage(array('HTML' => true, 'DETAILS' => $errorMessage));?>
</td></tr>
	<?endif;?>
<tr><td>
<form method="post">
	<?
	if ($currentStep == 1)
	{
		?>
		<?= GetMessage("CRM_CES_STEP_1"); ?>
		<?
	}
	elseif ($currentStep == 2)
	{
		if ($id > 0)
		{
			$arFields = $arRecord;
		}
		else
		{
			$cnt = CCrmExternalSale::Count();
			$arFields = array("NAME" => "", "LOGIN" => "", "PASSWORD" => "", "IMPORT_PREFIX" => "EShop".($cnt + 1), "SCHEME" => "http", "SERVER" => "", "PORT" => 80);
		}
		if (($arFields["SCHEME"] == "http" && $arFields["PORT"] != 80)
			|| ($arFields["SCHEME"] == "https" && $arFields["PORT"] != 443))
		{
			$arFields["SERVER"] .= ":".$arFields["PORT"];
		}
		foreach ($arFields as $key => &$value)
		{
			if (isset($_REQUEST[$key]))
				$value = htmlspecialcharsbx($_REQUEST[$key]);
		}
		?>
		<table align="center">
			<tr>
				<td class="bx-popup-label"><span style="color:red;">*</span><?= GetMessage("BPWC_WNCW_SHURL") ?>:</td>
				<td valign="top"><select name="SCHEME">
					<option value="http"<?= (($arFields["SCHEME"]=="http") ? " selected" : "") ?>>http</option>
					<option value="https"<?= (($arFields["SCHEME"]=="https") ? " selected" : "") ?>>https</option>
					</select>&nbsp;://&nbsp;<input type="text" name="SERVER"
					value="<?= $arFields["SERVER"] ?>" size="33"><!--&nbsp;:&nbsp;<input type="text" name="PORT"
					value="<?= $arFields["PORT"] ?>" size="5">--></td>
			</tr>
			<tr>
				<td class="bx-popup-label"><span style="color:red;">*</span><?= GetMessage("BPWC_WNCW_SHLOGIN") ?>:</td>
				<td valign="top"><input type="text" name="LOGIN" value="<?= $arFields["LOGIN"] ?>" size="45"></td>
			</tr>
			<tr>
				<td class="bx-popup-label"><?= ($id > 0) ? '' : '<span style="color:red;">*</span>' ?><?= GetMessage("BPWC_WNCW_SHPASSWORD") ?>:</td>
				<td valign="top"><input type="password" name="PASSWORD" value="" size="45"></td>
			</tr>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHNAME") ?>:</td>
				<td valign="top"><input type="text" name="NAME" value="<?= $arFields["NAME"] ?>" size="45"></td>
			</tr>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHPREF") ?>:</td>
				<td valign="top"><input type="text" name="IMPORT_PREFIX" value="<?= $arFields["IMPORT_PREFIX"] ?>" size="45"></td>
			</tr>
			<?
			if ($id > 0)
			{
				?>
				<tr>
					<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHDATE_CREATE") ?>:</td>
					<td valign="top"><?= $arRecord["DATE_CREATE"] ?></td>
				</tr>
				<?
				$lastStatusValue = "";
				if (intval($arRecord["MODIFICATION_LABEL"]) == 0)
					$lastStatusValue .= GetMessage("BPWC_WLC_NEED_FIRST_SYNC1");
				if ($arRecord["LAST_STATUS"] != "" && strtolower(substr($arRecord["LAST_STATUS"], 0, strlen("success"))) != "success")
					$lastStatusValue .= GetMessage("BPWC_WLC_NEED_FIRST_SYNC3").$arRecord["LAST_STATUS"];
				if ($lastStatusValue == "")
					$lastStatusValue .= GetMessage("BPWC_WLC_NEED_FIRST_SYNC2");
				?>
				<tr>
					<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHSTATUS") ?>:</td>
					<td valign="top"><?= $lastStatusValue ?></td>
				</tr>
				<?
			}
			?>
		</table>
		<?
	}
	elseif ($currentStep == 3)
	{
		if ($id > 0)
			$arFields = $arRecord;
		else
			$arFields = array("IMPORT_PERIOD" => 7, "IMPORT_PROBABILITY" => "20", "IMPORT_RESPONSIBLE" => "", "IMPORT_PUBLIC" => "Y");

		foreach ($arFields as $key => &$value)
		{
			if (isset($_REQUEST[$key]))
				$value = htmlspecialcharsbx($_REQUEST[$key]);
		}

		$arUser = false;
		if (intval($arFields["IMPORT_RESPONSIBLE"]) > 0)
		{
			$dbUser = CUser::GetByID($arFields["IMPORT_RESPONSIBLE"]);
			$arUser = $dbUser->GetNext();
		}
		?>
		<table align="center">
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHPERIOD") ?>:</td>
				<td valign="top"><input type="text" name="IMPORT_PERIOD" value="<?= $arFields["IMPORT_PERIOD"] ?>" size="10"></td>
			</tr>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHPROB") ?>:</td>
				<td valign="top"><input type="text" name="IMPORT_PROBABILITY" value="<?= $arFields["IMPORT_PROBABILITY"] ?>" size="10"></td>
			</tr>
			<script type="text/javascript">
			function __BXOnImportResponsibleChange()
			{
				var ddd = document.getElementById("id_IMPORT_RESPONSIBLE_TXT");
				ddd.innerHTML = arguments[0]["name"];
				document.getElementById("id_IMPORT_RESPONSIBLE").value = arguments[0]["id"];
				window.BXMembersSelector.close();
			}

			function __BXOnImportResponsibleShow(el)
			{
				if (!window.BXMembersSelector)
				{
					window.BXMembersSelector = BX.PopupWindowManager.create("members-popup", el, {
						offsetTop : 1,
						autoHide : true,
						closeByEsc : true,
						content : BX("IMPORT_RESPONSIBLE_selector_content")
					});
				}

				if (window.BXMembersSelector.popupContainer.style.display != "block")
				{
					window.BXMembersSelector.show();
				}
			}
			</script>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHRESPONS") ?>:</td>
				<td valign="top"><a onclick="javascript:__BXOnImportResponsibleShow(this)" class="crm-field-action-link" id="id_IMPORT_RESPONSIBLE_TXT"><?= $arUser ? $arUser["NAME"]." ".$arUser["LAST_NAME"]." (".$arUser["LOGIN"].")" : GetMessage("BPWC_WNCW_SELECT") ?></a><?
					$GLOBALS["APPLICATION"]->IncludeComponent(
						'bitrix:intranet.user.selector.new',
						'',
						array(
							'NAME' => 'IMPORT_RESPONSIBLE',
							'VALUE' => $arFields["IMPORT_RESPONSIBLE"],
							'MULTIPLE' => 'N',
							"POPUP" => "Y",
							"SITE_ID" => $_REQUEST["site_id"],
							"ON_SELECT" => "__BXOnImportResponsibleChange",
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
					?>
					<input type="hidden" name="IMPORT_RESPONSIBLE" id="id_IMPORT_RESPONSIBLE" value="<?= $arFields["IMPORT_RESPONSIBLE"] ?>">
				</td>
			</tr>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHPUBLIC") ?>:</td>
				<td valign="top"><input type="checkbox" name="IMPORT_PUBLIC" value="Y"<?= $arFields["IMPORT_PUBLIC"] == "Y" ? " checked" : "" ?>></td>
			</tr>
		</table>
		<?
	}
	elseif ($currentStep == 4)
	{
		?>
		<div style="display: none; color: green; margin:0 30% 0 30%; width:40%;" id="id_stat_load_success"><b><?= GetMessage("BPWC_WNCW_SYNC_SUCCESS") ?></b></div>
		<table align="center">
			<tr>
				<td colspan="2"><?= GetMessage("BPWC_WNCW_SYNC_STAT") ?>:</td>
			</tr>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SYNC_STAT_DEALS") ?>:</td>
				<td id="id_stat_load_deal" valign="top">0</td>
			</tr>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SYNC_STAT_CONTS") ?>:</td>
				<td id="id_stat_load_contact" valign="top">0</td>
			</tr>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SYNC_STAT_COMPS") ?>:</td>
				<td id="id_stat_load_company" valign="top">0</td>
			</tr>
		</table>
		<table align="center" id="id_es_progress_stop">
			<tr>
				<td align="center">
					<img src="/bitrix/components/bitrix/crm.config.external_sale/images/pb1.gif" alt="Loading..."/><br />
					<a href="javascript:ExtSaleDoStop()" id="id_es_progress1_text"><?= GetMessage("BPWC_WNCW_SYNC_STOP_LOAD") ?></a>
				</td>
			</tr>
		</table>
		<script type="text/javascript">
			var extSaleSyncStep = 0;
			var statLoadDeal = 0;
			var statLoadContact = 0;
			var statLoadCompany = 0;
			var extSaleDoStop = false;

			function ExtSaleDoSyncStart()
			{
				if (extSaleSyncStep != 0)
					return;

				statLoadDeal = 0;
				statLoadContact = 0;
				statLoadCompany = 0;
				ExtSaleDoStat();

				ExtSaleDoBtns(true);

				extSaleDoStop = false;
				document.getElementById("id_es_progress1_text").innerHTML = "<?= GetMessage("BPWC_WNCW_SYNC_STOP_LOAD") ?>";

				ExtSaleDoSync();
			}

			function ExtSaleDoStat()
			{
				document.getElementById("id_stat_load_deal").innerHTML = statLoadDeal;
				document.getElementById("id_stat_load_contact").innerHTML = statLoadContact;
				document.getElementById("id_stat_load_company").innerHTML = statLoadCompany;
			}

			function ExtSaleDoBtns(val)
			{
				document.getElementById("btn-prev").disabled = val;
				document.getElementById("btn-next").disabled = val;
				document.getElementById("btn-cancel").disabled = val;
			}

			function ExtSaleDoStop()
			{
				document.getElementById("id_es_progress1_text").innerHTML = "<?= GetMessage("BPWC_WNCW_SYNC_STOPPING") ?>...";
				extSaleDoStop = true;
			}

			function ExtSaleDoSync()
			{
				BX.showWait();

				BX.ajax.get(
					"/bitrix/components/bitrix/crm.config.external_sale/ajax.php",
					{
						id:<?=$id?>,
						skip_bp:'Y',
						skip_notify:'Y',
						timestamp:(new Date()).getTime() //for preventing request caching
					},
					function(v)
					{
						ExtSaleDoSyncResult(ExtSaleDoPrepareResponse(v));
					}
				);
			}

			function ExtSaleDoPrepareResponse(v)
			{
				v = v.replace(/^\s+|\s+$/g, '');
				while (v.length > 0 && v.charCodeAt(0) == 65279)
					v = v.substring(1);

				if (v.length <= 0)
					return undefined;

				try
				{
					eval("v1 = " + v);
				}
				catch (e)
				{
					alert(v);
					return undefined;
				}

				return v1;
			}

			function ExtSaleDoSyncResult(result)
			{
				BX.closeWait();
				if ((result != undefined) && (result["result"] != undefined))
				{
					if (result["result"] == 0 || result["result"] == 1)
					{
						statLoadDeal += parseInt(result["details"]["CreatedDeals"]) + parseInt(result["details"]["UpdatedDeals"]);
						statLoadContact += parseInt(result["details"]["CreatedContacts"]) + parseInt(result["details"]["UpdatedContacts"]);
						statLoadCompany += parseInt(result["details"]["CreatedCompanies"]) + parseInt(result["details"]["UpdatedCompanies"]);

						ExtSaleDoStat();

						if (result["result"] == 0 || extSaleDoStop)
						{
							ExtSaleDoBtns(false);
							document.getElementById("id_es_progress_stop").style.display = "none";
							document.getElementById("id_stat_load_success").style.display = "";
							if (extSaleDoStop)
								document.getElementById("id_stat_load_success").innerHTML = "<?= GetMessage("BPWC_WNCW_SYNC_TERMINATED") ?>";
							extSaleSyncStep = 0;
						}
						else
						{
							extSaleSyncStep++;
							ExtSaleDoSync();
						}
					}
					else
					{
						ExtSaleDoBtns(false);
						document.getElementById("id_es_progress_stop").style.display = "none";
						alert("<?= GetMessageJS("BPWC_WNCW_SYNC_ERROR") ?>:\n" + result["errors"].replace("<br>", "\n"));
						extSaleSyncStep = 0;
					}
				}
				else
				{
					ExtSaleDoBtns(false);
					document.getElementById("id_es_progress_stop").style.display = "none";
					alert("<?= GetMessageJS("BPWC_WNCW_SYNC_ERROR") ?>");
					extSaleSyncStep = 0;
				}
			}

			BX.ready(function(){ExtSaleDoSyncStart();});
		</script>
		<?
	}
	elseif ($currentStep == 5)
	{
		if ($id > 0)
		{
			$arFields = $arRecord;
			$arFields["DATA_SYNC_PERIOD"] = 0;
			$dbAgents = CAgent::GetList(array(), array("NAME" => "CCrmExternalSaleImport::DataSync(".$id.");", "MODULE_ID" => "crm", "ACTIVE" => "Y"));
			if ($arAgent = $dbAgents->Fetch())
				$arFields["DATA_SYNC_PERIOD"] = intval($arAgent["AGENT_INTERVAL"] / 60);

			if (!$modificationMode)
			{
				if ($arFields["DATA_SYNC_PERIOD"] == 0)
					$arFields["DATA_SYNC_PERIOD"] = 10;
				if (intval($arFields["IMPORT_GROUP_ID"]) == 0)
					$arFields["IMPORT_GROUP_ID"] = 3;
			}
		}
		else
		{
			$arFields = array("DATA_SYNC_PERIOD" => 10, "IMPORT_PROBABILITY" => "", "IMPORT_RESPONSIBLE" => "", "IMPORT_PUBLIC" => "", "IMPORT_GROUP_ID" => 3);
		}

		foreach ($arFields as $key => &$value)
		{
			if (isset($_REQUEST[$key]))
				$value = htmlspecialcharsbx($_REQUEST[$key]);
		}

		$arUser = false;
		if (intval($arFields["IMPORT_RESPONSIBLE"]) > 0)
		{
			$dbUser = CUser::GetByID($arFields["IMPORT_RESPONSIBLE"]);
			$arUser = $dbUser->GetNext();
			if (!$arUser)
				$arFields["IMPORT_RESPONSIBLE"] = 0;
		}
		$arGroup = false;
		if (intval($arFields["IMPORT_GROUP_ID"]) > 0)
		{
			$arGroup = CSocNetGroup::GetByID($arFields["IMPORT_GROUP_ID"]);
			if (!$arGroup)
				$arFields["IMPORT_GROUP_ID"] = 0;
		}
		?>
		<table align="center">
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHAGENT") ?>:</td>
				<td valign="top"><input type="text" name="DATA_SYNC_PERIOD" value="<?= $arFields["DATA_SYNC_PERIOD"] ?>" size="10"></td>
			</tr>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHPROB") ?>:</td>
				<td valign="top"><input type="text" name="IMPORT_PROBABILITY" value="<?= $arFields["IMPORT_PROBABILITY"] ?>" size="10"></td>
			</tr>
			<script type="text/javascript">
			function __BXOnImportResponsibleChange()
			{
				var ddd = document.getElementById("id_IMPORT_RESPONSIBLE_TXT");
				ddd.innerHTML = arguments[0]["name"];
				document.getElementById("id_IMPORT_RESPONSIBLE").value = arguments[0]["id"];
				window.BXMembersSelector.close();
			}

			function __BXOnImportResponsibleShow(el)
			{
				if (!window.BXMembersSelector)
				{
					window.BXMembersSelector = BX.PopupWindowManager.create("members-popup", el, {
						offsetTop : 1,
						autoHide : true,
						closeByEsc : true,
						content : BX("IMPORT_RESPONSIBLE_selector_content")
					});
				}

				if (window.BXMembersSelector.popupContainer.style.display != "block")
				{
					window.BXMembersSelector.show();
				}
			}
			</script>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHRESPONS") ?>:</td>
				<td valign="top"><a onclick="javascript:__BXOnImportResponsibleShow(this)" class="crm-field-action-link" id="id_IMPORT_RESPONSIBLE_TXT"><?= $arUser ? $arUser["NAME"]." ".$arUser["LAST_NAME"]." (".$arUser["LOGIN"].")" : GetMessage("BPWC_WNCW_SELECT") ?></a><?
					$GLOBALS["APPLICATION"]->IncludeComponent(
						'bitrix:intranet.user.selector.new',
						'',
						array(
							'NAME' => 'IMPORT_RESPONSIBLE',
							'VALUE' => $arFields["IMPORT_RESPONSIBLE"],
							'MULTIPLE' => 'N',
							"POPUP" => "Y",
							"SITE_ID" => $_REQUEST["site_id"],
							"ON_SELECT" => "__BXOnImportResponsibleChange"
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
					?>
					<input type="hidden" name="IMPORT_RESPONSIBLE" id="id_IMPORT_RESPONSIBLE" value="<?= $arFields["IMPORT_RESPONSIBLE"] ?>">
				</td>
			</tr>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHPUBLIC") ?>:</td>
				<td><input type="checkbox" name="IMPORT_PUBLIC" value="Y"<?= $arFields["IMPORT_PUBLIC"] == "Y" ? " checked" : "" ?>></td>
			</tr>
			<script type="text/javascript">
			function __BXOnImportGroupChange()
			{
				var ddd = document.getElementById("id_GROUP_TXT");
				ddd.innerHTML = arguments[0][0]["title"];
				document.getElementById("id_IMPORT_GROUP_ID").value = arguments[0][0]["id"];
				//groupsPopup.close();
			}

			function __BXOnImportGroupShow()
			{
				groupsPopup.show();
			}
			</script>
			<tr>
				<td class="bx-popup-label"><?= GetMessage("BPWC_WNCW_SHGROUPS") ?>:</td>
				<td valign="top">
					<a onclick="javascript:__BXOnImportGroupShow()" class="crm-field-action-link" id="id_GROUP_TXT"><?= $arGroup ? $arGroup["NAME"] : GetMessage("BPWC_WNCW_SELECT") ?></a>
					<?
					$name = $APPLICATION->IncludeComponent(
							"bitrix:socialnetwork.group.selector", ".default", array(
								"BIND_ELEMENT" => "id_GROUP_TXT",
								"ON_SELECT" => "__BXOnImportGroupChange",
								"SELECTED" => $arFields["IMPORT_GROUP_ID"]
							), null, array("HIDE_ICONS" => "Y")
						);
					?>
					<input type="hidden" name="IMPORT_GROUP_ID" id="id_IMPORT_GROUP_ID" value="<?= $arFields["IMPORT_GROUP_ID"] ?>" />
				</td>
			</tr>
		</table>
		<?
	}
	elseif ($currentStep == 6)
	{
		?>
		<?= GetMessage("CRM_CES_STEP_6", array("#URL#" => "/crm/configs/external_sale/")); ?>
		<?
	}
	?>
	<input type="hidden" name="current_step" value="<?= $currentStep ?>">
	<input type="hidden" name="modification_mode" value="<?= $modificationMode ? "Y" : "N" ?>">
	<input type="hidden" name="id" value="<?= $id ?>">
	<?=bitrix_sessid_post();?>
	<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
	<input type="hidden" name="site_id" value="<?= htmlspecialcharsbx($_REQUEST["site_id"]) ?>">
</form>

</td></tr>
</table>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>
