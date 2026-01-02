import { Extension } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { InfoHelper } from 'ui.info-helper';

import 'ui.forms';

import { BasePage } from './base-page';
import { TypeSelector } from '../common/availability/type-selector';
import { availabilityType as availabilityTypeList } from '../enum/availability-type';
import { TimePeriod as TimePeriodValidator } from './../../validator/time-period';

export const SettingsPage = {
	extends: BasePage,
	components: {
		TypeSelector,
	},

	data(): Object
	{
		let currentAutoCheckTypeId = 0;
		if (this.settings.baas?.hasPackage)
		{
			currentAutoCheckTypeId = this.data.autoCheckTypeId ?? 1;
		}

		return {
			id: 'settings',
			callTypeId: this.data.callTypeId ?? 1,
			autoCheckTypeId: currentAutoCheckTypeId,
			availabilityType: this.data.availabilityType ?? availabilityTypeList.always_active,
			availabilityData: this.data.availabilityData ?? [],
			timePeriodValidator: new TimePeriodValidator(),
			timePeriodError: null,
		};
	},

	methods: {
		getData(): Object
		{
			return {
				callTypeId: this.callTypeId,
				autoCheckTypeId: this.autoCheckTypeId,
				availabilityType: this.availabilityType,
				availabilityData: this.$refs.typeSelector?.getAvailabilityData() ?? [],
			};
		},

		onAutoCheckTypeIdChange(event: BaseEvent): void
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

		onAvailabilityTypeChange(data: Object): void
		{
			this.availabilityType = data.availabilityType;

			this.timePeriodError = null;
		},

		onAvailabilityDataChange(data: Object): void
		{
			// @todo: setup "availabilityData" data here in the future if needed
			// const { availabilityType, availabilityTypeData, updatedRow } = data;

			this.validate();
			this.onValidationFailed();
		},

		validate(): boolean
		{
			if (this.availabilityType === availabilityTypeList.custom)
			{
				const data = this.$refs.typeSelector?.getAvailabilityData() ?? [];
				const invalidItems = data.filter((item: Object) => {
					return !this.timePeriodValidator.validate(item.startPoint, item.endPoint);
				});

				return invalidItems.length === 0;
			}

			return true;
		},

		onValidationFailed(): void
		{
			this.timePeriodError = this.timePeriodValidator.getError();
		},
	},

	mounted()
	{
		this.$refs.typeSelector?.setAvailabilityData(this.availabilityData);
	},

	computed: {
		pageTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_TITLE');
		},

		pageDescription(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_DESCRIPTION');
		},

		pageSectionSettingsCallType(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE');
		},

		pageSectionSettingsCallTypeDescription(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_DESCRIPTION');
		},

		pageSectionSettingsCheck(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK');
		},

		pageSectionSettingsCheckDescription(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_DESCRIPTION');
		},

		callTypes(): Array<Object>
		{
			return [
				{
					id: 1,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_ALL'),
				},
				{
					id: 2,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_INCOMING'),
				},
				{
					id: 3,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_OUTGOING'),
				},
			];
		},

		checkTypes(): Array<Object>
		{
			return [
				{
					value: 1,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_FIRST_INCOMING'),
				},
				{
					value: 2,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_ALL_INCOMING'),
				},
				{
					value: 3,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_ALL_OUTGOING'),
				},
				{
					value: 4,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_ALL'),
				},
				{
					value: 0,
					title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_DISABLED'),
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

		pageSectionSettingsAvailability(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_AVAILABILITY');
		},

		pageSectionSettingsAvailabilityDescription(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_AVAILABILITY_DESCRIPTION');
		},

		isCallAssessmentAvailabilityEnabled(): boolean
		{
			return Extension.getSettings('crm.copilot.call-assessment').get('callAssessmentAvailabilityEnabled');
		},
	},

	template: `
		<div v-show="isActive">
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

				<div
					v-if="isCallAssessmentAvailabilityEnabled"
					class="crm-copilot__call-assessment_page-section-body"
				>
					<div class="crm-copilot__call-assessment_page-section-body-field">
						<label>{{ pageSectionSettingsAvailability }}</label>
						<TypeSelector
							ref="typeSelector"
							:availabilityType="availabilityType"
							:readOnly="readOnly"
							@typeChange="onAvailabilityTypeChange"
							@dataChange="onAvailabilityDataChange"
						/>
						<div class="crm-copilot__call-assessment_page-section-body-field-description">
							{{ pageSectionSettingsAvailabilityDescription }}
						</div>
						<div v-if="timePeriodError" class="crm-copilot__call-assessment_page-section-body-field-error">
							{{ timePeriodError }}
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
