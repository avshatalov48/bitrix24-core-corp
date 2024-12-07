/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,stafftrack_userStatisticsLink,ui_analytics) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	var _onDataReceived = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDataReceived");
	var _render = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("render");
	var _onClickHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickHandler");
	var _renderCounter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderCounter");
	class StafftrackCheckIn {
	  // eslint-disable-next-line no-unused-private-class-members

	  constructor(params) {
	    Object.defineProperty(this, _renderCounter, {
	      value: _renderCounter2
	    });
	    Object.defineProperty(this, _onClickHandler, {
	      value: _onClickHandler2
	    });
	    Object.defineProperty(this, _render, {
	      value: _render2
	    });
	    Object.defineProperty(this, _onDataReceived, {
	      value: _onDataReceived2
	    });
	    Object.defineProperty(this, _bindEvents, {
	      value: _bindEvents2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    const settings = main_core.Extension.getSettings('timeman.stafftrack-check-in');
	    if (!settings.isCheckinEnabled) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = settings.counter;
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = {};
	    const pwtContainer = params.container.querySelector('#timeman-pwt-container');
	    if (pwtContainer) {
	      pwtContainer.before(babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]());
	    } else {
	      params.container.append(babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]());
	    }
	    this.onDataReceived = babelHelpers.classPrivateFieldLooseBase(this, _onDataReceived)[_onDataReceived].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _bindEvents)[_bindEvents]();
	  }
	}
	function _bindEvents2() {
	  BX.addCustomEvent('onTimeManDataRecieved', this.onDataReceived);
	  BX.addCustomEvent('onTimeManNeedRebuild', this.onDataReceived);
	}
	function _onDataReceived2(data) {
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = data.CHECKIN_COUNTER;
	  babelHelpers.classPrivateFieldLooseBase(this, _renderCounter)[_renderCounter]();
	}
	function _render2() {
	  const wrap = main_core.Tag.render(_t || (_t = _`
			<div class="timeman-stafftrack-check-in">
				<div class="ui-icon-set --play"></div>
				<div class="timeman-stafftrack-check-in-text">
					${0}
				</div>
				${0}
			</div>
		`), main_core.Loc.getMessage('TIMEMAN_STAFFTRACK_CHECK_IN'), babelHelpers.classPrivateFieldLooseBase(this, _renderCounter)[_renderCounter]());
	  main_core.Event.bind(wrap, 'click', babelHelpers.classPrivateFieldLooseBase(this, _onClickHandler)[_onClickHandler].bind(this));
	  return wrap;
	}
	function _onClickHandler2() {
	  if (!stafftrack_userStatisticsLink.UserStatisticsLink) {
	    return;
	  }
	  new stafftrack_userStatisticsLink.UserStatisticsLink({
	    intent: 'check-in'
	  }).show();
	  ui_analytics.sendData({
	    tool: 'checkin',
	    category: 'shift',
	    event: 'popup_open',
	    c_section: 'timeman'
	  });
	}
	function _renderCounter2() {
	  var _babelHelpers$classPr;
	  const display = main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].VALUE) ? '' : 'none';
	  const counter = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="timeman-stafftrack-check-in-counter" style="display: ${0};">
				<span class="ui-counter ${0} ui-counter-sm">
					<span class="ui-counter-inner">
						${0}
					</span>
				</span>
			</div>
		`), display, babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].CLASS, babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].VALUE);
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].counter) == null ? void 0 : _babelHelpers$classPr.replaceWith(counter);
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].counter = counter;
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].counter;
	}

	exports.StafftrackCheckIn = StafftrackCheckIn;

}((this.BX.Timeman = this.BX.Timeman || {}),BX,BX.Stafftrack,BX.UI.Analytics));
//# sourceMappingURL=stafftrack-check-in.bundle.js.map
