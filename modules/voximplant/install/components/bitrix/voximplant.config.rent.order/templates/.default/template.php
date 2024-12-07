<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Localization\Loc;

CJSCore::RegisterExt('voximplant_config_rent_order', array(
	'js' => '/bitrix/components/bitrix/voximplant.config.rent.order/templates/.default/template.js',
	'lang' => '/bitrix/components/bitrix/voximplant.config.rent.order/templates/.default/lang/'.LANGUAGE_ID.'/template.php',
));

CJSCore::Init(["sidepanel", "voximplant.common", "voximplant_config_rent_order", "ui.buttons", "ui.alerts"]);

$showAddButton = $arResult['ORDER_STATUS']['OPERATOR_STATUS'] == CVoxImplantPhoneOrder::OPERATOR_STATUS_DECLINE;
$showForm = $arResult['ORDER_STATUS']['OPERATOR_STATUS'] == CVoxImplantPhoneOrder::OPERATOR_STATUS_NONE;
$showExtraButton = $arResult['ORDER_STATUS']['OPERATOR_STATUS'] == CVoxImplantPhoneOrder::OPERATOR_STATUS_ACCEPT;
$statusMessage = GetMessage('VI_CONFIG_RENT_ORDER_INFO_'.$arResult['ORDER_STATUS']['OPERATOR_STATUS']);
if (!$statusMessage)
{
	$statusMessage = GetMessage('VI_CONFIG_RENT_ORDER_INFO_NA');
}
?>

