this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core_events,main_core,ui_notification) {
	'use strict';

	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _onSkippedPeriodChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkippedPeriodChange");
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	var _onExternalEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onExternalEvent");
	var _onSetSkipPeriod = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSetSkipPeriod");
	var _onSkippedPeriodChangeCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkippedPeriodChangeCallback");
	class TodoNotificationSkip {
	  constructor(params) {
	    Object.defineProperty(this, _onSkippedPeriodChangeCallback, {
	      value: _onSkippedPeriodChangeCallback2
	    });
	    Object.defineProperty(this, _onSetSkipPeriod, {
	      value: _onSetSkipPeriod2
	    });
	    Object.defineProperty(this, _onExternalEvent, {
	      value: _onExternalEvent2
	    });
	    Object.defineProperty(this, _bindEvents, {
	      value: _bindEvents2
	    });
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _onSkippedPeriodChange, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = params.entityTypeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _onSkippedPeriodChange)[_onSkippedPeriodChange] = params.onSkippedPeriodChange;
	    babelHelpers.classPrivateFieldLooseBase(this, _bindEvents)[_bindEvents]();
	  }
	  saveSkippedPeriod(skippedPeriod) {
	    BX.localStorage.set('BX.Crm.onCrmEntityTodoNotificationSkip', {
	      entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId],
	      period: skippedPeriod
	    }, 5);
	    main_core_events.EventEmitter.emit('BX.Crm.Activity.TodoNotification:SetSkipPeriod', {
	      entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId],
	      period: skippedPeriod
	    });
	    return main_core.ajax.runAction('crm.activity.todo.skipEntityDetailsNotification', {
	      data: {
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId],
	        period: skippedPeriod
	      }
	    }).then(() => {
	      return skippedPeriod;
	    }).catch(response => {
	      ui_notification.UI.Notification.Center.notify({
	        content: response.errors.map(item => item.message).join(', '),
	        autoHideDelay: 5000
	      });
	    });
	  }
	  showCancelPeriodNotification() {
	    const self = this;
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_CANCELED_TEXT'),
	      autoHideDelay: 3000,
	      actions: [{
	        title: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_CANCELED_BUTTON'),
	        events: {
	          click: function (event, balloon, action) {
	            balloon.close();
	            self.saveSkippedPeriod('');
	          }
	        }
	      }]
	    });
	  }
	}
	function _bindEvents2() {
	  main_core_events.EventEmitter.subscribe('onLocalStorageSet', babelHelpers.classPrivateFieldLooseBase(this, _onExternalEvent)[_onExternalEvent].bind(this));
	  main_core_events.EventEmitter.subscribe('BX.Crm.Activity.TodoNotification:SetSkipPeriod', babelHelpers.classPrivateFieldLooseBase(this, _onSetSkipPeriod)[_onSetSkipPeriod].bind(this));
	}
	function _onExternalEvent2(event) {
	  const [data] = event.getData();
	  if (data.key === 'BX.Crm.onCrmEntityTodoNotificationSkip') {
	    const eventParams = data.value;
	    if (eventParams.entityTypeId === babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _onSkippedPeriodChangeCallback)[_onSkippedPeriodChangeCallback](eventParams.period);
	    }
	  }
	}
	function _onSetSkipPeriod2(event) {
	  babelHelpers.classPrivateFieldLooseBase(this, _onSkippedPeriodChangeCallback)[_onSkippedPeriodChangeCallback](event.getData().period);
	}
	function _onSkippedPeriodChangeCallback2(period) {
	  if (main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(this, _onSkippedPeriodChange)[_onSkippedPeriodChange])) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onSkippedPeriodChange)[_onSkippedPeriodChange](period);
	  }
	}

	exports.TodoNotificationSkip = TodoNotificationSkip;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX.Event,BX,BX));
//# sourceMappingURL=todo-notification-skip.bundle.js.map
