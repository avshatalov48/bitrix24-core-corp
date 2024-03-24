<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Loader;

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global string $mid
 */

if (!$USER->isAdmin())
{
	$APPLICATION->authForm(Loc::getMessage("ACCESS_DENIED"));
}

Loader::requireModule('voximplant');

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
Loc::loadMessages(__FILE__);


$tabControl = new \CAdminTabControl(
	"tabControl", [
		[
			"DIV" => "edit1",
			"TAB" => Loc::getMessage("VI_TAB_SETTINGS"),
			"ICON" => "voximplant_config",
			"TITLE" => Loc::getMessage("VI_TAB_TITLE_SETTINGS_2"),
		],
	]
);

$ViAccount = new \CVoxImplantAccount();
$ViAccount->UpdateAccountInfo();

if ($ViAccount->GetError()->error)
{
	$accountName = '-';
	$accountBalance = '-';
	if ($ViAccount->GetError()->code == 'LICENCE_ERROR')
	{
		$errorMessage = Loc::getMessage('VI_ACCOUNT_ERROR_LICENSE');
	}
	else
	{
		$errorMessage = Loc::getMessage('VI_ACCOUNT_ERROR') . ";  " . $ViAccount->GetError()->msg ;
	}
}
else
{
	$accountName = $ViAccount->GetAccountName();
	$accountBalance = $ViAccount->GetAccountBalance().' '.$ViAccount->GetAccountCurrency();
	$errorMessage = '';
}

$publicUrl = Option::get('voximplant', 'portal_url', '');
$viHttp = new \CVoxImplantHttp();
$result = $viHttp->checkPortalVisibility();
if ($result->isSuccess())
{
	if ($result->getData()['isVisible'] === false)
	{
		$errorMessage = Loc::getMessage('VI_ACCOUNT_ERROR_PUBLIC_EXTENDED', [
			'#PUBLIC_URL#' => htmlspecialcharsbx($publicUrl),
		]);
	}
}
else
{
	$error = $result->getErrorCollection()[0];
	switch ($error->getCode())
	{
		case 'VI_PORTAL_URL_WITHOUT_PROTOCOL':
			$errorMessage = Loc::getMessage('VI_ACCOUNT_ERROR_PUBLIC_WITHOUT_PROTOCOL');
			break;
		case 'VI_PORTAL_URL_IS_LOCAL':
			$errorMessage = Loc::getMessage('VI_ACCOUNT_ERROR_PUBLIC_IS_LOCAL');
			break;
		default:
			$errorMessage = Loc::getMessage('VI_ACCOUNT_ERROR_PUBLIC_EXTENDED', [
				'#PUBLIC_URL#' => htmlspecialcharsbx($publicUrl),
			]);
			break;
	}
}

if (isset($_POST['Update']) && ($_POST['Update'] <> '') && check_bitrix_sessid())
{
	if (!empty($_POST['PUBLIC_URL']) && mb_strlen($_POST['PUBLIC_URL']) < 12)
	{
		$errorMessage = Loc::getMessage('VI_ACCOUNT_ERROR_PUBLIC');
	}
	else
	{
		Option::set("voximplant", "portal_url", $_POST['PUBLIC_URL']);
		Option::set("voximplant", "debug", isset($_POST['DEBUG_MODE']));

		$viHttp = new \CVoxImplantHttp();
		$viHttp->ClearConfigCache();

		if (!empty($_REQUEST["back_url_settings"] <> ''))
		{
			LocalRedirect($_REQUEST["back_url_settings"]);
		}
		else
		{
			LocalRedirect(
				$APPLICATION->GetCurPage()
				. "?mid=".urlencode($mid)
				. "&lang=".urlencode(LANGUAGE_ID)
				. "&back_url_settings=".urlencode($_REQUEST["back_url_settings"])
				. "&".$tabControl->ActiveTabParam()
			);
		}
	}
}
?>
<form method="post" action="<?= $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?= LANG?>">
<?= \bitrix_sessid_post()?>
<?php
$tabControl->Begin();
$tabControl->BeginNextTab();
if ($errorMessage):?>
<tr>
	<td colspan="2" align="center"><b style="color:red"><?=$errorMessage?></b></td>
</tr>
<?endif;?>
<tr>
	<td width="40%"><?=Loc::getMessage("VI_ACCOUNT_NAME")?>:</td>
	<td width="60%"><b><?=str_replace('.bitrixphone.com', '', $accountName)?></b></td>
</tr>
<tr>
	<td><?=Loc::getMessage("VI_ACCOUNT_BALANCE")?>:</td>
	<td><b><?=$accountBalance?></b></td>
</tr>
<tr>
	<td><?=Loc::getMessage("VI_ACCOUNT_URL")?>:</td>
	<td><input type="text" name="PUBLIC_URL"  value="<?=htmlspecialcharsbx(\CVoxImplantHttp::GetServerAddress())?>" style="width: 100%;" /></td>
</tr>
<tr>
	<td><?=Loc::getMessage("VI_ACCOUNT_DEBUG")?>:</td>
	<td><input type="checkbox" name="DEBUG_MODE" value="Y" <?=( (int)Option::get("voximplant", "debug") ? 'checked' : '')?> /></td>
</tr>
<?$tabControl->Buttons();?>
<input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE')?>">
<input type="reset" name="reset" value="<?= Loc::getMessage('MAIN_RESET')?>">
<?$tabControl->End();?>
</form>