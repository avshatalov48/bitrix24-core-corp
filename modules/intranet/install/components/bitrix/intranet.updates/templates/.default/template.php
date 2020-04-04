<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
CUtil::InitJSCore("popup");
$APPLICATION->AddHeadScript("/bitrix/js/main/admin_tools.js");
$bLockControls = false;
?>
<table id="content-edit-form-config" class="content-edit-form" cellspacing="0" cellpadding="0">
	<tr>
		<td class="content-edit-form-header content-edit-form-header-first" colspan="3" >
			<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('UPDATES_TITLE')?></div>
		</td>
	</tr>

	<?if (is_array($arResult["UPDATE_LIST"]) && array_key_exists("CLIENT", $arResult["UPDATE_LIST"])):?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_ACTIVE")?></td>
			<td class="content-edit-form-field-input"><?echo GetMessage("SUP_ACTIVE_PERIOD_TO", array("#DATE_TO#"=>((strlen($arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["DATE_TO_FORMAT"]) > 0) ? $arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["DATE_TO_FORMAT"] : "<i>N/A</i>")));?></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_SERVER")?></td>
			<td class="content-edit-form-field-input"><?echo $arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["HTTP_HOST"]?></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
	<?else:?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_SERVER")?></td>
			<td class="content-edit-form-field-input"><?echo (($s=COption::GetOptionString("main", "update_site"))==""? "-":$s)?></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
	<?endif;?>
	<tr>
		<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_SUBI_CHECK")?></td>
		<td class="content-edit-form-field-input"><?= COption::GetOptionString("main", "update_system_check", "-") ?></td>
		<td class="content-edit-form-field-error"></td>
	</tr>

	<tr>
		<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_SUBI_UPD")?></td>
		<td class="content-edit-form-field-input"><?= COption::GetOptionString("main", "update_system_update", "-") ?></td>
		<td class="content-edit-form-field-error"></td>
	</tr>

	<tr>
		<td class="content-edit-form-field-name content-edit-form-field-name-left"><?echo GetMessage("SUP_SU_RECOMEND")?></td>
		<td class="content-edit-form-field-input">
			<?
			$bComma = False;
			if ($arResult["UPDATES_NUM"] > 0)
			{
				echo str_replace("#NUM#", $arResult["UPDATES_NUM"], GetMessage("SUP_SU_RECOMEND_MOD"));
				$bComma = True;
			}
			if ($arResult["COUNT_LANG_UPDATES"] > 0)
			{
				if ($bComma)
					echo ", ";
				echo str_replace("#NUM#", $arResult["COUNT_LANG_UPDATES"], GetMessage("SUP_SU_RECOMEND_LAN"));
				$bComma = True;
			}
			if ($arResult["UPDATES_NUM"] <= 0 && $arResult["COUNT_LANG_UPDATES"] <= 0)
				echo GetMessage("SUP_SU_RECOMEND_NO");
			?>
		</td>
		<td class="content-edit-form-field-error"></td>
	</tr>
</table>

<script>
	var updRand = 0;
	function PrepareString(str)
	{
		str = str.replace(/^\s+|\s+$/, '');
		while (str.length > 0 && str.charCodeAt(0) == 65279)
			str = str.substring(1);
		return str;
	}
</script>

