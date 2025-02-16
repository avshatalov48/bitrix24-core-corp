/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_loader) {
	'use strict';

	const Loader = {
	  name: 'BookingLoader',
	  props: {
	    options: {
	      type: Object,
	      default: null
	    }
	  },
	  methods: {
	    getOptions() {
	      return {
	        ...this.getDefaultOptions(),
	        ...this.options
	      };
	    },
	    getDefaultOptions() {
	      return {
	        target: this.$refs.loader,
	        type: 'BULLET',
	        size: 'xs'
	      };
	    }
	  },
	  mounted() {
	    this.loader = new ui_loader.Loader(this.getOptions());
	    this.loader.render();
	    this.loader.show();
	  },
	  beforeUnmount() {
	    var _this$loader;
	    (_this$loader = this.loader) == null ? void 0 : _this$loader.hide == null ? void 0 : _this$loader.hide();
	    this.loader = null;
	  },
	  template: `
		<div class="booking-loader__container" ref="loader"></div>
	`
	};

	exports.Loader = Loader;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.UI));
//# sourceMappingURL=loader.bundle.js.map
