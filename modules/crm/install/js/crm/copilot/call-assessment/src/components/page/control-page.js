import { Loc } from 'main.core';
import { UI } from 'ui.notification';
import { BordersAccord } from '../../validator/border-accord';
import { Pill } from '../common/pill';
import { TextWithDialog } from '../common/text-with-dialog';
import { BasePage } from './base-page';

export const ControlPage = {
	extends: BasePage,

	components: {
		TextWithDialog,
		Pill,
	},

	data(): Object
	{
		return {
			id: 'control',
			lowBorder: this.data.lowBorder,
			highBorder: this.data.highBorder,
			validator: new BordersAccord(),
		};
	},

	methods: {
		getData(): Object
		{
			return {
				lowBorder: this.lowBorder,
				highBorder: this.highBorder,
			};
		},
		setLowBorder(value: number): void
		{
			this.lowBorder = value;
		},
		setHighBorder(value: number): void
		{
			this.highBorder = value;
		},
		validate(): boolean
		{
			return this.validator.validate(this.lowBorder, this.highBorder);
		},
		onValidationFailed(): void
		{
			UI.Notification.Center.notify({
				content: this.validator.getError(),
				autoHideDelay: 3000,
			});
		},
	},

	computed: {
		pageTitle(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_TITLE');
		},
		pageDescription(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_DESCRIPTION');
		},
	},

	template: `
		<div v-if="isActive">
			<div class="crm-copilot__call-assessment_page-section">
				<Breadcrumbs :active-tab-id="id"/>
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
					<div class="crm-copilot__call-assessment_page-section-body-field-container">
						<div class="crm-copilot__call-assessment_page-section-body-field-title --low-icon">
							{{ this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_LOW_BORDER_TITLE') }}
						</div>
						<div class="crm-copilot__call-assessment_page-section-body-field-content">
							<div
								class="crm-copilot__call-assessment_page-section-body-field-subtitle"
								v-html="this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_LOW_BORDER_DESCRIPTION')"
							>
							</div>
							<Pill
								additionalClass="--low"
								:value="lowBorder"
								@change="setLowBorder"
							/>
							<div class="crm-copilot__call-assessment_page-section-body-field-additional">
								{{ this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_LOW_BORDER_ADDITIONAL') }}
							</div>
						</div>
					</div>
					
					<div class="crm-copilot__call-assessment_page-section-body-field-container">
						<div class="crm-copilot__call-assessment_page-section-body-field-title --high-icon">
							{{ this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_HIGH_BORDER_TITLE') }}
						</div>
						<div class="crm-copilot__call-assessment_page-section-body-field-content">
							<div 
								class="crm-copilot__call-assessment_page-section-body-field-subtitle"
								v-html="this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_HIGH_BORDER_DESCRIPTION')"
							>
							</div>
							<Pill
								additionalClass="--high"
								:value="highBorder"
								@change="setHighBorder"
							/>
							<div class="crm-copilot__call-assessment_page-section-body-field-additional">
								{{ this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_HIGH_BORDER_ADDITIONAL') }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
