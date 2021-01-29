import {Manager} from 'salescenter.manager';

const Configure = {
	props:['url'],

	methods:
	{
		openSlider()
		{
			Manager.openSlider(this.url).then(() => this.onConfigure());
		},

		onConfigure()
		{
			this.$emit('on-configure');
		}

	},
	template: `
		<div class="ui-alert ui-alert-danger ui-alert-xs salescenter-app-payment-by-sms-item-container-alert">
			<span class="ui-alert-message">
				<slot name="sms-configure-text-alert"></slot>
			</span>
			<span class="salescenter-app-payment-by-sms-item-container-alert-config" @click="openSlider()">
				<slot name="sms-configure-text-setting"></slot>
			</span>
		</div>
	`
};

export {
	Configure
}