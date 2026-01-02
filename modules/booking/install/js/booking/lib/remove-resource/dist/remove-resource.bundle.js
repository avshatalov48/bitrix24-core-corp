/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_notification,booking_const,booking_core,booking_provider_service_resourcesService,main_core,ui_dialogs_messagebox,main_popup) {
	'use strict';

	class RemoveConfirmation {
	  static confirmDelete() {
	    return new Promise(resolve => {
	      const messageBox = ui_dialogs_messagebox.MessageBox.create({
	        title: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_DELETE_TITLE'),
	        yesCaption: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_DELETE_YES'),
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        popupOptions: {
	          className: 'booking-resource-remove-confirmation',
	          minHeight: 135,
	          closeByEsc: true,
	          closeIcon: true,
	          closeIconSize: main_popup.CloseIconSize.LARGE
	        },
	        useAirDesign: true,
	        onYes: async box => {
	          box.close();
	          resolve(true);
	        },
	        onCancel: box => {
	          box.close();
	          resolve(false);
	        }
	      });
	      messageBox.show();
	    });
	  }
	  static confirmMoveFutureBooking() {
	    return new Promise(resolve => {
	      const messageBox = ui_dialogs_messagebox.MessageBox.create({
	        title: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_MOVE_FUTURE_BOOKINGS_TITLE'),
	        message: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_MOVE_FUTURE_BOOKINGS_TEXT'),
	        yesCaption: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_MOVE_FUTURE_BOOKINGS_YES'),
	        noCaption: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_MOVE_FUTURE_BOOKINGS_NO'),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	        popupOptions: {
	          className: 'booking-resource-remove-confirmation booking-resource-remove-confirmation-move-bookings --air',
	          minHeight: 176,
	          minWidth: 418,
	          closeByEsc: true,
	          closeIcon: true,
	          closeIconSize: main_popup.CloseIconSize.LARGE
	        },
	        useAirDesign: true,
	        onYes: async box => {
	          box.close();
	          resolve(true);
	        },
	        onNo: box => {
	          box.close();
	          resolve(false);
	        }
	      });
	      messageBox.show();
	    });
	  }
	  static confirmAfterMoveFutureBooking(resource) {
	    return new Promise(resolve => {
	      const messageBox = ui_dialogs_messagebox.MessageBox.create({
	        title: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_AFTER_MOVE_FUTURE_BOOKINGS_TITLE'),
	        message: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_AFTER_MOVE_FUTURE_BOOKINGS_TEXT'),
	        yesCaption: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_AFTER_MOVE_FUTURE_BOOKINGS_YES'),
	        noCaption: main_core.Loc.getMessage('BOOKING_RESOURCE_CONFIRM_AFTER_MOVE_FUTURE_BOOKINGS_NO'),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	        popupOptions: {
	          id: `booking-resource-remove-confirmation-${resource.id}`,
	          className: 'booking-resource-remove-confirmation booking-resource-remove-confirmation-after-clear --air',
	          minHeight: 176,
	          closeByEsc: true,
	          closeIcon: true,
	          closeIconSize: main_popup.CloseIconSize.LARGE
	        },
	        useAirDesign: true,
	        onYes: async box => {
	          box.close();
	          resolve(true);
	        },
	        onNo: box => {
	          box.close();
	          resolve(false);
	        }
	      });
	      messageBox.show();
	    });
	  }
	}

	const secondsToDelete = 5;
	var _resourceId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resourceId");
	var _shouldRemoveFutureBookings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldRemoveFutureBookings");
	var _futureBookingIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("futureBookingIds");
	var _isDeletionCancelled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDeletionCancelled");
	var _balloon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("balloon");
	var _secondsLeft = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("secondsLeft");
	var _countdownInterval = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("countdownInterval");
	var _runCancellableDeletion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("runCancellableDeletion");
	var _runCountdown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("runCountdown");
	var _getBalloonTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBalloonTitle");
	var _cancelDeletion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelDeletion");
	var _onBalloonClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBalloonClose");
	var _cancelRemovingResource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelRemovingResource");
	class RemoveResource {
	  constructor(resourceId) {
	    Object.defineProperty(this, _cancelRemovingResource, {
	      value: _cancelRemovingResource2
	    });
	    Object.defineProperty(this, _getBalloonTitle, {
	      value: _getBalloonTitle2
	    });
	    Object.defineProperty(this, _runCountdown, {
	      value: _runCountdown2
	    });
	    Object.defineProperty(this, _runCancellableDeletion, {
	      value: _runCancellableDeletion2
	    });
	    Object.defineProperty(this, _resourceId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _shouldRemoveFutureBookings, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _futureBookingIds, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _isDeletionCancelled, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _balloon, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _secondsLeft, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _countdownInterval, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cancelDeletion, {
	      writable: true,
	      value: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _isDeletionCancelled)[_isDeletionCancelled] = true;
	        babelHelpers.classPrivateFieldLooseBase(this, _balloon)[_balloon].close();
	        void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/removeDeletingResource`, babelHelpers.classPrivateFieldLooseBase(this, _resourceId)[_resourceId]);
	        if (babelHelpers.classPrivateFieldLooseBase(this, _futureBookingIds)[_futureBookingIds].length > 0) {
	          void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/removeDeletingBooking`, babelHelpers.classPrivateFieldLooseBase(this, _futureBookingIds)[_futureBookingIds]);
	        }
	      }
	    });
	    Object.defineProperty(this, _onBalloonClose, {
	      writable: true,
	      value: () => {
	        clearInterval(babelHelpers.classPrivateFieldLooseBase(this, _countdownInterval)[_countdownInterval]);
	        if (babelHelpers.classPrivateFieldLooseBase(this, _isDeletionCancelled)[_isDeletionCancelled]) {
	          babelHelpers.classPrivateFieldLooseBase(this, _isDeletionCancelled)[_isDeletionCancelled] = false;
	          return;
	        }
	        void booking_provider_service_resourcesService.resourceService.delete(babelHelpers.classPrivateFieldLooseBase(this, _resourceId)[_resourceId], babelHelpers.classPrivateFieldLooseBase(this, _shouldRemoveFutureBookings)[_shouldRemoveFutureBookings]);
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _resourceId)[_resourceId] = resourceId;
	  }
	  async run() {
	    await babelHelpers.classPrivateFieldLooseBase(this, _cancelRemovingResource)[_cancelRemovingResource]();
	    const hasFutureBookings = await booking_provider_service_resourcesService.resourceService.hasFutureBookings(babelHelpers.classPrivateFieldLooseBase(this, _resourceId)[_resourceId]);
	    if (hasFutureBookings) {
	      const shouldMoveFutureBookings = await RemoveConfirmation.confirmMoveFutureBooking();
	      if (shouldMoveFutureBookings) {
	        await booking_core.Core.getStore().dispatch(`${booking_const.Model.Filter}/setDeletingResourceFilter`, {
	          resourceId: babelHelpers.classPrivateFieldLooseBase(this, _resourceId)[_resourceId]
	        });
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _shouldRemoveFutureBookings)[_shouldRemoveFutureBookings] = true;
	        babelHelpers.classPrivateFieldLooseBase(this, _runCancellableDeletion)[_runCancellableDeletion]();
	      }
	      return;
	    }
	    const isDeletionConfirmed = await RemoveConfirmation.confirmDelete();
	    if (isDeletionConfirmed) {
	      babelHelpers.classPrivateFieldLooseBase(this, _runCancellableDeletion)[_runCancellableDeletion]();
	    }
	  }
	  async runAfterMoveBookings() {
	    await babelHelpers.classPrivateFieldLooseBase(this, _cancelRemovingResource)[_cancelRemovingResource]();
	    const hasFutureBookings = await booking_provider_service_resourcesService.resourceService.hasFutureBookings(babelHelpers.classPrivateFieldLooseBase(this, _resourceId)[_resourceId]);
	    if (hasFutureBookings) {
	      return;
	    }
	    const resource = booking_core.Core.getStore().getters[`${booking_const.Model.Resources}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _resourceId)[_resourceId]);
	    const isDeletionConfirmed = await RemoveConfirmation.confirmAfterMoveFutureBooking(resource);
	    if (isDeletionConfirmed) {
	      babelHelpers.classPrivateFieldLooseBase(this, _runCancellableDeletion)[_runCancellableDeletion]();
	    }
	  }
	}
	function _runCancellableDeletion2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _secondsLeft)[_secondsLeft] = secondsToDelete;
	  babelHelpers.classPrivateFieldLooseBase(this, _balloon)[_balloon] = BX.UI.Notification.Center.notify({
	    id: `booking-notify-remove-resource-${babelHelpers.classPrivateFieldLooseBase(this, _resourceId)[_resourceId]}`,
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getBalloonTitle)[_getBalloonTitle](),
	    actions: [{
	      title: main_core.Loc.getMessage('BOOKING_RESOURCE_REMOVE_BALLOON_CANCEL'),
	      events: {
	        mouseup: babelHelpers.classPrivateFieldLooseBase(this, _cancelDeletion)[_cancelDeletion]
	      }
	    }],
	    events: {
	      onClose: babelHelpers.classPrivateFieldLooseBase(this, _onBalloonClose)[_onBalloonClose]
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _runCountdown)[_runCountdown]();
	}
	function _runCountdown2() {
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/addDeletingResource`, babelHelpers.classPrivateFieldLooseBase(this, _resourceId)[_resourceId]);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _shouldRemoveFutureBookings)[_shouldRemoveFutureBookings]) {
	    const futureBookings = booking_core.Core.getStore().getters[`${booking_const.Model.Bookings}/getFutureByResourceId`](babelHelpers.classPrivateFieldLooseBase(this, _resourceId)[_resourceId]);
	    babelHelpers.classPrivateFieldLooseBase(this, _futureBookingIds)[_futureBookingIds] = futureBookings.map(booking => booking.id);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _futureBookingIds)[_futureBookingIds].length > 0) {
	      void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/addDeletingBooking`, babelHelpers.classPrivateFieldLooseBase(this, _futureBookingIds)[_futureBookingIds]);
	    }
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _countdownInterval)[_countdownInterval] = setInterval(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _secondsLeft)[_secondsLeft]--;
	    babelHelpers.classPrivateFieldLooseBase(this, _balloon)[_balloon].update({
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getBalloonTitle)[_getBalloonTitle]()
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _secondsLeft)[_secondsLeft] <= 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _balloon)[_balloon].close();
	    }
	  }, 1000);
	}
	function _getBalloonTitle2() {
	  return main_core.Loc.getMessage('BOOKING_RESOURCE_REMOVE_BALLOON_TEXT', {
	    '#COUNTDOWN#': babelHelpers.classPrivateFieldLooseBase(this, _secondsLeft)[_secondsLeft]
	  });
	}
	async function _cancelRemovingResource2() {
	  const $store = booking_core.Core.getStore();
	  if ($store.getters[`${booking_const.Model.Filter}/isDeletingResourceFilterMode`]) {
	    await Promise.all([$store.dispatch(`${booking_const.Model.Filter}/clearFilter`), $store.dispatch(`${booking_const.Model.Interface}/setPinnedResourceIds`, [])]);
	  }
	}

	exports.RemoveResource = RemoveResource;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX.Booking.Const,BX.Booking,BX.Booking.Provider.Service,BX,BX.UI.Dialogs,BX.Main));
//# sourceMappingURL=remove-resource.bundle.js.map
