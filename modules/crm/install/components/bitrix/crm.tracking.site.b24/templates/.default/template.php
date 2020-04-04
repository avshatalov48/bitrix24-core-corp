<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CAllMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");
Extension::load([
	'ui.icons', 'ui.switcher',
	'sidepanel', 'crm.tracking.connector'
]);

$name = htmlspecialcharsbx($arResult['ROW']['NAME']);
$iconClass = htmlspecialcharsbx($arResult['ROW']['ICON_CLASS']);

$containerId = 'crm-tracking-site-b24';
?>

<div id="<?=htmlspecialcharsbx($containerId)?>" class="crm-analytics-source-block-wrap">

	<?
	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		\Bitrix\Crm\Tracking\Provider::getFeedbackParameters()
	);
	?>

	<form method="post">
		<?=bitrix_sessid_post();?>

		<div class="crm-analytics-source-block crm-analytics-source-block-desc">
			<span class="crm-analytics-source-icon <?=$iconClass?>">
				<i></i>
			</span>

			<div class="crm-analytics-source-section">
				<div class="crm-analytics-source-header">
					<?=Loc::getMessage('CRM_TRACKING_SITE_B24_AUTO_CONNECTED', ['%name%' => $name])?>
				</div>
				<div class="crm-analytics-source-desc">
					<span class="crm-analytics-source-desc-text">
						<?=Loc::getMessage('CRM_TRACKING_SITE_B24_AUTO_DESC', ['%name%' => $name])?>
					</span>
				</div>
			</div>
		</div>

		<div class="crm-analytics-source-block">
			<?if (empty($arResult['SITES'])):?>
				<div class="crm-analytics-source-empty">
					<div class="crm-analytics-source-empty-img"></div>
					<span class="crm-analytics-source-empty-text">
						<?=Loc::getMessage('CRM_TRACKING_SITE_B24_EMPTY_' . ($arParams['IS_SHOP'] ? 'STORES' : 'SITES'))?>
					</span>
				</div>
			<?else:?>

				<div class="crm-analytics-source-subject">
					<span class="crm-analytics-source-subject-text">
						<?=Loc::getMessage('CRM_TRACKING_SITE_B24_REPLACEMENT')?>
					</span>
				</div>

				<div class="crm-analytics-source-settings">
				<?foreach ($arResult['SITES'] as $site):
					$id = htmlspecialcharsbx('b24-site-' . $site['ID']);
				?>

					<div class="crm-analytics-source-settings-block">
						<div class="crm-analytics-source-settings-name-block">
							<span class="crm-analytics-source-settings-name"><?=htmlspecialcharsbx($site['TITLE'])?></span>
							<span class="crm-analytics-source-settings-detail"><?=htmlspecialcharsbx($site['DOMAIN_NAME'])?></span>
						</div>
						<div class="crm-analytics-source-settings-control">
							<button type="button" data-site-id="<?=htmlspecialcharsbx($site['ID'])?>"
								class="ui-btn ui-btn-sm ui-btn-link crm-analytics-source-settings-control-btn">
								<?=Loc::getMessage('CRM_TRACKING_SITE_B24_VIEW')?>
							</button>
							<span data-switcher="<?=htmlspecialcharsbx(Json::encode([
								'id' => $id,
								'checked' => !$site['EXCLUDED'],
								'inputName' => "SITE[{$site['ID']}]",
							]))?>" class="ui-switcher"></span>
						</div>
					</div>
				<?endforeach;?>
				</div>

			<?endif;?>
		</div>

		<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'BUTTONS' => empty($arResult['SITES']) ?
				['close' => $arParams['PATH_TO_LIST']]
				:
				[
					'save',
					'cancel' => $arParams['PATH_TO_LIST']
				]
		]);?>

	</form>

	<script type="text/javascript">
		BX.ready(function () {
			BX.Crm.Tracking.B24Site.init(<?=Json::encode([
				'containerId' => $containerId,
				'sources' => $arResult['SOURCES'],
				'sites' => $arResult['SITES'],
				'mess' => []
			])?>);
		});
	</script>
</div>
