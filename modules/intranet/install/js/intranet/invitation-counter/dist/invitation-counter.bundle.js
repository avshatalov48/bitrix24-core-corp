/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_counterpanel,main_core,main_core_events) {
	'use strict';

	var _counterPanel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counterPanel");
	var _presetRelation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("presetRelation");
	var _onReceiveCounterValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onReceiveCounterValue");
	var _subscribeFilterEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeFilterEvents");
	var _prepareItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareItems");
	class InvitationCounter {
	  constructor(options) {
	    Object.defineProperty(this, _prepareItems, {
	      value: _prepareItems2
	    });
	    Object.defineProperty(this, _subscribeFilterEvents, {
	      value: _subscribeFilterEvents2
	    });
	    Object.defineProperty(this, _onReceiveCounterValue, {
	      value: _onReceiveCounterValue2
	    });
	    Object.defineProperty(this, _counterPanel, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _presetRelation, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _counterPanel)[_counterPanel] = new ui_counterpanel.CounterPanel({
	      target: options.target,
	      items: babelHelpers.classPrivateFieldLooseBase(this, _prepareItems)[_prepareItems](options.items),
	      title: options.title,
	      multiselect: options.multiselect === true
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _presetRelation)[_presetRelation] = main_core.Type.isObject(options.presetRelation) ? options.presetRelation : null;
	    BX.addCustomEvent("onPullEvent-main", babelHelpers.classPrivateFieldLooseBase(this, _onReceiveCounterValue)[_onReceiveCounterValue].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeFilterEvents)[_subscribeFilterEvents](options.filterEvents);
	  }
	  getCounterPanel() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _counterPanel)[_counterPanel];
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _counterPanel)[_counterPanel].init();
	  }
	}
	function _onReceiveCounterValue2(command, params) {
	  if (command === "user_counter" && params[BX.message("SITE_ID")]) {
	    const counters = BX.clone(params[BX.message('SITE_ID')]);
	    babelHelpers.classPrivateFieldLooseBase(this, _counterPanel)[_counterPanel].getItems().forEach(counterItem => {
	      const counterValue = counters[counterItem.id];
	      if (!main_core.Type.isNumber(counterValue)) {
	        return;
	      }
	      counterItem.updateValue(counterValue);
	      counterItem.updateColor(counterValue > 0 ? 'DANGER' : 'THEME');
	    });
	  }
	}
	function _subscribeFilterEvents2(events) {
	  if (main_core.Type.isObject(events)) {
	    for (const eventType in events) {
	      const handler = events[eventType];
	      if (main_core.Type.isFunction(handler)) {
	        main_core_events.EventEmitter.subscribe('BX.Main.Filter:' + eventType, handler);
	      }
	    }
	  }
	}
	function _prepareItems2(items) {
	  return items.map((value, index) => {
	    if (main_core.Type.isNil(value.id)) {
	      throw new Error('Field "id" is required');
	    }
	    value.value = main_core.Type.isNumber(value.value) ? value.value : 0;
	    value.color = value.value > 0 ? 'DANGER' : 'THEME';
	    return value;
	  });
	}

	exports.InvitationCounter = InvitationCounter;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.UI,BX,BX.Event));
//# sourceMappingURL=invitation-counter.bundle.js.map
