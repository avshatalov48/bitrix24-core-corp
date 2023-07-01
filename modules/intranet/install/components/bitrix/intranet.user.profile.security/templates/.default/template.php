<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet\Site\Sections\TimemanSection;

$APPLICATION->SetTitle("");

\Bitrix\Main\UI\Extension::load([
	'intranet.security',
	'ui.notification',
	'ui.design-tokens',
	'ui.info-helper',
]);
$menuContainerId = 'intranet-user-profile-security-menu-'.$this->randString();
?>

<?if (\Bitrix\Main\Context::getCurrent()->getRequest()->get('IFRAME') !== 'Y'):?>
	<div class="intranet-user-profile-menu-sidebar">
		<?$APPLICATION->ShowViewContent("left-panel");?>
	</div>
<?endif?>

<?
$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrappermenu", "", array(
	"ID" => $menuContainerId,
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
		BX.Intranet.UserProfile.Security = new BX.Intranet.UserProfile.Security({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName() ?>',
			userId: '<?=CUtil::JSEscape($arParams["USER_ID"])?>',
			currentPage: '<?=CUtil::JSEscape($arResult["CURRENT_PAGE"])?>',
			menuContainer: document.querySelector('#<?=$menuContainerId?>'),
			contentContainer: document.querySelector("#intranet-user-profile-security-content")
		});
	});

	function openUserLoginHistory()
	{
		BX.SidePanel.Instance.open(
			'<?= TimemanSection::getUserLoginHistoryUrlById((int)$arParams["USER_ID"]) ?>',
			{
				width: 1100,
				events: {
					onClose: function(){
						var slider = BX.SidePanel.Instance.getSlider('/company/personal/user/<?= CUtil::JSEscape($arParams["USER_ID"]) ?>/common_security/?page=auth');

						if (slider !== null)
						{
							slider.reload();
						}
					},
					omCloseComplete: function(){
						BX.SidePanel.Instance.destroy('<?= TimemanSection::getUserLoginHistoryUrlById((int)$arParams["USER_ID"]) ?>');
					}
				}
			}
		);
	}
</script>
