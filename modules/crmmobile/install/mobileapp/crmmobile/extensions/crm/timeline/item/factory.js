/**
 * @module crm/timeline/item/factory
 */
jn.define('crm/timeline/item/factory', (require, exports, module) => {
	const { TimelineItemCompatible } = require('crm/timeline/item/compatible');
	const { GenericTimelineItem } = require('crm/timeline/item/generic');
	const { TimelineSchedulerCommentProvider } = require('crm/timeline/scheduler/providers');
	const { get } = require('utils/object');

	const {
		CallActivity,
		Modification,
		OpenlineChat,
		VisitActivity,
		TaskActivity,
	} = require('crm/timeline/item/custom-types');

	/**
	 * You MUST register record type here.
	 * @type {string[]}
	 */
	const SupportedTypes = [
		'Creation',
		'Modification',
		'Link',
		'Unlink',
		'TodoCreated',
		'CallIncoming',
		'Ping',
		'DocumentViewed',
		'Document',
		'RestLog',
		'Conversion',
		'Restoration',
		'ElementCompletion',
		'Activity:Email',
		'ContactList',
		'EmailActivitySuccessfullyDelivered',
		'EmailActivityNonDelivered',
		'EmailLogIncomingMessage',
		'Activity:Call',
		'Activity:OpenLine',
		'Activity:Creation',
		'Activity:ToDo',
		'Activity:ConfigurableRestApp',
		'Activity:Payment',
		'Activity:Sms',
		'Activity:Notification',
		'Activity:Whatsapp',
		'PaymentPaid',
		'PaymentViewed',
		'PaymentNotViewed',
		'PaymentSentToTerminal',
		'PaymentError',
		'FinalSummary',
		'OrderCheckNotPrinted',
		'OrderCheckPrinted',
		'OrderCheckCreationError',
		'SmsStatus',
		'CustomerSelectedPaymentMethod',
		'Activity:CalendarSharing',
		'CalendarSharingNotViewed',
		'CalendarSharingViewed',
		'CalendarSharingEventConfirmed',
		'CalendarSharingInvitationSent',
		'CalendarSharingLinkCopied',
		'CalendarSharingRuleUpdated',
		'Activity:TasksTask',
		'Activity:TasksTaskComment',
		'TasksTaskCreation',
		'TasksTaskModification',
		'StoreDocumentRealization:Modification',
		'StoreDocumentRealization:Creation',
		'StoreDocumentConduction:Modification',
		'Activity:Visit',
	];

	if (TimelineSchedulerCommentProvider.isSupported())
	{
		SupportedTypes.push('Comment');
	}

	if (get(jnExtensionData.get('crm:timeline/item'), 'isBizprocActivityAvailable', false))
	{
		SupportedTypes.push(
			'Activity:BizprocTask',
			'Activity:BizprocWorkflowCompleted',
			'Activity:BizprocCommentAdded',
			'BizprocWorkflowStarted',
			'BizprocWorkflowCompleted',
			'BizprocWorkflowTerminated',
			'BizprocTaskCreation',
			'BizprocTaskCompleted',
			'BizprocTaskDelegated',
			'BizprocCommentRead',
			'BizprocCommentAdded',
		);
	}

	/**
	 * You can specify custom item class here. It MUST inherit TimelineItemBase.
	 * @type {Object.<string, TimelineItemBase>}
	 */
	const TypeAliases = {
		Modification,
		'Activity:Call': CallActivity,
		'Activity:OpenLine': OpenlineChat,
		'Activity:Visit': VisitActivity,
		'Activity:TasksTask': TaskActivity,
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
			if (SupportedTypes.includes(type))
			{
				const ItemClass = TypeAliases[type] || GenericTimelineItem;

				return new ItemClass(props);
			}

			return new TimelineItemCompatible(props);
		}
	}

	module.exports = { TimelineItemFactory, SupportedTypes };
});
