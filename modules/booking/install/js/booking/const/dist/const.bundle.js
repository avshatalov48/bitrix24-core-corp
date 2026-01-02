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
	  SelectResources: 'select_resources',
	  CyclePopup: 'cycle_popup',
	  SearchNavigation: 'search_navigation'
	});

	const HelpDesk = Object.freeze({
	  Intersection: {
	    code: '23712054',
	    anchorCode: 'inte'
	  },
	  ResourceIntegrationSettings: {
	    code: '23661822',
	    anchorCode: 'calen'
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
	  },
	  CrmFormsPopup: {
	    code: '25366370',
	    anchorCode: ''
	  },
	  WaitListDescription: {
	    code: '24846212',
	    anchorCode: ''
	  }
	});

	const BusySlot = Object.freeze({
	  OffHours: 'offHours',
	  Intersection: 'intersection',
	  IntersectionOverbooking: 'intersection-overbooking'
	});

	const CrmEntity = Object.freeze({
	  Contact: 'CONTACT',
	  Company: 'COMPANY',
	  Deal: 'DEAL'
	});

	const CrmFormTemplateId = Object.freeze({
	  BookingAutoSelection: 'booking_auto_selection',
	  BookingAnyResource: 'booking_any_resource',
	  BookingManualSettings: 'booking_manual_settings'
	});
	const CrmFormSettingsDataPropName = Object.freeze({
	  autoSelection: 'autoSelection',
	  default: 'default',
	  isAutoSelectionOn: 'isAutoSelectionOn'
	});

	const DateFormat = Object.freeze({
	  Server: 'Y-m-d',
	  ServerParse: 'YYYY-MM-DD',
	  WeekDays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
	});

	const EntitySelectorEntity = Object.freeze({
	  Deal: 'deal',
	  Resource: 'resource',
	  Room: 'room',
	  User: 'user',
	  ResourceType: 'resource-type'
	});

	const EntitySelectorTab = Object.freeze({
	  Recent: 'recents'
	});

	const EntityTypeId = Object.freeze({
	  Company: 'COMPANY',
	  Contact: 'CONTACT',
	  Deal: 'DEAL',
	  Lead: 'LEAD'
	});

	const EventName = Object.freeze({
	  CloseWizard: 'booking:resource-creation-wizard:close',
	  CreateBookings: 'booking:booking:create',
	  DeleteBooking: 'booking:booking:delete',
	  UpdateBooking: 'booking:booking:update'
	});

	const Limit = Object.freeze({
	  ResourcesDialog: 20
	});

	const Model = Object.freeze({
	  BookingInfo: 'booking-info',
	  Bookings: 'bookings',
	  Clients: 'clients',
	  Counters: 'counters',
	  Dictionary: 'dictionary',
	  Favorites: 'favorites',
	  FormsMenu: 'forms-menu',
	  Interface: 'interface',
	  MainResources: 'main-resources',
	  MessageStatus: 'message-status',
	  Notifications: 'notifications',
	  ResourceCreationWizard: 'resource-creation-wizard',
	  ResourceTypes: 'resourceTypes',
	  Resources: 'resources',
	  WaitList: 'wait-list',
	  Filter: 'filter'
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
	const Settings = Object.freeze({
	  info: ['infoNotificationDelay'],
	  confirmation: ['confirmationNotificationDelay', 'confirmationNotificationRepetitions', 'confirmationNotificationRepetitionsInterval', 'confirmationCounterDelay'],
	  reminder: ['reminderNotificationDelay'],
	  delayed: ['delayedNotificationDelay', 'delayedCounterDelay'],
	  feedback: []
	});
	const Ordinal = Object.freeze({
	  info: 1,
	  confirmation: 2,
	  reminder: 3,
	  delayed: 4,
	  feedback: 5
	});
	const NotificationFieldsMap = Object.freeze({
	  NotificationOn,
	  TemplateType,
	  Settings,
	  Ordinal
	});

	const Option = Object.freeze({
	  BookingEnabled: 'aha_banner',
	  IntersectionForAll: 'IntersectionForAll',
	  WaitListExpanded: 'wait_list_expanded',
	  CalendarExpanded: 'calendar_expanded',
	  NotificationsExpanded: 'notificationsExpanded',
	  // AhaMoments
	  AhaBanner: 'aha_banner',
	  AhaTrialBanner: 'aha_trial_banner',
	  AhaAddResource: 'aha_add_resource',
	  AhaMessageTemplate: 'aha_message_template',
	  AhaAddClient: 'aha_add_client',
	  AhaResourceWorkload: 'aha_resource_workload',
	  AhaResourceIntersection: 'aha_resource_intersection',
	  AhaExpandGrid: 'aha_expand_grid',
	  AhaSelectResources: 'aha_select_resources',
	  AhaCyclePopup: 'aha_cycle_popup',
	  AhaSearchNavigation: 'aha_search_navigation'
	});

	const NotificationChannel = Object.freeze({
	  WhatsApp: 'wha',
	  Sms: 'sms'
	});

	const BookingCounterType = Object.freeze({
	  Delayed: 'booking_delayed',
	  Unconfirmed: 'booking_unconfirmed'
	});

	const DraggedElementKind = Object.freeze({
	  Booking: 'booking',
	  WaitListItem: 'wait-list-item'
	});

	const VisitStatus = Object.freeze({
	  Unknown: 'unknown',
	  Visited: 'visited',
	  NotVisited: 'notVisited'
	});

	const ResourceEntityType = Object.freeze({
	  Calendar: 'calendar'
	});

	const AnalyticsTool = Object.freeze({
	  booking: 'booking'
	});
	const AnalyticsCategory = Object.freeze({
	  booking: 'booking',
	  waitlist: 'waitlist'
	});
	const AnalyticsEvent = Object.freeze({
	  showPopup: 'show_popup',
	  clickAddResource: 'click_add_resource',
	  addResourceStep1: 'add_resource_step1',
	  addResourceStep2: 'add_resource_step2',
	  addResourceFinish: 'add_resource_finish',
	  acceptAgreement: 'accept_agreement'
	});
	const AnalyticsType = Object.freeze({
	  showPopup: 'show_popup',
	  clickAddResource: 'click_add_resource'
	});
	const AnalyticsCSection = Object.freeze({
	  booking: 'booking',
	  crm: 'crm',
	  mainMenu: 'main_menu'
	});
	const AnalyticsElement = Object.freeze({
	  addButton: 'add_button'
	});
	const AnalyticsCSubSection = Object.freeze({
	  accept: 'accept',
	  deny: 'deny'
	});

	exports.AhaMoment = AhaMoment;
	exports.HelpDesk = HelpDesk;
	exports.BusySlot = BusySlot;
	exports.CrmEntity = CrmEntity;
	exports.CrmFormTemplateId = CrmFormTemplateId;
	exports.CrmFormSettingsDataPropName = CrmFormSettingsDataPropName;
	exports.DateFormat = DateFormat;
	exports.EntitySelectorEntity = EntitySelectorEntity;
	exports.EntitySelectorTab = EntitySelectorTab;
	exports.EntityTypeId = EntityTypeId;
	exports.EventName = EventName;
	exports.Limit = Limit;
	exports.Model = Model;
	exports.Module = Module;
	exports.NotificationFieldsMap = NotificationFieldsMap;
	exports.Option = Option;
	exports.NotificationChannel = NotificationChannel;
	exports.BookingCounterType = BookingCounterType;
	exports.DraggedElementKind = DraggedElementKind;
	exports.VisitStatus = VisitStatus;
	exports.ResourceEntityType = ResourceEntityType;
	exports.AnalyticsTool = AnalyticsTool;
	exports.AnalyticsCategory = AnalyticsCategory;
	exports.AnalyticsEvent = AnalyticsEvent;
	exports.AnalyticsType = AnalyticsType;
	exports.AnalyticsCSection = AnalyticsCSection;
	exports.AnalyticsElement = AnalyticsElement;
	exports.AnalyticsCSubSection = AnalyticsCSubSection;

}((this.BX.Booking.Const = this.BX.Booking.Const || {})));
//# sourceMappingURL=const.bundle.js.map
