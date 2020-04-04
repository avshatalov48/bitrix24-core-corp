<?php
if(!$USER->IsAdmin())
	return;

use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/faceid/options.php');

CModule::IncludeModule('faceid');

$errorMessage = '';

$aTabs = array(
	array(
		"DIV" => "edit1", "TAB" => GetMessage("FACEID_TAB_SETTINGS"), "ICON" => "faceid_config", "TITLE" => GetMessage("FACEID_TAB_TITLE_SETTINGS_2"),
	),
	array(
		"DIV" => "edit2", "TAB" => GetMessage("FACEID_TAB_SETTINGS_BUY"), "ICON" => "faceid_config", "TITLE" => GetMessage("FACEID_TAB_TITLE_SETTINGS_3"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if(strlen($_POST['Update'])>0 && check_bitrix_sessid())
{
	if (strlen($_POST['PUBLIC_URL']) > 0 && strlen($_POST['PUBLIC_URL']) < 12)
	{
		$errorMessage = GetMessage('FACEID_ACCOUNT_ERROR_PUBLIC');
	}
	else if(strlen($_POST['Update'])>0)
	{
		COption::SetOptionString("faceid", "portal_url", $_POST['PUBLIC_URL']);
		COption::SetOptionString("faceid", "debug", isset($_POST['DEBUG_MODE']));
		if (isset($_POST['DEBUG_MODE']))
		{
			COption::SetOptionString("faceid", "wait_response", isset($_POST['WAIT_RESPONSE']));
		}

		if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0)
		{
			LocalRedirect($_REQUEST["back_url_settings"]);
		}
		else
		{
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
		}
	}
}

// cloud stats
$isFaceidAvailable = false;
$faceidCloudResponse = \Bitrix\FaceId\FaceId::getUsageStats();

if (!empty($faceidCloudResponse['status']['exists']))
{
	$isFaceidAvailable = true;
	$faceidUsage = $faceidCloudResponse['result']['usage'];
	$faceidBalance = $faceidCloudResponse['status']['balance'];

	$expDate = \Bitrix\Main\Type\DateTime::createFromTimestamp($faceidCloudResponse['status']['balance_expire_ts']);
	$faceidBalanceExpire = FormatDate('j F Y', $expDate->getTimestamp());
}


?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?echo LANG?>">
<?php echo bitrix_sessid_post()?>
<?php
$tabControl->Begin();
$tabControl->BeginNextTab();
if ($errorMessage):?>
<tr>
	<td colspan="2" align="center"><b style="color:red"><?=$errorMessage?></b></td>
</tr>
<?endif;?>
<tr>
	<td width="40%"><?=GetMessage("FACEID_PUBLIC_URL")?>:</td>
	<td width="60%"><input type="text" name="PUBLIC_URL" value="<?=htmlspecialcharsbx(\Bitrix\FaceId\Http::getServerAddress())?>" /></td>
</tr>
<?if (COption::GetOptionInt("faceid", "debug")):?>
<tr>
	<td width="40%" valign="top"><?=GetMessage("FACEID_WAIT_RESPONSE")?>:</td>
	<td width="60%">
		<input type="checkbox" name="WAIT_RESPONSE" value="Y" <?=(COption::GetOptionInt("faceid", "wait_response")? 'checked':'')?> /><br>
		<?=GetMessage("FACEID_WAIT_RESPONSE_DESC")?>
	</td>
</tr>
<?endif;?>
<tr>
	<td width="40%"><?=GetMessage("FACEID_ACCOUNT_DEBUG")?>:</td>
	<td width="60%"><input type="checkbox" name="DEBUG_MODE" value="Y" <?=(COption::GetOptionInt("faceid", "debug")? 'checked':'')?> /></td>
</tr>

<? $tabControl->BeginNextTab() ?>

<tr>
	<td colspan="2" align="left">
		<div class="adm-table-content-wrap">
			<? if($isFaceidAvailable): ?>
				<? if(empty($faceidBalance)): ?>
					<div class="adm-table-content-title-main"><?=Loc::getMessage("FACEID_ADM_STATS_BALANCE_0")?></div>
				<? else: ?>
					<div class="adm-table-content-title-main"><?=Loc::getMessage("FACEID_ADM_STATS_BALANCE", array('#COUNT#' => $faceidBalance, '#DATE#' => $faceidBalanceExpire))?></div>
				<? endif ?>
				<div class="adm-table-content-title"><?=Loc::getMessage("FACEID_ADM_STATS_USAGE", array('#COUNT#' => array_sum($faceidUsage)))?></div>
				<div class="adm-table-content-container">
					<div class="adm-table-content-cell">
						<div class="adm-table-content-cell-item adm-table-content-cell-icon"><?=Loc::getMessage("FACEID_ADM_STATS_USAGE_1C")?></div>
						<div class="adm-table-content-cell-item adm-table-content-cell-icon">Bitrix24.Time</div>
						<div class="adm-table-content-cell-item adm-table-content-cell-icon"><?=Loc::getMessage("FACEID_ADM_STATS_USAGE_FTRACKER")?></div>
						<div class="adm-table-content-cell-item adm-table-content-cell-icon"><?=Loc::getMessage("FACEID_ADM_STATS_USAGE_VTRACKER")?></div>
					</div>
					<div class="adm-table-content-cell">
						<div class="adm-table-content-cell-item adm-table-content-text-bold"><?=(int)$faceidUsage['1c']?></div>
						<div class="adm-table-content-cell-item adm-table-content-text-bold"><?=(int)$faceidUsage['b24time']?></div>
						<div class="adm-table-content-cell-item adm-table-content-text-bold"><?=(int)$faceidUsage['ftracker']?></div>
						<div class="adm-table-content-cell-item adm-table-content-text-bold"><?=(int)$faceidUsage['vtracker']?></div>
					</div>
				</div>
				<a href="https://www.1c-bitrix.ru/buy/intranet.php#tab-face-link" class="adm-table-link"><?=Loc::getMessage("FACEID_ADM_STATS_BY_1000")?></a>
			<? else: ?>
				<div class="adm-table-content-title-main"><?=Loc::getMessage("FACEID_ADM_STATS_BALANCE_EMPTY")?></div>
			<? endif ?>
		</div>
	</td>
</tr>

<?$tabControl->Buttons();?>
<input type="submit" name="Update" value="<?echo GetMessage('MAIN_SAVE')?>">
<input type="reset" name="reset" value="<?echo GetMessage('MAIN_RESET')?>">
<?$tabControl->End();?>
</form>