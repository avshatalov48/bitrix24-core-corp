<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global \CAllMain $APPLICATION */
/** @global \CAllDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$containerId = 'crm-tracking-entity-details-view';

$hasTrace = !empty($arResult['DATA']['ROW']);
$hasPages = $arResult['DATA']['SITE'] && $arResult['DATA']['PAGES'];
?>
<div id="<?=htmlspecialcharsbx($containerId)?>">
	<?if ($arResult['DATA']['IS_EMPTY']):?>
		<span style="opacity: 0.5;">
			<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_ORGANIC')?>
		</span>
	<?else:?>
	<div id="crm-tracking-entity-details" class="crm-tracking-entity-details">
		<div class="crm-tracking-entity-details-path-block-inner">
			<div>
				<?if (false && $arResult['DATA']['SOURCE']):?>
					<div class="crm-tracking-entity-details-path-info">
						<span class="crm-tracking-entity-details-path-icon <?=htmlspecialcharsbx($arResult['DATA']['SOURCE']['ICON_CLASS'])?>">
							<i></i>
						</span>
						<span class="crm-tracking-entity-details-path-name">
							<?=htmlspecialcharsbx($arResult['DATA']['SOURCE']['NAME'])?>
						</span>
					</div>
				<?endif;?>

				<?
				$APPLICATION->includeComponent(
					'bitrix:crm.tracking.entity.path',
					'',
					[
						'ENTITY_TYPE_ID' => $arParams['ENTITY_TYPE_ID'],
						'ENTITY_ID' => $arParams['ENTITY_ID'],
						'SKIP_SOURCE' => false
					]
				);
				?>
			</div>

			<?if ($arResult['DATA']['SITE'] && $arResult['DATA']['PAGES']):?>
				<div class="crm-tracking-entity-details-path-header"></div>
				<div>
					<div class="crm-tracking-entity-details-path-site">
						<span class="crm-tracking-entity-details-path-name">
							<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_PAGES')?>:
						</span>
						<a target="_blank"
							href="http://<?=htmlspecialcharsbx($arResult['DATA']['SITE']['DOMAIN'])?>"
							class="crm-tracking-entity-details-path-site-link"
						>
							<?=htmlspecialcharsbx($arResult['DATA']['SITE']['DOMAIN'])?>
						</a>
						<!--
						<span id="crm-tracking-entity-details-toggler"
							class="crm-tracking-entity-details-path-tip"
						></span>
						-->
					</div>

					<div title="<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_DEVICE_' . ($arResult['DATA']['IS_MOBILE'] ? 'MOBILE' : 'DESKTOP'))?>"
						class="crm-tracking-entity-details-device crm-tracking-entity-details-device-<?=($arResult['DATA']['IS_MOBILE'] ? 'mobile' : 'desktop')?>"
					></div>

					<div id="crm-tracking-entity-details-pages" class="crm-tracking-entity-details-popup-list">
						<br>
						<?foreach ($arResult['DATA']['PAGES'] as $page):?>
							<div class="crm-tracking-entity-details-popup-item">
								<span class="crm-tracking-entity-details-popup-time">
									<?=htmlspecialcharsbx($page['DATE_INSERT'])?>
								</span>
								<a target="_blank" href="<?=\CUtil::JSEscape(htmlspecialcharsbx($page['URL']))?>"
									class="crm-tracking-entity-details-popup-decs"
								>
									<?=htmlspecialcharsbx($page['TITLE'] ?: Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_NO_TITLE'))?>
								</a>
							</div>
						<?endforeach;?>
					</div>
				</div>
			<?endif;?>

		</div>
	</div>
	<?endif;?>
	<script>
		BX.ready(function () {
			var toggler = BX('crm-tracking-entity-details-toggler');
			var pages = BX('crm-tracking-entity-details-pages');
			BX.bind(toggler, 'click', function (e) {
				e.stopPropagation();
				BX.toggleClass(pages, 'crm-tracking-entity-details-popup-list-hidden');
			});
		})
	</script>
</div>
<?