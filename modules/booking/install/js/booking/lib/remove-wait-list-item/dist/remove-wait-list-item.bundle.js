/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,ui_notification,booking_const,booking_core,booking_provider_service_waitListService) {
	'use strict';

	const secondsToDelete = 5;
	var _balloon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("balloon");
	var _waitListItemId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("waitListItemId");
	var _secondsLeft = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("secondsLeft");
	var _cancelingTheDeletion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelingTheDeletion");
	var _interval = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("interval");
	var _removeWaitListItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeWaitListItem");
	var _startDeletion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startDeletion");
	var _getBalloonTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBalloonTitle");
	var _cancelDeletion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelDeletion");
	var _onBalloonClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBalloonClose");
	class RemoveWaitListItem {
	  constructor(waitListItemId) {
	    Object.defineProperty(this, _getBalloonTitle, {
	      value: _getBalloonTitle2
	    });
	    Object.defineProperty(this, _startDeletion, {
	      value: _startDeletion2
	    });
	    Object.defineProperty(this, _removeWaitListItem, {
	      value: _removeWaitListItem2
	    });
	    Object.defineProperty(this, _balloon, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _waitListItemId, {
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
	        void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/removeDeletingWaitListItemId`, babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId]);
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
	        void booking_provider_service_waitListService.waitListService.delete(babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId]);
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId] = waitListItemId;
	    babelHelpers.classPrivateFieldLooseBase(this, _removeWaitListItem)[_removeWaitListItem]();
	  }
	}
	function _removeWaitListItem2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _secondsLeft)[_secondsLeft] = secondsToDelete;
	  babelHelpers.classPrivateFieldLooseBase(this, _balloon)[_balloon] = BX.UI.Notification.Center.notify({
	    id: `booking-notify-remove-waitlist-item-${babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId]}`,
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
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/addDeletingWaitListItemId`, babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId]);
	}
	function _getBalloonTitle2() {
	  return main_core.Loc.getMessage('BB_BOOKING_REMOVE_BALLOON_TEXT', {
	    '#countdown#': babelHelpers.classPrivateFieldLooseBase(this, _secondsLeft)[_secondsLeft]
	  });
	}

	exports.RemoveWaitListItem = RemoveWaitListItem;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX,BX.Booking.Const,BX.Booking,BX.Booking.Provider.Service));
//# sourceMappingURL=remove-wait-list-item.bundle.js.map
