import { Loc, Runtime, Type } from 'main.core';
import { Breadcrumbs } from '../navigation/breadcrumbs';
import { UI } from 'ui.notification';

export const BasePage = {
	components: {
		Breadcrumbs,
	},

	props: {
		readOnly: {
			type: Boolean,
			default: false,
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
	},
};
