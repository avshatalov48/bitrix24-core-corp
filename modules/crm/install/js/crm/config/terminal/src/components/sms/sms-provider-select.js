// @flow

import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import { Event } from 'main.core';

export const SmsProviderSelect = {

	data(): Object
	{
		return {
			isListShowed: false,
		};
	},

	mounted() {
		Event.bind(document, 'click', () => {
			this.isListShowed = false;
		});
	},

	methods: {
		...mapGetters([
			'getServiceLink',
			'getActiveSmsServices',
		]),

		...mapMutations([
			'selectSMSService',
		]),

		switchService(serviceId)
		{
			this.isListShowed = false;
			this.selectSMSService(serviceId);
		},

		switchVisibility() {
			this.isListShowed = !this.isListShowed;
		},

		openSmsServicesSlider() {
			const options = {
				cacheable: false,
				allowChangeHistory: false,
				requestMethod: 'get',
				width: 700,
				events: {
					onClose: () => {
						this.$emit('onConnectSliderClosed');
					},
				},
			};

			BX.SidePanel.Instance.open(this.getServiceLink(), options);
		},
	},

	computed: {
		...mapGetters([
			'getSelectedService',
		]),

		getListClassname(): string
		{
			if (!this.isListShowed)
			{
				return 'sms-provider-selector-hided-list';
			}

			return 'sms-provider-selector-list';
		},
	},

	// language=Vue
	template: `
		<div style="display: inline-block; vertical-align: top; position: relative;">
			<span class="sms-provider-selector" @click.stop="switchVisibility">{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_CHANGE_MSGVER_1') }}</span>
			<ul :class="getListClassname" @click.stop>
				<li v-for="provider in getActiveSmsServices()" @click="switchService(provider['ID'])" v-show="provider['ID'] !== getSelectedService['ID']">{{ provider['NAME'] }}</li>
				<li @click="openSmsServicesSlider">{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_CONNECT_MORE') }}</li>
			</ul>
		</div>
	`,
};
