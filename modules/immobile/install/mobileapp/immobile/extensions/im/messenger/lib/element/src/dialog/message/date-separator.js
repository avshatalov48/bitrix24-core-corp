/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/element/dialog/message/date-separator
 */
jn.define('im/messenger/lib/element/dialog/message/date-separator', (require, exports, module) => {

	const {
		MessageAlign,
		MessageTextAlign,
	} = require('im/messenger/lib/element/dialog/message/base');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');

	/**
	 * @class DateSeparatorMessage
	 */
	class DateSeparatorMessage extends Message
	{
		constructor(id, date, options = {})
		{
			super({
				id,
			}, options);

			this.setMessage(date);
			this.setShowReaction(false);
			this.setCanBeQuoted(false);
			this.setMessageAlign(MessageAlign.center);
			this.setTextAlign(MessageTextAlign.center);
			this.setFontColor('#FFFFFF');
			this.setBackgroundColor('#525C69');
		}

		setMessage(date)
		{
			const text = DateFormatter.getDateGroupFormat(date);

			super.setMessage(text);

			return this;
		}

		getType()
		{
			return 'text';
		}
	}

	module.exports = {
		DateSeparatorMessage,
	};
});
