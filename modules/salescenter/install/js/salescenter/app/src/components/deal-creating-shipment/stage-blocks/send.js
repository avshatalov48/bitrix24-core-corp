import {Loc} from 'main.core';

export default {
	props: {
		buttonEnabled: {
			type: Boolean,
			required: true,
		},
	},
	computed: {
		buttonClass()
		{
			return {'salescenter-app-payment-by-sms-item-disabled': this.buttonEnabled === false};
		},
	},
	methods: {
		submit(event)
		{
			this.$emit('on-submit', event);
		},
	},
	template: `
		<div
			:class="buttonClass"
			class="salescenter-app-payment-by-sms-item-show salescenter-app-payment-by-sms-item salescenter-app-payment-by-sms-item-send"
		>
			<div class="salescenter-app-payment-by-sms-item-counter">
				<div class="salescenter-app-payment-by-sms-item-counter-rounder"></div>
				<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
				<div class="salescenter-app-payment-by-sms-item-counter-number"></div>
			</div>
			<div class="">
				<div class="salescenter-app-payment-by-sms-item-container">
					<div class="salescenter-app-payment-by-sms-item-container-payment">
						<div class="salescenter-app-payment-by-sms-item-container-payment-inline">
							<div
								@click="submit($event)"
								class="ui-btn ui-btn-lg ui-btn-success ui-btn-round"
							>
								${Loc.getMessage('SALESCENTER_CREATE_SHIPMENT')}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
}
