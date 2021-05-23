<? if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$HTTP_ACCEPT_ENCODING = "";
$_SERVER["HTTP_ACCEPT_ENCODING"] = "";
include_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/captcha.php");

$cpt = new CCaptcha();
$cpt->setImageSize(240, 80);
$cpt->SetBorderColor(array(255, 255, 255));
$cpt->SetEllipseColor(array("#5daac9"));
$cpt->SetEllipsesNumber(6);
$cpt->textStartX = 45;
$cpt->textFontSize = 50;


if (isset($_GET["captcha_sid"]))
{
	if ($cpt->InitCode($_GET["captcha_sid"]))
	{
		$cpt->Output();
	}
	else
	{
		$cpt->OutputError();
	}
}
elseif (isset($_GET["captcha_code"]))
{
	$captchaPass = COption::GetOptionString("main", "captcha_password", "");

	if ($cpt->InitCodeCrypt($_GET["captcha_code"], $captchaPass))
	{
		$cpt->Output();
	}
	else
	{
		$cpt->OutputError();
	}
}
else
{
	$cpt->OutputError();
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
?>