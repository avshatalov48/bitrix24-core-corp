/**
 * @module crm/timeline/item/factory
 */
jn.define('crm/timeline/item/factory', (require, exports, module) => {
	const {
		EmailActivity,
		CallActivity,
		OpenLineActivity,
		CreationActivity,
		TodoActivity,
		Document,
		ConfigurableRestAppActivity,
		PaymentActivity,
		SmsActivity,
		NotificationActivity,
		CalendarSharingActivity,
		TasksTaskActivity,
		TasksTaskCommentActivity,
	} = require('crm/timeline/item/activity');

	const {
		Creation,
		Modification,
		Link,
		Unlink,
		TodoCreated,
		CallIncoming,
		Ping,
		DocumentViewed,
		RestLog,
		Conversion,
		PaymentPaid,
		PaymentViewed,
		PaymentNotViewed,
		PaymentError,
		FinalSummary,
		OrderCheckNotPrinted,
		OrderCheckPrinted,
		OrderCheckCreationError,
		SmsStatus,
		CalendarSharingNotViewed,
		CalendarSharingViewed,
		CalendarSharingEventConfirmed,
		CalendarSharingInvitationSent,
		CalendarSharingLinkCopied,
		TasksTaskCreation,
		TasksTaskModification,
		CustomerSelectedPaymentMethod,
	} = require('crm/timeline/item/log');

	const { TimelineItemCompatible } = require('crm/timeline/item/compatible');

	/**
	 * You MUST register record type here.
	 */
	const SupportedTypes = {
		Creation,
		Modification,
		Link,
		Unlink,
		TodoCreated,
		CallIncoming,
		Ping,
		'Activity:Email': EmailActivity,
		DocumentViewed,
		Document,
		RestLog,
		Conversion,
		'Activity:Call': CallActivity,
		'Activity:OpenLine': OpenLineActivity,
		'Activity:Creation': CreationActivity,
		'Activity:ToDo': TodoActivity,
		'Activity:ConfigurableRestApp': ConfigurableRestAppActivity,
		'Activity:Payment': PaymentActivity,
		'Activity:Sms': SmsActivity,
		'Activity:Notification': NotificationActivity,
		PaymentPaid,
		PaymentViewed,
		PaymentNotViewed,
		PaymentError,
		FinalSummary,
		OrderCheckNotPrinted,
		OrderCheckPrinted,
		OrderCheckCreationError,
		SmsStatus,
		'Activity:CalendarSharing': CalendarSharingActivity,
		CalendarSharingNotViewed,
		CalendarSharingViewed,
		CalendarSharingEventConfirmed,
		CalendarSharingInvitationSent,
		CalendarSharingLinkCopied,
		'Activity:TasksTask': TasksTaskActivity,
		'Activity:TasksTaskComment': TasksTaskCommentActivity,
		TasksTaskCreation,
		TasksTaskModification,
		CustomerSelectedPaymentMethod,
	};

	/**
     * @class TimelineItemFactory
     */
	class TimelineItemFactory
	{
		/**
		 * @param {string} type
		 * @param {object} props
		 * @returns {TimelineItemBase}
		 */
		static make(type, props)
		{
			if (SupportedTypes[type])
			{
				return new SupportedTypes[type](props);
			}

			return new TimelineItemCompatible(props);
		}
	}

	module.exports = { TimelineItemFactory, SupportedTypes };
});
