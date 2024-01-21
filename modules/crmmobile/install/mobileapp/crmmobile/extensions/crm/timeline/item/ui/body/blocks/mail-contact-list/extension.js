/**
 * @module crm/timeline/item/ui/body/blocks/mail-contact-list
 */
jn.define('crm/timeline/item/ui/body/blocks/mail-contact-list', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { ContactList } = require('crm/mail/message/elements/contact/list');
	const maxWidthTextFiled = 200;

	/**
	 * @class TimelineMailContactListBlock
	 */
	class TimelineMailContactListBlock extends TimelineItemBodyBlock
	{
		render()
		{
			return View(
				{
					style: {},
				},
				new ContactList({
					maxWidthTextFiled,
					format: 'little',
					list: this.props.contactList,
					title: this.props.title,
				}),
			);
		}
	}

	module.exports = { TimelineMailContactListBlock };
});
