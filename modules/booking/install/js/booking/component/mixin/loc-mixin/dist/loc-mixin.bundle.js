/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Component = this.BX.Booking.Component || {};
(function (exports,main_core) {
	'use strict';

	const locMixin = {
	  methods: {
	    loc(name, replacements) {
	      return main_core.Loc.getMessage(name, replacements);
	    }
	  }
	};

	exports.locMixin = locMixin;

}((this.BX.Booking.Component.Mixin = this.BX.Booking.Component.Mixin || {}),BX));
//# sourceMappingURL=loc-mixin.bundle.js.map
