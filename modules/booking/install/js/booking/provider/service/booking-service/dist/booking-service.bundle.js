/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,main_core,booking_core,booking_const,booking_lib_apiClient,booking_lib_bookingFilter,booking_provider_service_mainPageService,booking_provider_service_clientService,booking_provider_service_resourcesService) {
	'use strict';

	function mapModelToDto(booking) {
	  const mappings = {
	    id: () => Number(booking.id) || 0,
	    resources: () => booking.resourcesIds.map(id => ({
	      id
	    })),
	    primaryClient: () => {
	      var _booking$clients;
	      return (_booking$clients = booking.clients) == null ? void 0 : _booking$clients[0];
	    },
	    clients: () => booking.clients,
	    name: () => booking.name,
	    datePeriod: () => ({
	      from: {
	        timestamp: booking.dateFromTs / 1000,
	        timezone: booking.timezoneFrom
	      },
	      to: {
	        timestamp: booking.dateToTs / 1000,
	        timezone: booking.timezoneTo
	      }
	    }),
	    isConfirmed: () => booking.isConfirmed,
	    rrule: () => booking.rrule,
	    note: () => booking.note,
	    visitStatus: () => booking.visitStatus,
	    externalData: () => booking.externalData
	  };
	  const dependentFields = new Map([['resources', ['resourcesIds']], ['datePeriod', ['dateFromTs', 'dateToTs']]]);
	  return Object.keys(mappings).reduce((result, field) => {
	    const dependencies = dependentFields.get(field);
	    const hasDependencies = dependencies ? dependencies.every(dep => dep in booking) : true;
	    if (hasDependencies && (field in booking || dependencies)) {
	      const value = mappings[field]();
	      if (value !== undefined) {
	        // eslint-disable-next-line no-param-reassign
	        result[field] = value;
	      }
	    }
	    return result;
	  }, {});
	}
	function mapDtoToModel(bookingDto) {
	  const clients = bookingDto.clients.filter(client => main_core.Type.isArrayFilled(Object.values(client.data)));
	  return {
	    id: bookingDto.id,
	    updatedAt: bookingDto.updatedAt,
	    resourcesIds: bookingDto.resources.map(({
	      id
	    }) => id),
	    primaryClient: clients == null ? void 0 : clients[0],
	    clients,
	    counter: bookingDto.counter,
	    counters: bookingDto.counters,
	    name: bookingDto.name,
	    dateFromTs: bookingDto.datePeriod.from.timestamp * 1000,
	    timezoneFrom: bookingDto.datePeriod.from.timezone,
	    dateToTs: bookingDto.datePeriod.to.timestamp * 1000,
	    timezoneTo: bookingDto.datePeriod.to.timezone,
	    isConfirmed: bookingDto.isConfirmed,
	    rrule: bookingDto.rrule,
	    note: bookingDto.note,
	    visitStatus: bookingDto.visitStatus,
	    externalData: bookingDto.externalData
	  };
	}

	var _response = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("response");
	class BookingDataExtractor {
	  constructor(response) {
	    Object.defineProperty(this, _response, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _response)[_response] = response;
	  }
	  getBookings() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].map(bookingDto => mapDtoToModel(bookingDto));
	  }
	  getBookingsIds() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].map(({
	      id
	    }) => id);
	  }
	  getClients() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].flatMap(({
	      clients
	    }) => clients).map(clientDto => {
	      return booking_provider_service_clientService.ClientMappers.mapDtoToModel(clientDto);
	    });
	  }
	  getResources() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].flatMap(({
	      resources
	    }) => resources).map(resourceDto => {
	      return booking_provider_service_resourcesService.ResourceMappers.mapDtoToModel(resourceDto);
	    });
	  }
	}

	var _filterRequests = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filterRequests");
	var _lastFilterRequest = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lastFilterRequest");
	var _onAfterDelete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onAfterDelete");
	var _extractFilterData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractFilterData");
	var _requestFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestFilter");
	class BookingService {
	  constructor() {
	    Object.defineProperty(this, _requestFilter, {
	      value: _requestFilter2
	    });
	    Object.defineProperty(this, _extractFilterData, {
	      value: _extractFilterData2
	    });
	    Object.defineProperty(this, _onAfterDelete, {
	      value: _onAfterDelete2
	    });
	    Object.defineProperty(this, _filterRequests, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _lastFilterRequest, {
	      writable: true,
	      value: void 0
	    });
	  }
	  async add(booking) {
	    const id = booking.id;
	    try {
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/addQuickFilterIgnoredBookingId`, id);
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/add`, booking);
	      const bookingDto = mapModelToDto(booking);
	      const data = await new booking_lib_apiClient.ApiClient().post('Booking.add', {
	        booking: bookingDto
	      });
	      const createdBooking = mapDtoToModel(data);
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/addQuickFilterIgnoredBookingId`, createdBooking.id);
	      void booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/update`, {
	        id,
	        booking: createdBooking
	      });
	      void booking_provider_service_mainPageService.mainPageService.fetchCounters();
	    } catch (error) {
	      void booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/delete`, id);
	      console.error('BookingService: add error', error);
	    }
	  }
	  async addList(bookings) {
	    try {
	      const bookingList = bookings.map(booking => mapModelToDto(booking));
	      const api = new booking_lib_apiClient.ApiClient();
	      const data = await api.post('Booking.addList', {
	        bookingList
	      });
	      const createdBookings = data.map(d => mapDtoToModel(d));
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/upsertMany`, createdBookings);
	      void booking_provider_service_mainPageService.mainPageService.fetchCounters();
	      return createdBookings;
	    } catch (error) {
	      console.error('BookingService: add list error', error);
	      return [];
	    }
	  }
	  async update(booking) {
	    const id = booking.id;
	    const bookingBeforeUpdate = {
	      ...booking_core.Core.getStore().getters[`${booking_const.Model.Bookings}/getById`](id)
	    };
	    try {
	      if (booking.clients) {
	        var _booking$primaryClien;
	        (_booking$primaryClien = booking.primaryClient) != null ? _booking$primaryClien : booking.primaryClient = booking.clients[0];
	      }
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/update`, {
	        id,
	        booking
	      });
	      const bookingDto = mapModelToDto(booking);
	      const data = await new booking_lib_apiClient.ApiClient().post('Booking.update', {
	        booking: bookingDto
	      });
	      const updatedBooking = mapDtoToModel(data);
	      void booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/update`, {
	        id,
	        booking: updatedBooking
	      });
	      const clients = new BookingDataExtractor([data]).getClients();
	      void booking_core.Core.getStore().dispatch('clients/upsertMany', clients);
	      void booking_provider_service_mainPageService.mainPageService.fetchCounters();
	    } catch (error) {
	      void booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/update`, {
	        id,
	        booking: bookingBeforeUpdate
	      });
	      console.error('BookingService: update error', error);
	    }
	  }
	  async delete(id) {
	    const bookingBeforeDelete = {
	      ...booking_core.Core.getStore().getters[`${booking_const.Model.Bookings}/getById`](id)
	    };
	    try {
	      void booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/delete`, id);
	      await new booking_lib_apiClient.ApiClient().post('Booking.delete', {
	        id
	      });
	      await babelHelpers.classPrivateFieldLooseBase(this, _onAfterDelete)[_onAfterDelete](id);
	    } catch (error) {
	      void booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/upsert`, bookingBeforeDelete);
	      console.error('BookingService: delete error', error);
	    }
	  }
	  async deleteList(ids) {
	    try {
	      void booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/deleteMany`, ids);
	      await new booking_lib_apiClient.ApiClient().post('Booking.deleteList', {
	        ids
	      });
	      await Promise.all(ids.map(id => babelHelpers.classPrivateFieldLooseBase(this, _onAfterDelete)[_onAfterDelete](id)));
	    } catch (error) {
	      console.error('BookingService: delete list error', error);
	    }
	  }
	  clearFilterCache() {
	    babelHelpers.classPrivateFieldLooseBase(this, _filterRequests)[_filterRequests] = {};
	  }
	  async filter(fields) {
	    try {
	      var _babelHelpers$classPr, _babelHelpers$classPr2;
	      const filter = booking_lib_bookingFilter.bookingFilter.prepareFilter(fields);
	      const key = JSON.stringify(filter);
	      (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _filterRequests)[_filterRequests])[key]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[key] = babelHelpers.classPrivateFieldLooseBase(this, _requestFilter)[_requestFilter](filter);
	      babelHelpers.classPrivateFieldLooseBase(this, _lastFilterRequest)[_lastFilterRequest] = babelHelpers.classPrivateFieldLooseBase(this, _filterRequests)[_filterRequests][key];
	      const data = await babelHelpers.classPrivateFieldLooseBase(this, _filterRequests)[_filterRequests][key];
	      void babelHelpers.classPrivateFieldLooseBase(this, _extractFilterData)[_extractFilterData]({
	        data,
	        key
	      });
	    } catch (error) {
	      console.error('BookingService: filter error', error);
	    }
	  }
	  async getById(id) {
	    try {
	      const data = await babelHelpers.classPrivateFieldLooseBase(this, _requestFilter)[_requestFilter]({
	        ID: [id]
	      });
	      const extractor = new BookingDataExtractor(data);
	      await Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.Resources}/upsertMany`, extractor.getResources()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/upsertMany`, extractor.getBookings()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Clients}/upsertMany`, extractor.getClients())]);
	    } catch (error) {
	      console.error('BookingService: getById error', error);
	    }
	  }
	}
	async function _onAfterDelete2(id) {
	  const editingBookingId = booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/editingBookingId`];
	  if (id === editingBookingId) {
	    await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setEditingBookingId`, 0);
	    const selectedDateTs = booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/selectedDateTs`];
	    await booking_provider_service_mainPageService.mainPageService.loadData(selectedDateTs / 1000);
	    const resourcesIds = booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/resourcesIds`];
	    booking_provider_service_mainPageService.mainPageService.clearCache(resourcesIds);
	  }
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/addDeletingBooking`, id);
	}
	async function _extractFilterData2({
	  data,
	  key
	}) {
	  const extractor = new BookingDataExtractor(data);
	  await Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.Resources}/insertMany`, extractor.getResources()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Bookings}/insertMany`, extractor.getBookings()), booking_core.Core.getStore().dispatch(`${booking_const.Model.Clients}/insertMany`, extractor.getClients())]);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _filterRequests)[_filterRequests][key] !== babelHelpers.classPrivateFieldLooseBase(this, _lastFilterRequest)[_lastFilterRequest]) {
	    return;
	  }
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setFilteredBookingsIds`, extractor.getBookingsIds());
	}
	async function _requestFilter2(filter) {
	  return new booking_lib_apiClient.ApiClient().post('Booking.list', {
	    filter,
	    select: ['RESOURCES', 'CLIENTS', 'EXTERNAL_DATA', 'NOTE'],
	    withCounters: true,
	    withClientData: true,
	    withExternalData: true
	  });
	}
	const bookingService = new BookingService();

	const BookingMappers = {
	  mapModelToDto,
	  mapDtoToModel
	};

	exports.BookingMappers = BookingMappers;
	exports.bookingService = bookingService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX,BX.Booking,BX.Booking.Const,BX.Booking.Lib,BX.Booking.Lib,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.Booking.Provider.Service));
//# sourceMappingURL=booking-service.bundle.js.map
