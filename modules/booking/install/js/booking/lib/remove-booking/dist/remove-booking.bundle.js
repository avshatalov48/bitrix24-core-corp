/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,ui_notification,booking_const,booking_core,booking_provider_service_bookingService) {
	'use strict';

	const secondsToDelete = 5;
	var _balloon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("balloon");
	var _bookingId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bookingId");
	var _secondsLeft = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("secondsLeft");
	var _cancelingTheDeletion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelingTheDeletion");
	var _interval = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("interval");
	var _removeBooking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeBooking");
	var _startDeletion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startDeletion");
	var _getBalloonTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBalloonTitle");
	var _cancelDeletion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelDeletion");
	var _onBalloonClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBalloonClose");
	class RemoveBooking {
	  constructor(bookingId) {
	    Object.defineProperty(this, _getBalloonTitle, {
	      value: _getBalloonTitle2
	    });
	    Object.defineProperty(this, _startDeletion, {
	      value: _startDeletion2
	    });
	    Object.defineProperty(this, _removeBooking, {
	      value: _removeBooking2
	    });
	    Object.defineProperty(this, _balloon, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _bookingId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _secondsLeft, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cancelingTheDeletion, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _interval, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cancelDeletion, {
	      writable: true,
	      value: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _cancelingTheDeletion)[_cancelingTheDeletion] = true;
	        babelHelpers.classPrivateFieldLooseBase(this, _balloon)[_balloon].close();
	        void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/removeDeletingBooking`, babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId]);
	      }
	    });
	    Object.defineProperty(this, _onBalloonClose, {
	      writable: true,
	      value: () => {
	        clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _interval)[_interval]);
	        if (babelHelpers.classPrivateFieldLooseBase(this, _cancelingTheDeletion)[_cancelingTheDeletion]) {
	          babelHelpers.classPrivateFieldLooseBase(this, _cancelingTheDeletion)[_cancelingTheDeletion] = false;
	          return;
	        }
	        void booking_provider_service_bookingService.bookingService.delete(babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId]);
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId] = bookingId;
	    babelHelpers.classPrivateFieldLooseBase(this, _removeBooking)[_removeBooking]();
	  }
	}
	function _removeBooking2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _secondsLeft)[_secondsLeft] = secondsToDelete;
	  babelHelpers.classPrivateFieldLooseBase(this, _balloon)[_balloon] = BX.UI.Notification.Center.notify({
	    id: `booking-notify-remove-${babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId]}`,
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getBalloonTitle)[_getBalloonTitle](),
	    actions: [{
	      title: main_core.Loc.getMessage('BB_BOOKING_REMOVE_BALLOON_CANCEL'),
	      events: {
	        mouseup: babelHelpers.classPrivateFieldLooseBase(this, _cancelDeletion)[_cancelDeletion]
	      }
	    }],
	    events: {
	      onClose: babelHelpers.classPrivateFieldLooseBase(this, _onBalloonClose)[_onBalloonClose]
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _startDeletion)[_startDeletion]();
	}
	function _startDeletion2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _interval)[_interval] = setInterval(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _secondsLeft)[_secondsLeft]--;
	    babelHelpers.classPrivateFieldLooseBase(this, _balloon)[_balloon].update({
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getBalloonTitle)[_getBalloonTitle]()
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _secondsLeft)[_secondsLeft] <= 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _balloon)[_balloon].close();
	    }
	  }, 1000);
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/addDeletingBooking`, babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId]);
	}
	function _getBalloonTitle2() {
	  return main_core.Loc.getMessage('BB_BOOKING_REMOVE_BALLOON_TEXT', {
	    '#countdown#': babelHelpers.classPrivateFieldLooseBase(this, _secondsLeft)[_secondsLeft]
	  });
	}

	exports.RemoveBooking = RemoveBooking;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX,BX.Booking.Const,BX.Booking,BX.Booking.Provider.Service));
//# sourceMappingURL=remove-booking.bundle.js.map
