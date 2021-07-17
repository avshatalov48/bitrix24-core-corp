import {Loc} from 'main.core';

export default {
	props: {
		buttonLabel: {
			type: String,
			required: true,
		},
		buttonEnabled: {
			type: Boolean,
			required: true,
		},
		showWhatClientSeesControl: {
			type: Boolean,
			required: true,
		}
	},
	computed: {
		buttonClass()
		{
			return {'salescenter-app-payment-by-sms-item-disabled': this.buttonEnabled === false};
		},
	},
	methods: {
		showWhatClientSees(event)
		{
			BX.Salescenter.Manager.openWhatClientSee(event);
		},
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
								{{buttonLabel}}
							</div>
							<div
								v-if="showWhatClientSeesControl"
								@click="showWhatClientSees"
								class="salescenter-app-add-item-link"
							>
								${Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_WHAT_DOES_CLIENT_SEE')}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
}
