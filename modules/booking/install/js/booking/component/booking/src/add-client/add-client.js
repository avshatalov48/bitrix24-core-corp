import { Type } from 'main.core';
import { PopupManager } from 'main.popup';
import { mapGetters } from 'ui.vue3.vuex';

import { ClientPopup, CLIENT_POPUP_ID } from 'booking.component.client-popup';
import { Model } from 'booking.const';
import { limit } from 'booking.lib.limit';

import './add-client.css';

// @vue/component
export const AddClient = {
	name: 'AddClient',
	components: {
		ClientPopup,
	},
	props: {
		expired: {
			type: Boolean,
			default: false,
		},
		dataAttributes: {
			type: Object,
			default: () => ({}),
		},
		buttonClass: {
			type: String,
			default: '',
		},
		popupOffsetLeft: {
			type: Number,
			default: null,
		},
	},
	emits: ['add'],
	data(): Object
	{
		return {
			showPopup: false,
		};
	},
	computed: {
		...mapGetters({
			providerModuleId: `${Model.Clients}/providerModuleId`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
	},
	methods: {
		clickHandler(): void
		{
			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			if (this.showPopup)
			{
				return;
			}

			PopupManager.getPopupById(CLIENT_POPUP_ID)?.destroy();

			this.showPopup = true;
		},
		getOffsetLeft(): number
		{
			const { left } = this.$refs.button.getBoundingClientRect();
			if (window.innerWidth - left < 370)
			{
				return -317;
			}

			return Type.isNil(this.popupOffsetLeft) ? this.$refs.button.offsetWidth + 10 : this.popupOffsetLeft;
		},
	},
	template: `
		<div
			v-if="providerModuleId"
			class="booking--booking-base--add-client-button"
			:class="[buttonClass, { '--expired': expired }]"
			v-bind="$props.dataAttributes"
			ref="button"
			@click="clickHandler"
		>
			{{ loc('BOOKING_BOOKING_PLUS_CLIENT') }}
		</div>
		<ClientPopup
			v-if="showPopup"
			:bindElement="this.$refs.button"
			:offset-top="-100"
			:offset-left="getOffsetLeft()"
			@create="$emit('add', $event)"
			@close="showPopup = false"
		/>
	`,
};