<? // license
if (!$arResult["IS_LICENSE_SIGNED"])
{
	$bLockControls = true;
	?>
	<div id="upd_licence_div">
		<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
			<tr>
				<td>
					<div class="config_notify_message">
						<b><?= GetMessage("SUP_SUBT_LICENCE") ?></b><br>
						<?= GetMessage("SUP_SUBT_LICENCE_HINT") ?><br><br>
						<input type="button" id="agree_licence_btn" name="agree_licence_btn" value="<?= GetMessage("SUP_SUBT_LICENCE_BUTTON") ?>" onclick="ShowLicence()" class="webform-small-button webform-small-button-accept">
					</div>
				</td>
			</tr>
		</table>
		<br>
	</div>
	<script>
		function ShowLicence()
		{
			if (document.getElementById("licence_float_div"))
				return;

			BX.PopupWindowManager.create("UpdateLicense", null, {
				autoHide: false,
				zIndex: 0,
				offsetLeft: 0,
				offsetTop: 0,
				overlay:true,
				draggable: {restrict:true},
				closeByEsc: true,
				titleBar: '<?=GetMessageJS("SUP_SUBT_LICENCE")?>',
				contentColor: "white",
				contentNoPaddings: true,
				closeIcon: { right : "12px", top : "10px"},
				buttons: [
					new BX.PopupWindowButton({
						text : '<?= GetMessageJS("SUP_APPLY") ?>',
						className : 'popup-window-button-create',
						events : { click : BX.proxy(function()
						{
							if (BX("agree_license_id").checked)
							{
								AgreeLicence();
							}
							else
							{
								alert('<?=GetMessageJS("SUP_SUBT_LICENCE_HINT")?>');
							}
						}, this)}
					})
				],
				content: '<iframe name="license_text" src="//www.1c-bitrix.ru/license-<?=(IsModuleInstalled("intranet")? "intranet-":"")?><?= ((LANGUAGE_ID == "ru") ? "ru" : "en") ?>.htm" style="width:450px; height:250px; display:block;"></iframe><form name="license_form"><input name="agree_license" type="checkbox" value="Y" id="agree_license_id"><label for="agree_license_id"><?= GetMessage("SUP_SUBT_AGREE") ?></label></form>',
				events: {

				}
			}).show();
		}

		function AgreeLicence()
		{
			BX.showWait(BX("upd_licence_div"));

			CHttpRequest.Action = function(result)
			{
				result = PrepareString(result);

				BX.closeWait(BX("upd_licence_div"));

				if (result == "Y")
				{
					CloseLicence();
					var udl = document.getElementById("upd_licence_div");
					udl.style["display"] = "none";
					UnLockControls();
				}
				else
				{
					alert("<?= GetMessage("SUP_SUBT_ERROR_LICENCE") ?>");
				}
			}

			updRand++;
			CHttpRequest.Send('/bitrix/admin/update_system_act.php?query_type=licence&<?= bitrix_sessid_get() ?>&updRand=' + updRand);
		}

		function UnLockControls()
		{
			var button = document.getElementById("install_updates_button");
			if (BX.type.isDomNode(button))
			{
				button.disabled = false;
				BX.removeClass(button.parentNode, "webform-button-disable");
			}
		}

		function CloseLicence()
		{
			BX.PopupWindowManager._currentPopup.close();
		}
	</script>
	<?
}

