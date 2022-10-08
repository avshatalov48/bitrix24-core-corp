<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use \Bitrix\Main\Localization\Loc;

/**
 *  @var array $arParams
 *  @var array $arResult
 *  @var CrmOrderConnectorInstagramEdit $component
 *  @var CBitrixComponentTemplate $this
 *  @var string $templateName
 *  @var string $templateFolder
 *  @var string $componentPath
 */

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-all-paddings no-background');

Loc::loadMessages(__FILE__);

$jsMessages = [];
foreach (array_keys(Loc::loadLanguageFile(__FILE__)) as $code)
{
	if (mb_strpos($code, '_WITH_ASTERISK') !== false)
	{
		continue;
	}

	$jsMessages[$code] = $component->getLocalizationMessage($code);
}

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'sidepanel',
	'ui.alerts',
	'ui.notification',
	'ui.buttons',
	'ui.hint',
	'ui.icons',
]);

$this->addExternalCss($templateFolder.'/style_settings.css');


$this->setViewTarget('pagetitle', 10);

if (!$arResult['ACTIVE_STATUS'] || !$arResult['STATUS'])
{
	?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<a class="ui-btn ui-btn-md ui-btn-light-border"
				href="<?=htmlspecialcharsbx($arParams['PATH_TO_CONNECTOR_INSTAGRAM_FEEDBACK'])?>">
			<?=$component->getLocalizationMessage('CRM_OIIE_IMPORT_FEEDBACK');?>
		</a>
	</div>
	<?
}

$this->endViewTarget();

if (!empty($arResult['SHOW_ACTUAL_PAGE']) && $arParams['IFRAME'])
{
	?>
	<script>
		if (window.top.BX.SidePanel)
		{
			var topSlider = window.top.BX.SidePanel.Instance.getTopSlider();
			var previousSlider = window.top.BX.SidePanel.Instance.getPreviousSlider();

			if (previousSlider && previousSlider.getUrl().indexOf('<?=$arParams['PATH_TO_CONNECTOR_INSTAGRAM_VIEW']?>') !== -1)
			{
				topSlider.close();
				topSlider = previousSlider;
			}

			addPreloader(topSlider.getWindow().document.body);
			topSlider.getWindow().document.location.href = '<?=CUtil::JSEscape($arParams['PATH_TO_CONNECTOR_INSTAGRAM_VIEW_FULL'])?>';
		}
	</script>
	<?
	die();
}
?>
<form action="<?=$arResult["URL"]["DELETE"]?>" method="post" id="form_delete_<?=$arResult["CONNECTOR"]?>">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>

<div class="crm-order-instagram-edit">
	<?
	$pagePath = \Bitrix\Main\Application::getDocumentRoot().$templateFolder.'/pages/';

	if (empty($arResult['PAGE']) && $arResult['ACTIVE_STATUS'] && $arResult['STATUS'])
	{
		include $pagePath.'page1.php';
	}
	else
	{
		// start case with clear connections
		if (empty($arResult['FORM']['USER']['INFO']))
		{
			include $pagePath.'page2.php';
		}
		else
		{
			$logo = isset($arResult['FORM']['USER']['INFO']['PROFILE_PICTURE_URL'])
				? ' style="background-image: url('.$arResult['FORM']['USER']['INFO']['PROFILE_PICTURE_URL'].');"'
				: '';
			?>
			<div class="crm-order-instagram-edit-block">
				<div class="crm-order-instagram-edit-header">
					<span class="crm-order-instagram-edit-header-logo"></span>
					<div class="crm-order-instagram-edit-content-inner">
						<div class="crm-order-instagram-edit-title">
							<?=$component->getLocalizationMessage('CRM_OIIE_FACEBOOK_CONNECTED')?>
						</div>
						<div class="crm-order-instagram-edit-disconnect">
							<? if (empty($arResult['FORM']['USER']['INFO']['URL'])): ?>
							<span class="crm-order-instagram-edit-user">
							<? else: ?>
							<a href="<?=$arResult['FORM']['USER']['INFO']['URL']?>"
									target="_blank"
									class="crm-order-instagram-edit-user">
							<? endif; ?>
								<span class="crm-order-instagram-edit-user-img"<?=$logo?>></span>
								<span class="crm-order-instagram-edit-user-name"><?=$arResult['FORM']['USER']['INFO']['NAME']?></span>
							<? if (empty($arResult['FORM']['USER']['INFO']['URL'])): ?>
							</span>
							<? else: ?>
							</a>
							<? endif; ?>
							<button class="ui-btn ui-btn-sm ui-btn-light-border"
									onclick="popupShowDisconnectImport(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)">
								<?=$component->getLocalizationMessage('CRM_OIIE_SETTINGS_DISABLE')?>
							</button>
						</div>
					</div>
				</div>
			</div>
			<? include $pagePath.'messages.php'; ?>
			<?
			// case user haven't got any groups
			if (empty($arResult['FORM']['PAGES']))
			{
				?>
				<div class="crm-order-instagram-edit-block">
					<div class="crm-order-instagram-edit-content-inner">
						<div class="crm-order-instagram-edit-section">
							<?=$component->getLocalizationMessage('CRM_OIIE_INSTAGRAM_CONNECTION_TITLE')?>
						</div>
						<div class="crm-order-instagram-edit-desc">
							<span class="crm-order-instagram-edit-decs-text">
								<?=$component->getLocalizationMessage('CRM_OIIE_THERE_IS_NO_PAGE_WHERE_THE_ADMINISTRATOR')?>
							</span>
						</div>
					</div>
					<div class="crm-order-instagram-edit-btn">
						<a href="https://www.facebook.com/pages/create/"
								class="ui-btn ui-btn-primary"
								target="_blank">
							<?=$component->getLocalizationMessage('CRM_OIIE_TO_CREATE_A_PAGE')?>
						</a>
						<button class="ui-btn ui-btn-light-border show-preloader-button"
								data-entity="create-store-link"
								style="display: none;">
							<?=$component->getLocalizationMessage('CRM_OIIE_CREATE_WITHOUT_CONNECTION')?>
						</button>
					</div>
				</div>
				<?
			}
			else
			{
				// case user haven't choose active group yet
				if (empty($arResult['FORM']['PAGE']))
				{
					include $pagePath.'page3.php';
				}
				else
				{
					include $pagePath.'page4.php';
				}
			}
		}
	}
	?>
	<script>
		BX.ready(function()
		{
			BX.message(<?=CUtil::PhpToJSObject($jsMessages)?>);

			window.top.BX.UI.Notification.Center.setStackDefaults(
				window.top.BX.UI.Notification.Position.TOP_RIGHT,
				{
					offsetX: 79,
					offsetY: 74
				}
			);

			<?
			if (!empty($arResult['NOTIFICATIONS']))
			{
				foreach ($arResult['NOTIFICATIONS'] as $notification)
				{
					?>
					window.top.BX.UI.Notification.Center.notify({
						content: '<?=$notification?>',
						category: 'InstagramStore::general',
						width: 'auto'
					});
					<?
				}

				$component::markSessionNotificationsRead();
			}
			?>
		});
	</script>
</div>

<?php if ($arResult['NEED_RESTRICTION_NOTE']): ?>
	<div class="crm-order-instagram-edit-restriction">
		<?=$component->getLocalizationMessage("CRM_OUIE_META_RESTRICTION")?>
	</div>
<?php endif ?>