/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,booking_core,booking_const) {
	'use strict';

	var _update = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("update");
	class MousePosition {
	  constructor() {
	    Object.defineProperty(this, _update, {
	      value: _update2
	    });
	  }
	  bindMouseMove() {
	    if (this.onMouseMove) {
	      return;
	    }
	    this.onMouseMove = babelHelpers.classPrivateFieldLooseBase(this, _update)[_update].bind(this);
	    main_core.Event.bind(document, 'mousemove', this.onMouseMove);
	  }
	}
	function _update2(event) {
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setMousePosition`, {
	    top: event.clientY + window.scrollY,
	    left: event.clientX + window.scrollX
	  });
	}
	const mousePosition = new MousePosition();

	exports.MousePosition = MousePosition;
	exports.mousePosition = mousePosition;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX.Booking,BX.Booking.Const));
//# sourceMappingURL=mouse-position.bundle.js.map
