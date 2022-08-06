<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;
\Bitrix\Main\UI\Extension::load(["ui.buttons", "ui.alerts", "ui.fonts.opensans"]);

/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
if ($arResult["OTP"]["IS_ENABLED"] == "N")
{
	return;
}
?>

<div class="intranet-user-otp-con-top">
<?
if (
	$USER->GetID() == $arParams["USER_ID"]
	|| $arResult["OTP"]["USER_HAS_EDIT_RIGHTS"]
)
{
?>
	<div class="intranet-user-otp-con-top-title"><?=GetMessage("INTRANET_USER_OTP_AUTH")?></div>
	<?
	if ($arResult["OTP"]["IS_ACTIVE"])
	{
	?>
		<div class="intranet-user-otp-con-top-status">
			<span class="intranet-user-otp-con-top-status-block intranet-user-otp-con-top-status-block-on">
				<?=GetMessage("INTRANET_USER_OTP_ACTIVE")?>
			</span>

			<?if ($arResult["OTP"]["USER_HAS_EDIT_RIGHTS"]|| !$arResult["OTP"]["IS_MANDATORY"]):?>
				<a class="intranet-user-otp-con-top-status-link" href="javascript:void(0)" data-role="intranet-otp-deactivate">
					<?=GetMessage("INTRANET_USER_OTP_DEACTIVATE")?>
				</a>
			<?endif?>
		</div>

		<?if ($USER->GetID() == $arParams["USER_ID"]):?>
			<a class="ui-btn ui-btn-light-border" href="javascript:void(0)" data-role="intranet-otp-change-phone"><?=GetMessage("INTRANET_USER_OTP_CHANGE_PHONE_1")?></a>
		<?endif?>
	<?
	}
	elseif (
		!$arResult["OTP"]["IS_ACTIVE"]
		&& $arResult["OTP"]["IS_MANDATORY"]
	)
	{
	?>
		<div class="intranet-user-otp-con-top-status">
			<span class="intranet-user-otp-con-top-status-block intranet-user-otp-con-top-status-block-off">
				<?=($arResult["OTP"]["IS_EXIST"]) ? GetMessage("INTRANET_USER_OTP_NOT_ACTIVE") : GetMessage("INTRANET_USER_OTP_NOT_EXIST")?>
			</span>
			<?
			if ($arResult["OTP"]["IS_EXIST"])
			{
			?>
				<a class="intranet-user-otp-con-top-status-link" href="javascript:void(0)" onclick="BX.Intranet.UserOtpConnected.activateUserOtp()"><?=GetMessage("INTRANET_USER_OTP_ACTIVATE")?></a>
			<?
			}
			else
			{
				if ($USER->GetID() == $arParams["USER_ID"]):?>
					<a class="intranet-user-otp-con-top-status-link" href="javascript:void(0)" data-role="intranet-otp-change-phone">
						<?=GetMessage("INTRANET_USER_OTP_SETUP")?>
					</a>
				<?else:?>
					<a class="intranet-user-otp-con-top-status-link" href="javascript:void(0)" data-role="intranet-otp-defer">
						<?=GetMessage("INTRANET_USER_OTP_PROROGUE")?>
					</a>
				<?endif;
			}
			?>
		</div>

		<?if ($arResult["OTP"]["IS_EXIST"] && $USER->GetID() == $arParams["USER_ID"]):?>
			<a class="ui-btn ui-btn-light-border" href="javascript:void(0)" data-role="intranet-otp-change-phone">
				<?=GetMessage("INTRANET_USER_OTP_CHANGE_PHONE_1")?>
			</a>
		<?endif;

		if ($arResult["OTP"]["NUM_LEFT_DAYS"])
		{
		?>
			<div style="margin-left: 10px">
				<div class="ui-alert ui-alert-xs ui-alert-warning">
					<span class="ui-alert-message">
						<?=Loc::getMessage("INTRANET_USER_OTP_LEFT_DAYS", array(
							"#NUM#" => "<strong>".$arResult["OTP"]["NUM_LEFT_DAYS"]."</strong>"
						))?>
					</span>
				</div>
			</div>
		<?
		}
	}
	elseif (
		!$arResult["OTP"]["IS_ACTIVE"]
		&& $arResult["OTP"]["IS_EXIST"]
		&& !$arResult["OTP"]["IS_MANDATORY"]
	)
	{
		?>
		<div class="intranet-user-otp-con-top-status">
			<span class="intranet-user-otp-con-top-status-block intranet-user-otp-con-top-status-block-off">
				<?=GetMessage("INTRANET_USER_OTP_NOT_ACTIVE")?>
			</span>

			<a class="intranet-user-otp-con-top-status-link" href="javascript:void(0)" onclick="BX.Intranet.UserOtpConnected.activateUserOtp()"><?=GetMessage("INTRANET_USER_OTP_ACTIVATE")?></a>
		</div>
		<?
		if ($USER->GetID() == $arParams["USER_ID"])
		{
		?>
			<a class="ui-btn ui-btn-light-border" href="javascript:void(0)" data-role="intranet-otp-change-phone">
				<?=GetMessage("INTRANET_USER_OTP_CHANGE_PHONE_1")?>
			</a>
		<?
		}

		if ($arResult["OTP"]["NUM_LEFT_DAYS"])
		{
		?>
			<div style="width: 100%; margin-top: 10px;">
				<div class="ui-alert ui-alert-xs ui-alert-warning ui-alert-text-center">
					<span class="ui-alert-message">
						<?=Loc::getMessage("INTRANET_USER_OTP_LEFT_DAYS", array(
							"#NUM#" => "<strong>".$arResult["OTP"]["NUM_LEFT_DAYS"]."</strong>"
						))?>
					</span>
				</div>
			</div>
		<?
		}
	}
	elseif (
		!$arResult["OTP"]["IS_ACTIVE"]
		&& !$arResult["OTP"]["IS_EXIST"]
	)
	{
	?>
		<div class="intranet-user-otp-con-top-status">
			<span class="intranet-user-otp-con-top-status-block intranet-user-otp-con-top-status-block-off">
				<?=GetMessage("INTRANET_USER_OTP_NOT_EXIST")?>
			</span>
		</div>
		<?
		if ($USER->GetID() == $arParams["USER_ID"])
		{
			?>
			<a class="ui-btn ui-btn-light-border" href="javascript:void(0)" data-role="intranet-otp-change-phone">
				<?=GetMessage("INTRANET_USER_OTP_CONNECT")?>
			</a>
			<?
		}
		?>
	<?
	}
	?>

	<!-- codes --><?
	if (
		$USER->GetID() == $arParams["USER_ID"]
		&& $arResult["OTP"]["IS_ACTIVE"]
		&& $arResult["OTP"]["ARE_RECOVERY_CODES_ENABLED"]
	)
	{
	?>
		<a href="javascript:void(0)" class="ui-btn ui-btn-link" data-role="intranet-recovery-codes"><?=GetMessage("INTRANET_USER_OTP_CODES")?></a>
	<?
	}
}
?>
</div>

<?
$days = array();
for($i=1; $i<=10; $i++)
{
	$days[$i] = FormatDate("ddiff", time()-60*60*24*$i);
}
$days[0] = GetMessage("INTRANET_USER_OTP_NO_DAYS");

$arJSParams = array(
	"signedParameters" => $this->getComponent()->getSignedParameters(),
	"componentName" => $this->getComponent()->getName(),
	"otpDays" => $days,
	"showOtpPopup" => (isset($_GET["otp"]) && $_GET["otp"] == "Y") ? "Y" : "N",
	//"otpRecoveryCodes" => $arResult["IS_OTP_RECOVERY_CODES_ENABLE"] ? "Y" : "N",
);
?>
<script>
	BX.ready(function () {
		BX.Intranet.UserOtpConnected.init(<?=CUtil::PhpToJSObject($arJSParams)?>);
	})
</script>