if ($arResult["IS_LICENSE_FOUND"])
{
	//update update system
	if ($arResult["UPDATE_LIST"] !== false && isset($arResult["UPDATE_LIST"]["UPDATE_SYSTEM"]))
	{
		$bLockControls = true;
		?>
		<div id="upd_updateupdate_div">
			<table border="0" cellspacing="1" cellpadding="3" width="100%" class="internal">
				<tr>
					<td class="icon-new"><div class="icon icon-update"></div></td>
					<td>
						<div class="config_notify_message">
							<b><?= GetMessage("SUP_SUBU_UPDATE") ?></b><br/>
							<?= GetMessage("SUP_SUBU_HINT") ?><br/><br/>
							<input type="button" id="id_updateupdate_btn" name="updateupdate_btn" value="<?= GetMessage("SUP_SUBU_BUTTON") ?>" onclick="UpdateUpdate()" class="webform-small-button webform-small-button-accept">
						</div>
					</td>
				</tr>
			</table>
			<br>
		</div>
		<script>
			function UpdateUpdate()
			{
				document.getElementById("id_updateupdate_btn").disabled = true;
				BX.showWait(BX("upd_updateupdate_div"));

				CHttpRequest.Action = function(result)
				{
					BX.closeWait(BX("upd_updateupdate_div"));

					result = PrepareString(result);
					if (result == "Y")
					{
						location.reload();
						//var udl = document.getElementById("upd_register_div");
						//udl.style["display"] = "none";
					}
					else
					{
						alert("<?= GetMessage("SUP_SUBU_ERROR") ?>: " + result);
						document.getElementById("id_updateupdate_btn").disabled = false;
					}
				}

				updRand++;
				CHttpRequest.Send('/bitrix/admin/update_system_act.php?query_type=updateupdate&<?= bitrix_sessid_get() ?>&updRand=' + updRand);
			}
		</script>
	<?
	}

	//updates
	?>
	<div class="content-edit-form-notice-successfully" id="upd_success_div" style="display:none">
		<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=GetMessage('SUP_SUB_SUCCESS')?></span>
	</div>

	<div id="upd_error_div" class="content-edit-form-notice-error" style="display:none">
		<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?= GetMessage("SUP_SUB_ERROR") ?></span>
	</div>

	<div id="upd_install_div" style="display:none">
		<table border="0" cellspacing="1" cellpadding="3" width="100%" class="content-edit-form">
			<tr>
				<td valign="top">
					<div class="config_notify_message">
						<?=GetMessage("SUP_UPDATES_HINT")?>
					</div>
					<div class="progressbar-container" style="display: none">
						<div class="progressbar-track">
							<div id="PBdoneD" class="progressbar-loader" style=""></div>
						</div>
						<div class="progressbar-counter"></div>
					</div>

					<div class="progressbar-container">
						<div class="progressbar-track">
							<div id="PBdone" class="progressbar-loader" style=""></div>
						</div>
						<div class="progressbar-counter" id="install_progress_hint"></div>
					</div>
				</td>
			</tr>
			<tr>
				<td valign="top" align="center">
					<input type="button" name="stop_updates" id="id_stop_updates" value="<?= GetMessage("SUP_SUB_STOP") ?>" onclick="StopUpdates()" class="webform-small-button">
				</td>
			</tr>
		</table>
	</div>

	<!-- update button -->
	<div id="upd_select_div" style="display:block">
		<table border="0" cellspacing="1" cellpadding="3" width="100%" class="content-edit-form">
			<tr>
				<td style="padding: 10px 0 0 22px">
					<input type="button" id="install_updates_button" name="install_updates"<?= (($arResult["COUNT_MODULE_UPDATES"] <= 0 && $arResult["COUNT_LANG_UPDATES"] <= 0 || $bLockControls) ? " disabled" : "") ?> value="<?= GetMessage("SUP_SU_UPD_BUTTON") ?>" onclick="InstallUpdates()" class="webform-small-button webform-small-button-accept <?if ($arResult["COUNT_MODULE_UPDATES"] <= 0 && $arResult["COUNT_LANG_UPDATES"] <= 0 || $bLockControls):?>webform-button-disable<?endif?>">
				</td>
			</tr>
		</table>
	</div>
<?
}

