<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
?>

<div class="crm-tracking-call-tester">
	<div class="crm-tracking-call-tester-title-wrap">
		<div class="">
			<div class="crm-tracking-call-tester-title">
				<span data-role="crm/tracking/pool/tester/title" class=""></span>
			</div>
		</div>
	</div>
	<div data-role="crm/tracking/pool/tester/status" class="crm-tracking-call-tester-section">

		<div class="crm-tracking-call-tester-section-success">
			<div class="crm-tracking-call-tester-header">
				<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TITLE_SUPPORT')?>
				<span class="crm-tracking-call-tester-status-icon crm-tracking-call-tester-status-icon-green"></span>
			</div>
			<div class="crm-tracking-call-tester-desc">
				<span class="crm-tracking-call-tester-desc-text">
					<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TITLE_SUPPORT_DESC')?>
				</span>
			</div>
		</div>

		<div class="crm-tracking-call-tester-section-unknown">
			<div data-role="crm/tracking/pool/tester/status" class="crm-tracking-call-tester-header">
				<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TITLE_UNKNOWN')?>
				<span class="crm-tracking-call-tester-status-icon crm-tracking-call-tester-status-icon-gray"></span>
			</div>
			<div class="crm-tracking-call-tester-desc">
				<span class="crm-tracking-call-tester-desc-text">
					<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TITLE_UNKNOWN_DESC')?>
				</span>
			</div>
		</div>

		<div>
			<div class="crm-tracking-call-tester-desc" style="margin: 0;">
				<span class="crm-tracking-call-tester-desc-text">
					<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_DATE')?>:
					<span data-role="crm/tracking/pool/tester/date"></span>
				</span>
			</div>
		</div>
	</div>

	<br><br>

	<div class="crm-tracking-call-tester-section">
		<div class="crm-tracking-call-tester-header">
			<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_NUMBER')?>
		</div>
		<div class="crm-tracking-call-tester-desc">
			<span class="crm-tracking-call-tester-desc-text">
				<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_NUMBER_DESC')?>
			</span>
		</div>
		<div style="text-align: center;">
			<div class="ui-ctl ui-ctl-textbox ui-ctl-block ui-ctl-w100"
				data-role="crm/tracking/pool/tester/from"
			>
				<input type="text"
					class="ui-ctl-element"
					placeholder="<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_NUMBER_INPUT')?>"
					data-role="crm/tracking/pool/tester/from/input"
				>
			</div>
			<br>
			<div data-role="crm/tracking/pool/tester/btn" class="ui-btn ui-btn-primary"
				data-lang-start="<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_START')?>"
				data-lang-stop="<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_STOP')?>"
			>
				<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_START')?>
			</div>
		</div>
		<br><br>
		<div style="display: none;"
			class="crm-tracking-call-tester-desc"
			data-role="crm/tracking/pool/tester/wait"
		>
			<div style="text-align: center;" class="crm-tracking-call-tester-desc-text">
				<?=Loc::getMessage(
					'CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_INST_CALL',
					['%number%' => '<span data-role="crm/tracking/pool/tester/wait/number"></span>']
				)?>
				<br>
				<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_INST_END')?>
				<br>
				<br>
				<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_TEST_INST_WAIT')?>...
			</div>
			<div class=""
				data-role="crm/tracking/pool/tester/wait/loader"
				style="height: 100px; position: relative;"
			></div>
		</div>

		<div style="text-align: center;" class="crm-tracking-call-tester-result">
			<div class="crm-tracking-call-tester-result-success"
				data-role="crm/tracking/pool/tester/result/success"
				style="display: none;"
			>
				<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_RESULT_SUCCESS')?>
			</div>
			<div class="crm-tracking-call-tester-result-unknown"
				data-role="crm/tracking/pool/tester/result/unknown"
				style="display: none;"
			>
				<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_RESULT_UNKNOWN')?>
			</div>
			<div class="crm-tracking-call-tester-result-unknown"
				data-role="crm/tracking/pool/tester/result/fail"
				style="display: none;"
			>
				<?=Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TESTER_RESULT_FAIL')?>
			</div>
		</div>
	</div>
</div>