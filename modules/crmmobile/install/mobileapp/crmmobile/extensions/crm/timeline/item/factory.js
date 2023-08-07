/**
 * @module crm/timeline/item/factory
 */
jn.define('crm/timeline/item/factory', (require, exports, module) => {
	const { TimelineItemCompatible } = require('crm/timeline/item/compatible');
	const { GenericTimelineItem } = require('crm/timeline/item/generic');
	const {
		CallActivity,
		Modification,
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
		'Activity:Email',
		'ContactList',
		'EmailActivitySuccessfullyDelivered',
		'Activity:Call',
		'Activity:OpenLine',
		'Activity:Creation',
		'Activity:ToDo',
		'Activity:ConfigurableRestApp',
		'Activity:Payment',
		'Activity:Sms',
		'Activity:Notification',
		'PaymentPaid',
		'PaymentViewed',
		'PaymentNotViewed',
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
		'Activity:TasksTask',
		'Activity:TasksTaskComment',
		'TasksTaskCreation',
		'TasksTaskModification',
	];

	/**
	 * You can specify custom item class here. It MUST inherit TimelineItemBase.
	 * @type {Object.<string, TimelineItemBase>}
	 */
	const TypeAliases = {
		Modification,
		'Activity:Call': CallActivity,
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
