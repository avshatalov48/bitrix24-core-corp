/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_popup,booking_component_button,booking_component_popup) {
	'use strict';

	const StatisticsPopup = {
	  emits: ['close'],
	  props: {
	    popupId: {
	      type: String,
	      required: true
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    rows: {
	      type: Array,
	      required: true
	    },
	    button: {
	      type: Object,
	      required: false
	    },
	    dataset: {
	      type: Object,
	      default: {}
	    }
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor
	    };
	  },
	  computed: {
	    config() {
	      return {
	        bindElement: this.bindElement,
	        minWidth: 200,
	        offsetTop: 10,
	        offsetLeft: this.bindElement.offsetWidth / 2,
	        background: '#2878ca',
	        padding: 13,
	        angle: true,
	        angleBorderRadius: '4px 0'
	      };
	    }
	  },
	  methods: {
	    prepareDataset(dataset) {
	      if (!dataset) {
	        return {};
	      }
	      return Object.fromEntries(Object.entries(dataset).map(([key, value]) => [`data-${key.replaceAll(/([A-Z])/g, '-$1').toLowerCase()}`, value]));
	    }
	  },
	  components: {
	    Popup: booking_component_popup.Popup,
	    Button: booking_component_button.Button
	  },
	  template: `
		<Popup
			:id="popupId"
			:config="config"
			@close="$emit('close')"
			ref="popup"
		>
			<div class="booking-statistics-popup" v-bind="prepareDataset(dataset)">
				<div class="booking-statistics-popup-title">
					{{ title }}
				</div>
				<template v-for="(row, index) of rows" :key="index">
					<div class="booking-statistics-popup-row">
						<div>
							{{ row.title }}
						</div>
						<div
							class="booking-statistics-popup-row-value"
							v-bind="prepareDataset(row.dataset)"
							v-html="row.value"
						></div>
					</div>
				</template>
				<template v-if="button">
					<Button
						class="booking-statistics-popup-button bitrix24-light-theme"
						buttonClass="ui-btn-themes"
						:text="button.title"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT_BORDER"
						@click="button.click"
					/>
				</template>
			</div>
		</Popup>
	`
	};

	exports.StatisticsPopup = StatisticsPopup;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.Main,BX.Booking.Component,BX.Booking.Component));
//# sourceMappingURL=statistics-popup.bundle.js.map
