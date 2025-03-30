/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,booking_core,booking_const,booking_lib_duration) {
	'use strict';

	var _selectedDateTs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedDateTs");
	var _offset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("offset");
	var _zoom = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("zoom");
	var _resourcesIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resourcesIds");
	var _fromHour = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fromHour");
	var _toHour = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toHour");
	var _offHoursExpanded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("offHoursExpanded");
	class Grid {
	  constructor() {
	    Object.defineProperty(this, _offHoursExpanded, {
	      get: _get_offHoursExpanded,
	      set: void 0
	    });
	    Object.defineProperty(this, _toHour, {
	      get: _get_toHour,
	      set: void 0
	    });
	    Object.defineProperty(this, _fromHour, {
	      get: _get_fromHour,
	      set: void 0
	    });
	    Object.defineProperty(this, _resourcesIds, {
	      get: _get_resourcesIds,
	      set: void 0
	    });
	    Object.defineProperty(this, _zoom, {
	      get: _get_zoom,
	      set: void 0
	    });
	    Object.defineProperty(this, _offset, {
	      get: _get_offset,
	      set: void 0
	    });
	    Object.defineProperty(this, _selectedDateTs, {
	      get: _get_selectedDateTs,
	      set: void 0
	    });
	  }
	  calculateLeft(resourceId) {
	    const cellWidth = 280 * babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	    const indexOfResource = babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds].indexOf(resourceId);
	    return indexOfResource * cellWidth;
	  }
	  calculateTop(fromTs) {
	    const hourHeight = 50 * babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	    const from = new Date(Math.max(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs], fromTs + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset]));
	    const bookingMinutes = from.getHours() * 60 + from.getMinutes();
	    const fromMinutes = babelHelpers.classPrivateFieldLooseBase(this, _fromHour)[_fromHour] * 60;
	    return (bookingMinutes - fromMinutes) * (hourHeight / 60);
	  }
	  calculateHeight(fromTs, toTs) {
	    const hourHeight = 50 * babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	    const minHeight = hourHeight / 4;
	    const from = Math.max(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs], fromTs + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset]);
	    const to = Math.min(new Date(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs]).setHours(24), toTs + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset]);
	    return Math.max((to - from) / booking_lib_duration.Duration.getUnitDurations().H * hourHeight, minHeight);
	  }
	  calculateRealHeight(fromTs, toTs) {
	    const hourHeight = 50 * babelHelpers.classPrivateFieldLooseBase(this, _zoom)[_zoom];
	    const minHeight = hourHeight / 4;
	    const minTs = new Date(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs]).setHours(babelHelpers.classPrivateFieldLooseBase(this, _offHoursExpanded)[_offHoursExpanded] ? 0 : babelHelpers.classPrivateFieldLooseBase(this, _fromHour)[_fromHour]);
	    const maxTs = new Date(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs]).setHours(babelHelpers.classPrivateFieldLooseBase(this, _offHoursExpanded)[_offHoursExpanded] ? 24 : babelHelpers.classPrivateFieldLooseBase(this, _toHour)[_toHour]);
	    const from = Math.max(minTs, fromTs + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset]);
	    const to = Math.min(maxTs, toTs + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset]);
	    return Math.max((to - from) / booking_lib_duration.Duration.getUnitDurations().H * hourHeight, minHeight);
	  }
	}
	function _get_selectedDateTs() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/selectedDateTs`] + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset];
	}
	function _get_offset() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/offset`];
	}
	function _get_zoom() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/zoom`];
	}
	function _get_resourcesIds() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/resourcesIds`];
	}
	function _get_fromHour() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/fromHour`];
	}
	function _get_toHour() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/toHour`];
	}
	function _get_offHoursExpanded() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/offHoursExpanded`];
	}
	const grid = new Grid();

	exports.grid = grid;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX.Booking,BX.Booking.Const,BX.Booking.Lib));
//# sourceMappingURL=grid.bundle.js.map
