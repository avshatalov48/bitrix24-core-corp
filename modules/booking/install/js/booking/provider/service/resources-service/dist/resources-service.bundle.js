/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_core,booking_const,booking_lib_apiClient) {
	'use strict';

	function mapDtoToModel(resourceDto) {
	  return {
	    id: resourceDto.id,
	    typeId: resourceDto.type.id,
	    name: resourceDto.name,
	    description: resourceDto.description,
	    slotRanges: resourceDto.slotRanges.map(slotRange => ({
	      ...slotRange,
	      weekDays: Object.values(slotRange.weekDays)
	    })),
	    counter: resourceDto.counter,
	    isMain: resourceDto.isMain,
	    isDeleted: resourceDto.isDeleted,
	    createdBy: resourceDto.createdBy,
	    createdAt: resourceDto.createdAt,
	    updatedAt: resourceDto.updatedAt,
	    deletedAt: resourceDto.deletedAt,
	    // info
	    isInfoNotificationOn: resourceDto.isInfoNotificationOn,
	    templateTypeInfo: resourceDto.templateTypeInfo,
	    infoNotificationDelay: resourceDto.infoNotificationDelay,
	    // confirmation
	    isConfirmationNotificationOn: resourceDto.isConfirmationNotificationOn,
	    templateTypeConfirmation: resourceDto.templateTypeConfirmation,
	    confirmationNotificationDelay: resourceDto.confirmationNotificationDelay,
	    confirmationNotificationRepetitions: resourceDto.confirmationNotificationRepetitions,
	    confirmationNotificationRepetitionsInterval: resourceDto.confirmationNotificationRepetitionsInterval,
	    confirmationCounterDelay: resourceDto.confirmationCounterDelay,
	    // reminder
	    isReminderNotificationOn: resourceDto.isReminderNotificationOn,
	    templateTypeReminder: resourceDto.templateTypeReminder,
	    reminderNotificationDelay: resourceDto.reminderNotificationDelay,
	    // delayed
	    isDelayedNotificationOn: resourceDto.isDelayedNotificationOn,
	    templateTypeDelayed: resourceDto.templateTypeDelayed,
	    delayedNotificationDelay: resourceDto.delayedNotificationDelay,
	    delayedCounterDelay: resourceDto.delayedCounterDelay,
	    // feedback
	    isFeedbackNotificationOn: resourceDto.isFeedbackNotificationOn,
	    templateTypeFeedback: resourceDto.templateTypeFeedback,
	    // integrationCalendar
	    entities: resourceDto.entities || []
	  };
	}
	function mapModelToDto(resource) {
	  return {
	    id: resource.id,
	    type: {
	      id: resource.typeId
	    },
	    name: resource.name,
	    description: resource.description,
	    slotRanges: resource.slotRanges,
	    counter: null,
	    isMain: resource.isMain,
	    isDeleted: resource.isDeleted,
	    createdBy: null,
	    createdAt: null,
	    updatedAt: null,
	    // info
	    isInfoNotificationOn: resource.isInfoNotificationOn,
	    templateTypeInfo: resource.templateTypeInfo,
	    infoNotificationDelay: resource.infoNotificationDelay,
	    // confirmation
	    isConfirmationNotificationOn: resource.isConfirmationNotificationOn,
	    templateTypeConfirmation: resource.templateTypeConfirmation,
	    confirmationNotificationDelay: resource.confirmationNotificationDelay,
	    confirmationNotificationRepetitions: resource.confirmationNotificationRepetitions,
	    confirmationNotificationRepetitionsInterval: resource.confirmationNotificationRepetitionsInterval,
	    confirmationCounterDelay: resource.confirmationCounterDelay,
	    // reminder
	    isReminderNotificationOn: resource.isReminderNotificationOn,
	    templateTypeReminder: resource.templateTypeReminder,
	    reminderNotificationDelay: resource.reminderNotificationDelay,
	    // delayed
	    isDelayedNotificationOn: resource.isDelayedNotificationOn,
	    templateTypeDelayed: resource.templateTypeDelayed,
	    delayedNotificationDelay: resource.delayedNotificationDelay,
	    delayedCounterDelay: resource.delayedCounterDelay,
	    // feedback
	    isFeedbackNotificationOn: resource.isFeedbackNotificationOn,
	    templateTypeFeedback: resource.templateTypeFeedback,
	    // integrationCalendar
	    entities: entitiesToDto(resource.entities)
	  };
	}
	function entitiesToDto(entities) {
	  return checkEntityCalendar(entities);
	}
	function checkEntityCalendar(entities) {
	  // eslint-disable-next-line max-len
	  return entities.filter(entity => {
	    var _entity$data;
	    return !(entity.entityType === booking_const.ResourceEntityType.Calendar && ((_entity$data = entity.data) == null ? void 0 : _entity$data.userIds.length) === 0);
	  });
	}

	var _updateResourcesFromFavorites = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateResourcesFromFavorites");
	var _turnOnTrial = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("turnOnTrial");
	class ResourceService {
	  constructor() {
	    Object.defineProperty(this, _turnOnTrial, {
	      value: _turnOnTrial2
	    });
	    Object.defineProperty(this, _updateResourcesFromFavorites, {
	      value: _updateResourcesFromFavorites2
	    });
	  }
	  async add(resource) {
	    try {
	      const resourceDto = mapModelToDto(resource);
	      const data = await new booking_lib_apiClient.ApiClient().post('Resource.add', {
	        resource: resourceDto
	      });
	      const createdResource = mapDtoToModel(data);
	      booking_core.Core.getStore().commit('resources/upsert', createdResource);
	      if (createdResource.isMain) {
	        await booking_core.Core.getStore().dispatch(`${booking_const.Model.Favorites}/add`, createdResource.id);
	      }
	      if (booking_core.Core.getStore().getters[`${booking_const.Model.Interface}/canTurnOnTrial`]) {
	        void babelHelpers.classPrivateFieldLooseBase(this, _turnOnTrial)[_turnOnTrial]();
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _updateResourcesFromFavorites)[_updateResourcesFromFavorites]();
	      return data;
	    } catch (error) {
	      console.error('ResourceService: add error', error);
	      return error;
	    }
	  }
	  async update(resource) {
	    const id = resource.id;
	    const resourceBeforeUpdate = {
	      ...booking_core.Core.getStore().getters['resources/getById'](id)
	    };
	    try {
	      const resourceDto = mapModelToDto(resource);
	      const data = await new booking_lib_apiClient.ApiClient().post('Resource.update', {
	        resource: resourceDto
	      });
	      const updatedResource = mapDtoToModel(data);
	      booking_core.Core.getStore().commit('resources/upsert', updatedResource);
	      if (resourceBeforeUpdate.isMain && !updatedResource.isMain) {
	        await booking_core.Core.getStore().dispatch(`${booking_const.Model.Favorites}/delete`, id);
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _updateResourcesFromFavorites)[_updateResourcesFromFavorites]();
	      return data;
	    } catch (error) {
	      console.error('ResourceService: update error', error);
	      return error;
	    }
	  }
	  async delete(resourceId, withFutureBookings = false) {
	    try {
	      const action = withFutureBookings ? 'Resource.forceDelete' : 'Resource.delete';
	      const {
	        removedBookingIds
	      } = await new booking_lib_apiClient.ApiClient().post(action, {
	        id: resourceId
	      });
	      const $store = booking_core.Core.getStore();
	      const updateStoreActions = [$store.dispatch(`${booking_const.Model.Resources}/delete`, resourceId), $store.dispatch(`${booking_const.Model.Favorites}/delete`, resourceId), $store.dispatch(`${booking_const.Model.Interface}/deleteResourceId`, resourceId), $store.dispatch(`${booking_const.Model.Interface}/removeDeletingResource`, resourceId)];
	      if (removedBookingIds) {
	        updateStoreActions.push($store.dispatch(`${booking_const.Model.Bookings}/deleteMany`, removedBookingIds));
	      }
	      await Promise.all(updateStoreActions);
	    } catch (error) {
	      console.error('ResourceService: delete error', error);
	    }
	  }
	  async hasFutureBookings(resourceId) {
	    try {
	      return new booking_lib_apiClient.ApiClient().post('Resource.hasFutureBookings', {
	        resourceId
	      });
	    } catch (error) {
	      console.error('ResourceService: hasFutureBookings error', error);
	    }
	    return Promise.resolve();
	  }
	}
	function _updateResourcesFromFavorites2() {
	  const isFilterMode = booking_core.Core.getStore().getters[`${booking_const.Model.Filter}/isFilterMode`];
	  if (isFilterMode) {
	    return;
	  }
	  const favorites = booking_core.Core.getStore().getters[`${booking_const.Model.Favorites}/get`];
	  void booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setResourcesIds`, favorites);
	}
	async function _turnOnTrial2() {
	  await Promise.all([booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setCanTurnOnTrial`, false), booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setIsFeatureEnabled`, true)]);
	  await new Promise(resolve => setTimeout(resolve, 2000));
	  await booking_core.Core.getStore().dispatch(`${booking_const.Model.Interface}/setIsShownTrialPopup`, true);
	}
	const resourceService = new ResourceService();

	const ResourceMappers = {
	  mapModelToDto,
	  mapDtoToModel
	};

	exports.ResourceMappers = ResourceMappers;
	exports.resourceService = resourceService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking,BX.Booking.Const,BX.Booking.Lib));
//# sourceMappingURL=resources-service.bundle.js.map
