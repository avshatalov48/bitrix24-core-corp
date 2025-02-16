/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_events,ui_iconSet_api_core) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	var _sizeLimit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sizeLimit");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _counterNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counterNode");
	var _getIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIcon");
	var _getLimitContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLimitContainer");
	class DocumentCounters extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _getLimitContainer, {
	      value: _getLimitContainer2
	    });
	    Object.defineProperty(this, _getIcon, {
	      value: _getIcon2
	    });
	    Object.defineProperty(this, _sizeLimit, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _counterNode, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.B2e.DocumentCounters');
	    babelHelpers.classPrivateFieldLooseBase(this, _sizeLimit)[_sizeLimit] = Number(options.documentCountersLimit);
	    babelHelpers.classPrivateFieldLooseBase(this, _counterNode)[_counterNode] = main_core.Tag.render(_t || (_t = _`<span class="sign-b2e-settings__document-counter-select">0</span>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sign-b2e-settings__document-counter">
				${0}
				<div class="sign-b2e-settings__document-counter_limit-block">
					${0}
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getIcon)[_getIcon]().render(), babelHelpers.classPrivateFieldLooseBase(this, _counterNode)[_counterNode], babelHelpers.classPrivateFieldLooseBase(this, _getLimitContainer)[_getLimitContainer]());
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  getCount() {
	    return Number(babelHelpers.classPrivateFieldLooseBase(this, _counterNode)[_counterNode].textContent);
	  }
	  update(size) {
	    babelHelpers.classPrivateFieldLooseBase(this, _counterNode)[_counterNode].textContent = size;
	    if (size >= babelHelpers.classPrivateFieldLooseBase(this, _sizeLimit)[_sizeLimit]) {
	      this.emit('limitExceeded');
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], '--alert');
	    } else {
	      this.emit('limitNotExceeded');
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], '--alert');
	    }
	  }
	}
	function _getIcon2() {
	  return new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Main.DOCUMENT,
	    size: 18,
	    color: getComputedStyle(document.body).getPropertyValue('--ui-color-palette-gray-60')
	  });
	}
	function _getLimitContainer2() {
	  return main_core.Tag.render(_t3 || (_t3 = _`<span class="sign-b2e-settings__document-counter-limit">/ ${0}</span>`), babelHelpers.classPrivateFieldLooseBase(this, _sizeLimit)[_sizeLimit]);
	}

	exports.DocumentCounters = DocumentCounters;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Event,BX.UI.IconSet));
//# sourceMappingURL=document-counters.bundle.js.map
