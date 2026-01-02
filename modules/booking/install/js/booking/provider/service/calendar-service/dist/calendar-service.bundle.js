/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,main_core,booking_core,booking_const,booking_lib_apiClient,booking_lib_bookingFilter) {
	'use strict';

	async function* requestNextBookingDatesGenerator(startDateTs, limitDateTs, filter, request) {
	  const direction = startDateTs < limitDateTs ? 1 : -1;
	  const getWithin = getWithinBuilder(3, direction, limitDateTs);
	  let {
	    fromDate,
	    toDate
	  } = getWithin(new Date(startDateTs));
	  const isLimit = toDateTs => {
	    return direction === 1 ? toDateTs > limitDateTs : toDateTs < limitDateTs;
	  };
	  while (!isLimit(fromDate.getTime())) {
	    const data = await request({
	      ...filter,
	      WITHIN: {
	        DATE_FROM: fromDate.getTime() / 1000,
	        DATE_TO: toDate.getTime() / 1000
	      }
	    });
	    const nextDates = getWithin(direction === 1 ? toDate : fromDate);
	    fromDate = nextDates.fromDate;
	    toDate = nextDates.toDate;
	    if (data.foundDates.length > 0) {
	      yield data;
	    }
	  }
	}
	function getWithinBuilder(duration, direction, limitDateTs) {
	  let intervalDuration = duration;
	  return function (firstDate) {
	    intervalDuration++;
	    const secondDate = new Date(firstDate);
	    secondDate.setMonth(secondDate.getMonth() + (intervalDuration + 1) * direction);
	    secondDate.setDate(0);
	    if (direction === 1) {
	      return {
	        fromDate: firstDate,
	        toDate: secondDate.getDate() > limitDateTs ? new Date(limitDateTs) : secondDate
	      };
	    }
	    const fromDate = secondDate.getTime() < limitDateTs ? new Date(limitDateTs) : secondDate;
	    const toDate = firstDate;
	    if (toDate.getTime() === fromDate.getTime()) {
	      toDate.setDate(toDate.getDate() + 1);
	    }
	    return {
	      fromDate,
	      toDate
	    };
	  };
	}

	var _filterMarksRequests = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filterMarksRequests");
	var _filterBookingDatesCountRequests = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filterBookingDatesCountRequests");
	var _lastFilterMarksRequest = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lastFilterMarksRequest");
	var _freeMarksRequests = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("freeMarksRequests");
	var _lastFreeMarksRequest = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lastFreeMarksRequest");
	var _counterMarksRequests = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counterMarksRequests");
	var _requestLoadMarks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestLoadMarks");
	var _requestFilterMarks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestFilterMarks");
	var _requestNextBookingDates = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestNextBookingDates");
	var _requestCounterMarks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestCounterMarks");
	var _requestBookingsDateCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestBookingsDateCount");
	var _offset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("offset");
	var _timezone = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timezone");
	class CalendarService {
	  constructor() {
	    Object.defineProperty(this, _timezone, {
	      get: _get_timezone,
	      set: void 0
	    });
	    Object.defineProperty(this, _offset, {
	      get: _get_offset,
	      set: void 0
	    });
	    Object.defineProperty(this, _requestBookingsDateCount, {
	      value: _requestBookingsDateCount2
	    });
	    Object.defineProperty(this, _requestCounterMarks, {
	      value: _requestCounterMarks2
	    });
	    Object.defineProperty(this, _requestNextBookingDates, {
	      value: _requestNextBookingDates2
	    });
	    Object.defineProperty(this, _requestFilterMarks, {
	      value: _requestFilterMarks2
	    });
	    Object.defineProperty(this, _requestLoadMarks, {
	      value: _requestLoadMarks2
	    });
	    Object.defineProperty(this, _filterMarksRequests, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _filterBookingDatesCountRequests, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _lastFilterMarksRequest, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _freeMarksRequests, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _lastFreeMarksRequest, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _counterMarksRequests, {
	      writable: true,
	      value: {}
	    });
	  }
	  clearCache(timestamp, resourceId) {
	    Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _filterMarksRequests)[_filterMarksRequests]).forEach(key => {
	      const {
	        dateTs,
	        sortedResources
	      } = JSON.parse(key);
	      if (timestamp === dateTs) {
	        delete babelHelpers.classPrivateFieldLooseBase(this, _filterMarksRequests)[_filterMarksRequests][key];
	      }
	      for (const ids of sortedResources) {
	        if (ids.includes(resourceId)) {
	          delete babelHelpers.classPrivateFieldLooseBase(this, _filterMarksRequests)[_filterMarksRequests][key];
	          break;
	        }
	      }
	    });
	  }
	  clearFilterCache() {
	    babelHelpers.classPrivateFieldLooseBase(this, _filterMarksRequests)[_filterMarksRequests] = {};
	    this.clearDataCountCache();
	  }
	  clearDataCountCache() {
	    babelHelpers.classPrivateFieldLooseBase(this, _filterBookingDatesCountRequests)[_filterBookingDatesCountRequests] = {};
	  }
	  async loadMarks(dateTs, resources) {
	    try {
	      var _babelHelpers$classPr, _babelHelpers$classPr2;
	      if (!main_core.Type.isArrayFilled(resources)) {
	        return;
	      }
	      const sortedResources = resources.map(ids => {
	        return ids.sort((a, b) => a - b);
	      }).sort((a, b) => a[0] - b[0]);
	      const key = JSON.stringify({
	        dateTs,
	        sortedResources
	      });
	      (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _freeMarksRequests)[_freeMarksRequests])[key]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[key] = babelHelpers.classPrivateFieldLooseBase(this, _requestLoadMarks)[_requestLoadMarks](dateTs, resources);
	      babelHelpers.classPrivateFieldLooseBase(this, _lastFreeMarksRequest)[_lastFreeMarksRequest] = babelHelpers.classPrivateFieldLooseBase(this, _freeMarksRequests)[_freeMarksRequests][key];
	      const freeMarks = await babelHelpers.classPrivateFieldLooseBase(this, _freeMarksRequests)[_freeMarksRequests][key];
	      if (babelHelpers.classPrivateFieldLooseBase(this, _freeMarksRequests)[_freeMarksRequests][key] !== babelHelpers.classPrivateFieldLooseBase(this, _lastFreeMarksRequest)[_lastFreeMarksRequest]) {
	        return;
	      }
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setFreeMarks`, freeMarks);
	    } catch (error) {
	      console.error('BookingService: loadMarks error', error);
	    }
	  }
	  async loadFilterMarks(fields, inFuture = false) {
	    try {
	      var _babelHelpers$classPr3, _babelHelpers$classPr4;
	      const filter = inFuture ? booking_lib_bookingFilter.bookingDateCountFilter.prepareFutureFilter(fields, true) : booking_lib_bookingFilter.bookingFilter.prepareFilter(fields, true);
	      const key = JSON.stringify(filter);
	      (_babelHelpers$classPr4 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _filterMarksRequests)[_filterMarksRequests])[key]) != null ? _babelHelpers$classPr4 : _babelHelpers$classPr3[key] = babelHelpers.classPrivateFieldLooseBase(this, _requestFilterMarks)[_requestFilterMarks](filter);
	      babelHelpers.classPrivateFieldLooseBase(this, _lastFilterMarksRequest)[_lastFilterMarksRequest] = babelHelpers.classPrivateFieldLooseBase(this, _filterMarksRequests)[_filterMarksRequests][key];
	      const {
	        foundDates,
	        foundDatesWithCounters
	      } = await babelHelpers.classPrivateFieldLooseBase(this, _filterMarksRequests)[_filterMarksRequests][key];
	      if (babelHelpers.classPrivateFieldLooseBase(this, _filterMarksRequests)[_filterMarksRequests][key] !== babelHelpers.classPrivateFieldLooseBase(this, _lastFilterMarksRequest)[_lastFilterMarksRequest]) {
	        return;
	      }
	      await Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.Filter}/addFilterDates`, foundDates), booking_core.Core.getStore().dispatch(`${booking_const.Model.Filter}/setFilteredMarks`, foundDates), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setCounterMarks`, foundDatesWithCounters)]);
	    } catch (error) {
	      console.error('BookingService: loadFilterMarks error', error);
	    }
	  }
	  async loadCounterMarks(dateTs, force = false) {
	    try {
	      const key = dateTs.toString();
	      if (force) {
	        babelHelpers.classPrivateFieldLooseBase(this, _counterMarksRequests)[_counterMarksRequests][key] = babelHelpers.classPrivateFieldLooseBase(this, _requestCounterMarks)[_requestCounterMarks](dateTs);
	      } else {
	        var _babelHelpers$classPr5, _babelHelpers$classPr6;
	        (_babelHelpers$classPr6 = (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _counterMarksRequests)[_counterMarksRequests])[key]) != null ? _babelHelpers$classPr6 : _babelHelpers$classPr5[key] = babelHelpers.classPrivateFieldLooseBase(this, _requestCounterMarks)[_requestCounterMarks](dateTs);
	      }
	      const counterMarks = await babelHelpers.classPrivateFieldLooseBase(this, _counterMarksRequests)[_counterMarksRequests][key];
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setCounterMarks`, counterMarks);
	    } catch (error) {
	      console.error('CalendarService: loadCounterMarks error', error);
	    }
	  }
	  async loadBookingsDateCount(fields, futureOnly = false) {
	    try {
	      var _babelHelpers$classPr7, _babelHelpers$classPr8;
	      const filter = futureOnly ? booking_lib_bookingFilter.bookingDateCountFilter.prepareFutureOnlyFilter(fields, true) : booking_lib_bookingFilter.bookingDateCountFilter.prepareUndatedFilter(fields, true);
	      const key = JSON.stringify(filter);
	      (_babelHelpers$classPr8 = (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _filterBookingDatesCountRequests)[_filterBookingDatesCountRequests])[key]) != null ? _babelHelpers$classPr8 : _babelHelpers$classPr7[key] = babelHelpers.classPrivateFieldLooseBase(this, _requestBookingsDateCount)[_requestBookingsDateCount](filter);
	      const data = await babelHelpers.classPrivateFieldLooseBase(this, _filterBookingDatesCountRequests)[_filterBookingDatesCountRequests][key];
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.Filter}/setDatesCount`, data);
	    } catch (error) {
	      console.error('CalendarService: loadBookingDatesCount error', error);
	    }
	  }
	  async loadNextFilterMarks(fields, startDateTs, limitDateTs) {
	    const $store = booking_core.Core.getStore();
	    try {
	      const filter = booking_lib_bookingFilter.bookingFilter.prepareFilter(fields, true);
	      $store.dispatch(`${booking_const.Model.Filter}/setFetchingNextDate`, true);
	      const {
	        foundDates,
	        foundDatesWithCounters
	      } = await babelHelpers.classPrivateFieldLooseBase(this, _requestNextBookingDates)[_requestNextBookingDates](filter, startDateTs, limitDateTs);
	      await Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.Filter}/addFilterDates`, foundDates), booking_core.Core.getStore().dispatch(`${booking_const.Model.Filter}/setFilteredMarks`, foundDates), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setCounterMarks`, foundDatesWithCounters)]);
	    } catch (error) {
	      console.error('BookingService: loadNextFilterMarks error', error);
	    } finally {
	      $store.dispatch(`${booking_const.Model.Filter}/setFetchingNextDate`, false);
	    }
	  }
	}
	async function _requestLoadMarks2(dateTs, resources) {
	  const now = new Date();
	  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
	  const minTs = today.getTime() + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset];
	  const dateFromTs = Math.max(minTs, dateTs) / 1000;
	  const dateToTs = new Date(dateTs).setMonth(new Date(dateTs).getMonth() + 1) / 1000;
	  if (dateToTs <= minTs / 1000) {
	    return [];
	  }
	  const {
	    freeDates
	  } = await new booking_lib_apiClient.ApiClient().post('Calendar.getResourceOccupation', {
	    timezone: babelHelpers.classPrivateFieldLooseBase(this, _timezone)[_timezone],
	    dateFromTs,
	    dateToTs,
	    resources
	  });
	  return freeDates;
	}
	function _requestFilterMarks2(filter) {
	  return new booking_lib_apiClient.ApiClient().post('Calendar.getBookingsDates', {
	    timezone: babelHelpers.classPrivateFieldLooseBase(this, _timezone)[_timezone],
	    dateFromTs: filter.WITHIN.DATE_FROM,
	    dateToTs: filter.WITHIN.DATE_TO,
	    filter
	  });
	}
	async function _requestNextBookingDates2(filter, startTs, limitDateTs) {
	  const getBookingDatesGenerator = requestNextBookingDatesGenerator(startTs, limitDateTs, filter, babelHelpers.classPrivateFieldLooseBase(this, _requestFilterMarks)[_requestFilterMarks].bind(this));
	  for await (const data of getBookingDatesGenerator) {
	    if (data.foundDates.length > 0) {
	      return data;
	    }
	  }
	  return {
	    foundDates: [],
	    foundDatesWithCounters: []
	  };
	}
	async function _requestCounterMarks2(dateTs) {
	  const dateFromTs = dateTs / 1000;
	  const dateToTs = new Date(dateTs).setMonth(new Date(dateTs).getMonth() + 1) / 1000;
	  const {
	    foundDatesWithCounters
	  } = await new booking_lib_apiClient.ApiClient().post('Calendar.getBookingsDates', {
	    timezone: babelHelpers.classPrivateFieldLooseBase(this, _timezone)[_timezone],
	    dateFromTs,
	    dateToTs,
	    filter: {
	      HAS_COUNTERS_USER_ID: 1
	    }
	  });
	  return foundDatesWithCounters;
	}
	function _requestBookingsDateCount2(filter) {
	  return new booking_lib_apiClient.ApiClient().post('Calendar.getBookingsDatesCount', {
	    timezone: babelHelpers.classPrivateFieldLooseBase(this, _timezone)[_timezone],
	    filter
	  });
	}
	function _get_offset() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/offset`];
	}
	function _get_timezone() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/timezone`];
	}
	const calendarService = new CalendarService();

	exports.calendarService = calendarService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX,BX.Booking,BX.Booking.Const,BX.Booking.Lib,BX.Booking.Lib));
//# sourceMappingURL=calendar-service.bundle.js.map
