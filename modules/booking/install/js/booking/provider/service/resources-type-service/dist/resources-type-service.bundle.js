/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_core,booking_lib_apiClient,booking_model_resourceTypes) {
	'use strict';

	function mapDtoToModel(resourceDto) {
	  return {
	    id: resourceDto.id,
	    moduleId: resourceDto.moduleId,
	    name: resourceDto.name,
	    code: resourceDto.code,
	    isConfirmationNotificationOn: resourceDto.isConfirmationNotificationOn,
	    isFeedbackNotificationOn: resourceDto.isFeedbackNotificationOn,
	    isInfoNotificationOn: resourceDto.isInfoNotificationOn,
	    isDelayedNotificationOn: resourceDto.isDelayedNotificationOn,
	    isReminderNotificationOn: resourceDto.isReminderNotificationOn,
	    templateTypeConfirmation: resourceDto.templateTypeConfirmation,
	    templateTypeFeedback: resourceDto.templateTypeFeedback,
	    templateTypeInfo: resourceDto.templateTypeInfo,
	    templateTypeDelayed: resourceDto.templateTypeDelayed,
	    templateTypeReminder: resourceDto.templateTypeReminder
	  };
	}
	function mapModelToDto(resourceType) {
	  return {
	    id: resourceType.id,
	    moduleId: resourceType.moduleId,
	    name: resourceType.name,
	    code: resourceType.code,
	    isConfirmationNotificationOn: resourceType.isConfirmationNotificationOn,
	    isFeedbackNotificationOn: resourceType.isFeedbackNotificationOn,
	    isInfoNotificationOn: resourceType.isInfoNotificationOn,
	    isDelayedNotificationOn: resourceType.isDelayedNotificationOn,
	    isReminderNotificationOn: resourceType.isReminderNotificationOn,
	    templateTypeConfirmation: resourceType.templateTypeConfirmation,
	    templateTypeFeedback: resourceType.templateTypeFeedback,
	    templateTypeInfo: resourceType.templateTypeInfo,
	    templateTypeDelayed: resourceType.templateTypeDelayed,
	    templateTypeReminder: resourceType.templateTypeReminder
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
