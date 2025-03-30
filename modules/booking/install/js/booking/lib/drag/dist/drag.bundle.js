/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,main_popup,main_date,ui_draganddrop_draggable,booking_const,booking_core,booking_lib_busySlots,booking_provider_service_bookingService) {
	'use strict';

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _dragManager = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dragManager");
	var _onDragStart = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDragStart");
	var _onDragMove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDragMove");
	var _onDragEnd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDragEnd");
	var _getBookingElements = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBookingElements");
	var _getAdditionalBookingElements = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAdditionalBookingElements");
	var _updateScroll = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateScroll");
	var _getSpeed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSpeed");
	var _isDragDeleteHovered = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDragDeleteHovered");
	var _moveBooking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("moveBooking");
	var _timeFormatted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timeFormatted");
	var _draggedBooking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedBooking");
	var _draggedBookingId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedBookingId");
	var _draggedBookingResourceId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedBookingResourceId");
	var _hoveredCell = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hoveredCell");
	var _offset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("offset");
	var _gridWrap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("gridWrap");
	var _gridColumns = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("gridColumns");
	class Drag {
	  constructor(params) {
	    Object.defineProperty(this, _gridColumns, {
	      get: _get_gridColumns,
	      set: void 0
	    });
	    Object.defineProperty(this, _gridWrap, {
	      get: _get_gridWrap,
	      set: void 0
	    });
	    Object.defineProperty(this, _offset, {
	      get: _get_offset,
	      set: void 0
	    });
	    Object.defineProperty(this, _hoveredCell, {
	      get: _get_hoveredCell,
	      set: void 0
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
	    Object.defineProperty(this, _timeFormatted, {
	      get: _get_timeFormatted,
	      set: void 0
	    });
	    Object.defineProperty(this, _moveBooking, {
	      value: _moveBooking2
	    });
	    Object.defineProperty(this, _isDragDeleteHovered, {
	      value: _isDragDeleteHovered2
	    });
	    Object.defineProperty(this, _getSpeed, {
	      value: _getSpeed2
	    });
	    Object.defineProperty(this, _updateScroll, {
	      value: _updateScroll2
	    });
	    Object.defineProperty(this, _getAdditionalBookingElements, {
	      value: _getAdditionalBookingElements2
	    });
	    Object.defineProperty(this, _getBookingElements, {
	      value: _getBookingElements2
	    });
	    Object.defineProperty(this, _onDragEnd, {
	      value: _onDragEnd2
	    });
	    Object.defineProperty(this, _onDragMove, {
	      value: _onDragMove2
	    });
	    Object.defineProperty(this, _onDragStart, {
	      value: _onDragStart2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dragManager, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _dragManager)[_dragManager] = new ui_draganddrop_draggable.Draggable({
	      container: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].container,
	      draggable: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].draggable,
	      elementsPreventingDrag: ['.booking-booking-resize'],
	      delay: 200
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _dragManager)[_dragManager].subscribe('start', babelHelpers.classPrivateFieldLooseBase(this, _onDragStart)[_onDragStart].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _dragManager)[_dragManager].subscribe('move', babelHelpers.classPrivateFieldLooseBase(this, _onDragMove)[_onDragMove].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _dragManager)[_dragManager].subscribe('end', babelHelpers.classPrivateFieldLooseBase(this, _onDragEnd)[_onDragEnd].bind(this));
	  }
	  destroy() {
	    babelHelpers.classPrivateFieldLooseBase(this, _dragManager)[_dragManager].destroy();
	  }
	}
	async function _onDragStart2(event) {
	  const {
	    draggable,
	    source: {
	      dataset
	    },
	    clientX,
	    clientY
	  } = event.getData();
	  await Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setDraggedBookingId`, Number(dataset.id)), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setDraggedBookingResourceId`, Number(dataset.resourceId))]);
	  main_core.Dom.style(draggable, 'pointer-events', 'none');
	  babelHelpers.classPrivateFieldLooseBase(this, _getAdditionalBookingElements)[_getAdditionalBookingElements](babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].container).forEach(element => {
	    const clone = main_core.Runtime.clone(element);
	    draggable.append(clone);
	    const translateX = element.getBoundingClientRect().left - draggable.getBoundingClientRect().left;
	    const translateY = element.getBoundingClientRect().top - draggable.getBoundingClientRect().top;
	    main_core.Dom.style(clone, 'transition', 'none');
	    main_core.Dom.style(clone, 'transform', `translate(${translateX}px, ${translateY}px)`);
	    main_core.Dom.style(clone, 'animation', 'none');
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _getBookingElements)[_getBookingElements](babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].container).forEach(element => {
	    if (draggable.contains(element)) {
	      return;
	    }
	    main_core.Dom.addClass(element, '--drag-source');
	    main_core.Dom.style(element, 'visibility', 'visible');
	  });
	  const transformOriginX = clientX - draggable.getBoundingClientRect().left;
	  const transformOriginY = clientY - draggable.getBoundingClientRect().top;
	  babelHelpers.classPrivateFieldLooseBase(this, _getBookingElements)[_getBookingElements](draggable).forEach(clone => {
	    main_core.Dom.style(clone, 'transform-origin', `${transformOriginX}px ${transformOriginY}px`);
	  });
	  main_popup.PopupManager.getPopups().forEach(popup => popup.close());
	  void booking_lib_busySlots.busySlots.loadBusySlots();
	}
	function _onDragMove2(event) {
	  const {
	    draggable,
	    clientX,
	    clientY
	  } = event.getData();
	  babelHelpers.classPrivateFieldLooseBase(this, _getAdditionalBookingElements)[_getAdditionalBookingElements](draggable).forEach((clone, index) => {
	    main_core.Dom.style(clone, 'transition', '');
	    main_core.Dom.style(clone, 'transform', `rotate(${index === 1 ? 4 : 0}deg)`);
	    main_core.Dom.style(clone, 'zIndex', `-${index + 1}`);
	  });
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isDragDeleteHovered)[_isDragDeleteHovered](clientX, clientY)) {
	    main_core.Dom.addClass(draggable, '--deleting');
	  } else {
	    main_core.Dom.removeClass(draggable, '--deleting');
	  }
	  draggable.querySelectorAll('[data-element="booking-booking-time"]').forEach(time => {
	    time.innerText = babelHelpers.classPrivateFieldLooseBase(this, _timeFormatted)[_timeFormatted];
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _updateScroll)[_updateScroll](draggable, clientX, clientY);
	}
	async function _onDragEnd2() {
	  clearInterval(this.scrollTimeout);
	  babelHelpers.classPrivateFieldLooseBase(this, _getBookingElements)[_getBookingElements](babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].container).forEach(element => {
	    main_core.Dom.removeClass(element, '--drag-source');
	    main_core.Dom.style(element, 'visibility', '');
	  });
	  if (babelHelpers.classPrivateFieldLooseBase(this, _hoveredCell)[_hoveredCell]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _moveBooking)[_moveBooking]({
	      booking: babelHelpers.classPrivateFieldLooseBase(this, _draggedBooking)[_draggedBooking],
	      resourceId: babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingResourceId)[_draggedBookingResourceId],
	      cell: babelHelpers.classPrivateFieldLooseBase(this, _hoveredCell)[_hoveredCell]
	    });
	  }
	  await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setDraggedBookingId`, null);
	  void booking_lib_busySlots.busySlots.loadBusySlots();
	}
	function _getBookingElements2(container) {
	  const element = 'booking-booking';
	  const id = babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingId)[_draggedBookingId];
	  return [...container.querySelectorAll(`[data-element="${element}"][data-id="${id}"]`)];
	}
	function _getAdditionalBookingElements2(container) {
	  const element = 'booking-booking';
	  const id = babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingId)[_draggedBookingId];
	  const resourceId = babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingResourceId)[_draggedBookingResourceId];
	  return [...container.querySelectorAll(`[data-element="${element}"][data-id="${id}"]:not([data-resource-id="${resourceId}"])`)];
	}
	function _updateScroll2(draggable, x, y) {
	  clearTimeout(this.scrollTimeout);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isDragDeleteHovered)[_isDragDeleteHovered](x, y)) {
	    return;
	  }
	  const gridRect = babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].getBoundingClientRect();
	  const draggableRect = draggable.getBoundingClientRect();
	  this.scrollTimeout = setTimeout(() => babelHelpers.classPrivateFieldLooseBase(this, _updateScroll)[_updateScroll](draggable), 16);
	  if (draggableRect.left < gridRect.left) {
	    babelHelpers.classPrivateFieldLooseBase(this, _gridColumns)[_gridColumns].scrollLeft -= babelHelpers.classPrivateFieldLooseBase(this, _getSpeed)[_getSpeed](draggableRect.left, gridRect.left);
	  } else if (draggableRect.right > gridRect.right) {
	    babelHelpers.classPrivateFieldLooseBase(this, _gridColumns)[_gridColumns].scrollLeft += babelHelpers.classPrivateFieldLooseBase(this, _getSpeed)[_getSpeed](draggableRect.right, gridRect.right);
	  } else if (draggableRect.top < gridRect.top) {
	    babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].scrollTop -= babelHelpers.classPrivateFieldLooseBase(this, _getSpeed)[_getSpeed](draggableRect.top, gridRect.top);
	  } else if (draggableRect.bottom > gridRect.bottom) {
	    babelHelpers.classPrivateFieldLooseBase(this, _gridWrap)[_gridWrap].scrollTop += 2 * babelHelpers.classPrivateFieldLooseBase(this, _getSpeed)[_getSpeed](draggableRect.bottom, gridRect.bottom);
	  } else {
	    clearTimeout(this.scrollTimeout);
	  }
	}
	function _getSpeed2(a, b) {
	  return (Math.floor(Math.sqrt(Math.abs(a - b))) + 1) / 2;
	}
	function _isDragDeleteHovered2(x, y) {
	  var _document$elementFrom;
	  if (!x || !y) {
	    return false;
	  }
	  return (_document$elementFrom = document.elementFromPoint(x, y)) == null ? void 0 : _document$elementFrom.closest('[data-element="booking-drag-delete"]');
	}
	function _moveBooking2({
	  booking,
	  resourceId,
	  cell
	}) {
	  if (cell.fromTs === booking.dateFromTs && cell.toTs === booking.dateToTs && cell.resourceId === resourceId) {
	    return;
	  }
	  const additionalResourcesIds = booking.resourcesIds.includes(cell.resourceId) ? booking.resourcesIds : booking.resourcesIds.filter(id => id !== resourceId);
	  void booking_provider_service_bookingService.bookingService.update({
	    id: booking.id,
	    dateFromTs: cell.fromTs,
	    dateToTs: cell.toTs,
	    resourcesIds: [...new Set([cell.resourceId, ...additionalResourcesIds])],
	    timezoneFrom: booking.timezoneFrom,
	    timezoneTo: booking.timezoneTo
	  });
	}
	function _get_timeFormatted() {
	  var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4;
	  const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	  const from = (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _hoveredCell)[_hoveredCell]) == null ? void 0 : _babelHelpers$classPr2.fromTs) != null ? _babelHelpers$classPr : babelHelpers.classPrivateFieldLooseBase(this, _draggedBooking)[_draggedBooking].dateFromTs;
	  const to = (_babelHelpers$classPr3 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _hoveredCell)[_hoveredCell]) == null ? void 0 : _babelHelpers$classPr4.toTs) != null ? _babelHelpers$classPr3 : babelHelpers.classPrivateFieldLooseBase(this, _draggedBooking)[_draggedBooking].dateToTs;
	  return main_core.Loc.getMessage('BOOKING_BOOKING_TIME_RANGE', {
	    '#FROM#': main_date.DateTimeFormat.format(timeFormat, (from + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset]) / 1000),
	    '#TO#': main_date.DateTimeFormat.format(timeFormat, (to + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset]) / 1000)
	  });
	}
	function _get_draggedBooking() {
	  var _Core$getStore$getter;
	  return (_Core$getStore$getter = booking_core.Core.getStore().getters[`${booking_const.Model.Bookings}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingId)[_draggedBookingId])) != null ? _Core$getStore$getter : null;
	}
	function _get_draggedBookingId() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/draggedBookingId`];
	}
	function _get_draggedBookingResourceId() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/draggedBookingResourceId`];
	}
	function _get_hoveredCell() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/hoveredCell`];
	}
	function _get_offset() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/offset`];
	}
	function _get_gridWrap() {
	  return BX('booking-booking-grid-wrap');
	}
	function _get_gridColumns() {
	  return BX('booking-booking-grid-columns');
	}

	exports.Drag = Drag;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX.Main,BX.Main,BX.UI.DragAndDrop,BX.Booking.Const,BX.Booking,BX.Booking.Lib,BX.Booking.Provider.Service));
//# sourceMappingURL=drag.bundle.js.map
