/**
 * @module crm/timeline/item/ui/body/blocks
 */
jn.define('crm/timeline/item/ui/body/blocks', (require, exports, module) => {
	const { TimelineItemBodyTextBlock: TextBlock } = require('crm/timeline/item/ui/body/blocks/text-block');
	const { TimelineItemBodyLinkBlock: LinkBlock } = require('crm/timeline/item/ui/body/blocks/link-block');
	const { TimelineItemBodyWithTitleBlock: WithTitle } = require('crm/timeline/item/ui/body/blocks/with-title');
	const { TimelineItemBodySharingSlotsListBlock: SharingSlotsList } = require(
		'crm/timeline/item/ui/body/blocks/sharing-slots-list',
	);
	const { TimelineItemBodyValueChangeBlock: ValueChange } = require('crm/timeline/item/ui/body/blocks/value-change');
	const { TimelineItemBodyLineOfTextBlocks: LineOfTextBlocks } = require(
		'crm/timeline/item/ui/body/blocks/line-of-text-blocks',
	);
	const { TimelineItemBodySmsMessageBlock: SmsMessage } = require('crm/timeline/item/ui/body/blocks/sms-message');
	const { TimelineItemBodyCommentContentBlock: CommentContent } = require(
		'crm/timeline/item/ui/body/blocks/comment-content',
	);
	const { TimelineItemBodyAudioBlock: TimelineAudio } = require('crm/timeline/item/ui/body/blocks/audio-block');
	const { TimelineItemBodyClientMark: ClientMark } = require('crm/timeline/item/ui/body/blocks/client-mark');
	const { TimelineItemBodyDatePill: DatePill } = require('crm/timeline/item/ui/body/blocks/date-pill');
	const { TimelineItemBodyDateBlock: DateBlock } = require('crm/timeline/item/ui/body/blocks/date-block');
	const { TimelineItemBodyPlayerAlertBlock: PlayerAlert } = require('crm/timeline/item/ui/body/blocks/player-alert');
	const { TimelineItemBodyEditableDescriptionBlock: EditableDescription } = require(
		'crm/timeline/item/ui/body/blocks/editable-description',
	);
	const { TimelineItemBodyNoteBlock: Note } = require('crm/timeline/item/ui/body/blocks/note');
	const { TimelineItemBodyFileList: FileList } = require('crm/timeline/item/ui/body/blocks/file-list');
	const { TimelineItemBodyMoney: Money } = require('crm/timeline/item/ui/body/blocks/money');
	const { TimelineItemBodyMoneyPill: MoneyPill } = require('crm/timeline/item/ui/body/blocks/money-pill');
	const { TimelineItemBodyEcommerceDocumentsList: EcommerceDocumentsList } = require(
		'crm/timeline/item/ui/body/blocks/ecommerce-documents-list',
	);
	const { TimelineMailContactListBlock: ContactList } = require('crm/timeline/item/ui/body/blocks/mail-contact-list');
	const { TimelineItemBodyItemSelector: ItemSelector } = require('crm/timeline/item/ui/body/blocks/item-selector');
	const { TimelineItemBodyAddressBlock: AddressBlock } = require('crm/timeline/item/ui/body/blocks/address-block');

	const AvailableBlocks = {
		TextBlock,
		LinkBlock,
		WithTitle,
		SharingSlotsList,
		ValueChange,
		LineOfTextBlocks,
		SmsMessage,
		CommentContent,
		TimelineAudio,
		ClientMark,
		DateBlock,
		PlayerAlert,
		EditableDescription,
		Note,
		FileList,
		Money,
		MoneyPill,
		EcommerceDocumentsList,
		ContactList,
		ItemSelector,
		DatePill,
		AddressBlock,
	};

	/**
	 * @class TimelineItemBodyBlockFactory
	 */
	class TimelineItemBodyBlockFactory
	{
		/**
		 * @param {TimelineItemModel} model
		 * @param {EventEmitter} itemScopeEventBus
		 * @param {EventEmitter} timelineScopeEventBus
		 * @param {function} onAction
		 */
		constructor({ model, itemScopeEventBus, timelineScopeEventBus, onAction })
		{
			this.model = model;
			this.itemScopeEventBus = itemScopeEventBus;
			this.timelineScopeEventBus = timelineScopeEventBus;
			this.onAction = onAction;
		}

		/**
		 * @param {string} rendererName
		 * @param {object} props
		 * @returns {TimelineItemBodyBlock|null}
		 */
		make(rendererName, props = {})
		{
			if (AvailableBlocks[rendererName])
			{
				return new AvailableBlocks[rendererName](props, this);
			}

			console.log('Content block not supported', rendererName, props);

			return null;
		}
	}

	module.exports = { TimelineItemBodyBlockFactory };
});
