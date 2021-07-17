import {Manager} from 'salescenter.manager';

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
			Manager.openSlider(this.error.fixUrl).then(() => this.onConfigure());
		},
		onConfigure()
		{
			this.$emit('on-configure');
		}
	},
	template: `
		<div class="ui-alert ui-alert-danger ui-alert-xs ui-alert-icon-danger salescenter-app-payment-by-sms-item-container-alert">
			<span class="ui-alert-message">
				{{error.text}}
			</span>
			<span
				v-if="error.fixUrl && error.fixText"
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
