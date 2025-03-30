/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports) {
	'use strict';

	const AhaMoment = Object.freeze({
	  Banner: 'banner',
	  TrialBanner: 'trial_banner',
	  AddResource: 'add_resource',
	  MessageTemplate: 'message_template',
	  AddClient: 'add_client',
	  ResourceWorkload: 'resource_workload',
	  ResourceIntersection: 'resource_intersection',
	  ExpandGrid: 'expand_grid',
	  SelectResources: 'select_resources'
	});

	const HelpDesk = Object.freeze({
	  Intersection: {
	    code: '23712054',
	    anchorCode: 'inte'
	  },
	  ResourceType: {
	    code: '23661822',
	    anchorCode: 'reso'
	  },
	  ResourceSchedule: {
	    code: '23661822',
	    anchorCode: 'show'
	  },
	  ResourceWorkTime: {
	    code: '23661822',
	    anchorCode: 'sche'
	  },
	  ResourceSlotLength: {
	    code: '23661822',
	    anchorCode: 'dur'
	  },
	  ResourceNotificationInfo: {
	    code: '23661926',
	    anchorCode: 'mess'
	  },
	  ResourceNotificationConfirmation: {
	    code: '23661926',
	    anchorCode: 'conf'
	  },
	  ResourceNotificationReminder: {
	    code: '23661926',
	    anchorCode: 'remi'
	  },
	  ResourceNotificationLate: {
	    code: '23661926',
	    anchorCode: 'late'
	  },
	  ResourceNotificationFeedback: {
	    code: '23661926',
	    anchorCode: 'feed'
	  },
	  AhaAddClient: {
	    code: '23661964',
	    anchorCode: ''
	  },
	  AhaSelectResources: {
	    code: '23661972',
	    anchorCode: 'filt'
	  },
	  AhaResourceWorkload: {
	    code: '23661972',
	    anchorCode: 'cont'
	  },
	  AhaResourceIntersection: {
	    code: '23712054',
	    anchorCode: 'inte'
	  },
	  AhaAddResource: {
	    code: '23661822',
	    anchorCode: ''
	  },
	  AhaMessageTemplate: {
	    code: '23661926',
	    anchorCode: ''
	  },
	  AhaExpandGrid: {
	    code: '23712054',
	    anchorCode: 'refl'
	  },
	  BookingActionsDeal: {
	    code: '23661964',
	    anchorCode: 'deal'
	  },
	  BookingActionsMessage: {
	    code: '23661964',
	    anchorCode: 'remind'
	  },
	  BookingActionsConfirmation: {
	    code: '23661964',
	    anchorCode: 'appr'
	  },
	  BookingActionsVisit: {
	    code: '23661964',
	    anchorCode: 'visit'
	  }
	});

	const BusySlot = Object.freeze({
	  OffHours: 'offHours',
	  Intersection: 'intersection'
	});

	const CrmEntity = Object.freeze({
	  Contact: 'CONTACT',
	  Company: 'COMPANY',
	  Deal: 'DEAL'
	});

	const DateFormat = Object.freeze({
	  Server: 'Y-m-d',
	  ServerParse: 'YYYY-MM-DD',
	  WeekDays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
	});

	const EntitySelectorEntity = Object.freeze({
	  Deal: 'deal',
	  Resource: 'resource',
	  ResourceType: 'resource-type'
	});

	const EntitySelectorTab = Object.freeze({
	  Recent: 'recents'
	});

	const EventName = Object.freeze({
	  CloseWizard: 'booking:resource-creation-wizard:close'
	});

	const Limit = Object.freeze({
	  ResourcesDialog: 20
	});

	const Model = Object.freeze({
	  Bookings: 'bookings',
	  Clients: 'clients',
	  Counters: 'counters',
	  Dictionary: 'dictionary',
	  Favorites: 'favorites',
	  Interface: 'interface',
	  MainResources: 'main-resources',
	  MessageStatus: 'message-status',
	  Notifications: 'notifications',
	  ResourceCreationWizard: 'resource-creation-wizard',
	  ResourceTypes: 'resourceTypes',
	  Resources: 'resources'
	});

	const Module = Object.freeze({
	  Booking: 'booking',
	  Crm: 'crm'
	});

	const NotificationOn = Object.freeze({
	  info: 'isInfoNotificationOn',
	  confirmation: 'isConfirmationNotificationOn',
	  reminder: 'isReminderNotificationOn',
	  delayed: 'isDelayedNotificationOn',
	  feedback: 'isFeedbackNotificationOn'
	});
	const TemplateType = Object.freeze({
	  info: 'templateTypeInfo',
	  confirmation: 'templateTypeConfirmation',
	  reminder: 'templateTypeReminder',
	  delayed: 'templateTypeDelayed',
	  feedback: 'templateTypeFeedback'
	});
	const NotificationFieldsMap = Object.freeze({
	  NotificationOn,
	  TemplateType
	});

	const Option = Object.freeze({
	  BookingEnabled: 'aha_banner',
	  IntersectionForAll: 'IntersectionForAll',
	  // AhaMoments
	  AhaBanner: 'aha_banner',
	  AhaTrialBanner: 'aha_trial_banner',
	  AhaAddResource: 'aha_add_resource',
	  AhaMessageTemplate: 'aha_message_template',
	  AhaAddClient: 'aha_add_client',
	  AhaResourceWorkload: 'aha_resource_workload',
	  AhaResourceIntersection: 'aha_resource_intersection',
	  AhaExpandGrid: 'aha_expand_grid',
	  AhaSelectResources: 'aha_select_resources'
	});

	const NotificationChannel = Object.freeze({
	  WhatsApp: 'wha',
	  Sms: 'sms'
	});

	exports.AhaMoment = AhaMoment;
	exports.HelpDesk = HelpDesk;
	exports.BusySlot = BusySlot;
	exports.CrmEntity = CrmEntity;
	exports.DateFormat = DateFormat;
	exports.EntitySelectorEntity = EntitySelectorEntity;
	exports.EntitySelectorTab = EntitySelectorTab;
	exports.EventName = EventName;
	exports.Limit = Limit;
	exports.Model = Model;
	exports.Module = Module;
	exports.NotificationFieldsMap = NotificationFieldsMap;
	exports.Option = Option;
	exports.NotificationChannel = NotificationChannel;

}((this.BX.Booking.Const = this.BX.Booking.Const || {})));
//# sourceMappingURL=const.bundle.js.map
