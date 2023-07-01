//@flow

import {mapGetters, mapMutations} from "ui.vue3.vuex";

export const SmsProviderSelect = {

	data() {
		return {
			isListShowed: false,
		};
	},

	mounted() {
		document.addEventListener('click', () => {
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
				requestMethod: "get",
				width: 700,
				events: {
					onClose: () => {
						this.$emit('onConnectSliderClosed');
					}
				}
			};

			BX.SidePanel.Instance.open(this.getServiceLink(), options);
		},
	},

	computed: {
		...mapGetters([
			'getSelectedService',
		]),

		getListClassname() {
			if (!this.isListShowed)
			{
				return 'sms-provider-selector-hided-list';
			}
			else
			{
				return 'sms-provider-selector-list';
			}
		},
	},

	// language=Vue
	template: `
		<div style="display: inline-block; vertical-align: top">
			<span class="sms-provider-selector" @click.stop="switchVisibility">{{getSelectedService['NAME']}}</span>
			<ul :class="getListClassname" @click.stop>
				<li v-for="provider in getActiveSmsServices()" @click="switchService(provider['ID'])" v-show="provider['ID'] !== getSelectedService['ID']">{{ provider['NAME'] }}</li>
				<li @click="openSmsServicesSlider">{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_CONNECT_MORE') }}</li>
			</ul>
		</div>
	`,
};