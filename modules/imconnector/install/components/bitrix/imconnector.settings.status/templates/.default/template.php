<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

Loc::loadMessages(__FILE__);
\CJSCore::Init(['loader', 'ui.fonts.opensans']);
?>

<?
if (!empty($arResult))
{
	?>
	<script>
		var params = {
			signedParameters: '<?= $this->getComponent()->getSignedParameters() ?>',
			componentName: '<?= $this->getComponent()->getName() ?>'
		}
	</script>
	<div class="imconnector-contact-block" id="bx-imconnector-status-wrap">
		<div class="imconnector-contact-wrap" id="imconnector-contact-wrap">
			<div class="imconnector-contact-list" id="imconnector-contact-list">
				<?
				foreach ($arResult as $connector)
				{
					?>
					<div class="imconnector-contact-item<?=($connector["STATUS"] ? " imconnector-contact-item-selected "  . $connector["COLOR_CLASS"] : "")?>"
						 <? if($arParams['LINK_ON']) { ?>
							 onclick="BX.SidePanel.Instance.open('<?=$connector['LINK']?>', {
							 		width: 700,
								 	events: {
							 			onClose: function() {
											 BX.ImConnectorSettingStatus.reload(params)
										 }
							 	}
							 })"
						 <? } ?>>
						<div class="imconnector-contact-logo-container">
							<span class="imconnector-contact-logo <?=$connector["LOGO_CLASS"]?>"><i></i></span>
						</div>
						<div class="imconnector-contact-name">
							<span class="imconnector-contact-name-text">
								<?=$connector["NAME"]?>
							</span>
						</div>
					</div>
					<?
				}
				?>
			</div>
		</div>
	</div>
	<?
}
?>