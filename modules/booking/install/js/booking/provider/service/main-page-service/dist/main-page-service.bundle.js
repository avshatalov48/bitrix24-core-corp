/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_core,booking_lib_resourcesDateCache,booking_lib_apiClient,booking_const,booking_provider_service_bookingService,booking_provider_service_clientService,booking_provider_service_resourcesService,booking_provider_service_resourcesTypeService) {
	'use strict';

	var _response = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("response");
	var _extractClients = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractClients");
	var _extractClientsFromBookings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractClientsFromBookings");
	class MainPageDataExtractor {
	  constructor(response) {
	    Object.defineProperty(this, _extractClientsFromBookings, {
	      value: _extractClientsFromBookings2
	    });
	    Object.defineProperty(this, _extractClients, {
	      value: _extractClients2
	    });
	    Object.defineProperty(this, _response, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _response)[_response] = response;
	  }
	  getFavoriteIds() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].favorites.resources.map(resource => resource.id);
	  }
	  getBookings() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].bookings.map(booking => {
	      return booking_provider_service_bookingService.BookingMappers.mapDtoToModel(booking);
	    });
	  }
	  getClientsProviderModuleId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].clients.providerModuleId;
	  }
	  getClients() {
	    return [...babelHelpers.classPrivateFieldLooseBase(this, _extractClients)[_extractClients](booking_const.CrmEntity.Contact), ...babelHelpers.classPrivateFieldLooseBase(this, _extractClients)[_extractClients](booking_const.CrmEntity.Company), ...babelHelpers.classPrivateFieldLooseBase(this, _extractClientsFromBookings)[_extractClientsFromBookings]()];
	  }
	  getCounters() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].counters;
	  }
	  getResources() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    const favoriteResources = (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].favorites) == null ? void 0 : _babelHelpers$classPr2.resources) != null ? _babelHelpers$classPr : [];
	    const bookingResources = babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].bookings.flatMap(({
	      resources
	    }) => resources);
	    const result = {};
	    [...favoriteResources, ...bookingResources].forEach(resourceDto => {
	      var _resourceDto$id, _result$_resourceDto$;
	      (_result$_resourceDto$ = result[_resourceDto$id = resourceDto.id]) != null ? _result$_resourceDto$ : result[_resourceDto$id] = booking_provider_service_resourcesService.ResourceMappers.mapDtoToModel(resourceDto);
	    });
	    return Object.values(result);
	  }
	  getResourceTypes() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].resourceTypes.map(resourceTypeDto => {
	      return booking_provider_service_resourcesTypeService.ResourceTypeMappers.mapDtoToModel(resourceTypeDto);
	    });
	  }
	  getIntersectionMode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].isIntersectionForAll;
	  }
	  getIsCurrentSenderAvailable() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].isCurrentSenderAvailable;
	  }
	}
	function _extractClients2(code) {
	  const module = babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].clients.providerModuleId;
	  if (!module) {
	    return [];
	  }
	  return Object.values(babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].clients.recent[code]).map(client => ({
	    ...client,
	    type: {
	      module,
	      code
	    }
	  }));
	}
	function _extractClientsFromBookings2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].bookings.flatMap(({
	    clients
	  }) => clients.map(client => {
	    return booking_provider_service_clientService.ClientMappers.mapDtoToModel(client);
	  }));
	}

	var _response$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("response");
	class CountersExtractor {
	  constructor(response) {
	    Object.defineProperty(this, _response$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _response$1)[_response$1] = response;
	  }
	  getCounters() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response$1)[_response$1].counters;
	  }
	  getTotalClients() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response$1)[_response$1].clientStatistics.total;
	  }
	  getTotalNewClientsToday() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response$1)[_response$1].clientStatistics.totalToday;
	  }
	  getMoneyStatistics() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response$1)[_response$1].moneyStatistics;
	  }
	}

	var _dateCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dateCache");
	var _requestData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestData");
	var _requestDataForBooking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestDataForBooking");
	class MainPageService {
	  constructor() {
	    Object.defineProperty(this, _requestDataForBooking, {
	      value: _requestDataForBooking2
	    });
	    Object.defineProperty(this, _requestData, {
	      value: _requestData2
	    });
	    Object.defineProperty(this, _dateCache, {
	      writable: true,
	      value: []
	    });
	  }
	  clearCache(ids) {
	    babelHelpers.classPrivateFieldLooseBase(this, _dateCache)[_dateCache] = babelHelpers.classPrivateFieldLooseBase(this, _dateCache)[_dateCache].filter(date => booking_lib_resourcesDateCache.resourcesDateCache.isDateLoaded(date, ids));
	  }
	  async fetchData(dateTs) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _dateCache)[_dateCache].includes(dateTs)) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _dateCache)[_dateCache].push(dateTs);
	    await this.loadData(dateTs);
	  }
	  async loadData(dateTs) {
	    try {
	      if (booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/isEditingBookingMode`]) {
	        await babelHelpers.classPrivateFieldLooseBase(this, _requestDataForBooking)[_requestDataForBooking](dateTs);
	      } else {
	        await babelHelpers.classPrivateFieldLooseBase(this, _requestData)[_requestData](dateTs);
	      }
	    } catch (error) {
	      console.error('BookingMainPageGetRequest: error', error);
	    }
	  }
	  async fetchCounters() {
	    try {
	      const data = await new booking_lib_apiClient.ApiClient().get('MainPage.getCounters');
	      const extractor = new CountersExtractor(data);
	      await Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setTotalClients`, extractor.getTotalClients()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setTotalNewClientsToday`, extractor.getTotalNewClientsToday()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setMoneyStatistics`, extractor.getMoneyStatistics()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Counters}/set`, extractor.getCounters())]);
	    } catch (error) {
	      console.error('BookingMainPageGetCountersRequest: error', error);
	    }
	  }
	  async activateDemo() {
	    try {
	      return await new booking_lib_apiClient.ApiClient().get('MainPage.activateDemo');
	    } catch (error) {
	      console.error('BookingMainPageActivateDemoRequest: error', error);
	    }
	    return Promise.resolve(false);
	  }
	}
	async function _requestData2(dateTs) {
	  const data = await new booking_lib_apiClient.ApiClient().get('MainPage.get', {
	    dateTs
	  });
	  const extractor = new MainPageDataExtractor(data);
	  booking_lib_resourcesDateCache.resourcesDateCache.upsertIds(dateTs, extractor.getFavoriteIds());
	  await Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.Favorites}/set`, extractor.getFavoriteIds()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setResourcesIds`, extractor.getFavoriteIds()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setIntersectionMode`, extractor.getIntersectionMode()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Resources}/upsertMany`, extractor.getResources()), booking_core.Core.getStore().dispatch(`${booking_const.Model.ResourceTypes}/upsertMany`, extractor.getResourceTypes()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Counters}/set`, extractor.getCounters()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/upsertMany`, extractor.getBookings()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Clients}/upsertMany`, extractor.getClients()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Clients}/setProviderModuleId`, extractor.getClientsProviderModuleId()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setIsCurrentSenderAvailable`, extractor.getIsCurrentSenderAvailable())]);
	}
	async function _requestDataForBooking2(dateTs) {
	  const bookingId = booking_core.Core.getParams().editingBookingId;
	  const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
	  const resourcesIds = booking_core.Core.getStore().getters[`${booking_const.Model.Favorites}/get`];
	  const data = await new booking_lib_apiClient.ApiClient().get('MainPage.getForBooking', {
	    dateTs,
	    bookingId,
	    timezone,
	    resourcesIds
	  });
	  const extractor = new MainPageDataExtractor(data);
	  const promises = [booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setIntersectionMode`, extractor.getIntersectionMode()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Resources}/upsertMany`, extractor.getResources()), booking_core.Core.getStore().dispatch(`${booking_const.Model.ResourceTypes}/upsertMany`, extractor.getResourceTypes()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Counters}/set`, extractor.getCounters()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/upsertMany`, extractor.getBookings()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Clients}/upsertMany`, extractor.getClients()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Clients}/setProviderModuleId`, extractor.getClientsProviderModuleId()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setIsCurrentSenderAvailable`, extractor.getIsCurrentSenderAvailable())];
	  const editingBooking = extractor.getBookings().find(booking => booking.id === bookingId);
	  if (!editingBooking && dateTs === 0) {
	    promises.push(booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setEditingBookingId`, 0));
	  }
	  let selectedDate = new Date(dateTs * 1000);
	  if (editingBooking && dateTs === 0) {
	    const dateFrom = new Date(editingBooking.dateFromTs);
	    selectedDate = new Date(dateFrom.getFullYear(), dateFrom.getMonth(), dateFrom.getDate());
	    promises.push(booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setSelectedDateTs`, selectedDate.getTime()));
	    babelHelpers.classPrivateFieldLooseBase(this, _dateCache)[_dateCache].push(selectedDate.getTime() / 1000);
	  }
	  let selectedResourcesIds = resourcesIds;
	  if (editingBooking && resourcesIds.length === 0) {
	    selectedResourcesIds = [editingBooking.resourcesIds[0]];
	    promises.push(booking_core.Core.getStore().dispatch(`${booking_const.Model.Favorites}/set`, [editingBooking.resourcesIds[0]]), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setResourcesIds`, [editingBooking.resourcesIds[0]]));
	  }
	  booking_lib_resourcesDateCache.resourcesDateCache.upsertIds(selectedDate.getTime() / 1000, selectedResourcesIds);
	  await Promise.all(promises);
	}
	const mainPageService = new MainPageService();

	exports.mainPageService = mainPageService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking,BX.Booking.Lib,BX.Booking.Lib,BX.Booking.Const,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Provider.Service));
//# sourceMappingURL=main-page-service.bundle.js.map
