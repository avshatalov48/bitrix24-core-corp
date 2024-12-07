<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
?>

<div class="crm-analytics-utm-editor">
	<form class="crm-analytics-utm-editor-form">
		<div class="crm-analytics-utm-editor-block">
			<div class="crm-analytics-utm-editor-title-block">
				<input type="text" class="crm-analytics-utm-editor-title-input" placeholder="<?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_NAME") ?>">
			</div>
			<div class="crm-analytics-utm-editor-field">
				<span class="crm-analytics-utm-editor-field-title"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_SITE_NAME") ?></span>
				<input class="crm-analytics-utm-editor-field-input" type="text" placeholder="https://yoursite.ru">
				<div class="crm-analytics-utm-editor-field-decs"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_SITE_NAME_DESC") ?></div>
			</div>
		</div>
		<div class="crm-analytics-utm-editor-block">
			<div class="crm-analytics-utm-editor-title"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_OBLIGATORY_FIELDS") ?></div>
			<div class="crm-analytics-utm-editor-field">
				<span class="crm-analytics-utm-editor-field-title"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_SOURCE") ?></span>
				<div class="crm-analytics-utm-editor-field-input-block">
					<div class="crm-analytics-utm-editor-field-input-decs">utm_source</div>
					<input class="crm-analytics-utm-editor-field-input" type="text" placeholder="google">
				</div>
				<div class="crm-analytics-utm-editor-field-decs"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_SOURCE_DESC") ?></div>
			</div>
			<div class="crm-analytics-utm-editor-field">
				<span class="crm-analytics-utm-editor-field-title"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_MEDIUM") ?></span>
				<div class="crm-analytics-utm-editor-field-input-block">
					<div class="crm-analytics-utm-editor-field-input-decs">utm_medium</div>
					<input class="crm-analytics-utm-editor-field-input" type="text" placeholder="cpc">
				</div>
				<div class="crm-analytics-utm-editor-field-decs"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_MEDIUM_DESC") ?></div>
			</div>
			<div class="crm-analytics-utm-editor-field">
				<span class="crm-analytics-utm-editor-field-title"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_CAMPAIGN") ?></span>
				<div class="crm-analytics-utm-editor-field-input-block">
					<div class="crm-analytics-utm-editor-field-input-decs">utm_campaign</div>
					<input class="crm-analytics-utm-editor-field-input" type="text" placeholder="net|{network}|cid|{campaignid}">
				</div>
				<div class="crm-analytics-utm-editor-field-decs"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_CAMPAIGN_DESC") ?></div>
			</div>
		</div>
		<div class="crm-analytics-utm-editor-addition crm-analytics-utm-editor-addition-closed" id="crm-analytics-utm-editor-addition">
			<div class="crm-analytics-utm-editor-addition-inner">
				<div class="crm-analytics-utm-editor-addition-title" id="crm-analytics-utm-editor-addition-title">
					<?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_ADDITIONAL_GOOGLE_MSGVER_1") ?>
				</div>
			</div>
			<div class="crm-analytics-utm-editor-addition-list">
				<div class="crm-analytics-utm-editor-addition-list-inner">
					<div class="crm-analytics-utm-editor-addition-item">
						<div class="crm-analytics-utm-editor-addition-item-inner">
							<input type="checkbox" class="crm-analytics-utm-editor-addition-item-checkbox" id="placement">
							<label for="placement" class="crm-analytics-utm-editor-addition-item-label">
								<span class="crm-analytics-utm-editor-addition-item-name"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_PLACEMENT") ?></span>
								<span class="crm-analytics-utm-editor-addition-item-code">placement={placement}</span>
							</label>
						</div>
						<div class="crm-analytics-utm-editor-addition-item-decs"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_PLACEMENT_DESC_GOOGLE") ?></div>
					</div>
					<div class="crm-analytics-utm-editor-addition-item">
						<div class="crm-analytics-utm-editor-addition-item-inner">
							<input type="checkbox" class="crm-analytics-utm-editor-addition-item-checkbox" id="position">
							<label for="position" class="crm-analytics-utm-editor-addition-item-label">
								<span class="crm-analytics-utm-editor-addition-item-name"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_POSITION") ?></span>
								<span class="crm-analytics-utm-editor-addition-item-code">adposition={adposition}</span>
							</label>
						</div>
						<div class="crm-analytics-utm-editor-addition-item-decs"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_POSITION_DESC_GOOGLE") ?></div>
					</div>
					<div class="crm-analytics-utm-editor-addition-item">
						<div class="crm-analytics-utm-editor-addition-item-inner">
							<input type="checkbox" class="crm-analytics-utm-editor-addition-item-checkbox" id="matchtype">
							<label for="matchtype" class="crm-analytics-utm-editor-addition-item-label">
								<span class="crm-analytics-utm-editor-addition-item-name"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_MATCHTYPE") ?></span>
								<span class="crm-analytics-utm-editor-addition-item-code">matchtype={matchtype}</span>
							</label>
						</div>
						<div class="crm-analytics-utm-editor-addition-item-decs"><?= Loc::getMessage("CRM_TRACKING_SOURCE_UTM_MATCHTYPE_DESC_GOOGLE") ?></div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>

<script>
	BX.ready(function () {
		var title = BX('crm-analytics-utm-editor-addition-title');
		var block = BX('crm-analytics-utm-editor-addition');
		title.addEventListener('click', showCode.bind(this));

		function showCode() {
			block.classList.toggle('crm-analytics-utm-editor-addition-closed')
		}
	});
</script>
