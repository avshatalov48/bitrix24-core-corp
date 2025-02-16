/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,main_core,booking_core,booking_const,booking_lib_resourcesDateCache,booking_lib_apiClient,booking_provider_service_bookingService,booking_provider_service_clientService,booking_provider_service_resourcesService) {
	'use strict';

	var _response = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("response");
	class ResourceDialogDataExtractor {
	  constructor(response) {
	    Object.defineProperty(this, _response, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _response)[_response] = response;
	  }
	  getBookings() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].bookings.map(booking => {
	      return booking_provider_service_bookingService.BookingMappers.mapDtoToModel(booking);
	    });
	  }
	  getClients() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].bookings.flatMap(({
	      clients
	    }) => clients.map(client => {
	      return booking_provider_service_clientService.ClientMappers.mapDtoToModel(client);
	    }));
	  }
	  getResources() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].resources.map(resource => {
	      return booking_provider_service_resourcesService.ResourceMappers.mapDtoToModel(resource);
	    });
	  }
	}

	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	class MainResourcesExtractor {
	  constructor(data) {
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = data;
	  }
	  getMainResourceIds() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].map(resource => resource.id);
	  }
	}

	var _queryCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("queryCache");
	var _loadByIdsPromises = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadByIdsPromises");
	var _mainResourcesCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mainResourcesCache");
	var _requestLoadByIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestLoadByIds");
	var _upsertResponseData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("upsertResponseData");
	var _isQueryLoaded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isQueryLoaded");
	var _requestGetMainResources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestGetMainResources");
	class ResourceDialogService {
	  constructor() {
	    Object.defineProperty(this, _requestGetMainResources, {
	      value: _requestGetMainResources2
	    });
	    Object.defineProperty(this, _isQueryLoaded, {
	      value: _isQueryLoaded2
	    });
	    Object.defineProperty(this, _upsertResponseData, {
	      value: _upsertResponseData2
	    });
	    Object.defineProperty(this, _requestLoadByIds, {
	      value: _requestLoadByIds2
	    });
	    Object.defineProperty(this, _queryCache, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _loadByIdsPromises, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _mainResourcesCache, {
	      writable: true,
	      value: null
	    });
	  }
	  async loadByIds(idsToLoad, dateTs) {
	    try {
	      var _babelHelpers$classPr, _babelHelpers$classPr2;
	      (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _loadByIdsPromises)[_loadByIdsPromises])[dateTs]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[dateTs] = {};
	      const requestedIds = Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _loadByIdsPromises)[_loadByIdsPromises][dateTs]).flatMap(key => key.split(',')).map(id => Number(id));
	      const requestedIdsSet = new Set(requestedIds);
	      const ids = idsToLoad.filter(id => !requestedIdsSet.has(id));
	      if (!main_core.Type.isArrayFilled(ids)) {
	        await Promise.all(Object.values(babelHelpers.classPrivateFieldLooseBase(this, _loadByIdsPromises)[_loadByIdsPromises][dateTs]));
	        return;
	      }
	      const idsKey = ids.join(',');
	      babelHelpers.classPrivateFieldLooseBase(this, _loadByIdsPromises)[_loadByIdsPromises][dateTs][idsKey] = babelHelpers.classPrivateFieldLooseBase(this, _requestLoadByIds)[_requestLoadByIds](ids, dateTs);
	      const data = await babelHelpers.classPrivateFieldLooseBase(this, _loadByIdsPromises)[_loadByIdsPromises][dateTs][idsKey];
	      await babelHelpers.classPrivateFieldLooseBase(this, _upsertResponseData)[_upsertResponseData](data, dateTs);
	    } catch (error) {
	      console.error('ResourceDialogLoadByIdsRequest: error', error);
	    }
	  }
	  async fillDialog(dateTs) {
	    try {
	      const data = await new booking_lib_apiClient.ApiClient().post('ResourceDialog.fillDialog', {
	        dateTs
	      });
	      await babelHelpers.classPrivateFieldLooseBase(this, _upsertResponseData)[_upsertResponseData](data, dateTs);
	    } catch (error) {
	      console.error('ResourceDialogFillDialogRequest: error', error);
	    }
	  }
	  async doSearch(query, dateTs) {
	    if (!main_core.Type.isStringFilled(query)) {
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isQueryLoaded)[_isQueryLoaded](query)) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _queryCache)[_queryCache].push(query);
	    try {
	      const data = await new booking_lib_apiClient.ApiClient().post('ResourceDialog.doSearch', {
	        query,
	        dateTs
	      });
	      await babelHelpers.classPrivateFieldLooseBase(this, _upsertResponseData)[_upsertResponseData](data, dateTs);
	    } catch (error) {
	      console.error('ResourceDialogDoSearchRequest: error', error);
	    }
	  }
	  async getMainResources() {
	    try {
	      if (main_core.Type.isNull(babelHelpers.classPrivateFieldLooseBase(this, _mainResourcesCache)[_mainResourcesCache])) {
	        babelHelpers.classPrivateFieldLooseBase(this, _mainResourcesCache)[_mainResourcesCache] = babelHelpers.classPrivateFieldLooseBase(this, _requestGetMainResources)[_requestGetMainResources]();
	      }
	      const data = await babelHelpers.classPrivateFieldLooseBase(this, _mainResourcesCache)[_mainResourcesCache];
	      const extractor = new MainResourcesExtractor(data);
	      const ids = extractor.getMainResourceIds();
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.MainResources}/setMainResources`, ids);
	    } catch (error) {
	      console.error('ResourceDialogGetMainResources: error', error);
	    }
	  }
	  clearMainResourcesCache() {
	    babelHelpers.classPrivateFieldLooseBase(this, _mainResourcesCache)[_mainResourcesCache] = null;
	  }
	}
	async function _requestLoadByIds2(ids, dateTs) {
	  return new booking_lib_apiClient.ApiClient().post('ResourceDialog.loadByIds', {
	    ids,
	    dateTs
	  });
	}
	async function _upsertResponseData2(data, dateTs) {
	  const extractor = new ResourceDialogDataExtractor(data);
	  booking_lib_resourcesDateCache.resourcesDateCache.upsertIds(dateTs, extractor.getResources().map(it => it.id));
	  await Promise.all([booking_core.Core.getStore().dispatch('bookings/upsertMany', extractor.getBookings()), booking_core.Core.getStore().dispatch('clients/upsertMany', extractor.getClients()), booking_core.Core.getStore().dispatch('resources/upsertMany', extractor.getResources())]);
	}
	function _isQueryLoaded2(query) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _queryCache)[_queryCache].some(it => query.startsWith(it));
	}
	function _requestGetMainResources2() {
	  const api = new booking_lib_apiClient.ApiClient();
	  return api.post('ResourceDialog.getMainResources', {});
	}
	const resourceDialogService = new ResourceDialogService();

	exports.resourceDialogService = resourceDialogService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX,BX.Booking,BX.Booking.Const,BX.Booking.Lib,BX.Booking.Lib,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Provider.Service));
//# sourceMappingURL=resource-dialog-service.bundle.js.map
