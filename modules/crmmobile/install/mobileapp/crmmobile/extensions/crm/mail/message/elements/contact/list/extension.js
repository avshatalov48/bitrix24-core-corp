/**
 * @module crm/mail/message/elements/contact/list
 */
jn.define('crm/mail/message/elements/contact/list', (require, exports, module) => {
	const { ContactCard } = require('crm/mail/message/elements/contact/card');
	const { PureComponent } = require('layout/pure-component');

	class ContactList extends PureComponent
	{
		render()
		{
			let titleFiled = null;
			const {
				maxWidthTextFiled,
				title,
				format,
				list,
			} = this.props;

			if (!Array.isArray(list) || list.length === 0)
			{
				return null;
			}

			if (title)
			{
				titleFiled = Text({
					style: {
						paddingRight: 3,
						fontWeight: '400',
						fontSize: 13,
						color: '#959CA4',
					},
					text: title,
				});
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						width: '100%',
						flexWrap: 'wrap',
						display: 'flex',
					},
				},
				titleFiled,
				...list.map((item) => {
					return new ContactCard({ maxWidthTextFiled, format, ...item });
				}),
			);
		}
	}

	module.exports = { ContactList };
});
