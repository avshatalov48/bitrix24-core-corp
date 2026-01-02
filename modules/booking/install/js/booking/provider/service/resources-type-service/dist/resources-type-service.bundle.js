/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_core,booking_lib_apiClient,booking_model_resourceTypes) {
	'use strict';

	function mapDtoToModel(resourceTypeDto) {
	  return {
	    id: resourceTypeDto.id,
	    moduleId: resourceTypeDto.moduleId,
	    name: resourceTypeDto.name,
	    code: resourceTypeDto.code,
	    resourcesCnt: resourceTypeDto.resourcesCnt,
	    // info
	    isInfoNotificationOn: resourceTypeDto.isInfoNotificationOn,
	    templateTypeInfo: resourceTypeDto.templateTypeInfo,
	    infoNotificationDelay: resourceTypeDto.infoNotificationDelay,
	    // confirmation
	    isConfirmationNotificationOn: resourceTypeDto.isConfirmationNotificationOn,
	    templateTypeConfirmation: resourceTypeDto.templateTypeConfirmation,
	    confirmationNotificationDelay: resourceTypeDto.confirmationNotificationDelay,
	    confirmationNotificationRepetitions: resourceTypeDto.confirmationNotificationRepetitions,
	    confirmationNotificationRepetitionsInterval: resourceTypeDto.confirmationNotificationRepetitionsInterval,
	    confirmationCounterDelay: resourceTypeDto.confirmationCounterDelay,
	    // reminder
	    isReminderNotificationOn: resourceTypeDto.isReminderNotificationOn,
	    templateTypeReminder: resourceTypeDto.templateTypeReminder,
	    reminderNotificationDelay: resourceTypeDto.reminderNotificationDelay,
	    // delayed
	    isDelayedNotificationOn: resourceTypeDto.isDelayedNotificationOn,
	    templateTypeDelayed: resourceTypeDto.templateTypeDelayed,
	    delayedNotificationDelay: resourceTypeDto.delayedNotificationDelay,
	    delayedCounterDelay: resourceTypeDto.delayedCounterDelay,
	    // feedback
	    isFeedbackNotificationOn: resourceTypeDto.isFeedbackNotificationOn,
	    templateTypeFeedback: resourceTypeDto.templateTypeFeedback
	  };
	}
	function mapModelToDto(resourceType) {
	  return {
	    id: resourceType.id,
	    moduleId: resourceType.moduleId,
	    name: resourceType.name,
	    code: resourceType.code,
	    resourcesCnt: resourceType.resourcesCnt,
	    // info
	    isInfoNotificationOn: resourceType.isInfoNotificationOn,
	    templateTypeInfo: resourceType.templateTypeInfo,
	    infoNotificationDelay: resourceType.infoNotificationDelay,
	    // confirmation
	    isConfirmationNotificationOn: resourceType.isConfirmationNotificationOn,
	    templateTypeConfirmation: resourceType.templateTypeConfirmation,
	    confirmationNotificationDelay: resourceType.confirmationNotificationDelay,
	    confirmationNotificationRepetitions: resourceType.confirmationNotificationRepetitions,
	    confirmationNotificationRepetitionsInterval: resourceType.confirmationNotificationRepetitionsInterval,
	    confirmationCounterDelay: resourceType.confirmationCounterDelay,
	    // reminder
	    isReminderNotificationOn: resourceType.isReminderNotificationOn,
	    templateTypeReminder: resourceType.templateTypeReminder,
	    reminderNotificationDelay: resourceType.reminderNotificationDelay,
	    // delayed
	    isDelayedNotificationOn: resourceType.isDelayedNotificationOn,
	    templateTypeDelayed: resourceType.templateTypeDelayed,
	    delayedNotificationDelay: resourceType.delayedNotificationDelay,
	    delayedCounterDelay: resourceType.delayedCounterDelay,
	    // feedback
	    isFeedbackNotificationOn: resourceType.isFeedbackNotificationOn,
	    templateTypeFeedback: resourceType.templateTypeFeedback
	  };
	}

	class ResourceTypeService {
	  async add(resourceType) {
	    let createdResourceType = null;
	    try {
	      const resourceDto = mapModelToDto(resourceType);
	      const data = await new booking_lib_apiClient.ApiClient().post('ResourceType.add', {
	        resourceType: resourceDto
	      });
	      createdResourceType = mapDtoToModel(data);
	      void booking_core.Core.getStore().dispatch('resourceTypes/upsert', createdResourceType);
	    } catch (error) {
	      console.error('ResourceTypeService: add error', error);
	    }
	    return createdResourceType;
	  }
	  async update(resourceType) {
	    try {
	      const resourceDto = mapModelToDto(resourceType);
	      const data = await new booking_lib_apiClient.ApiClient().post('ResourceType.update', {
	        resourceType: resourceDto
	      });
	      void booking_core.Core.getStore().dispatch('resourceTypes/upsert', mapDtoToModel(data));
	    } catch (error) {
	      console.error('ResourceTypeService: update error', error);
	    }
	  }
	  async delete(resourceTypeId) {
	    return Promise.resolve();
	  }
	}
	const resourceTypeService = new ResourceTypeService();

	const ResourceTypeMappers = {
	  mapModelToDto,
	  mapDtoToModel
	};

	exports.ResourceTypeMappers = ResourceTypeMappers;
	exports.resourceTypeService = resourceTypeService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking,BX.Booking.Lib,BX.Booking.Model));
//# sourceMappingURL=resources-type-service.bundle.js.map