<div class="voximplant-container">
	<div class="voximplant-rect-box">
		<div class="voximplant-rect-title">
			<div class="voximplant-rect-title-name"><?= Loc::getMessage("VI_CONFIG_RENT_ORDER_ORDER") ?></div>
		</div>
		<div class="voximplant-rect-inner">
			<div class="voximplant-rect-content">
				<? if (!empty($arResult['LIST_RENT_NUMBERS'])): ?>
					<div class="tel-set-text-block" id="phone-confing-title">
						<strong><?= GetMessage('VI_CONFIG_RENT_PHONES') ?></strong></div>
					<div id="phone-confing-wrap">
						<? foreach ($arResult['LIST_RENT_NUMBERS'] as $id => $config): ?>
							<div class="tel-set-num-block" id="phone-confing-<?= $id ?>">
								<span class="tel-set-inp-ready-to-use"><?= $config['PHONE_NAME'] ?></span>
								<button class="ui-btn ui-btn-secondary" onclick="BX.VoxImplant.rentPhoneOrder.showConfig(<?=(int)$id?>)">
									<?= $config["PORTAL_MODE"] === CVoxImplantConfig::MODE_GROUP ? GetMessage('VI_CONFIG_RENT_GROUP_CONFIGURE') : GetMessage('VI_CONFIG_RENT_PHONE_CONFIGURE') ?>
								</button>
							</div>
						<? endforeach; ?>
					</div>
				<? endif; ?>

				<? if ($arResult['ORDER_STATUS']['OPERATOR_STATUS'] != CVoxImplantPhoneOrder::OPERATOR_STATUS_NONE): ?>
					<div class="tel-set-main-wrap tel-set-main-wrap-white"
						 style="<?= ($showAddButton ? 'margin-bottom: 32px;' : '') ?>">
						<div class="tel-set-inner-wrap">
							<div class="tel-number-order-info">
								<? if (mb_substr($arResult['ORDER_STATUS']['OPERATOR_STATUS'], 0, 7) == 'ACTIVE_'): ?>
									<div class="tel-order-form-desc"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_TITLE_2') ?></div>
									<div class="tel-order-form-info-box">
										<div class="tel-order-form-info"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_ACCOUNT') ?>
											<b><?= $arResult['ACCOUNT_NAME'] ?></b></div>
										<div class="tel-order-form-info"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_STATUS') ?>
											<span class="tel-order-form-status tel-order-form-status-<?= $arResult['ORDER_STATUS']['OPERATOR_STATUS'] ?>"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_'.$arResult['ORDER_STATUS']['OPERATOR_STATUS']) ?></span>
										</div>
										<div class="tel-order-form-info"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_DATE_MODIFY') ?>
											<b><?= $arResult['ORDER_STATUS']['DATE_MODIFY'] ?></b></div>
									</div>
								<? elseif ($arResult['ORDER_STATUS']['OPERATOR_STATUS'] != CVoxImplantPhoneOrder::OPERATOR_STATUS_ACCEPT): ?>
									<div class="tel-order-form-desc"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_TITLE_1') ?></div>
									<div class="tel-order-form-info-box">
										<div class="tel-order-form-info"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_ACCOUNT') ?>
											<b><?= $arResult['ACCOUNT_NAME'] ?></b></div>
										<div class="tel-order-form-info"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_STATUS') ?>
											<span class="tel-order-form-status tel-order-form-status-<?= $arResult['ORDER_STATUS']['OPERATOR_STATUS'] ?>"><?= $statusMessage ?></span>
										</div>
										<div class="tel-order-form-info"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_DATE') ?>
											<b><?= $arResult['ORDER_STATUS']['DATE_CREATE'] ?></b></div>
										<div class="tel-order-form-info"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_DATE_MODIFY') ?>
											<b><?= $arResult['ORDER_STATUS']['DATE_MODIFY'] ?></b></div>
									</div>
								<? else: ?>
									<div class="tel-order-form-desc"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_TITLE_2') ?></div>
									<div class="tel-order-form-info-box">
										<div class="tel-order-form-info"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_ACCOUNT') ?>
											<b><?= $arResult['ACCOUNT_NAME'] ?></b></div>
										<div class="tel-order-form-info"><?= GetMessage('VI_CONFIG_RENT_ORDER_INFO_OID') ?>
											<b><?= $arResult['ORDER_STATUS']['OPERATOR_CONTRACT'] ?></b></div>
									</div>
								<? endif; ?>
							</div>
						</div>
					</div>
				<? endif; ?>

				<? if ($showAddButton): ?>
					<div class="tel-set-inp-add-new">
						<span id="vi_rent_order" class="ui-btn ui-btn-primary">
							<?= GetMessage('VI_CONFIG_RENT_FORM_BTN') ?>
						</span>
					</div>
				<? endif; ?>

				<div id="vi_rent_order_div" class="tel-set-main-wrap tel-set-main-wrap-white"
					 style="margin-top: 15px; <?=$showForm ? "" : "display:none; "?>">
					<div class="ui-alert ui-alert-warning"><?= GetMessage('VI_CONFIG_RENT_FORM_TITLE_'.mb_strtoupper($arResult['ACCOUNT_LANG'])) ?></div>
					<div class="voximplant-control-row">
						<div class="voximplant-control-subtitle"><?= GetMessage('VI_CONFIG_RENT_ORDER_COMPANY_NAME') ?></div>
						<input id="vi_rent_order_name" type="text" class="voximplant-control-input">
					</div>
					<div class="voximplant-control-row">
						<div class="voximplant-control-subtitle"><?= GetMessage('VI_CONFIG_RENT_ORDER_COMPANY_CONTACT') ?></div>
						<input id="vi_rent_order_contact" type="text" class="voximplant-control-input">
					</div>
					<div class="voximplant-control-row">
						<div class="voximplant-control-subtitle">
							<? if ($arResult['ACCOUNT_LANG'] == 'ua'): ?>
								<?= GetMessage('VI_CONFIG_RENT_ORDER_COMPANY_REG_CODE') ?>
							<? elseif ($arResult['ACCOUNT_LANG'] == 'kz'): ?>
								<?= GetMessage('VI_CONFIG_RENT_ORDER_COMPANY_BIN') ?>
							<? endif ?></div>
						<input id="vi_rent_order_reg_code" type="text" class="voximplant-control-input">
					</div>
					<div class="voximplant-control-row">
						<div class="voximplant-control-subtitle"><?= GetMessage('VI_CONFIG_RENT_ORDER_COMPANY_PHONE') ?></div>
						<input id="vi_rent_order_phone" type="text" class="voximplant-control-input">
					</div>
					<div class="voximplant-control-row">
						<div class="voximplant-control-subtitle"><?= GetMessage('VI_CONFIG_RENT_ORDER_COMPANY_EMAIL') ?></div>
						<input id="vi_rent_order_email" type="text" class="voximplant-control-input">
					</div>
					<span id="tel-order-form-button" class="ui-btn ui-btn-primary"><?= GetMessage('VI_CONFIG_RENT_ORDER_BTN') ?></span>
					<div class="tel-order-form-button">
						<div id="tel-order-form-success" class="tel-order-form-confirm" style="display:none">
							<?= GetMessage('VI_CONFIG_RENT_ORDER_COMPLETE') ?>
						</div>
					</div>
				</div>

				<script>
					BX.ready(function ()
					{
						BX.VoxImplant.rentPhoneOrder.init();
					});
				</script>
				<? if ($showExtraButton): ?>
					<div class="tel-set-inp-add-new" style="margin-top: 15px;">
						<span id="vi_rent_order_extra" class="ui-btn ui-btn-primary"><?= GetMessage('VI_CONFIG_RENT_ORDER_EXTRA_TITLE') ?></span>
					</div>
					<div class="tel-set-main-wrap tel-set-main-wrap-white" id="vi_rent_order_extra_div"
						 style="display: none; margin-top: 15px;">
						<div class="tel-set-inner-wrap">
							<div class="tel-number-order-info" id="rent-extra-placeholder">
								<div class="tel-order-form-value">
									<select id="vi_rent_order_extra_type" class="tel-set-inp tel-set-item-select">
										<option value="TOLLFREE"><?= GetMessage('VI_CONFIG_RENT_ORDER_EXTRA_TOLLFREE') ?></option>
										<option value="LINE"><?= GetMessage('VI_CONFIG_RENT_ORDER_EXTRA_LINE') ?></option>
										<option value="NUMBER"><?= GetMessage('VI_CONFIG_RENT_ORDER_EXTRA_NUMBER') ?></option>
										<option value="CITY"><?= GetMessage('VI_CONFIG_RENT_ORDER_EXTRA_CITY') ?></option>
										<option value="CHANGE"><?= GetMessage('VI_CONFIG_RENT_ORDER_EXTRA_CHANGE') ?></option>
									</select>
								</div>

								<div class="tel-order-form-button">
									<span id="tel-order-extra-form-button" class="ui-btn ui-btn-primary">
										<?= GetMessage('VI_CONFIG_RENT_ORDER_BTN') ?>
									</span>
									<div id="tel-order-extra-form-success" class="tel-order-form-confirm"
										 style="display:none"><?= GetMessage('VI_CONFIG_RENT_ORDER_COMPLETE') ?></div>
								</div>
							</div>
						</div>
					</div>
					<script>
						BX.ready(function ()
						{
							BX.VoxImplant.rentPhoneOrderExtra.init();
						});
					</script>
				<? endif; ?>
			</div>
		</div>
	</div>
</div>

