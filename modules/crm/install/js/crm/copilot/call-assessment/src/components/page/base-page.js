import { Loc, Runtime, Type } from 'main.core';
import { UI } from 'ui.notification';
import { AiDisabledInSettings } from '../common/ai-disabled-in-settings';
import { Breadcrumbs } from '../navigation/breadcrumbs';

export const BasePage = {
	components: {
		Breadcrumbs,
		AiDisabledInSettings,
	},

	props: {
		readOnly: {
			type: Boolean,
			default: false,
		},
		isEnabled: {
			type: Boolean,
			default: true,
		},
		isActive: {
			type: Boolean,
		},
		data: {
			type: Object,
			default: {},
		},
		settings: {
			type: Object,
			default: {},
		},
	},

	data(): Object
	{
		return {
			id: null, // must be defined in child class
		};
	},

	methods: {
		getId(): string
		{
			return this.id;
		},
		getData(): Object
		{
			// must be implement in child class

			return {};
		},
		isReadyToMoveOn(): boolean
		{
			return this.validate();
		},
		validate(): boolean
		{
			// must be implement in child class

			return true;
		},
		onValidationFailed(): void
		{
			UI.Notification.Center.notify({
				content: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_VALIDATION_ERROR'),
				autoHideDelay: 3000,
			});
		},
		emitChangeData(): void
		{
			if (!Type.isFunction(this.onChangeDebounce))
			{
				this.onChangeDebounce = Runtime.debounce(this.onChange, 100, this);
			}

			this.onChangeDebounce();
		},
		onChange(): void
		{
			this.$emit('onChange', {
				id: this.id,
				data: this.getData(),
			});
		},
		getBodyFieldClassList(additionalClasses: string[] = []): Array
		{
			return [
				'crm-copilot__call-assessment_page-section-body-field',
				...additionalClasses,
				{ '--read-only': this.isEnabled !== true },
			];
		},
	},
};
