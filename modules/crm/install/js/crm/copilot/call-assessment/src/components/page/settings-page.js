import { Loc } from 'main.core';
import { InfoHelper } from 'ui.info-helper';
import { BasePage } from './base-page';
import 'ui.forms';

export const SettingsPage = {
	extends: BasePage,

	data(): Object
	{
		let autoCheckTypeId = 0;
		if (this.settings.baas?.hasPackage)
		{
			autoCheckTypeId = this.data.autoCheckTypeId ?? 1;
		}

		return {
			id: 'settings',
			callTypeId: this.data.callTypeId ?? 1,
			autoCheckTypeId,
		};
	},

	methods: {
		getData(): Object
		{
			return {
				callTypeId: this.callTypeId,
				autoCheckTypeId: this.autoCheckTypeId,
			};
		},
		onAutoCheckTypeIdChange(event): void
		{
			if (this.isBaasHasPackage)
			{
				return;
			}

			const { value } = event.target;
			if (value !== 0)
			{
				if (this.packageEmptySliderCode)
				{
					InfoHelper.show(this.packageEmptySliderCode);
				}

				void this.$nextTick(() => {
					this.autoCheckTypeId = 0;
				});
			}
		},
	},

	computed: {
		pageTitle(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_TITLE');
		},
		pageDescription(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_DESCRIPTION');
		},
		pageSectionSettingsCallType(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE');
		},
		pageSectionSettingsCallTypeDescription(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_DESCRIPTION');
		},
		callTypes(): Array<Object>
		{
			return [
				{
					id: 1,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_ALL'),
				},
				{
					id: 2,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_INCOMING'),
				},
				{
					id: 3,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_OUTGOING'),
				},
			];
		},
		pageSectionSettingsCheck(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK');
		},
		pageSectionSettingsCheckDescription(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_DESCRIPTION');
		},
		checkTypes(): Array<Object>
		{
			return [
				{
					value: 1,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_FIRST_INCOMING'),
				},
				{
					value: 2,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_ALL_INCOMING'),
				},
				{
					value: 3,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_ALL_OUTGOING'),
				},
				{
					value: 4,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_ALL'),
				},
				{
					value: 0,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_DISABLED'),
				},
			];
		},
		isBaasAvailable(): boolean
		{
			return this.settings.baas?.isAvailable ?? false;
		},
		isBaasHasPackage(): boolean
		{
			return this.settings.baas?.hasPackage ?? false;
		},
		packageEmptySliderCode(): ?string
		{
			return this.settings.baas?.aiPackagesEmptySliderCode ?? null;
		},
	},

	template: `
		<div v-if="isActive">
			<div class="crm-copilot__call-assessment_page-section">
				<Breadcrumbs :active-tab-id="id" />
				<header class="crm-copilot__call-assessment_page-section-header">
					<div class="crm-copilot__call-assessment_page-section-header-title">
						{{ pageTitle }}
					</div>
					<div class="crm-copilot__call-assessment_page-section-header-description">
						{{ pageDescription }}
					</div>
				</header>
				
				<div class="crm-copilot__call-assessment_page-section-body">
					<AiDisabledInSettings v-if="!isEnabled" />
					<div :class="this.getBodyFieldClassList(['ui-ctl', 'ui-ctl-after-icon', 'ui-ctl-dropdown', 'ui-ctl-w100'])">
						<label>{{ pageSectionSettingsCallType }}</label>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select class="ui-ctl-element" v-model="callTypeId">
								<option 
									v-for="callType in callTypes"
									:value="callType.id"
									:disabled="readOnly && callTypeId !== callType.id"
								>
									{{callType.title}}
								</option>
							</select>
						</div>
						<div class="crm-copilot__call-assessment_page-section-body-field-description">
							{{ pageSectionSettingsCallTypeDescription }}
						</div>
					</div>
				</div>
				
				<div 
					v-if="isBaasAvailable"
					class="crm-copilot__call-assessment_page-section-body"
				>
					<div :class="this.getBodyFieldClassList(['ui-ctl', 'ui-ctl-after-icon', 'ui-ctl-dropdown', 'ui-ctl-w100'])">
						<label>{{ pageSectionSettingsCheck }}</label>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select 
								class="ui-ctl-element"
								v-model="autoCheckTypeId"
								@change="onAutoCheckTypeIdChange"
							>
								<option 
									v-for="checkType in checkTypes"
									:value="checkType.value"
									:disabled="readOnly && autoCheckTypeId !== checkType.value"
								>
									{{checkType.title}}
								</option>
							</select>
						</div>
						<div class="crm-copilot__call-assessment_page-section-body-field-description">
							{{ pageSectionSettingsCheckDescription }}
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
