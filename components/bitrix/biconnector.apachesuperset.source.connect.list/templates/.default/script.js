/* eslint-disable */
(function (exports,main_core,main_core_events) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	/**
	 * @namespace BX.BIConnector
	 */
	var _slider = /*#__PURE__*/new WeakMap();
	var _subscribeOnEvents = /*#__PURE__*/new WeakSet();
	var _closeSlider = /*#__PURE__*/new WeakSet();
	var _onCloseSlider = /*#__PURE__*/new WeakSet();
	var SourceConnectList = function SourceConnectList() {
	  babelHelpers.classCallCheck(this, SourceConnectList);
	  _classPrivateMethodInitSpec(this, _onCloseSlider);
	  _classPrivateMethodInitSpec(this, _closeSlider);
	  _classPrivateMethodInitSpec(this, _subscribeOnEvents);
	  _classPrivateFieldInitSpec(this, _slider, {
	    writable: true,
	    value: void 0
	  });
	  babelHelpers.classPrivateFieldSet(this, _slider, BX.SidePanel.Instance.getSliderByWindow(window));
	  _classPrivateMethodGet(this, _subscribeOnEvents, _subscribeOnEvents2).call(this);
	};
	function _subscribeOnEvents2() {
	  var _this = this;
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClose', _classPrivateMethodGet(this, _onCloseSlider, _onCloseSlider2).bind(this));
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', function (event) {
	    var _event$getData = event.getData(),
	      _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	      messageEvent = _event$getData2[0];
	    if (messageEvent.getEventId() === 'BIConnector:ExternalConnection:onConnectionCreated') {
	      _classPrivateMethodGet(_this, _closeSlider, _closeSlider2).call(_this);
	    }
	  });
	}
	function _closeSlider2() {
	  babelHelpers.classPrivateFieldGet(this, _slider).close();
	}
	function _onCloseSlider2() {
	  BX.SidePanel.Instance.postMessage(window, 'BIConnector:ExternalConnectionGrid:reload', {});
	}
	main_core.Reflection.namespace('BX.BIConnector').SourceConnectList = SourceConnectList;

}((this.window = this.window || {}),BX,BX.Event));
//# sourceMappingURL=script.js.map
