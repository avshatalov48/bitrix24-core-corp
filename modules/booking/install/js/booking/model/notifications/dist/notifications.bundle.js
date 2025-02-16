/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_vuex,booking_const) {
	'use strict';

	class Notifications extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.Notifications;
	  }
	  getState() {
	    return {
	      notifications: {},
	      senders: {}
	    };
	  }
	  getElementState() {
	    return {
	      type: '',
	      templates: [{
	        type: '',
	        text: '',
	        textSms: ''
	      }]
	    };
	  }
	  getGetters() {
	    return {
	      /** @function notifications/get */
	      get: state => Object.values(state.notifications),
	      /** @function notifications/getByType */
	      getByType: state => type => state.notifications[type],
	      /** @function notifications/getSenders */
	      getSenders: state => Object.values(state.senders),
	      /** @function notifications/isCurrentSenderAvailable */
	      isCurrentSenderAvailable: state => {
	        return Object.values(state.senders).some(sender => sender.canUse);
	      }
	    };
	  }
	  getActions() {
	    return {
	      /** @function notifications/upsert */
	      upsert: (store, notification) => {
	        store.commit('upsert', notification);
	      },
	      /** @function notifications/upsertMany */
	      upsertMany: (store, notifications) => {
	        notifications.forEach(notification => store.commit('upsert', notification));
	      },
	      /** @function notifications/upsertManySenders */
	      upsertManySenders: (store, senders) => {
	        senders.forEach(sender => store.commit('upsertSender', sender));
	      }
	    };
	  }
	  getMutations() {
	    return {
	      upsert: (state, notification) => {
	        var _state$notifications, _notification$type, _state$notifications$;
	        (_state$notifications$ = (_state$notifications = state.notifications)[_notification$type = notification.type]) != null ? _state$notifications$ : _state$notifications[_notification$type] = notification;
	        Object.assign(state.notifications[notification.type], notification);
	      },
	      upsertSender: (state, sender) => {
	        var _state$senders, _sender$code, _state$senders$_sende;
	        (_state$senders$_sende = (_state$senders = state.senders)[_sender$code = sender.code]) != null ? _state$senders$_sende : _state$senders[_sender$code] = sender;
	        Object.assign(state.senders[sender.code], sender);
	      }
	    };
	  }
	}

	exports.Notifications = Notifications;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX.Vue3.Vuex,BX.Booking.Const));
//# sourceMappingURL=notifications.bundle.js.map
