/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_core,booking_const,booking_lib_apiClient,booking_provider_service_optionService) {
	'use strict';

	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	class ResourceCreationWizardDataExtractor {
	  constructor(data) {
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = data;
	  }
	  getAdvertisingTypes() {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].advertisingResourceTypes) != null ? _babelHelpers$classPr : [];
	  }
	  getNotifications() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].notificationsSettings.notifications;
	  }
	  getSenders() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].notificationsSettings.senders;
	  }
	  getCompanyScheduleSlots() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].companyScheduleSlots;
	  }
	  isCompanyScheduleAccess() {
	    return Boolean(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].isCompanyScheduleAccess);
	  }
	  getCompanyScheduleUrl() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].companyScheduleUrl;
	  }
	  getWeekStart() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].weekStart;
	  }
	  isChannelChoiceAvailable() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].isChannelChoiceAvailable;
	  }
	}

	class ResourceCreationWizardService {
	  async fetchData() {
	    await this.loadData();
	  }
	  async loadData() {
	    try {
	      const data = await booking_lib_apiClient.apiClient.post('ResourceWizard.get', {});
	      const extractor = new ResourceCreationWizardDataExtractor(data);
	      const wizardModel = booking_const.Model.ResourceCreationWizard;
	      await Promise.all([this.$store.dispatch(`${wizardModel}/setAdvertisingTypes`, extractor.getAdvertisingTypes()), this.$store.dispatch(`${wizardModel}/setCompanyScheduleSlots`, extractor.getCompanyScheduleSlots()), this.$store.dispatch(`${wizardModel}/setCompanyScheduleAccess`, extractor.isCompanyScheduleAccess()), this.$store.dispatch(`${wizardModel}/setCompanyScheduleUrl`, extractor.getCompanyScheduleUrl()), this.$store.dispatch(`${wizardModel}/setWeekStart`, extractor.getWeekStart()), this.$store.dispatch(`${wizardModel}/setIsChannelChoiceAvailable`, extractor.isChannelChoiceAvailable()), this.$store.dispatch(`${booking_const.Model.Notifications}/upsertMany`, extractor.getNotifications()), this.$store.dispatch(`${booking_const.Model.Notifications}/upsertManySenders`, extractor.getSenders())]);
	    } catch (error) {
	      console.error('ResourceCreationWizardService loadData error', error);
	    }
	  }
	  async updateNotificationExpanded(type, isExpanded) {
	    await this.$store.dispatch(`${booking_const.Model.Notifications}/setIsExpanded`, {
	      type,
	      isExpanded
	    });
	    const notifications = Object.fromEntries(this.$store.getters[`${booking_const.Model.Notifications}/get`].map(notification => [notification.type, notification.isExpanded]));
	    try {
	      await booking_provider_service_optionService.optionService.set(booking_const.Option.NotificationsExpanded, JSON.stringify(notifications));
	    } catch (error) {
	      await this.$store.dispatch(`${booking_const.Model.Notifications}/setIsExpanded`, {
	        type,
	        isExpanded: !isExpanded
	      });
	      console.error('ResourceCreationWizardService updateNotificationExpanded error', error);
	    }
	  }
	  get $store() {
	    return booking_core.Core.getStore();
	  }
	}
	const resourceCreationWizardService = new ResourceCreationWizardService();

	exports.resourceCreationWizardService = resourceCreationWizardService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking,BX.Booking.Const,BX.Booking.Lib,BX.Booking.Provider.Service));
//# sourceMappingURL=resource-creation-wizard-service.bundle.js.map
