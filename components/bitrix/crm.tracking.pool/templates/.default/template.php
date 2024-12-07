<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");

Extension::load([
	"ui.buttons",
	"ui.buttons.icons",
	"ui.icons",
	"popup",
	"ui.forms",
	"loader",
	"ui.sidepanel-content",
	"ui.info-helper",
	"ui.fonts.opensans",
]);

$containerId = 'crm-tracking-channel-pool';
?>

<div id="<?=htmlspecialcharsbx($containerId)?>" class="crm-tracking-channel-pool-wrap">

	<?
	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		\Bitrix\Crm\Tracking\Provider::getFeedbackParameters()
	);
	?>

	<form method="post">
		<?=bitrix_sessid_post();?>
		<div class="ui-slider-section">
			<div class="ui-slider-heading-3">
				<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_HEAD_' . $arParams['TYPE_NAME'])?>
			</div>
			<p class="ui-slider-paragraph-2"><?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_DESC_' . $arParams['TYPE_NAME'])?></p>
			<div class="crm-tracking-channel-pool-inner">
				<div class="crm-tracking-channel-pool-inner-title">
					<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_ITEMS_' . $arParams['TYPE_NAME'])?>:
				</div>
				<div class="crm-tracking-channel-pool-inner-field">
					<?
					$GLOBALS['APPLICATION']->includeComponent(
						'bitrix:ui.tile.selector',
						'',
						array(
							'ID' => 'available-list',
							'MULTIPLE' => true,
							'LIST' => $arResult['POOL_TILES'],
							'CAN_REMOVE_TILES' => true,
							'SHOW_BUTTON_SELECT' => false,
							'SHOW_BUTTON_ADD' => false,
						)
					);
					?>
				</div>
				<div class="crm-tracking-channel-pool-inner-btn">
					<span id="item-allocate" class="ui-btn ui-btn-light-border">
						<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_BTN_ALLOCATE')?>
					</span>
					<span id="item-add" class="crm-tracking-channel-pool-inner-add">
						<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_BTN_ADD_' . $arParams['TYPE_NAME'])?>
					</span>
				</div>
			</div>

			<?/*
			<div class="crm-tracking-channel-format">
				<div class="crm-tracking-channel-format-inner">
					<span class="crm-tracking-channel-format-title">
						<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_FORMAT_' . $arParams['TYPE_NAME'])?>
					</span>
					<input class="crm-tracking-channel-format-select" type="text">
				</div>
			</div>
			*/?>

			<div class="crm-tracking-channel-pool-section-list">
				<?foreach ($arResult['SOURCES'] as $source):?>
				<div class="crm-tracking-channel-pool-section">
					<div class="crm-tracking-channel-pool-section-title">
						<span class="crm-tracking-channel-pool-section-title-text">
							<span class="crm-tracking-channel-pool-section-title-icon <?=htmlspecialcharsbx($source['ICON_CLASS'])?>">
								<i style="<?=htmlspecialcharsbx($source['ICON_COLOR'] ? 'background-color: ' . $source['ICON_COLOR'] . ';' : '')?>"></i>
							</span>
							<?=htmlspecialcharsbx($source['NAME'])?>
						</span>
					</div>
					<div class="crm-tracking-channel-pool-section-number">
						<?
						$GLOBALS['APPLICATION']->includeComponent(
							'bitrix:ui.tile.selector',
							'',
							array(
								'ID' => 'phone-list-' . $source['ID'],
								'INPUT_NAME'=> "SOURCE[{$source['ID']}]",
								'MULTIPLE' => true,
								'LIST' => $source['TILES'],
								'SHOW_BUTTON_SELECT' => true,
								'SHOW_BUTTON_ADD' => false,
							)
						);
						?>
					</div>
				</div>
				<?endforeach;?>

				<div class="crm-tracking-channel-pool-section">
					<div class="crm-tracking-channel-pool-section-title">
						<span class="crm-tracking-channel-pool-section-title-text">
							<span class="crm-tracking-channel-pool-section-title-icon <?=htmlspecialcharsbx($arResult['ORGANIC_SOURCE']['ICON_CLASS'])?>">
								<i style="<?=htmlspecialcharsbx($arResult['ORGANIC_SOURCE']['ICON_COLOR'] ? 'background-color: ' . $arResult['ORGANIC_SOURCE']['ICON_COLOR'] . ';' : '')?>"></i>
							</span>
							<?=htmlspecialcharsbx($arResult['ORGANIC_SOURCE']['NAME'])?>
						</span>
					</div>
					<div class="crm-tracking-channel-pool-section-number">
						<?
						$GLOBALS['APPLICATION']->includeComponent(
							'bitrix:ui.tile.selector',
							'',
							array(
								'ID' => 'organic-list',
								'MULTIPLE' => true,
								'LIST' => [],
								'SHOW_BUTTON_SELECT' => false,
								'SHOW_BUTTON_ADD' => false,
							)
						);
						?>
					</div>
				</div>

			</div>
		</div>

		<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'BUTTONS' => ($arResult['FEATURE_CODE'] ? [] : ['save']) + ['cancel' => $arParams['PATH_TO_LIST']]
		]);?>
	</form>

	<div style="display: none;">
		<div id="crm-tracking-dialog-add">
			<div class="ui-ctl ui-ctl-textbox ui-ctl-inline ui-ctl-w100">
				<input id="item-add-name"
					type="text" class="ui-ctl-element"
					placeholder="<?=htmlspecialcharsbx(Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_ITEM_DEMO_' . $arParams['TYPE_NAME']))?>"
				>
			</div>
			<div class="crm-tracking-phone-popup-buttons">
				<a id="item-add-name-btn-add" class="ui-btn ui-btn-primary ui-btn-sm">
					<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_BTN_ADD')?>
				</a>
				<a id="item-add-name-btn-close" class="ui-btn ui-btn-link ui-btn-sm">
					<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_BTN_CANCEL')?>
				</a>
			</div>
		</div>

		<div id="crm-tracking-pool-tester">
			<?include __DIR__ . '/tester.php'?>
		</div>
	</div>

	<script>
		BX.ready(function () {
			BX.Crm.Tracking.Channel.Pool.init(<?=Json::encode([
				'containerId' => $containerId,
				'typeId' => $arParams['TYPE_ID'],
				'componentName' => $this->getComponent()->getName(),
				'signedParameters' => $this->getComponent()->getSignedParameters(),
				'featureCode' => $arResult['FEATURE_CODE'],
				'mess' => [
					'searcherTitle' => Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_AV_ITEMS_' . $arParams['TYPE_NAME']),
					'searcherCategory' => Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_AV'),
					'dialogDeleteConfirm' => Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_DELETE_CONFIRM'),
					'phoneStatusSuccess' => Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_PHONE_STATUS_SUCCESS'),
					'phoneStatusUnknown' => Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_PHONE_STATUS_UNKNOWN'),
					'noPhoneTesting' => Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_DATE_NO'),
				]
			])?>);
		});
	</script>
</div>

