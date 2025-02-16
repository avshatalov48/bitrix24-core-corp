/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core) {
	'use strict';

	const PopupMaker = {
	  emits: ['freeze', 'unfreeze'],
	  props: {
	    contentStructure: {
	      type: Array,
	      required: true
	    }
	  },
	  data() {
	    return {
	      Type: main_core.Type
	    };
	  },
	  template: `
		<div class="booking-popup-maker__content">
			<template v-for="item in contentStructure" :key="item.id">
				<template v-if="Type.isArray(item)">
					<div class="booking-popup-maker__content-section">
						<template v-for="innerItem in item" :key="innerItem.id">
							<div class="booking-popup-maker__content-section_item">
								<component
									v-bind="innerItem.props"
									:is="innerItem.component"
									@freeze="$emit('freeze')"
									@unfreeze="$emit('unfreeze')"
								/>
							</div>
						</template>
					</div>
				</template>
				<template v-else>
					<div class="booking-popup-maker__content-section">
						<div class="booking-popup-maker__content-section_item">
							<component
								v-bind="item.props"
								:is="item.component"
								@freeze="$emit('freeze')"
								@unfreeze="$emit('unfreeze')"
							/>
						</div>
					</div>
				</template>
			</template>
		</div>
	`
	};

	exports.PopupMaker = PopupMaker;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX));
//# sourceMappingURL=popup-maker.bundle.js.map
