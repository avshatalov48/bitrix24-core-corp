import {BaseEvent} from 'main.core.events';

const Error = {
	props: {
		error: {
			type: Object,
			required: true
		}
	},
	methods: {
		openSlider()
		{
			this.error.fixer().then(() => this.onConfigure(this.error))
		},
		onConfigure(data)
		{
			this.$emit('on-configure', new BaseEvent({data}));
		}
	},
	template: `
		<div class="ui-alert ui-alert-warning ui-alert-xs ui-alert-icon-danger salescenter-app-payment-by-sms-item-container-alert">
			<span class="ui-alert-message">
				{{error.text}}
			</span>
			<span
				v-if="error.fixer && error.fixText"
				class="salescenter-app-payment-by-sms-item-container-alert-config"
				@click="openSlider()"
			>
				{{error.fixText}}
			</span>
		</div>
	`
};

export {
	Error
}
