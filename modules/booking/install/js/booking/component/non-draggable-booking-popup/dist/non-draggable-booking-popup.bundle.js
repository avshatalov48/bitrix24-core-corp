/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,main_popup) {
	'use strict';

	let _ = t => t,
	  _t;
	let popup = null;
	var _bindElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindElement");
	var _initPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPopup");
	var _getPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupContent");
	class NonDraggableBookingPopup {
	  constructor(options) {
	    Object.defineProperty(this, _getPopupContent, {
	      value: _getPopupContent2
	    });
	    Object.defineProperty(this, _initPopup, {
	      value: _initPopup2
	    });
	    Object.defineProperty(this, _bindElement, {
	      writable: true,
	      value: void 0
	    });
	    this.popupId = options.id;
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement] = options.bindElement;
	  }
	  show() {
	    if (!popup) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initPopup)[_initPopup]();
	    }
	    popup.show();
	    this.setBindElement(babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement]);
	    this.adjustPosition();
	    main_core.Event.bind(document, 'scroll', this.adjustPosition, true);
	  }
	  destroy(popupId) {
	    var _popup, _popup2;
	    if (popupId && popupId !== ((_popup = popup) == null ? void 0 : _popup.getId())) {
	      return;
	    }
	    (_popup2 = popup) == null ? void 0 : _popup2.destroy();
	    popup = null;
	    main_core.Event.unbind(document, 'scroll', this.adjustPosition, true);
	  }
	  hasPopup() {
	    return popup !== null;
	  }
	  setBindElement(bindElement) {
	    var _popup3;
	    (_popup3 = popup) == null ? void 0 : _popup3.setBindElement(bindElement);
	  }
	  adjustPosition() {
	    var _popup4;
	    (_popup4 = popup) == null ? void 0 : _popup4.adjustPosition();
	  }
	  get message() {
	    return main_core.Loc.getMessage('BOOKING_NON_DRAGGING_BOOKING_FROM_DELETED_RESOURCE');
	  }
	}
	function _initPopup2() {
	  if (popup) {
	    return;
	  }
	  popup = new main_popup.Popup({
	    id: this.popupId,
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement],
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getPopupContent)[_getPopupContent](),
	    offsetLeft: babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement].offsetWidth / 2,
	    maxWidth: 250,
	    minWidth: 200,
	    minHeight: 42,
	    background: '#2878ca',
	    angle: {
	      offset: 20,
	      position: 'top'
	    },
	    angleBorderRadius: '4px 0',
	    onPopupClose: () => {
	      var _popup5;
	      (_popup5 = popup) == null ? void 0 : _popup5.destroy();
	    }
	  });
	}
	function _getPopupContent2() {
	  return main_core.Tag.render(_t || (_t = _`
			<div class="booking--booking--non-draggable-booking-popup-content">
				<div class="booking--booking--non-draggable-booking-popup-content__text">
					${0}
				</div>
			</div>
		`), this.message);
	}

	exports.NonDraggableBookingPopup = NonDraggableBookingPopup;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX,BX.Main));
//# sourceMappingURL=non-draggable-booking-popup.bundle.js.map
