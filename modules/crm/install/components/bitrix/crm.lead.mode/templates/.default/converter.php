<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @var array $arResult */

$config = $arResult['CONVERTER_CONFIG'];

$createContact = in_array(\CCrmOwnerType::Contact, $config['items']);
$createCompany = in_array(\CCrmOwnerType::Company, $config['items']);
$dealCategory = $config['dealCategoryId'];
$completeActivities = $config['completeActivities'];

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

$APPLICATION->SetTitle(GetMessage("CRM_TYPE_SETTINGS"));
?>
<style>
	.crm-lead-mode-slider {
		padding: 0 15px 21px 21px;
	}

	.crm-lead-mode-slider-container {
		display: block;
		overflow: hidden;
		padding-top: 15px;
		margin-bottom: 68px;
		color: #333;
	}

	.crm-lead-mode-slider-container-title {
		font: 22px/22px var(--ui-font-family-secondary, var(--ui-font-family-open-sans));
		font-weight: var(--ui-font-weight-light, 300);
	}

	.crm-lead-mode-slider-container p {
		font: 14px/20px Helvetica, Arial,sans-serif;
	}

	.crm-lead-mode-slider-container-wrapper {
		position: relative;
		padding: 0 5px;
		background: #f8f9fa;
		font: 14px/20px Helvetica, Arial,sans-serif;
		color: #525c69;
	}

	.crm-lead-mode-slider-container-wrapper-item {
		display: block;
		overflow: hidden;
		position: relative;
		padding: 11px 30px 11px 0;
		border-bottom: 1px solid #e6e9ec;
		margin: 0 20px;
	}

	.crm-lead-mode-slider-container-wrapper-item:last-child	{
		border: none;
	}

	.crm-lead-mode-slider-container-wrapper-item-param {
		float: left;
		min-width: 170px;
		margin: 11px 4px 11px 0;
	}

	.crm-lead-mode-slider-container-wrapper-item-inner {
		display: block;
		overflow: hidden;
		padding-left: 20px;
		margin: 10px 0;
	}

	.crm-lead-mode-slider-checker {
		overflow: hidden;
	}

	.crm-lead-mode-slider-checker-item {
		clear: both;
		float: left;
		overflow: hidden;
		margin: 10px 0 ;
		cursor: pointer;
	}

	.crm-lead-mode-slider-checker .crm-lead-mode-slider-checker-item {
		margin-top: 0;
	}

	.crm-lead-mode-slider-container-wrapper-item-param-fix:before {
		content: '';
		width: 1px;
		height: 39px;
		display: inline-block;
		vertical-align: middle;
	}

	.crm-lead-mode-slider .ui-ctl-element {
		height: 100%;
	}

	.crm-lead-mode-slider-dropdown {
		position: relative;
		display: -webkit-box;
		display: -ms-flexbox;
		display: flex;
		-ms-flex-align: stretch;
		box-sizing: border-box;
		max-width: 100%;
		width: 320px;
		height: 39px;
		vertical-align: middle;
		align-items: center;
	}

	.crm-lead-mode-slider-dropdown-after[class*=ui-ctl-icon-] {
		background-position: center;
		background-size: 16px auto;
		background-repeat: no-repeat;
		opacity: .78;
		transition: 250ms linear opacity;
		pointer-events: none;
	}

	.crm-lead-mode-slider-dropdown-after {
		position: absolute;
		transition: .1s;
		z-index: 9;
		right: 0;
		width: 39px;
		height: 39px;
		opacity: .78;
	}

	.crm-lead-mode-slider-dropdown:hover .crm-lead-mode-slider-dropdown-after {
		opacity: .9;
	}

	.crm-lead-mode-slider-dropdown-after:after,
	.crm-lead-mode-slider-dropdown-after:before {
		position: absolute;
		top: 50%;
		left: 50%;
		width: 9px;
		height: 2px;
		background-color: #535c68;
		content: "";
		transition: all 250ms ease;
		-webkit-transform-origin: center;
		-moz-transform-origin: center;
		-ms-transform-origin: center;
		-o-transform-origin: center;
		transform-origin: center;
	}

	.crm-lead-mode-slider-dropdown-after:before {
		margin-left: -5px;
		transform: translateX(-50%) translateY(-50%) rotate(45deg);
	}

	.crm-lead-mode-slider-dropdown-after:after {
		margin-right: -4px;
		transform: translateX(-50%) translateY(-50%) rotate(-45deg);
	}

	.crm-lead-mode-slider-dropdown-element {
		height: 100%;
		z-index: 1;
		display: block;
		overflow: hidden;
		box-sizing: border-box;
		margin: 0;
		padding: 0 11px;
		width: 100%;
		outline: none;
		border: 1px solid #c6cdd3;
		border-radius: 2px;
		background-color: #fff;
		color: #535c69;
		vertical-align: middle;
		text-align: left;
		text-overflow: ellipsis;
		white-space: nowrap;
		font: 15px/37px var(--ui-font-family-secondary, var(--ui-font-family-open-sans));
		font-weight: var(--ui-font-weight-regular, 400);

		transition: border .3s ease, background-color .3s ease, color .3s ease, padding .3s ease;
		-webkit-box-flex: 1;
		-ms-flex: 1;
		flex: 1;
		-webkit-appearance: none;
		-moz-appearance: none;
		-ms-appearance: none;
		-o-appearance: none;
		appearance: none;
	}

	.ui-button-panel {
		text-align: center;
	}
