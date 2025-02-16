/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
this.BX.Booking.Provider = this.BX.Booking.Provider || {};
(function (exports,booking_core,booking_lib_apiClient,booking_const) {
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
	  getAdvertisingResourceTypes() {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].advertisingResourceTypes) != null ? _babelHelpers$classPr : [];
	  }
	  getNotificationsSettings() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].notificationsSettings;
	  }
	  getCompanyScheduleSlots() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].companyScheduleSlots;
	  }
	  isCompanyScheduleAccess() {
	    return Boolean(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].isCompanyScheduleAccess);
	  }
	  getWeekStart() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].weekStart;
	  }
	}

	class ResourceCreationWizardService {
	  async fetchData() {
	    await this.loadData();
	  }
	  async loadData() {
	    try {
	      const api = new booking_lib_apiClient.ApiClient();
	      const data = await api.post('ResourceWizard.get', {});
	      const extractor = new ResourceCreationWizardDataExtractor(data);
	      const store = booking_core.Core.getStore();
	      const {
	        notifications,
	        senders
	      } = extractor.getNotificationsSettings();
	      await Promise.all([store.dispatch(`${booking_const.Model.ResourceCreationWizard}/setAdvertisingResourceTypes`, extractor.getAdvertisingResourceTypes()), store.dispatch(`${booking_const.Model.Notifications}/upsertMany`, notifications), store.dispatch(`${booking_const.Model.Notifications}/upsertManySenders`, senders), store.dispatch(`${booking_const.Model.ResourceCreationWizard}/setCompanyScheduleSlots`, extractor.getCompanyScheduleSlots()), store.dispatch(`${booking_const.Model.ResourceCreationWizard}/setCompanyScheduleAccess`, extractor.isCompanyScheduleAccess()), store.dispatch(`${booking_const.Model.ResourceCreationWizard}/setWeekStart`, extractor.getWeekStart())]);
	    } catch (error) {
	      console.error('ResourceCreationWizardService load data error', error);
	    }
	  }
	}
	const resourceCreationWizardService = new ResourceCreationWizardService();

	exports.resourceCreationWizardService = resourceCreationWizardService;

}((this.BX.Booking.Provider.Service = this.BX.Booking.Provider.Service || {}),BX.Booking,BX.Booking.Lib,BX.Booking.Const));
//# sourceMappingURL=resource-creation-wizard-service.bundle.js.map
