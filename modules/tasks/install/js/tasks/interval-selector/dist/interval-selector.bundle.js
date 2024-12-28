/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup,main_core_events) {
	'use strict';

	let _ = t => t,
	  _t;
	const DEFAULT_INTERVAL = 'days';
	const DEFAULT_INTERVALS = ['hours', 'days', 'months'];
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _intervals = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("intervals");
	var _currentInterval = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentInterval");
	var _showIntervalMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showIntervalMenu");
	var _getIntervalPhrase = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIntervalPhrase");
	var _getIntervalDuration = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIntervalDuration");
	class IntervalSelector extends main_core_events.EventEmitter {
	  constructor(params = {}) {
	    super(params);
	    Object.defineProperty(this, _getIntervalDuration, {
	      value: _getIntervalDuration2
	    });
	    Object.defineProperty(this, _getIntervalPhrase, {
	      value: _getIntervalPhrase2
	    });
	    Object.defineProperty(this, _showIntervalMenu, {
	      value: _showIntervalMenu2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _intervals, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentInterval, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Tasks.IntervalSelector');
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _intervals)[_intervals] = params.intervals || DEFAULT_INTERVALS;
	    babelHelpers.classPrivateFieldLooseBase(this, _currentInterval)[_currentInterval] = params.defaultInterval || DEFAULT_INTERVAL;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].value) {
	      this.setSuitableInterval(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].value);
	    }
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].intervalSelector = main_core.Tag.render(_t || (_t = _`
			<div class="tasks-interval-selector">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getIntervalPhrase)[_getIntervalPhrase](babelHelpers.classPrivateFieldLooseBase(this, _currentInterval)[_currentInterval]));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].intervalSelector, 'click', babelHelpers.classPrivateFieldLooseBase(this, _showIntervalMenu)[_showIntervalMenu].bind(this));
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].intervalSelector;
	  }
	  getInterval() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _currentInterval)[_currentInterval];
	  }
	  getDuration() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getIntervalDuration)[_getIntervalDuration](babelHelpers.classPrivateFieldLooseBase(this, _currentInterval)[_currentInterval]);
	  }
	  setSuitableInterval(value) {
	    const durations = babelHelpers.classPrivateFieldLooseBase(this, _intervals)[_intervals].map(interval => ({
	      interval,
	      value: value / babelHelpers.classPrivateFieldLooseBase(this, _getIntervalDuration)[_getIntervalDuration](interval)
	    }));
	    const mostSuitable = durations.reduce((acc, duration) => {
	      if (duration.value % 1 === 0 && duration.value <= acc.value) {
	        return {
	          interval: duration.interval,
	          value: duration.value
	        };
	      }
	      return acc;
	    }, {
	      value: Math.max(...durations.map(duration => duration.value))
	    });
	    this.setInterval(mostSuitable.interval);
	  }
	  setInterval(interval) {
	    babelHelpers.classPrivateFieldLooseBase(this, _currentInterval)[_currentInterval] = interval;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].intervalSelector) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].intervalSelector.innerText = babelHelpers.classPrivateFieldLooseBase(this, _getIntervalPhrase)[_getIntervalPhrase](interval);
	    }
	  }
	}
	function _showIntervalMenu2() {
	  let menu;
	  const handleScroll = () => {
	    const popup = menu.getPopupWindow();
	    popup.adjustPosition();
	    const popupRect = popup.bindElement.getBoundingClientRect();
	    if (popupRect.top > window.innerHeight || popupRect.bottom < 0) {
	      menu.close();
	    }
	  };
	  menu = main_popup.MenuManager.create({
	    id: 'tasks-flow-create-planned-completion-time-interval-menu' + Date.now(),
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].intervalSelector,
	    items: babelHelpers.classPrivateFieldLooseBase(this, _intervals)[_intervals].map(interval => ({
	      id: interval,
	      text: babelHelpers.classPrivateFieldLooseBase(this, _getIntervalPhrase)[_getIntervalPhrase](interval),
	      onclick: (e, item) => {
	        this.setInterval(item.id);
	        this.emit('intervalChanged', {
	          'interval': item.id
	        });
	        menu.close();
	      }
	    })),
	    events: {
	      onShow: () => {
	        const popup = menu.getPopupWindow();
	        const popupWidth = popup.getPopupContainer().offsetWidth;
	        const elementWidth = popup.bindElement.offsetWidth;
	        popup.setOffset({
	          offsetLeft: elementWidth / 2 - popupWidth / 2 + 4,
	          offsetTop: 5
	        });
	        popup.adjustPosition();
	        main_core.Event.bind(window, 'scroll', handleScroll, true);
	      },
	      onClose: () => {
	        menu.destroy();
	        main_core.Event.unbind(window, 'scroll', handleScroll, true);
	      }
	    }
	  });
	  menu.show();
	}
	function _getIntervalPhrase2(interval) {
	  return main_core.Loc.getMessage(`TASKS_INTERVAL_SELECTOR_${interval.toUpperCase()}`);
	}
	function _getIntervalDuration2(interval) {
	  const intervalDurations = {
	    'minutes': 60,
	    'hours': 60 * 60,
	    'days': 60 * 60 * 24,
	    'months': 60 * 60 * 24 * 30
	  };
	  return intervalDurations[interval];
	}

	exports.IntervalSelector = IntervalSelector;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX.Main,BX.Event));
//# sourceMappingURL=interval-selector.bundle.js.map
