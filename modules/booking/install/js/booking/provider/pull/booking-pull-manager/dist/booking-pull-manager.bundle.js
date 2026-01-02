/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,main_core,main_core_events,pull_queuemanager,booking_provider_service_countersService,booking_provider_service_bookingService,booking_provider_service_calendarService,booking_provider_service_resourcesService,booking_provider_service_resourcesTypeService,booking_core,booking_const,booking_provider_service_clientService,booking_provider_service_mainPageService,booking_provider_service_waitListService) {
	'use strict';

	class BasePullHandler {
	  constructor() {
	    if (new.target === BasePullHandler) {
	      throw new TypeError('BasePullHandler: An abstract class cannot be instantiated');
	    }
	  }
	  getMap() {
	    return {};
	  }
	  getDelayedMap() {
	    return {};
	  }
	}

	var _handleBookingAdded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleBookingAdded");
	var _handleBookingDeleted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleBookingDeleted");
	var _updateCounters = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateCounters");
	class BookingPullHandler extends BasePullHandler {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _handleBookingAdded, {
	      writable: true,
	      value: params => {
	        const bookingDto = params.booking;
	        const booking = booking_provider_service_bookingService.BookingMappers.mapDtoToModel(bookingDto);
	        const resources = bookingDto.resources.map(resourceDto => {
	          return booking_provider_service_resourcesService.ResourceMappers.mapDtoToModel(resourceDto);
	        });
	        const clients = bookingDto.clients.map(clientDto => {
	          return booking_provider_service_clientService.ClientMappers.mapDtoToModel(clientDto);
	        });
	        void Promise.all([booking_core.Core.getStore().dispatch('resources/upsertMany', resources), booking_core.Core.getStore().dispatch('bookings/upsert', booking), booking_core.Core.getStore().dispatch('clients/upsertMany', clients)]);
	      }
	    });
	    Object.defineProperty(this, _handleBookingDeleted, {
	      writable: true,
	      value: params => {
	        void booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/delete`, params.id);
	        void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/addDeletingBooking`, params.id);
	      }
	    });
	    Object.defineProperty(this, _updateCounters, {
	      writable: true,
	      value: async () => {
	        await booking_provider_service_mainPageService.mainPageService.fetchCounters();
	      }
	    });
	  }
	  getMap() {
	    return {
	      bookingAdded: babelHelpers.classPrivateFieldLooseBase(this, _handleBookingAdded)[_handleBookingAdded],
	      bookingUpdated: babelHelpers.classPrivateFieldLooseBase(this, _handleBookingAdded)[_handleBookingAdded],
	      bookingDeleted: babelHelpers.classPrivateFieldLooseBase(this, _handleBookingDeleted)[_handleBookingDeleted]
	    };
	  }
	  getDelayedMap() {
	    return {
	      bookingAdded: babelHelpers.classPrivateFieldLooseBase(this, _updateCounters)[_updateCounters],
	      bookingUpdated: babelHelpers.classPrivateFieldLooseBase(this, _updateCounters)[_updateCounters],
	      bookingDeleted: babelHelpers.classPrivateFieldLooseBase(this, _updateCounters)[_updateCounters]
	    };
	  }
	}

	var _handleCountersUpdated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCountersUpdated");
	var _isFilterMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFilterMode");
	var _getViewDateTs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getViewDateTs");
	class CountersPullHandler extends BasePullHandler {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _getViewDateTs, {
	      value: _getViewDateTs2
	    });
	    Object.defineProperty(this, _isFilterMode, {
	      value: _isFilterMode2
	    });
	    Object.defineProperty(this, _handleCountersUpdated, {
	      value: _handleCountersUpdated2
	    });
	  }
	  getDelayedMap() {
	    return {
	      countersUpdated: babelHelpers.classPrivateFieldLooseBase(this, _handleCountersUpdated)[_handleCountersUpdated].bind(this)
	    };
	  }
	}
	async function _handleCountersUpdated2(params) {
	  await booking_provider_service_countersService.countersService.fetchData();
	  await booking_provider_service_bookingService.bookingService.getById(params.entityId);
	  const isFilterMode = babelHelpers.classPrivateFieldLooseBase(this, _isFilterMode)[_isFilterMode]();
	  if (!isFilterMode) {
	    const viewDateTs = babelHelpers.classPrivateFieldLooseBase(this, _getViewDateTs)[_getViewDateTs]();
	    const forcePull = true;
	    await booking_provider_service_calendarService.calendarService.loadCounterMarks(viewDateTs, forcePull);
	  }
	}
	function _isFilterMode2() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Filter}/isFilterMode`];
	}
	function _getViewDateTs2() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/viewDateTs`];
	}

	var _handleMessageSent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleMessageSent");
	class MessagePullHandler extends BasePullHandler {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _handleMessageSent, {
	      writable: true,
	      value: ({
	        entityId: bookingId,
	        message
	      }) => {
	        var _booking$messages;
	        const booking = this.$store.getters[`${booking_const.Model.Bookings}/getById`](bookingId);
	        if (!booking) {
	          return;
	        }
	        void this.$store.dispatch(`${booking_const.Model.Bookings}/update`, {
	          id: booking.id,
	          booking: {
	            ...booking,
	            messages: [...((_booking$messages = booking.messages) != null ? _booking$messages : []), message]
	          }
	        });
	      }
	    });
	  }
	  getMap() {
	    return {
	      messageSent: babelHelpers.classPrivateFieldLooseBase(this, _handleMessageSent)[_handleMessageSent]
	    };
	  }
	  get $store() {
	    return booking_core.Core.getStore();
	  }
	}

	var _handleResourceAdded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleResourceAdded");
	var _handleResourceUpdated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleResourceUpdated");
	var _handleResourceDeleted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleResourceDeleted");
	class ResourcePullHandler extends BasePullHandler {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _handleResourceDeleted, {
	      value: _handleResourceDeleted2
	    });
	    Object.defineProperty(this, _handleResourceUpdated, {
	      value: _handleResourceUpdated2
	    });
	    Object.defineProperty(this, _handleResourceAdded, {
	      value: _handleResourceAdded2
	    });
	  }
	  getMap() {
	    return {
	      resourceAdded: babelHelpers.classPrivateFieldLooseBase(this, _handleResourceAdded)[_handleResourceAdded].bind(this),
	      resourceUpdated: babelHelpers.classPrivateFieldLooseBase(this, _handleResourceUpdated)[_handleResourceUpdated].bind(this),
	      resourceDeleted: babelHelpers.classPrivateFieldLooseBase(this, _handleResourceDeleted)[_handleResourceDeleted].bind(this)
	    };
	  }
	}
	async function _handleResourceAdded2(params) {
	  const resourceDto = params.resource;
	  const resource = booking_provider_service_resourcesService.ResourceMappers.mapDtoToModel(resourceDto);
	  await booking_core.Core.getStore().dispatch(`${booking_const.Model.Resources}/upsert`, resource);
	  if (resource.isMain) {
	    await booking_core.Core.getStore().dispatch(`${booking_const.Model.Favorites}/addMany`, [resource.id]);
	  }
	  const isFilterMode = booking_core.Core.getStore().getters[`${booking_const.Model.Filter}/isFilterMode`];
	  if (isFilterMode) {
	    return;
	  }
	  const favorites = booking_core.Core.getStore().getters[`${booking_const.Model.Favorites}/get`];
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setResourcesIds`, favorites);
	}
	function _handleResourceUpdated2(params) {
	  const resourceDto = params.resource;
	  const resource = booking_provider_service_resourcesService.ResourceMappers.mapDtoToModel(resourceDto);
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.Resources}/upsert`, resource);
	}
	function _handleResourceDeleted2(params) {
	  void Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.Resources}/delete`, params.id), booking_core.Core.getStore().dispatch(`${booking_const.Model.Favorites}/delete`, params.id), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/deleteResourceId`, params.id)]);
	}

	var _handleResourceTypeAdded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleResourceTypeAdded");
	class ResourceTypePullHandler extends BasePullHandler {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _handleResourceTypeAdded, {
	      value: _handleResourceTypeAdded2
	    });
	  }
	  getMap() {
	    return {
	      resourceTypeAdded: babelHelpers.classPrivateFieldLooseBase(this, _handleResourceTypeAdded)[_handleResourceTypeAdded].bind(this)
	    };
	  }
	}
	function _handleResourceTypeAdded2(params) {
	  const resourceTypeDto = params.resourceType;
	  const resourceType = booking_provider_service_resourcesTypeService.ResourceTypeMappers.mapDtoToModel(resourceTypeDto);
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.ResourceTypes}/upsert`, resourceType);
	}

	var _delayTimeout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("delayTimeout");
	var _handleWaitListItemAdded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleWaitListItemAdded");
	var _handleWaitListItemDeleted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleWaitListItemDeleted");
	var _updateCounters$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateCounters");
	class WaitListItemPullHandler extends BasePullHandler {
	  constructor(props) {
	    super(props);
	    Object.defineProperty(this, _updateCounters$1, {
	      value: _updateCounters2
	    });
	    Object.defineProperty(this, _handleWaitListItemDeleted, {
	      value: _handleWaitListItemDeleted2
	    });
	    Object.defineProperty(this, _handleWaitListItemAdded, {
	      value: _handleWaitListItemAdded2
	    });
	    Object.defineProperty(this, _delayTimeout, {
	      writable: true,
	      value: null
	    });
	    this.handleWaitListItemAdded = babelHelpers.classPrivateFieldLooseBase(this, _handleWaitListItemAdded)[_handleWaitListItemAdded].bind(this);
	    this.handleWaitListItemDeleted = babelHelpers.classPrivateFieldLooseBase(this, _handleWaitListItemDeleted)[_handleWaitListItemDeleted].bind(this);
	    this.updateCounters = babelHelpers.classPrivateFieldLooseBase(this, _updateCounters$1)[_updateCounters$1].bind(this);
	  }
	  getMap() {
	    return {
	      waitListItemAdded: this.handleWaitListItemAdded,
	      waitListItemDeleted: this.handleWaitListItemDeleted
	    };
	  }
	  getDelayedMap() {
	    return {
	      waitListItemAdded: this.updateCounters,
	      waitListItemDeleted: this.updateCounters
	    };
	  }
	}
	function _handleWaitListItemAdded2(params) {
	  const waitListItemDto = params.waitListItem;
	  const waitListItem = booking_provider_service_waitListService.WaitListMappers.mapDtoToModel(waitListItemDto);
	  const clients = waitListItemDto.clients.map(clientDto => {
	    return booking_provider_service_clientService.ClientMappers.mapDtoToModel(clientDto);
	  });
	  const $store = booking_core.Core.getStore();
	  void Promise.all([$store.dispatch(`${booking_const.Model.WaitList}/upsert`, waitListItem), $store.dispatch(`${booking_const.Model.Clients}/upsertMany`, clients)]);
	}
	function _handleWaitListItemDeleted2(params) {
	  const $store = booking_core.Core.getStore();
	  void $store.dispatch(`${booking_const.Model.WaitList}/delete`, params.id);
	  void $store.commit(`${booking_const.Model.Interface}/addDeletingWaitListItemId`, params.id);
	}
	async function _updateCounters2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _delayTimeout)[_delayTimeout]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _delayTimeout)[_delayTimeout] = setTimeout(async () => {
	    try {
	      await booking_provider_service_mainPageService.mainPageService.fetchCounters();
	    } finally {
	      babelHelpers.classPrivateFieldLooseBase(this, _delayTimeout)[_delayTimeout] = null;
	    }
	  }, 0);
	}

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _loadItemsDelay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadItemsDelay");
	var _handlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handlers");
	var _onBeforePull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforePull");
	var _onPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPull");
	var _onBeforeQueueExecute = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforeQueueExecute");
	var _onQueueExecute = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onQueueExecute");
	var _onReload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onReload");
	var _executeQueue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("executeQueue");
	class BookingPullManager {
	  constructor(_params2) {
	    Object.defineProperty(this, _executeQueue, {
	      value: _executeQueue2
	    });
	    Object.defineProperty(this, _onReload, {
	      value: _onReload2
	    });
	    Object.defineProperty(this, _onQueueExecute, {
	      value: _onQueueExecute2
	    });
	    Object.defineProperty(this, _onBeforeQueueExecute, {
	      value: _onBeforeQueueExecute2
	    });
	    Object.defineProperty(this, _onPull, {
	      value: _onPull2
	    });
	    Object.defineProperty(this, _onBeforePull, {
	      value: _onBeforePull2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loadItemsDelay, {
	      writable: true,
	      value: 500
	    });
	    Object.defineProperty(this, _handlers, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = _params2;
	    babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers] = new Set([new BookingPullHandler(), new ResourcePullHandler(), new ResourceTypePullHandler(), new CountersPullHandler(), new MessagePullHandler(), new WaitListItemPullHandler()]);
	  }
	  initQueueManager() {
	    return new pull_queuemanager.QueueManager({
	      moduleId: booking_const.Module.Booking,
	      userId: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].currentUserId,
	      config: {
	        loadItemsDelay: babelHelpers.classPrivateFieldLooseBase(this, _loadItemsDelay)[_loadItemsDelay]
	      },
	      additionalData: {},
	      events: {
	        onBeforePull: baseEvent => {
	          babelHelpers.classPrivateFieldLooseBase(this, _onBeforePull)[_onBeforePull](baseEvent);
	        },
	        onPull: baseEvent => {
	          babelHelpers.classPrivateFieldLooseBase(this, _onPull)[_onPull](baseEvent);
	        }
	      },
	      callbacks: {
	        onBeforeQueueExecute: items => {
	          return babelHelpers.classPrivateFieldLooseBase(this, _onBeforeQueueExecute)[_onBeforeQueueExecute](items);
	        },
	        onQueueExecute: items => {
	          return babelHelpers.classPrivateFieldLooseBase(this, _onQueueExecute)[_onQueueExecute](items);
	        },
	        onReload: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _onReload)[_onReload]();
	        }
	      }
	    });
	  }
	}
	function _onBeforePull2(baseEvent) {
	  const {
	    pullData: {
	      command,
	      params
	    }
	  } = baseEvent.data;
	  for (const handler of babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers]) {
	    var _handler$getMap$comma, _handler$getMap;
	    (_handler$getMap$comma = (_handler$getMap = handler.getMap())[command]) == null ? void 0 : _handler$getMap$comma.call(_handler$getMap, params);
	  }
	}
	function _onPull2(baseEvent) {
	  const {
	    pullData: {
	      command,
	      params
	    },
	    promises
	  } = baseEvent.data;
	  for (const handler of babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers]) {
	    if (handler.getDelayedMap()[command]) {
	      var _params$entityId;
	      promises.push(Promise.resolve({
	        data: {
	          id: (_params$entityId = params.entityId) != null ? _params$entityId : main_core.Text.getRandom(),
	          command,
	          params
	        }
	      }));
	    }
	  }
	}
	function _onBeforeQueueExecute2(items) {
	  return Promise.resolve();
	}
	async function _onQueueExecute2(items) {
	  await babelHelpers.classPrivateFieldLooseBase(this, _executeQueue)[_executeQueue](items);
	}
	function _onReload2(event) {}
	function _executeQueue2(items) {
	  return new Promise(resolve => {
	    items.forEach(item => {
	      const {
	        data: {
	          command,
	          params
	        }
	      } = item;
	      for (const handler of babelHelpers.classPrivateFieldLooseBase(this, _handlers)[_handlers]) {
	        var _handler$getDelayedMa, _handler$getDelayedMa2;
	        (_handler$getDelayedMa = (_handler$getDelayedMa2 = handler.getDelayedMap())[command]) == null ? void 0 : _handler$getDelayedMa.call(_handler$getDelayedMa2, params);
	      }
	    });
	    resolve();
	  });
	}

	exports.BookingPullManager = BookingPullManager;

}((this.BX.Booking.Provider.Pull = this.BX.Booking.Provider.Pull || {}),BX,BX.Event,BX.Pull,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking,BX.Booking.Const,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Provider.Service));
//# sourceMappingURL=booking-pull-manager.bundle.js.map
