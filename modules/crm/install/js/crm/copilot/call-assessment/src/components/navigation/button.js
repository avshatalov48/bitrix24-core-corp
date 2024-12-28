import { Loc } from 'main.core';
import { Button as UiButton, ButtonState } from 'ui.buttons';

export const ButtonEvents = {
	click: 'crm:copilot:call-assessment:navigation-button-click',
};

export const Button = {
	props: {
		id: {
			type: String,
			required: true,
		},
		isEnabled: {
			type: Boolean,
			default: true,
		},
	},

	mounted()
	{
		this.initButton();
	},

	watch: {
		isEnabled(value: boolean): void
		{
			this.button.setState(value ? ButtonState.ACTIVE : ButtonState.DISABLED);
		},
	},

	methods: {
		initButton(): void
		{
			this.button = new UiButton({
				text: Loc.getMessage(`CRM_COPILOT_CALL_ASSESSMENT_NAVIGATION_BUTTON_${this.id.toUpperCase()}`),
				color: this.buttonColor,
				round: true,
				size: BX.UI.Button.Size.LARGE,
				onclick: () => {
					this.emitClickEvent();
				},
			});

			if (this.isEnabled !== true)
			{
				this.button.setState(ButtonState.DISABLED);
			}

			this.button.setDataSet({
				id: `crm-copilot-call-assessment-buttons-${this.id.toLowerCase()}`,
			});

			if (this.$refs.button)
			{
				this.button.renderTo(this.$refs.button);
			}
		},
		emitClickEvent(): void
		{
			if (this.isEnabled)
			{
				this.$Bitrix.eventEmitter.emit(ButtonEvents.click, { id: this.id });
			}
		},
	},

	computed: {
		buttonColor(): string
		{
			if (this.id === 'continue' || this.id === 'submit' || this.id === 'close')
			{
				return UiButton.Color.SUCCESS;
			}

			if (this.id === 'update')
			{
				return UiButton.Color.LIGHT_BORDER;
			}

			return UiButton.Color.LIGHT;
		},
	},

	template: `
		<div ref="button" class="crm-copilot-call-assessment-button"></div>
	`,
};
