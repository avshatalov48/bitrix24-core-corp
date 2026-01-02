/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,main_core,booking_core,booking_const,booking_lib_apiClient,booking_provider_service_mainPageService,booking_provider_service_clientService) {
	'use strict';

	function mapModelToDto(waitListItem) {
	  return {
	    id: waitListItem.id,
	    createdBy: waitListItem.createdBy,
	    createdAt: waitListItem.createdAt / 1000,
	    updatedAt: waitListItem.updatedAt / 1000,
	    clients: waitListItem.clients,
	    note: waitListItem.note,
	    externalData: waitListItem.externalData
	  };
	}
	function mapDtoToModel(waitListItemDto) {
	  const clients = waitListItemDto.clients.filter(client => main_core.Type.isArrayFilled(Object.values(client.data)));
	  return {
	    id: waitListItemDto.id,
	    createdBy: waitListItemDto.createdBy,
	    createdAt: waitListItemDto.createdAt * 1000,
	    updatedAt: waitListItemDto.updatedAt * 1000,
	    clients,
	    note: waitListItemDto.note,
	    externalData: waitListItemDto.externalData
	  };
	}

	var _response = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("response");
	class WaitListDataExtractor {
	  constructor(response) {
	    Object.defineProperty(this, _response, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _response)[_response] = response;
	  }
	  getClients() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _response)[_response].flatMap(({
	      clients
	    }) => clients).map(clientDto => {
	      return booking_provider_service_clientService.ClientMappers.mapDtoToModel(clientDto);
	    });
	  }
	  getWaitListItem() {
	    return mapDtoToModel(babelHelpers.classPrivateFieldLooseBase(this, _response)[_response]);
	  }
	}

	var _onAfterDelete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onAfterDelete");
	class WaitListService {
	  constructor() {
	    Object.defineProperty(this, _onAfterDelete, {
	      value: _onAfterDelete2
	    });
	  }
	  async getById(id) {
	    try {
	      const data = await new booking_lib_apiClient.ApiClient().post('WaitListItem.get', {
	        id
	      });
	      const extractor = new WaitListDataExtractor(data);
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.WaitList}/upsert`, extractor.getWaitListItem());
	    } catch (error) {
	      console.error('WaitListService. getById error', error);
	    }
	  }
	  async add(waitListItem) {
	    const id = waitListItem.id;
	    const $store = booking_core.Core.getStore();
	    try {
	      await $store.dispatch(`${booking_const.Model.WaitList}/add`, waitListItem);
	      const waitListItemDto = mapModelToDto(waitListItem);
	      const data = await new booking_lib_apiClient.ApiClient().post('WaitListItem.add', {
	        waitListItem: waitListItemDto
	      });
	      const createdWaitListItem = mapDtoToModel(data);
	      await $store.dispatch(`${booking_const.Model.Interface}/setAnimationPause`, true);
	      await $store.dispatch(`${booking_const.Model.WaitList}/update`, {
	        id,
	        waitListItem: createdWaitListItem
	      });
	      void booking_provider_service_mainPageService.mainPageService.fetchCounters();
	      return {
	        success: true,
	        waitListItem: createdWaitListItem
	      };
	    } catch (error) {
	      void $store.dispatch(`${booking_const.Model.WaitList}/delete`, id);
	      console.error('WaitListService: add error', error);
	      return {
	        success: false
	      };
	    } finally {
	      await $store.dispatch(`${booking_const.Model.Interface}/setAnimationPause`, false);
	    }
	  }
	  async createFromBooking(bookingId, waitListItem) {
	    const id = waitListItem.id;
	    const $store = booking_core.Core.getStore();
	    try {
	      if ($store.getters[`${booking_const.Model.Interface}/isBookingCreatedFromEmbed`](bookingId)) {
	        await $store.dispatch(`${booking_const.Model.Interface}/addCreatedFromEmbedWaitListItem`, bookingId);
	      }
	      await $store.dispatch(`${booking_const.Model.WaitList}/add`, waitListItem);
	      const data = await new booking_lib_apiClient.ApiClient().post('WaitListItem.createFromBooking', {
	        bookingId
	      });
	      const createdWaitListItem = mapDtoToModel(data);
	      await $store.dispatch(`${booking_const.Model.Interface}/setAnimationPause`, true);
	      await Promise.all([$store.dispatch(`${booking_const.Model.WaitList}/update`, {
	        id,
	        waitListItem: createdWaitListItem
	      }), $store.dispatch(`${booking_const.Model.Interface}/addCreatedFromEmbedWaitListItem`, createdWaitListItem.id)]);
	      void booking_provider_service_mainPageService.mainPageService.fetchCounters();
	      return {
	        success: true,
	        waitListItem: createdWaitListItem
	      };
	    } catch (error) {
	      void $store.dispatch(`${booking_const.Model.WaitList}/delete`, id);
	      console.error('WaitListService: createFromBooking error', error);
	      return {
	        success: false
	      };
	    } finally {
	      await $store.dispatch(`${booking_const.Model.Interface}/setAnimationPause`, false);
	    }
	  }
	  async update(waitListItem) {
	    const id = waitListItem.id;
	    const waitListItemBeforeUpdate = {
	      ...booking_core.Core.getStore().getters[`${booking_const.Model.WaitList}/getById`](id)
	    };
	    try {
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.WaitList}/update`, {
	        id,
	        waitListItem
	      });
	      const waitListItemDto = mapModelToDto(waitListItem);
	      const data = await new booking_lib_apiClient.ApiClient().post('WaitListItem.update', {
	        waitListItem: waitListItemDto
	      });
	      const updatedWaitListItem = mapDtoToModel(data);
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.WaitList}/update`, {
	        id,
	        waitListItem: updatedWaitListItem
	      });
	      const clients = new WaitListDataExtractor([data]).getClients();
	      await booking_core.Core.getStore().dispatch(`${booking_const.Model.Clients}/upsertMany`, clients);
	      void booking_provider_service_mainPageService.mainPageService.fetchCounters();
	    } catch (error) {
	      void booking_core.Core.getStore().dispatch(`${booking_const.Model.WaitList}/update`, {
	        id,
	        waitListItem: waitListItemBeforeUpdate
	      });
	      console.error('WaitListService: update error', error);
	    }
	  }
	  async delete(id) {
	    const $store = booking_core.Core.getStore();
	    const waitListItem = {
	      ...$store.getters[`${booking_const.Model.WaitList}/getById`](id)
	    };
	    try {
	      await $store.dispatch(`${booking_const.Model.WaitList}/delete`, id);
	      await new booking_lib_apiClient.ApiClient().post('WaitListItem.delete', {
	        id
	      });
	      await babelHelpers.classPrivateFieldLooseBase(this, _onAfterDelete)[_onAfterDelete](id);
	    } catch (error) {
	      await $store.dispatch(`${booking_const.Model.WaitList}/upsert`, waitListItem);
	      console.error('WaitListItemService. delete error', error);
	    }
	  }
	  async deleteList(ids) {
	    const $store = booking_core.Core.getStore();
	    const collection = $store.state[booking_const.Model.WaitList].collection;
	    const waitListItems = ids.map(id => {
	      return id in collection ? {
	        ...collection[id]
	      } : null;
	    }).filter(item => item !== null);
	    try {
	      await $store.dispatch(`${booking_const.Model.WaitList}/deleteMany`, ids);
	      await new booking_lib_apiClient.ApiClient().post('WaitListItem.deleteList', {
	        ids
	      });
	      await Promise.all(ids.map(id => babelHelpers.classPrivateFieldLooseBase(this, _onAfterDelete)[_onAfterDelete](id)));
	    } catch (error) {
	      await $store.dispatch(`${booking_const.Model.WaitList}/upsertMany`, waitListItems);
	      console.error('WaitListService. Delete list error', error);
	    }
	  }
	}
	async function _onAfterDelete2(id) {
	  const $store = booking_core.Core.getStore();
	  const editingWaitListItemId = $store.getters[`${booking_const.Model.Interface}/editingWaitListItemId`];
	  if (id === editingWaitListItemId) {
	    await $store.dispatch(`${booking_const.Model.Interface}/setEditingWaitListItemId`, 0);
	  }
	  await $store.dispatch(`${booking_const.Model.Interface}/addDeletingWaitListItemId`, id);
	}
	const waitListService = new WaitListService();

	const WaitListMappers = {
	  mapModelToDto,
	  mapDtoToModel
	};

	exports.WaitListMappers = WaitListMappers;
	exports.waitListService = waitListService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX,BX.Booking,BX.Booking.Const,BX.Booking.Lib,BX.Booking.Provider.Service,BX.Booking.Provider.Service));
//# sourceMappingURL=wait-list-service.bundle.js.map
