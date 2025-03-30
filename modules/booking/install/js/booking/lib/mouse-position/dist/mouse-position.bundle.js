/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,booking_core,booking_const) {
	'use strict';

	var _isMousePressed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isMousePressed");
	var _bindMousePressed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindMousePressed");
	var _bindMouseMove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindMouseMove");
	var _update = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("update");
	var _onMouseDown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMouseDown");
	var _onMouseUp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onMouseUp");
	class MousePosition {
	  constructor() {
	    Object.defineProperty(this, _onMouseUp, {
	      value: _onMouseUp2
	    });
	    Object.defineProperty(this, _onMouseDown, {
	      value: _onMouseDown2
	    });
	    Object.defineProperty(this, _update, {
	      value: _update2
	    });
	    Object.defineProperty(this, _bindMouseMove, {
	      value: _bindMouseMove2
	    });
	    Object.defineProperty(this, _bindMousePressed, {
	      value: _bindMousePressed2
	    });
	    Object.defineProperty(this, _isMousePressed, {
	      writable: true,
	      value: false
	    });
	  }
	  init() {
	    babelHelpers.classPrivateFieldLooseBase(this, _bindMouseMove)[_bindMouseMove]();
	    babelHelpers.classPrivateFieldLooseBase(this, _bindMousePressed)[_bindMousePressed]();
	  }
	  destroy() {
	    main_core.Event.unbind(window, 'mousemove', this.onMouseMove);
	    main_core.Event.unbind(window, 'mousedown', this.onMouseDown);
	    main_core.Event.unbind(window, 'mouseup', this.onMouseUp);
	  }
	  isMousePressed() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isMousePressed)[_isMousePressed];
	  }
	}
	function _bindMousePressed2() {
	  if (this.onMouseDown) {
	    return;
	  }
	  this.onMouseDown = babelHelpers.classPrivateFieldLooseBase(this, _onMouseDown)[_onMouseDown].bind(this);
	  this.onMouseUp = babelHelpers.classPrivateFieldLooseBase(this, _onMouseUp)[_onMouseUp].bind(this);
	  main_core.Event.bind(window, 'mousedown', this.onMouseDown);
	  main_core.Event.bind(window, 'mouseup', this.onMouseUp);
	}
	function _bindMouseMove2() {
	  if (this.onMouseMove) {
	    return;
	  }
	  this.onMouseMove = babelHelpers.classPrivateFieldLooseBase(this, _update)[_update].bind(this);
	  main_core.Event.bind(window, 'mousemove', this.onMouseMove);
	}
	function _update2(event) {
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setMousePosition`, {
	    top: event.clientY + window.scrollY,
	    left: event.clientX + window.scrollX
	  });
	}
	function _onMouseDown2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _isMousePressed)[_isMousePressed] = true;
	}
	function _onMouseUp2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _isMousePressed)[_isMousePressed] = false;
	}
	const mousePosition = new MousePosition();

	exports.mousePosition = mousePosition;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX.Booking,BX.Booking.Const));
//# sourceMappingURL=mouse-position.bundle.js.map