</style>
<div class="crm-lead-mode-slider">
	<div class="crm-lead-mode-slider-container">
		<div class="crm-lead-mode-slider-container-title"><?=GetMessage("CRM_TYPE_CREATION_SCENARIO")?></div>
		<p><?=GetMessage("CRM_TYPE_CREATION_SCENARIO_DESC")?></p>
		<div class="crm-lead-mode-slider-container-wrapper">
			<div class="crm-lead-mode-slider-container-wrapper-item">
				<div class="crm-lead-mode-slider-container-wrapper-item-param"><?=GetMessage("CRM_TYPE_DEAL_CREATE_AND")?></div>
				<div class="crm-lead-mode-slider-container-wrapper-item-inner">
					<div class="crm-lead-mode-slider-checker">
						<label class="crm-lead-mode-slider-checker-item">
							<input type="checkbox" name="createContact" <?=$createContact? 'checked' : ''?> value="Y" id="chk-contact" onclick="__crmLeadModeClientClick(event, this);">
							<span class="crm-lead-mode-slider-checker-item-label"><?=GetMessage("CRM_TYPE_CONTACT")?></span>
						</label>
						<label class="crm-lead-mode-slider-checker-item">
							<input type="checkbox" name="createCompany" <?=$createCompany? 'checked' : ''?> value="Y" id="chk-company" onclick="__crmLeadModeClientClick(event, this);">
							<span class="crm-lead-mode-slider-checker-item-label"><?=GetMessage("CRM_TYPE_COMPANY")?></span>
						</label>
					</div>
				</div>
			</div>
			<div class="crm-lead-mode-slider-container-wrapper-item">
				<div class="crm-lead-mode-slider-container-wrapper-item-param crm-lead-mode-slider-container-wrapper-item-param-fix"><?=GetMessage("CRM_TYPE_DEAL_DIRECTION")?></div>
				<div class="crm-lead-mode-slider-container-wrapper-item-inner">
					<div class="crm-lead-mode-slider-dropdown">
						<div class="crm-lead-mode-slider-dropdown-after"></div>
						<select class="crm-lead-mode-slider-dropdown-element" name="dealCategoryId" id="slc-deal">
							<?foreach ($arResult['DEAL_CATEGORIES'] as $id => $name):?>
							<option value="<?=htmlspecialcharsbx($id)?>" <?=($id == $dealCategory)? 'selected' : ''?>>
								<?=htmlspecialcharsbx($name)?>
							</option>
							<?endforeach;?>
						</select>
					</div>
				</div>
			</div>
			<div class="crm-lead-mode-slider-container-wrapper-item">
				<label class="crm-lead-mode-slider-checker-item">
					<input type="checkbox" name="disableCompleteActivities" <?=!$completeActivities? 'checked' : ''?> id="chk-act">
					<span class="crm-lead-mode-slider-checker-item-label"><?=GetMessage("CRM_TYPE_NOT_CLOSE_DEAL")?></span>
				</label>
			</div>
		</div>
	</div>
</div>
<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'BUTTONS' =>
		[
			[
				'TYPE' => 'save',
				'ONCLICK' => '__crmLeadModeConverterSave();',
			],
			'cancel'
		]
]);?>
<script>
	BX.ready(function()
	{
		window.__crmLeadModeConverterSave = function()
		{
			var contactNode = BX('chk-contact');
			var companyNode = BX('chk-company');
			var dealNode = BX('slc-deal');
			var actNode = BX('chk-act');

			BX.ajax({
				url: '/bitrix/tools/crm_lead_mode.php',
				method: 'POST',
				dataType: 'json',
				data: {
					sessid: BX.bitrix_sessid(),
					action: "setConverterConfig",
					createContact: contactNode.checked ? 'Y' : 'N',
					createCompany: companyNode.checked ? 'Y' : 'N',
					dealCategoryId: dealNode.value,
					completeActivities: actNode.checked ? 'N' : 'Y'
				},
				onsuccess: BX.proxy(function(result)
				{
					if (result.error)
					{
						alert(result.error);
					}
					else
					{
						var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
						if (slider)
						{
							slider.close(false, function()
							{
								slider.destroy();
							});
						}
					}
				}, this),
				onfailure: function()
				{
				}
			});
		};

		window.__crmLeadModeClientClick = function(event, node)
		{
			var contactNode = BX('chk-contact');
			var companyNode = BX('chk-company');

			if (!contactNode.checked && !companyNode.checked)
			{
				event.preventDefault();
			}
		}
	});
</script>