<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
use Bitrix\Main\Localization\Loc;

$APPLICATION->SetTitle("");

\Bitrix\Main\UI\Extension::load('intranet.security');
?>

<?if (\Bitrix\Main\Context::getCurrent()->getRequest()->get('IFRAME') !== 'Y'):?>
	<div class="intranet-user-profile-menu-sidebar">
		<?$APPLICATION->ShowViewContent("left-panel");?>
	</div>
<?endif?>

<?
$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrappermenu", "", array(
	"ITEMS" => $arResult["MENU_ITEMS"],
	"TITLE" => Loc::getMessage("INTRANET_USER_PROFILE_SECURITY_MENU_TITLE")
));
?>

<div id="intranet-user-profile-security-content"
	 class="intranet-user-profile-security-content
	 <?if(\Bitrix\Main\Context::getCurrent()->getRequest()->get('IFRAME') !== 'Y'):
		 echo "intranet-user-profile-security-container";
	 endif?>"
>
</div>

<script>
	BX.ready(function () {
		BX.Intranet.UserProfile.Security.init({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName() ?>',
			userId: '<?=CUtil::JSEscape($arParams["USER_ID"])?>',
			currentPage: '<?=CUtil::JSEscape($arResult["CURRENT_PAGE"])?>'
		});
	});
</script>
