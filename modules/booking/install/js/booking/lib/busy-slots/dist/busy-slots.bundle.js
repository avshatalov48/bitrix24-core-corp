/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,booking_core,booking_const,booking_lib_duration,booking_provider_service_resourceDialogService,booking_lib_resourcesDateCache) {
	'use strict';

	const minBookingViewMs = 15 * 60 * 1000;
	var _busySlots = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("busySlots");
	var _getBookings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBookings");
	var _getIntersectingBookings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIntersectingBookings");
	var _selectedWeekDay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedWeekDay");
	var _selectedDateTs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedDateTs");
	var _offset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("offset");
	var _timezoneOffset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timezoneOffset");
	var _resourcesIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resourcesIds");
	var _intersections = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("intersections");
	var _draggedBooking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedBooking");
	var _draggedBookingId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedBookingId");
	var _draggedBookingResourceId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedBookingResourceId");
	var _loadIntersections = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadIntersections");
	var _calculateOffHoursBusySlots = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calculateOffHoursBusySlots");
	var _calculateIntersectionBusySlots = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calculateIntersectionBusySlots");
	var _calculateMinutesRange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calculateMinutesRange");
	var _subtractRanges = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subtractRanges");
	var _rangesOverlap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rangesOverlap");
	var _getResource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getResource");
	var _getNextDay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNextDay");
	var _getPreviousDay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPreviousDay");
	class BusySlots {
	  constructor() {
	    Object.defineProperty(this, _getPreviousDay, {
	      value: _getPreviousDay2
	    });
	    Object.defineProperty(this, _getNextDay, {
	      value: _getNextDay2
	    });
	    Object.defineProperty(this, _getResource, {
	      value: _getResource2
	    });
	    Object.defineProperty(this, _rangesOverlap, {
	      value: _rangesOverlap2
	    });
	    Object.defineProperty(this, _subtractRanges, {
	      value: _subtractRanges2
	    });
	    Object.defineProperty(this, _calculateMinutesRange, {
	      value: _calculateMinutesRange2
	    });
	    Object.defineProperty(this, _calculateIntersectionBusySlots, {
	      value: _calculateIntersectionBusySlots2
	    });
	    Object.defineProperty(this, _calculateOffHoursBusySlots, {
	      value: _calculateOffHoursBusySlots2
	    });
	    Object.defineProperty(this, _loadIntersections, {
	      value: _loadIntersections2
	    });
	    Object.defineProperty(this, _draggedBookingResourceId, {
	      get: _get_draggedBookingResourceId,
	      set: void 0
	    });
	    Object.defineProperty(this, _draggedBookingId, {
	      get: _get_draggedBookingId,
	      set: void 0
	    });
	    Object.defineProperty(this, _draggedBooking, {
	      get: _get_draggedBooking,
	      set: void 0
	    });
	    Object.defineProperty(this, _intersections, {
	      get: _get_intersections,
	      set: void 0
	    });
	    Object.defineProperty(this, _resourcesIds, {
	      get: _get_resourcesIds,
	      set: void 0
	    });
	    Object.defineProperty(this, _timezoneOffset, {
	      get: _get_timezoneOffset,
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
	    Object.defineProperty(this, _selectedWeekDay, {
	      get: _get_selectedWeekDay,
	      set: void 0
	    });
	    Object.defineProperty(this, _getIntersectingBookings, {
	      value: _getIntersectingBookings2
	    });
	    Object.defineProperty(this, _getBookings, {
	      value: _getBookings2
	    });
	    Object.defineProperty(this, _busySlots, {
	      writable: true,
	      value: []
	    });
	  }
	  async loadBusySlots() {
	    await babelHelpers.classPrivateFieldLooseBase(this, _loadIntersections)[_loadIntersections]();
	    void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/clearDisabledBusySlots`);
	    void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/clearBusySlots`);
	    const resourcesWithIntersections = Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _intersections)[_intersections]).flatMap(key => {
	      const resourceId = Number(key);
	      if (resourceId > 0) {
	        return resourceId;
	      }
	      return babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds];
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _busySlots)[_busySlots] = [...babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds].flatMap(resourceId => babelHelpers.classPrivateFieldLooseBase(this, _calculateOffHoursBusySlots)[_calculateOffHoursBusySlots](resourceId)), ...resourcesWithIntersections.flatMap(resourceId => babelHelpers.classPrivateFieldLooseBase(this, _calculateIntersectionBusySlots)[_calculateIntersectionBusySlots](resourceId))];
	    return booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/upsertBusySlotMany`, babelHelpers.classPrivateFieldLooseBase(this, _busySlots)[_busySlots]);
	  }
	  filterSlotRanges(slotRanges) {
	    return slotRanges.map(({
	      from,
	      to
	    }) => ({
	      from,
	      to
	    })).sort((a, b) => a.from - b.from).reduce((acc, {
	      from,
	      to
	    }) => {
	      const last = acc.length - 1;
	      if (acc[last] && acc[last].to >= from) {
	        if (acc[last].to <= to) {
	          acc[last].to = to;
	        }
	      } else {
	        acc.push({
	          from,
	          to
	        });
	      }
	      return acc;
	    }, []).filter(({
	      from,
	      to
	    }) => to - from > 0);
	  }
	}
	function _getBookings2() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Bookings}/getByDateAndResources`](babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs], babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds]);
	}
	function _getIntersectingBookings2(resourcesIds) {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Bookings}/getByDateAndResources`](babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs], resourcesIds);
	}
	function _get_selectedWeekDay() {
	  return booking_const.DateFormat.WeekDays[new Date(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs] + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset]).getDay()];
	}
	function _get_selectedDateTs() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/selectedDateTs`];
	}
	function _get_offset() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/offset`];
	}
	function _get_timezoneOffset() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/timezoneOffset`];
	}
	function _get_resourcesIds() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/resourcesIds`];
	}
	function _get_intersections() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _draggedBooking)[_draggedBooking]) {
	    const draggedIds = [...babelHelpers.classPrivateFieldLooseBase(this, _draggedBooking)[_draggedBooking].resourcesIds];
	    const notDraggedIds = draggedIds.filter(id => id !== babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingResourceId)[_draggedBookingResourceId]);
	    return {
	      ...[...babelHelpers.classPrivateFieldLooseBase(this, _resourcesIds)[_resourcesIds]].reduce((acc, id) => ({
	        ...acc,
	        [id]: notDraggedIds
	      }), {}),
	      ...notDraggedIds.reduce((acc, id) => ({
	        ...acc,
	        [id]: draggedIds
	      }), {})
	    };
	  }
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/intersections`];
	}
	function _get_draggedBooking() {
	  var _Core$getStore$getter;
	  return (_Core$getStore$getter = booking_core.Core.getStore().getters[`${booking_const.Model.Bookings}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingId)[_draggedBookingId])) != null ? _Core$getStore$getter : null;
	}
	function _get_draggedBookingId() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/draggedBookingId`] || booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/resizedBookingId`];
	}
	function _get_draggedBookingResourceId() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/draggedBookingResourceId`];
	}
	async function _loadIntersections2() {
	  const selectedResourceIds = [...new Set(Object.values(babelHelpers.classPrivateFieldLooseBase(this, _intersections)[_intersections]).flat())];
	  const dateTs = babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs] / 1000;
	  const loadedResourcesIds = new Set(booking_lib_resourcesDateCache.resourcesDateCache.getIdsByDateTs(dateTs));
	  const idsToLoad = selectedResourceIds.filter(id => !loadedResourcesIds.has(id));
	  await booking_provider_service_resourceDialogService.resourceDialogService.loadByIds(idsToLoad, dateTs);
	}
	function _calculateOffHoursBusySlots2(resourceId) {
	  const resource = babelHelpers.classPrivateFieldLooseBase(this, _getResource)[_getResource](resourceId);
	  if (resource.slotRanges.length === 0) {
	    return [];
	  }
	  const bookingRanges = babelHelpers.classPrivateFieldLooseBase(this, _getBookings)[_getBookings]().filter(booking => booking.resourcesIds.includes(resourceId)).map(booking => babelHelpers.classPrivateFieldLooseBase(this, _calculateMinutesRange)[_calculateMinutesRange](booking));
	  const minutesInDay = booking_lib_duration.Duration.getUnitDurations().d / booking_lib_duration.Duration.getUnitDurations().i;
	  const slotRanges = resource.slotRanges.map(slotRange => {
	    const timeZone = slotRange.timezone;
	    const date = new Date(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs]);
	    const dateInTimezone = new Date(date.toLocaleString('en-US', {
	      timeZone
	    }));
	    const dateInUTC = new Date(date.toLocaleString('en-US', {
	      timeZone: 'UTC'
	    }));
	    const timezoneOffset = (dateInTimezone.getTime() - dateInUTC.getTime()) / 1000;
	    const minutesOffset = (babelHelpers.classPrivateFieldLooseBase(this, _timezoneOffset)[_timezoneOffset] - timezoneOffset) / 60;
	    return {
	      ...slotRange,
	      from: slotRange.from + minutesOffset,
	      to: slotRange.to + minutesOffset
	    };
	  }).map(slotRange => {
	    if (slotRange.from > minutesInDay) {
	      return {
	        ...slotRange,
	        from: slotRange.from - minutesInDay,
	        to: slotRange.to - minutesInDay,
	        weekDays: slotRange.weekDays.map(weekDay => babelHelpers.classPrivateFieldLooseBase(this, _getNextDay)[_getNextDay](weekDay))
	      };
	    }
	    if (slotRange.to < 0) {
	      return {
	        ...slotRange,
	        from: slotRange.from + minutesInDay,
	        to: slotRange.to + minutesInDay,
	        weekDays: slotRange.weekDays.map(weekDay => babelHelpers.classPrivateFieldLooseBase(this, _getPreviousDay)[_getPreviousDay](weekDay))
	      };
	    }
	    return slotRange;
	  }).flatMap(slotRange => {
	    if (slotRange.from < 0) {
	      return [{
	        ...slotRange,
	        from: 0
	      }, ...slotRange.weekDays.map(weekDay => ({
	        ...slotRange,
	        from: minutesInDay + slotRange.from,
	        to: minutesInDay,
	        weekDays: [babelHelpers.classPrivateFieldLooseBase(this, _getPreviousDay)[_getPreviousDay](weekDay)]
	      }))];
	    }
	    if (slotRange.to > minutesInDay) {
	      return [{
	        ...slotRange,
	        to: minutesInDay
	      }, ...slotRange.weekDays.map(weekDay => ({
	        ...slotRange,
	        from: 0,
	        to: slotRange.to - minutesInDay,
	        weekDays: [babelHelpers.classPrivateFieldLooseBase(this, _getNextDay)[_getNextDay](weekDay)]
	      }))];
	    }
	    return slotRange;
	  }).filter(slotRange => slotRange.weekDays.includes(babelHelpers.classPrivateFieldLooseBase(this, _selectedWeekDay)[_selectedWeekDay]));
	  const freeRanges = this.filterSlotRanges([...slotRanges, ...bookingRanges]);
	  const busyRanges = [0, ...freeRanges.flatMap(({
	    from,
	    to
	  }) => [from, to]), 24 * 60].reduce((acc, minutes, index) => {
	    var _acc$chunkIndex;
	    const chunkIndex = Math.floor(index / 2);
	    (_acc$chunkIndex = acc[chunkIndex]) != null ? _acc$chunkIndex : acc[chunkIndex] = [];
	    acc[chunkIndex].push(minutes);
	    return acc;
	  }, []);
	  return busyRanges.filter(([from, to]) => to - from > 0).map(([from, to]) => {
	    const fromTs = new Date(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs]).setMinutes(from);
	    const toTs = new Date(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs]).setMinutes(to);
	    const id = `${resourceId}-${fromTs}-${toTs}`;
	    const type = booking_const.BusySlot.OffHours;
	    return {
	      id,
	      fromTs,
	      toTs,
	      resourceId,
	      type
	    };
	  });
	}
	function _calculateIntersectionBusySlots2(resourceId) {
	  var _babelHelpers$classPr, _babelHelpers$classPr2;
	  const resource = babelHelpers.classPrivateFieldLooseBase(this, _getResource)[_getResource](resourceId);
	  if (resource.slotRanges.length === 0) {
	    return [];
	  }
	  const intersectingResourcesIds = [...((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _intersections)[_intersections][0]) != null ? _babelHelpers$classPr : []), ...((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _intersections)[_intersections][resourceId]) != null ? _babelHelpers$classPr2 : [])];
	  const intersectingBookings = babelHelpers.classPrivateFieldLooseBase(this, _getIntersectingBookings)[_getIntersectingBookings](intersectingResourcesIds).filter(booking => {
	    const notCurrentResource = !booking.resourcesIds.includes(resourceId);
	    const isNotDragged = booking.id !== babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingId)[_draggedBookingId];
	    return notCurrentResource && isNotDragged;
	  });
	  const intersectingBookingRanges = intersectingBookings.map(booking => babelHelpers.classPrivateFieldLooseBase(this, _calculateMinutesRange)[_calculateMinutesRange](booking));
	  if (intersectingBookingRanges.length === 0) {
	    return [];
	  }
	  const bookingRanges = babelHelpers.classPrivateFieldLooseBase(this, _getBookings)[_getBookings]().filter(booking => booking.resourcesIds.includes(resourceId)).map(booking => babelHelpers.classPrivateFieldLooseBase(this, _calculateMinutesRange)[_calculateMinutesRange](booking));
	  const busyRanges = intersectingBookingRanges.flatMap(intersectingRange => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _subtractRanges)[_subtractRanges](intersectingRange, bookingRanges);
	  });
	  return busyRanges.map(({
	    from,
	    to,
	    id
	  }) => {
	    const fromTs = new Date(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs]).setMinutes(from);
	    const toTs = new Date(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs]).setMinutes(to);
	    const type = booking_const.BusySlot.Intersection;
	    const booking = intersectingBookings.find(intersectingBooking => intersectingBooking.id === id);
	    const intersectingResourceId = booking ? booking.resourcesIds.find(it => intersectingResourcesIds.includes(it)) : 0;
	    return {
	      id: `${resourceId}-${fromTs}-${toTs}`,
	      fromTs,
	      toTs,
	      resourceId,
	      intersectingResourceId,
	      type
	    };
	  });
	}
	function _calculateMinutesRange2(booking) {
	  const date = new Date(babelHelpers.classPrivateFieldLooseBase(this, _selectedDateTs)[_selectedDateTs]);
	  const dateFromTs = Math.max(date.getTime(), booking.dateFromTs) + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset];
	  const bookingViewToTs = Math.max(booking.dateToTs, booking.dateFromTs + minBookingViewMs);
	  const dateToTs = Math.min(date.setDate(date.getDate() + 1), bookingViewToTs) + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset];
	  const dateFrom = new Date(dateFromTs);
	  const dateTo = new Date(dateToTs);
	  const to = dateTo.getHours() * 60 + dateTo.getMinutes();
	  return {
	    from: dateFrom.getHours() * 60 + dateFrom.getMinutes(),
	    to: to === 0 ? 60 * 24 : to,
	    id: booking.id
	  };
	}
	function _subtractRanges2(range, bookingRanges) {
	  let remainingRanges = [{
	    ...range
	  }];
	  bookingRanges.forEach(bookingRange => {
	    remainingRanges = remainingRanges.flatMap(remainingRange => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _rangesOverlap)[_rangesOverlap](remainingRange, bookingRange)) {
	        const parts = [];
	        if (remainingRange.from < bookingRange.from) {
	          parts.push({
	            from: remainingRange.from,
	            to: bookingRange.from,
	            id: remainingRange.id
	          });
	        }
	        if (remainingRange.to > bookingRange.to) {
	          parts.push({
	            from: bookingRange.to,
	            to: remainingRange.to,
	            id: remainingRange.id
	          });
	        }
	        return parts;
	      }
	      return [remainingRange];
	    });
	  });
	  return remainingRanges;
	}
	function _rangesOverlap2(range1, range2) {
	  return range1.from < range2.to && range2.from < range1.to;
	}
	function _getResource2(resourceId) {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Resources}/getById`](resourceId);
	}
	function _getNextDay2(weekDay) {
	  return booking_const.DateFormat.WeekDays[(booking_const.DateFormat.WeekDays.indexOf(weekDay) + 1) % 7];
	}
	function _getPreviousDay2(weekDay) {
	  return booking_const.DateFormat.WeekDays[(booking_const.DateFormat.WeekDays.indexOf(weekDay) + 7 - 1) % 7];
	}
	const busySlots = new BusySlots();

	exports.busySlots = busySlots;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX.Booking,BX.Booking.Const,BX.Booking.Lib,BX.Booking.Provider.Service,BX.Booking.Lib));
//# sourceMappingURL=busy-slots.bundle.js.map
