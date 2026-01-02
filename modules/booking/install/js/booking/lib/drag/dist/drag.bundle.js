/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,main_popup,main_date,ui_draganddrop_draggable,booking_const,booking_core,booking_lib_busySlots,booking_lib_analytics,booking_provider_service_bookingService,booking_component_nonDraggableBookingPopup) {
	'use strict';

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _dragManager = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dragManager");
	var _draggedId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedId");
	var _draggedKind = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedKind");
	var _onDragStart = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDragStart");
	var _onDragMove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDragMove");
	var _onDragEnd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDragEnd");
	var _getDraggedElements = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDraggedElements");
	var _tryGetLostDraggedElements = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tryGetLostDraggedElements");
	var _getAdditionalBookingElements = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAdditionalBookingElements");
	var _updateScroll = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateScroll");
	var _getSpeed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSpeed");
	var _isDragDeleteHovered = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDragDeleteHovered");
	var _moveBooking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("moveBooking");
	var _moveWaitListItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("moveWaitListItem");
	var _setEditingBookingId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setEditingBookingId");
	var _timeFormatted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timeFormatted");
	var _showPopupOnNonDraggableBookingFromDeletedResource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showPopupOnNonDraggableBookingFromDeletedResource");
	var _getResourceById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getResourceById");
	var _draggedBooking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedBooking");
	var _draggedDataTransfer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("draggedDataTransfer");
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
	    Object.defineProperty(this, _draggedDataTransfer, {
	      get: _get_draggedDataTransfer,
	      set: void 0
	    });
	    Object.defineProperty(this, _draggedBooking, {
	      get: _get_draggedBooking,
	      set: void 0
	    });
	    Object.defineProperty(this, _getResourceById, {
	      value: _getResourceById2
	    });
	    Object.defineProperty(this, _showPopupOnNonDraggableBookingFromDeletedResource, {
	      value: _showPopupOnNonDraggableBookingFromDeletedResource2
	    });
	    Object.defineProperty(this, _timeFormatted, {
	      get: _get_timeFormatted,
	      set: void 0
	    });
	    Object.defineProperty(this, _setEditingBookingId, {
	      value: _setEditingBookingId2
	    });
	    Object.defineProperty(this, _moveWaitListItem, {
	      value: _moveWaitListItem2
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
	    Object.defineProperty(this, _tryGetLostDraggedElements, {
	      value: _tryGetLostDraggedElements2
	    });
	    Object.defineProperty(this, _getDraggedElements, {
	      value: _getDraggedElements2
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
	    Object.defineProperty(this, _draggedId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _draggedKind, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = {
	      element: 'booking-booking',
	      ...params
	    };
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
	  var _parseInt;
	  const {
	    draggable,
	    source: {
	      dataset
	    },
	    clientX,
	    clientY
	  } = event.getData();
	  babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].element = `booking-${dataset.kind}`;
	  babelHelpers.classPrivateFieldLooseBase(this, _draggedKind)[_draggedKind] = dataset.kind;
	  babelHelpers.classPrivateFieldLooseBase(this, _draggedId)[_draggedId] = parseInt(dataset.id, 10);
	  await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setDraggedDataTransfer`, {
	    kind: babelHelpers.classPrivateFieldLooseBase(this, _draggedKind)[_draggedKind],
	    id: babelHelpers.classPrivateFieldLooseBase(this, _draggedId)[_draggedId],
	    resourceId: (_parseInt = parseInt(dataset.resourceId, 10)) != null ? _parseInt : 0
	  });
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
	  babelHelpers.classPrivateFieldLooseBase(this, _getDraggedElements)[_getDraggedElements](babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].container).forEach(element => {
	    if (draggable.contains(element)) {
	      return;
	    }
	    main_core.Dom.addClass(element, '--drag-source');
	    main_core.Dom.style(element, 'visibility', 'visible');
	  });
	  const transformOriginX = clientX - draggable.getBoundingClientRect().left;
	  const transformOriginY = clientY - draggable.getBoundingClientRect().top;
	  babelHelpers.classPrivateFieldLooseBase(this, _getDraggedElements)[_getDraggedElements](draggable).forEach(clone => {
	    main_core.Dom.style(clone, 'transform-origin', `${transformOriginX}px ${transformOriginY}px`);
	  });
	  main_popup.PopupManager.getPopups().forEach(popup => popup.close());
	  void booking_lib_busySlots.busySlots.loadBusySlots();
	}
	function _onDragMove2(event) {
	  if (main_core.Type.isNull(babelHelpers.classPrivateFieldLooseBase(this, _draggedKind)[_draggedKind])) {
	    return;
	  }
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
	  if (babelHelpers.classPrivateFieldLooseBase(this, _draggedKind)[_draggedKind] === booking_const.DraggedElementKind.Booking) {
	    draggable.querySelectorAll('[data-element="booking-booking-time"]').forEach(time => {
	      time.innerText = babelHelpers.classPrivateFieldLooseBase(this, _timeFormatted)[_timeFormatted];
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _updateScroll)[_updateScroll](draggable, clientX, clientY);
	}
	async function _onDragEnd2(event) {
	  var _babelHelpers$classPr, _babelHelpers$classPr2;
	  clearInterval(this.scrollTimeout);
	  babelHelpers.classPrivateFieldLooseBase(this, _getDraggedElements)[_getDraggedElements](babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].container).forEach(element => {
	    main_core.Dom.removeClass(element, '--drag-source');
	    main_core.Dom.style(element, 'visibility', '');
	  });
	  if (babelHelpers.classPrivateFieldLooseBase(this, _hoveredCell)[_hoveredCell] && !((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getResourceById)[_getResourceById](babelHelpers.classPrivateFieldLooseBase(this, _hoveredCell)[_hoveredCell].resourceId)) != null && _babelHelpers$classPr.isDeleted)) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _draggedKind)[_draggedKind] === booking_const.DraggedElementKind.Booking) {
	      void babelHelpers.classPrivateFieldLooseBase(this, _moveBooking)[_moveBooking]({
	        booking: babelHelpers.classPrivateFieldLooseBase(this, _draggedBooking)[_draggedBooking],
	        resourceId: babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingResourceId)[_draggedBookingResourceId],
	        cell: babelHelpers.classPrivateFieldLooseBase(this, _hoveredCell)[_hoveredCell]
	      });
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _draggedKind)[_draggedKind] === booking_const.DraggedElementKind.WaitListItem) {
	      void babelHelpers.classPrivateFieldLooseBase(this, _moveWaitListItem)[_moveWaitListItem]({
	        cell: babelHelpers.classPrivateFieldLooseBase(this, _hoveredCell)[_hoveredCell]
	      });
	    }
	  }
	  if ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _getResourceById)[_getResourceById](babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingResourceId)[_draggedBookingResourceId])) != null && _babelHelpers$classPr2.isDeleted) {
	    const bookingId = babelHelpers.classPrivateFieldLooseBase(this, _draggedBooking)[_draggedBooking].id;
	    if (booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/deletingBookings`][bookingId]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _showPopupOnNonDraggableBookingFromDeletedResource)[_showPopupOnNonDraggableBookingFromDeletedResource](event.data.source, bookingId);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _draggedKind)[_draggedKind] = null;
	  babelHelpers.classPrivateFieldLooseBase(this, _draggedId)[_draggedId] = null;
	  await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/clearDraggedDataTransfer`);
	  void booking_lib_busySlots.busySlots.loadBusySlots();
	}
	function _getDraggedElements2(container) {
	  const id = babelHelpers.classPrivateFieldLooseBase(this, _draggedDataTransfer)[_draggedDataTransfer].id || babelHelpers.classPrivateFieldLooseBase(this, _draggedId)[_draggedId];
	  const items = [...container.querySelectorAll(`[data-element="${babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].element}"][data-id="${id}"]`)];
	  if (!id && items.length === 0) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _tryGetLostDraggedElements)[_tryGetLostDraggedElements](container);
	  }
	  return items;
	}
	function _tryGetLostDraggedElements2(container) {
	  return [...container.querySelectorAll(`[data-element="${babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].element}"].--drag-source`)];
	}
	function _getAdditionalBookingElements2(container) {
	  const id = babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingId)[_draggedBookingId];
	  const resourceId = babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingResourceId)[_draggedBookingResourceId];
	  return [...container.querySelectorAll(`[data-element="${babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].element}"][data-id="${id}"]:not([data-resource-id="${resourceId}"])`)];
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
	async function _moveBooking2({
	  booking,
	  resourceId,
	  cell
	}) {
	  if (cell.fromTs === booking.dateFromTs && cell.toTs === booking.dateToTs && cell.resourceId === resourceId) {
	    return;
	  }
	  const resourceIds = booking.resourcesIds.includes(cell.resourceId) ? booking.resourcesIds : [cell.resourceId, ...booking.resourcesIds.filter(id => id !== resourceId)];
	  await booking_provider_service_bookingService.bookingService.update({
	    id: booking.id,
	    dateFromTs: cell.fromTs,
	    dateToTs: cell.toTs,
	    resourcesIds: [...new Set(resourceIds)],
	    timezoneFrom: booking.timezoneFrom,
	    timezoneTo: booking.timezoneTo
	  });
	}
	async function _moveWaitListItem2({
	  cell
	}) {
	  var _resource$slotRanges, _resource$slotRanges$, _intersections$, _intersections$cell$r;
	  const $store = booking_core.Core.getStore();
	  const waitListItemId = babelHelpers.classPrivateFieldLooseBase(this, _draggedDataTransfer)[_draggedDataTransfer].id;
	  const waitListItem = $store.getters[`${booking_const.Model.WaitList}/getById`](waitListItemId);
	  const resourceId = cell.resourceId;
	  const resource = $store.getters[`${booking_const.Model.Resources}/getById`](resourceId);
	  const timezone = resource == null ? void 0 : (_resource$slotRanges = resource.slotRanges) == null ? void 0 : (_resource$slotRanges$ = _resource$slotRanges[0]) == null ? void 0 : _resource$slotRanges$.timezone;
	  const clients = [...waitListItem.clients];
	  const intersections = $store.getters[`${booking_const.Model.Interface}/intersections`];
	  if ($store.getters[`${booking_const.Model.Interface}/editingWaitListItemId`] === waitListItemId) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _setEditingBookingId)[_setEditingBookingId](waitListItemId);
	  }
	  const result = await booking_provider_service_bookingService.bookingService.createFromWaitListItem(waitListItemId, {
	    id: `wl${waitListItemId}`,
	    clients,
	    primaryClient: clients.length > 0 ? clients[0] : undefined,
	    externalData: [...waitListItem.externalData],
	    name: waitListItem.name,
	    note: waitListItem.note,
	    resourcesIds: [...new Set([cell.resourceId, ...((_intersections$ = intersections[0]) != null ? _intersections$ : []), ...((_intersections$cell$r = intersections[cell.resourceId]) != null ? _intersections$cell$r : [])])],
	    dateFromTs: cell.fromTs,
	    dateToTs: cell.toTs,
	    timezoneFrom: timezone,
	    timezoneTo: timezone
	  });
	  if (result.success && result.booking) {
	    booking_lib_analytics.BookingAnalytics.sendAddBooking({
	      isOverbooking: false
	    });
	    if ($store.getters[`${booking_const.Model.Interface}/editingBookingId`] === waitListItemId) {
	      await babelHelpers.classPrivateFieldLooseBase(this, _setEditingBookingId)[_setEditingBookingId](result.booking.id);
	    }
	  }
	}
	async function _setEditingBookingId2(id) {
	  const $store = booking_core.Core.getStore();
	  await Promise.all([$store.dispatch(`${booking_const.Model.Interface}/setEditingBookingId`, id), $store.dispatch(`${booking_const.Model.Interface}/setEditingWaitListItemId`, null)]);
	}
	function _get_timeFormatted() {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6;
	  const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	  const from = (_babelHelpers$classPr3 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _hoveredCell)[_hoveredCell]) == null ? void 0 : _babelHelpers$classPr4.fromTs) != null ? _babelHelpers$classPr3 : babelHelpers.classPrivateFieldLooseBase(this, _draggedBooking)[_draggedBooking].dateFromTs;
	  const to = (_babelHelpers$classPr5 = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _hoveredCell)[_hoveredCell]) == null ? void 0 : _babelHelpers$classPr6.toTs) != null ? _babelHelpers$classPr5 : babelHelpers.classPrivateFieldLooseBase(this, _draggedBooking)[_draggedBooking].dateToTs;
	  return main_core.Loc.getMessage('BOOKING_BOOKING_TIME_RANGE', {
	    '#FROM#': main_date.DateTimeFormat.format(timeFormat, (from + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset]) / 1000),
	    '#TO#': main_date.DateTimeFormat.format(timeFormat, (to + babelHelpers.classPrivateFieldLooseBase(this, _offset)[_offset]) / 1000)
	  });
	}
	function _showPopupOnNonDraggableBookingFromDeletedResource2(bookingEl, bookingId) {
	  const popupId = `booking-non-draggable-booking-${bookingId}`;
	  const popup = new booking_component_nonDraggableBookingPopup.NonDraggableBookingPopup({
	    id: popupId,
	    bindElement: bookingEl
	  });
	  popup.show();
	  setTimeout(() => {
	    popup.destroy(popupId);
	  }, 5000);
	}
	function _getResourceById2(resourceId) {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Resources}/getById`](resourceId);
	}
	function _get_draggedBooking() {
	  var _Core$getStore$getter;
	  return (_Core$getStore$getter = booking_core.Core.getStore().getters[`${booking_const.Model.Bookings}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _draggedBookingId)[_draggedBookingId])) != null ? _Core$getStore$getter : null;
	}
	function _get_draggedDataTransfer() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/draggedDataTransfer`];
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

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX.Main,BX.Main,BX.UI.DragAndDrop,BX.Booking.Const,BX.Booking,BX.Booking.Lib,BX.Booking.Lib,BX.Booking.Provider.Service,BX.Booking.Component));
//# sourceMappingURL=drag.bundle.js.map
