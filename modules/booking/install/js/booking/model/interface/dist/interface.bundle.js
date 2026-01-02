/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,ui_vue3_vuex,booking_const,booking_lib_timezone) {
	'use strict';

	function getOverbookingOccupancy(overbookingMap, resources) {
	  const occupancyList = [];
	  const hashSet = new Set();
	  for (const [, overbooking] of overbookingMap) {
	    const {
	      dateFromTs,
	      dateToTs,
	      resourcesIds
	    } = overbooking.booking;
	    if (resourcesIds.every(id => !resources.has(id))) {
	      continue;
	    }
	    overbooking.items.filter(({
	      resourceId
	    }) => resources.has(resourceId)).forEach(({
	      resourceId,
	      intersections
	    }) => {
	      intersections.forEach(intersection => {
	        const occupancy = {
	          fromTs: dateFromTs >= intersection.dateFromTs ? dateFromTs : intersection.dateFromTs,
	          toTs: dateToTs <= intersection.dateToTs ? dateToTs : intersection.dateToTs,
	          resourcesIds: [resourceId]
	        };
	        const occupancyHash = `${occupancy.fromTs}-${occupancy.toTs}-${occupancy.resourcesIds.join('-')}`;
	        if (!hashSet.has(occupancyHash)) {
	          hashSet.add(occupancyHash);
	          occupancyList.push(occupancy);
	        }
	      });
	    });
	  }
	  return occupancyList;
	}

	/* eslint-disable no-param-reassign */
	class Interface extends ui_vue3_vuex.BuilderModel {
	  getName() {
	    return booking_const.Model.Interface;
	  }
	  getState() {
	    var _schedule$fromHour, _schedule$toHour;
	    const today = new Date();
	    const schedule = this.getVariable('schedule', {});
	    return {
	      isFeatureEnabled: this.getVariable('isFeatureEnabled', false),
	      canTurnOnTrial: this.getVariable('canTurnOnTrial', false),
	      canTurnOnDemo: this.getVariable('canTurnOnDemo', false),
	      editingBookingId: this.getVariable('editingBookingId', 0),
	      editingWaitListItemId: this.getVariable('editingWaitListItemId', 0),
	      draggedBookingId: 0,
	      draggedBookingResourceId: 0,
	      draggedDataTransfer: {
	        id: 0,
	        resourceId: 0,
	        kind: ''
	      },
	      resizedBookingId: 0,
	      isLoaded: false,
	      zoom: 1,
	      expanded: false,
	      scroll: 0,
	      offHoursHover: false,
	      offHoursExpanded: false,
	      waitListExpanded: this.getVariable('waitListExpanded', true),
	      calendarExpanded: this.getVariable('calendarExpanded', true),
	      fromHour: (_schedule$fromHour = schedule.fromHour) != null ? _schedule$fromHour : 9,
	      toHour: (_schedule$toHour = schedule.toHour) != null ? _schedule$toHour : 19,
	      selectedDateTs: new Date(today.getFullYear(), today.getMonth(), today.getDate()).getTime(),
	      viewDateTs: new Date(today.getFullYear(), today.getMonth()).getTime(),
	      deletingBookings: {},
	      deletingResources: {},
	      deletingWaitListItemIds: {},
	      selectedCells: {},
	      hoveredCell: null,
	      busySlots: {},
	      disabledBusySlots: {},
	      resourcesIds: [],
	      pinnedResourceIds: [],
	      isIntersectionForAll: true,
	      counterMarks: [],
	      freeMarks: [],
	      totalClients: this.getVariable('totalClients', 0),
	      totalNewClientsToday: this.getVariable('totalNewClientsToday', 0),
	      moneyStatistics: this.getVariable('moneyStatistics', null),
	      intersections: {},
	      timezone: this.getVariable('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone),
	      mousePosition: {
	        top: 0,
	        left: 0
	      },
	      isCurrentSenderAvailable: false,
	      isShownTrialPopup: false,
	      embedItems: this.getVariable('embedItems', []),
	      animationPause: false,
	      createdFromEmbedBookings: {},
	      createdFromEmbedWaitListItems: {}
	    };
	  }

	  // eslint-disable-next-line max-lines-per-function
	  getGetters() {
	    return {
	      /** @function interface/isFeatureEnabled */
	      isFeatureEnabled: state => {
	        return state.isFeatureEnabled || state.canTurnOnTrial;
	      },
	      /** @function interface/canTurnOnTrial */
	      canTurnOnTrial: state => state.canTurnOnTrial,
	      /** @function interface/canTurnOnDemo */
	      canTurnOnDemo: state => state.canTurnOnDemo,
	      /** @function interface/isShownTrialPopup */
	      isShownTrialPopup: state => state.isShownTrialPopup,
	      /** @function interface/editingBookingId */
	      editingBookingId: state => state.editingBookingId,
	      /** @function interface/editingWaitListItemId */
	      editingWaitListItemId: state => state.editingWaitListItemId,
	      /** @function interface/isEditingBookingMode */
	      isEditingBookingMode: state => {
	        return state.editingBookingId > 0 || state.editingWaitListItemId > 0;
	      },
	      /** @function interface/draggedBookingId */
	      draggedBookingId: state => {
	        return state.draggedDataTransfer.kind === booking_const.DraggedElementKind.Booking ? state.draggedDataTransfer.id : 0;
	      },
	      /** @function interface/draggedBookingResourceId */
	      draggedBookingResourceId: state => {
	        return state.draggedDataTransfer.kind === booking_const.DraggedElementKind.Booking ? state.draggedDataTransfer.resourceId : 0;
	      },
	      /** @function interface/draggedDataTransfer */
	      draggedDataTransfer: state => state.draggedDataTransfer,
	      /** @function interface/resizedBookingId */
	      resizedBookingId: state => state.resizedBookingId,
	      /** @function interface/isDragMode */
	      isDragMode: state => state.draggedDataTransfer.id > 0 || state.resizedBookingId,
	      /** @function interface/isLoaded */
	      isLoaded: state => state.isLoaded,
	      /** @function interface/zoom */
	      zoom: state => state.zoom,
	      /** @function interface/expanded */
	      expanded: state => state.expanded,
	      /** @function interface/scroll */
	      scroll: state => state.scroll,
	      /** @function interface/offHoursHover */
	      offHoursHover: state => state.offHoursHover,
	      /** @function interface/offHoursExpanded */
	      offHoursExpanded: state => state.offHoursExpanded,
	      /** @function interface/waitListExpanded */
	      waitListExpanded: state => state.waitListExpanded,
	      /** @function interface/calendarExpanded */
	      calendarExpanded: state => state.calendarExpanded,
	      /** @function interface/fromHour */
	      fromHour: state => state.fromHour,
	      /** @function interface/toHour */
	      toHour: state => state.toHour,
	      /** @function interface/selectedDateTs */
	      selectedDateTs: (state, getters) => state.selectedDateTs - getters.offset,
	      /** @function interface/viewDateTs */
	      viewDateTs: (state, getters) => state.viewDateTs - getters.offset,
	      /** @function interface/deletingBookings */
	      deletingBookings: state => state.deletingBookings,
	      /** @function interface/deletingResources */
	      deletingResources: state => state.deletingResources,
	      /** @function interface/deletingWaitListItems */
	      deletingWaitListItems: state => state.deletingWaitListItemIds,
	      /** @function interface/selectedCells */
	      selectedCells: state => state.selectedCells,
	      /** @function interface/hoveredCell */
	      hoveredCell: state => state.hoveredCell,
	      /** @function interface/busySlots */
	      busySlots: state => Object.values(state.busySlots),
	      /** @function interface/disabledBusySlots */
	      disabledBusySlots: state => state.disabledBusySlots,
	      /** @function interface/resourcesIds */
	      resourcesIds: (state, getters, rootState, rootGetters) => {
	        const extraResourcesIds = rootGetters[`${booking_const.Model.Bookings}/getByDate`](state.selectedDateTs).filter(booking => !rootGetters[`${booking_const.Model.Filter}/isFilterMode`] && booking.counter > 0).map(booking => booking.resourcesIds[0]);
	        const resourcesIds = [...new Set([...state.pinnedResourceIds, ...state.resourcesIds, ...extraResourcesIds])];
	        const excludeResources = new Set(getters.getOccupancy(resourcesIds, Object.values(rootGetters[`${booking_const.Model.Filter}/quickFilter`].ignoredBookingIds)).filter(occupancy => Object.values(rootGetters[`${booking_const.Model.Filter}/quickFilter`].active).some(hour => {
	          const fromTs = new Date(state.selectedDateTs).setHours(hour) - getters.offset;
	          const toTs = new Date(state.selectedDateTs).setHours(hour + 1) - getters.offset;
	          return toTs > occupancy.fromTs && fromTs < occupancy.toTs;
	        })).flatMap(occupancy => occupancy.resourcesIds));
	        Object.values(state.deletingResources).forEach(id => excludeResources.add(Number(id)));
	        return resourcesIds.filter(id => !excludeResources.has(id));
	      },
	      extraResourcesIds: (state, getters) => {
	        const resourcesIds = state.resourcesIds;
	        return getters.resourcesIds.filter(id => !resourcesIds.includes(id));
	      },
	      /** @function interface/isIntersectionForAll */
	      isIntersectionForAll: state => state.isIntersectionForAll,
	      /** @function interface/freeMarks */
	      freeMarks: state => state.freeMarks,
	      /** @function interface/totalClients */
	      totalClients: state => state.totalClients,
	      /** @function interface/totalNewClientsToday */
	      totalNewClientsToday: state => state.totalNewClientsToday,
	      /** @function interface/moneyStatistics */
	      moneyStatistics: state => state.moneyStatistics,
	      getCounterMarks: state => (filterDates = null) => {
	        if (main_core.Type.isNull(filterDates)) {
	          return state.counterMarks;
	        }
	        return state.counterMarks.filter(date => filterDates.includes(date));
	      },
	      /** @function interface/intersections */
	      intersections: state => state.intersections,
	      /** @function interface/timezone */
	      timezone: state => state.timezone,
	      /** @function interface/offset */
	      offset: state => {
	        const timezoneOffset = booking_lib_timezone.Timezone.getOffset(state.selectedDateTs, state.timezone);
	        return (timezoneOffset + new Date(state.selectedDateTs).getTimezoneOffset() * 60) * 1000;
	      },
	      /** @function interface/mousePosition */
	      mousePosition: state => state.mousePosition,
	      /** @function interface/isCurrentSenderAvailable */
	      isCurrentSenderAvailable: state => state.isCurrentSenderAvailable,
	      /** @function interface/getColliding */
	      getColliding: (state, getters) => {
	        return (resourceId, excludedBookingIds) => {
	          const resourcesIds = Array.isArray(resourceId) ? resourceId : [resourceId];
	          return [...getters.getOccupancy(resourcesIds, excludedBookingIds), ...Object.values(state.selectedCells).filter(cell => resourcesIds.includes(cell.resourceId)).map(cell => ({
	            fromTs: cell.fromTs,
	            toTs: cell.toTs,
	            resourcesIds: [cell.resourceId]
	          }))];
	        };
	      },
	      /** @function interface/getOccupancy */
	      getOccupancy: (state, getters, rootState, rootGetters) => {
	        return (resourcesIds, excludedBookingIds = []) => {
	          const resources = new Set(resourcesIds);
	          const bookings = rootGetters[`${booking_const.Model.Bookings}/get`].filter(booking => {
	            const belongsToResources = booking.resourcesIds.some(id => resources.has(id));
	            const isNotExcluded = main_core.Type.isFunction(excludedBookingIds) ? !excludedBookingIds(booking) : !excludedBookingIds.includes(booking.id);
	            return belongsToResources && isNotExcluded;
	          }).map(booking => ({
	            fromTs: booking.dateFromTs,
	            toTs: booking.dateToTs,
	            resourcesIds: booking.resourcesIds
	          }));
	          const busySlots = Object.values(state.busySlots).filter(busySlot => {
	            const isDragOffHours = getters.isDragMode && busySlot.type === booking_const.BusySlot.OffHours;
	            const isActive = !(busySlot.id in state.disabledBusySlots) && !isDragOffHours;
	            const belongsToResources = resources.has(busySlot.resourceId);
	            const isIntersectionOverbooking = busySlot.type === booking_const.BusySlot.IntersectionOverbooking;
	            return isIntersectionOverbooking && isActive && belongsToResources;
	          }).map(({
	            fromTs,
	            toTs,
	            resourceId
	          }) => ({
	            fromTs,
	            toTs,
	            resourcesIds: [resourceId]
	          }));
	          const overbookingOccupancy = getOverbookingOccupancy(rootGetters[`${booking_const.Model.Bookings}/overbookingMap`], resources);
	          return [...bookings, ...busySlots, ...overbookingOccupancy];
	        };
	      },
	      /** @function interface/embedItems */
	      embedItems: state => state.embedItems,
	      /** @function interface/animationPause */
	      animationPause: state => state.animationPause,
	      /** @function interface/isBookingCreatedFromEmbed */
	      isBookingCreatedFromEmbed: state => id => {
	        return id in state.createdFromEmbedBookings;
	      },
	      /** @function interface/isWaitListItemCreatedFromEmbed */
	      isWaitListItemCreatedFromEmbed: state => id => {
	        return id in state.createdFromEmbedWaitListItems;
	      }
	    };
	  }

	  // eslint-disable-next-line max-lines-per-function
	  getActions() {
	    const createBatchableAction = mutationName => {
	      return (store, payload) => {
	        if (main_core.Type.isArray(payload)) {
	          payload.forEach(id => store.commit(mutationName, id));
	        } else {
	          store.commit(mutationName, payload);
	        }
	      };
	    };
	    return {
	      /** @function interface/setEditingBookingId */
	      setEditingBookingId: (store, editingBookingId) => {
	        store.commit('setEditingBookingId', editingBookingId);
	      },
	      /** @function interface/setEditingWaitListItemId */
	      setEditingWaitListItemId: (store, editingWaitListItemId) => {
	        store.commit('setEditingWaitListItemId', editingWaitListItemId);
	      },
	      /** @function interface/setDraggedBookingId */
	      setDraggedBookingId: (store, draggedBookingId) => {
	        store.commit('setDraggedBookingId', draggedBookingId);
	      },
	      /** @function interface/setDraggedBookingResourceId */
	      setDraggedBookingResourceId: (store, draggedBookingResourceId) => {
	        store.commit('setDraggedBookingResourceId', draggedBookingResourceId);
	      },
	      /** @function interface/setDraggedDataTransfer */
	      setDraggedDataTransfer: (store, draggedDataTransfer) => {
	        store.commit('setDraggedDataTransfer', draggedDataTransfer);
	      },
	      /** @function interface/clearDraggedDataTransfer */
	      clearDraggedDataTransfer: store => {
	        store.commit('setDraggedDataTransfer', {
	          kind: null,
	          id: 0,
	          resourceId: 0
	        });
	      },
	      /** @function interface/setResizedBookingId */
	      setResizedBookingId: (store, resizedBookingId) => {
	        store.commit('setResizedBookingId', resizedBookingId);
	      },
	      /** @function interface/setIsLoaded */
	      setIsLoaded: (store, isLoaded) => {
	        store.commit('setIsLoaded', isLoaded);
	      },
	      /** @function interface/setZoom */
	      setZoom: (store, zoom) => {
	        store.commit('setZoom', zoom);
	      },
	      /** @function interface/setExpanded */
	      setExpanded: (store, expanded) => {
	        store.commit('setExpanded', expanded);
	      },
	      /** @function interface/setScroll */
	      setScroll: (store, scroll) => {
	        store.commit('setScroll', scroll);
	      },
	      /** @function interface/setOffHoursHover */
	      setOffHoursHover: (store, offHoursHover) => {
	        store.commit('setOffHoursHover', offHoursHover);
	      },
	      /** @function interface/setOffHoursExpanded */
	      setOffHoursExpanded: (store, offHoursExpanded) => {
	        store.commit('setOffHoursExpanded', offHoursExpanded);
	      },
	      /** @function interface/setWaitListExpanded */
	      setWaitListExpanded: (store, waitListExpanded) => {
	        store.commit('setWaitListExpanded', waitListExpanded);
	      },
	      /** @function interface/setCalendarExpanded */
	      setCalendarExpanded: (store, calendarExpanded) => {
	        store.commit('setCalendarExpanded', calendarExpanded);
	      },
	      /** @function interface/setSelectedDateTs */
	      setSelectedDateTs: (store, selectedDateTs) => {
	        store.commit('setSelectedDateTs', selectedDateTs);
	      },
	      /** @function interface/setViewDateTs */
	      setViewDateTs: (store, viewDateTs) => {
	        store.commit('setViewDateTs', viewDateTs);
	      },
	      /** @function interface/addDeletingBooking */
	      addDeletingBooking: createBatchableAction('addDeletingBooking'),
	      /** @function interface/removeDeletingBooking */
	      removeDeletingBooking: createBatchableAction('removeDeletingBooking'),
	      /** @function interface/addDeletingResource */
	      addDeletingResource: createBatchableAction('addDeletingResource'),
	      /** @function interface/removeDeletingResource */
	      removeDeletingResource: createBatchableAction('removeDeletingResource'),
	      /** @function interface/addDeletingWaitListItemId */
	      addDeletingWaitListItemId: createBatchableAction('addDeletingWaitListItemId'),
	      /** @function interface/removeDeletingWaitListItemId */
	      removeDeletingWaitListItemId: createBatchableAction('removeDeletingWaitListItemId'),
	      /** @function interface/addSelectedCell */
	      addSelectedCell: (store, cell) => {
	        store.commit('addSelectedCell', cell);
	      },
	      /** @function interface/removeSelectedCell */
	      removeSelectedCell: (store, cell) => {
	        store.commit('removeSelectedCell', cell);
	      },
	      /** @function interface/clearSelectedCells */
	      clearSelectedCells: store => {
	        store.commit('clearSelectedCells');
	      },
	      /** @function interface/setHoveredCell */
	      setHoveredCell: (store, cell) => {
	        store.commit('setHoveredCell', cell);
	      },
	      /** @function interface/upsertBusySlotMany */
	      upsertBusySlotMany: (store, busySlots) => {
	        busySlots.forEach(busySlot => store.commit('upsertBusySlot', busySlot));
	      },
	      /** @function interface/clearBusySlots */
	      clearBusySlots: store => {
	        store.commit('clearBusySlots');
	      },
	      /** @function interface/addDisabledBusySlot */
	      addDisabledBusySlot: (store, busySlot) => {
	        store.commit('addDisabledBusySlot', busySlot);
	      },
	      /** @function interface/clearDisabledBusySlots */
	      clearDisabledBusySlots: store => {
	        store.commit('clearDisabledBusySlots');
	      },
	      /** @function interface/setResourcesIds */
	      setResourcesIds: (store, resourcesIds) => {
	        store.commit('setResourcesIds', resourcesIds);
	      },
	      setPinnedResourceIds: ({
	        commit
	      }, resourceIds) => {
	        commit('setPinnedResourceIds', resourceIds);
	      },
	      /** @function interface/deleteResourceId */
	      deleteResourceId: (store, resourceId) => {
	        store.commit('deleteResourceId', resourceId);
	      },
	      /** @function interface/setIntersectionMode */
	      setIntersectionMode: (store, isIntersectionForAll) => {
	        store.commit('setIntersectionMode', isIntersectionForAll);
	      },
	      /** @function interface/setFreeMarks */
	      setFreeMarks: (store, dates) => {
	        store.commit('setFreeMarks', dates);
	      },
	      /** @function interface/setTotalClients */
	      setTotalClients: (store, totalClients) => {
	        store.commit('setTotalClients', totalClients);
	      },
	      /** @function interface/setTotalNewClientsToday */
	      setTotalNewClientsToday: (store, totalNewClientsToday) => {
	        store.commit('setTotalNewClientsToday', totalNewClientsToday);
	      },
	      /** @function interface/setMoneyStatistics */
	      setMoneyStatistics: (store, moneyStatistics) => {
	        store.commit('setMoneyStatistics', moneyStatistics);
	      },
	      setCounterMarks: (state, dates) => {
	        state.commit('setCounterMarks', dates);
	      },
	      /** @function interface/setIntersections */
	      setIntersections: (store, intersections) => {
	        store.commit('setIntersections', intersections);
	      },
	      /** @function interface/setMousePosition */
	      setMousePosition: (store, mousePosition) => {
	        store.commit('setMousePosition', mousePosition);
	      },
	      /** @function interface/setIsCurrentSenderAvailable */
	      setIsCurrentSenderAvailable: (store, isCurrentSenderAvailable) => {
	        store.commit('setIsCurrentSenderAvailable', isCurrentSenderAvailable);
	      },
	      /** @function interface/setIsFeatureEnabled */
	      setIsFeatureEnabled: (store, isFeatureEnabled) => {
	        store.commit('setIsFeatureEnabled', isFeatureEnabled);
	      },
	      /** @function interface/setCanTurnOnTrial */
	      setCanTurnOnTrial: (store, canTurnOnTrial) => {
	        store.commit('setCanTurnOnTrial', canTurnOnTrial);
	      },
	      /** @function interface/setIsShownTrialPopup */
	      setIsShownTrialPopup: (store, isShownTrialPopup) => {
	        store.commit('setIsShownTrialPopup', isShownTrialPopup);
	      },
	      /** @function interface/setEmbedItems */
	      setEmbedItems: (store, embedItems) => {
	        store.commit('setEmbedItems', embedItems);
	      },
	      /** @function interface/setAnimationPause */
	      setAnimationPause: ({
	        commit
	      }, pause) => {
	        commit('setAnimationPause', pause);
	      },
	      /** @function interface/addCreatedFromEmbedBooking */
	      addCreatedFromEmbedBooking: ({
	        commit,
	        getters
	      }, id) => {
	        const embedItems = getters.embedItems || [];
	        if (embedItems.length > 0) {
	          commit('addCreatedFromEmbedBooking', id);
	        }
	      },
	      /** @function interface/addCreatedFromEmbedWaitListItem */
	      addCreatedFromEmbedWaitListItem: ({
	        commit,
	        getters
	      }, id) => {
	        const embedItems = getters.embedItems || [];
	        if (embedItems.length > 0) {
	          commit('addCreatedFromEmbedWaitListItem', id);
	        }
	      }
	    };
	  }

	  // eslint-disable-next-line max-lines-per-function
	  getMutations() {
	    return {
	      setEditingBookingId: (state, editingBookingId) => {
	        state.editingBookingId = editingBookingId;
	      },
	      setEditingWaitListItemId: (state, editingWaitListItemId) => {
	        state.editingWaitListItemId = editingWaitListItemId;
	      },
	      setDraggedBookingId: (state, draggedBookingId) => {
	        state.draggedBookingId = draggedBookingId;
	      },
	      setResizedBookingId: (state, resizedBookingId) => {
	        state.resizedBookingId = resizedBookingId;
	      },
	      setDraggedBookingResourceId: (state, draggedBookingResourceId) => {
	        state.draggedBookingResourceId = draggedBookingResourceId;
	      },
	      setDraggedDataTransfer: (state, draggedDataTransfer) => {
	        state.draggedDataTransfer = draggedDataTransfer;
	      },
	      setIsLoaded: (state, isLoaded) => {
	        state.isLoaded = isLoaded;
	      },
	      setZoom: (state, zoom) => {
	        state.zoom = zoom;
	      },
	      setExpanded: (state, expanded) => {
	        state.expanded = expanded;
	      },
	      setScroll: (state, scroll) => {
	        state.scroll = scroll;
	      },
	      setOffHoursHover: (state, offHoursHover) => {
	        state.offHoursHover = offHoursHover;
	      },
	      setOffHoursExpanded: (state, offHoursExpanded) => {
	        state.offHoursExpanded = offHoursExpanded;
	      },
	      setWaitListExpanded: (state, waitListExpanded) => {
	        state.waitListExpanded = waitListExpanded;
	      },
	      setCalendarExpanded: (state, calendarExpanded) => {
	        state.calendarExpanded = calendarExpanded;
	      },
	      setSelectedDateTs: (state, selectedDateTs) => {
	        state.selectedDateTs = selectedDateTs;
	      },
	      setViewDateTs: (state, viewDateTs) => {
	        state.viewDateTs = viewDateTs;
	      },
	      addDeletingBooking: (state, bookingId) => {
	        state.deletingBookings[bookingId] = bookingId;
	      },
	      removeDeletingBooking: (state, bookingId) => {
	        delete state.deletingBookings[bookingId];
	      },
	      addDeletingResource: (state, resourceId) => {
	        state.deletingResources[resourceId] = resourceId;
	      },
	      removeDeletingResource: (state, resourceId) => {
	        delete state.deletingResources[resourceId];
	      },
	      addDeletingWaitListItemId: (state, waitListItemId) => {
	        state.deletingWaitListItemIds[waitListItemId] = waitListItemId;
	      },
	      removeDeletingWaitListItemId: (state, waitListItemId) => {
	        delete state.deletingWaitListItemIds[waitListItemId];
	      },
	      addSelectedCell: (state, cell) => {
	        state.selectedCells[cell.id] = cell;
	      },
	      removeSelectedCell: (state, cell) => {
	        delete state.selectedCells[cell.id];
	      },
	      clearSelectedCells: state => {
	        state.selectedCells = {};
	      },
	      setHoveredCell: (state, cell) => {
	        state.hoveredCell = cell;
	      },
	      upsertBusySlot: (state, busySlot) => {
	        var _state$busySlots, _busySlot$id, _state$busySlots$_bus;
	        (_state$busySlots$_bus = (_state$busySlots = state.busySlots)[_busySlot$id = busySlot.id]) != null ? _state$busySlots$_bus : _state$busySlots[_busySlot$id] = busySlot;
	        Object.assign(state.busySlots[busySlot.id], busySlot);
	      },
	      clearBusySlots: state => {
	        state.busySlots = {};
	      },
	      addDisabledBusySlot: (state, busySlot) => {
	        state.disabledBusySlots[busySlot.id] = busySlot;
	      },
	      clearDisabledBusySlots: state => {
	        state.disabledBusySlots = {};
	      },
	      setResourcesIds: (state, resourcesIds) => {
	        state.resourcesIds = resourcesIds.sort((a, b) => a - b);
	      },
	      setPinnedResourceIds: (state, resourceIds) => {
	        state.pinnedResourceIds = resourceIds;
	      },
	      deleteResourceId: (state, resourceId) => {
	        state.resourcesIds = state.resourcesIds.filter(id => id !== resourceId);
	      },
	      setIntersectionMode: (state, isIntersectionForAll) => {
	        state.isIntersectionForAll = isIntersectionForAll;
	      },
	      setFreeMarks: (state, dates) => {
	        state.freeMarks = dates;
	      },
	      setTotalClients: (state, totalClients) => {
	        state.totalClients = totalClients;
	      },
	      setTotalNewClientsToday: (state, totalNewClientsToday) => {
	        state.totalNewClientsToday = totalNewClientsToday;
	      },
	      setMoneyStatistics: (state, moneyStatistics) => {
	        state.moneyStatistics = moneyStatistics;
	      },
	      setCounterMarks: (state, dates) => {
	        state.counterMarks = dates;
	      },
	      setIntersections: (state, intersections) => {
	        state.intersections = intersections;
	      },
	      setMousePosition: (state, mousePosition) => {
	        state.mousePosition = mousePosition;
	      },
	      setIsCurrentSenderAvailable: (state, isCurrentSenderAvailable) => {
	        state.isCurrentSenderAvailable = isCurrentSenderAvailable;
	      },
	      setIsFeatureEnabled: (state, isFeatureEnabled) => {
	        state.isFeatureEnabled = isFeatureEnabled;
	      },
	      setCanTurnOnTrial: (state, canTurnOnTrial) => {
	        state.canTurnOnTrial = canTurnOnTrial;
	      },
	      setIsShownTrialPopup: (state, isShownTrialPopup) => {
	        state.isShownTrialPopup = isShownTrialPopup;
	      },
	      setEmbedItems: (state, embedItems) => {
	        state.embedItems = embedItems;
	      },
	      setAnimationPause: (state, pause) => {
	        state.animationPause = pause;
	      },
	      addCreatedFromEmbedBooking: (state, id) => {
	        if (main_core.Type.isArray(id)) {
	          for (const bookingId of id) {
	            state.createdFromEmbedBookings[bookingId] = bookingId;
	          }
	          return;
	        }
	        state.createdFromEmbedBookings[id] = id;
	      },
	      addCreatedFromEmbedWaitListItem: (state, id) => {
	        if (main_core.Type.isArray(id)) {
	          for (const waitListItemId of id) {
	            state.createdFromEmbedWaitListItems[waitListItemId] = waitListItemId;
	          }
	          return;
	        }
	        state.createdFromEmbedWaitListItems[id] = id;
	      }
	    };
	  }
	}

	exports.Interface = Interface;

}((this.BX.Booking.Model = this.BX.Booking.Model || {}),BX,BX.Vue3.Vuex,BX.Booking.Const,BX.Booking.Lib));
//# sourceMappingURL=interface.bundle.js.map