?>
<script>
	var updSelectDiv = document.getElementById("upd_select_div");
	var updInstallDiv = document.getElementById("upd_install_div");
	var updSuccessDiv = document.getElementById("upd_success_div");
	var updErrorDiv = document.getElementById("upd_error_div");

	var PBdone = document.getElementById('PBdone');
	var PBdoneD = document.getElementById('PBdoneD');

	var aStrParams;

	var globalQuantity = <?= $arResult["COUNT_TOTAL_IMPORTANT_UPDATES"] ?>;
	var globalCounter = 0;
	var globalQuantityD = 100;
	var globalCounterD = 0;

	var cycleModules = <?= ($arResult["COUNT_MODULE_UPDATES"] > 0) ? "true" : "false" ?>;
	var cycleLangs = <?= ($arResult["COUNT_LANG_UPDATES"] > 0) ? "true" : "false" ?>;
	var cycleHelps = false;

	var bStopUpdates = false;

	function findlayer(name, doc)
	{
		var i,layer;
		for (i = 0; i < doc.layers.length; i++)
		{
			layer = doc.layers[i];
			if (layer.name == name)
				return layer;
			if (layer.document.layers.length > 0)
				if ((layer = findlayer(name, layer.document)) != null)
					return layer;
		}
		return null;
	}

	function SetProgress(val)
	{
		if (val == 0)
			return;

		PBdone.style.width = val + '%';
	}

	function SetProgressD()
	{
		globalCounterD++;
		if (globalCounterD > globalQuantityD)
			globalCounterD = 0;

		var val = globalCounterD * 100 / globalQuantityD;

		PBdoneD.style.width = (val * 298 / 100) + '%';

		if (!bStopUpdates)
			setTimeout(SetProgressD, 1000);
	}

	function SetProgressHint(val)
	{
		var installProgressHintDiv = document.getElementById("install_progress_hint");
		installProgressHintDiv.innerHTML = val;
	}

	function InstallUpdates()
	{
		SetProgressHint("<?= GetMessage("SUP_INITIAL") ?>");

		__InstallUpdates();
		SetProgressD();
	}

	function __InstallUpdates()
	{
		updSelectDiv.style["display"] = "none";
		updSuccessDiv.style["display"] = "none";
		updErrorDiv.style["display"] = "none";
		updInstallDiv.style["display"] = "block";

		CHttpRequest.Action = function(result)
		{
			InstallUpdatesAction(result);
		}

		var param;
		if (cycleModules)
		{
			param = "M";
		}
		else
		{
			if (cycleLangs)
			{
				param = "L";
			}
			else
			{
				if (cycleHelps)
					param = "H";
			}
		}

		updRand++;
		CHttpRequest.Send('/bitrix/admin/update_system_call.php?' + aStrParams + "&<?= bitrix_sessid_get() ?>&query_type=" + param + "&updRand=" + updRand);
	}

	function InstallUpdatesDoStep(data)
	{
		if (data.length > 0)
		{
			arData = data.split("|");
			globalCounter += parseInt(arData[0]);
			if (arData.length > 1)
				SetProgressHint("<?= GetMessage("SUP_SU_UPD_INSMED1") ?> " + arData[1]);
			if (globalCounter > globalQuantity)
				globalCounter = 0;
			SetProgress(globalCounter * 100 / globalQuantity);
		}

		__InstallUpdates();
	}

	function InstallUpdatesAction(result)
	{
		result = PrepareString(result);

		if (result == "*")
		{
			window.location.reload(false);
			return;
		}

		var code = result.substring(0, 3);
		var data = result.substring(3);
		//alert("code=" + code + "; data=" + data);

		if (bStopUpdates)
		{
			BX.closeWait(BX("upd_install_div"));
			code = "FIN";
			cycleModules = false;
			cycleLangs = false;
			cycleHelps = false;
		}

		if (code == "FIN")
		{
			if (cycleModules)
			{
				cycleModules = false;
			}
			else
			{
				if (cycleLangs)
				{
					cycleLangs = false;
				}
				else
				{
					if (cycleHelps)
						cycleHelps = false;
				}
			}

			if (cycleModules || cycleLangs || cycleHelps)
			{
				InstallUpdatesDoStep(data);
			}
			else
			{
				updSelectDiv.style["display"] = "none";
				updErrorDiv.style["display"] = "none";
				updInstallDiv.style["display"] = "none";
				updSuccessDiv.style["display"] = "block";
			}
		}
		else
		{
			if (code == "STP")
			{
				InstallUpdatesDoStep(data);
			}
			else
			{
				updSelectDiv.style["display"] = "none";
				updSuccessDiv.style["display"] = "none";
				updInstallDiv.style["display"] = "none";
				updErrorDiv.style["display"] = "block";
			}
		}
	}

	function StopUpdates()
	{
		bStopUpdates = true;
		document.getElementById("id_stop_updates").disabled = true;
		BX.showWait(BX("upd_install_div"));
	}
</script>



