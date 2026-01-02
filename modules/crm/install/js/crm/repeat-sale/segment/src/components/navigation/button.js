import { Loc } from 'main.core';
import { AirButtonStyle, Button as UiButton } from 'ui.buttons';

export const ButtonEvents = {
	click: 'crm:repeat-sale:segment:navigation-button-click',
};

export const Button = {
	props: {
		id: {
			type: String,
			required: true,
		},
	},

	mounted()
	{
		this.initButton();
	},

	methods: {
		initButton(): void
		{
			this.button = new UiButton({
				useAirDesign: true,
				text: Loc.getMessage(`CRM_REPEAT_SALE_SEGMENT_NAVIGATION_BUTTON_${this.id.toUpperCase()}`),
				round: true,
				style: this.buttonStyle,
				onclick: () => {
					this.emitClickEvent();
				},
			});

			this.button.setDataSet({
				id: `crm-repeat-sale-segment-buttons-${this.id.toLowerCase()}`,
			});

			if (this.$refs.button)
			{
				this.button.renderTo(this.$refs.button);
			}
		},
		emitClickEvent(): void
		{
			this.$Bitrix.eventEmitter.emit(ButtonEvents.click, { id: this.id });
		},
	},

	computed: {
		buttonStyle(): string
		{
			if (this.id === 'update')
			{
				return AirButtonStyle.FILLED;
			}

			return AirButtonStyle.OUTLINE;
		},
	},

	// language=Vue
	template: `
		<div ref="button" class="crm-repeat-sale__segment_button"></div>
	`,
};
