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
	const { MessageType } = require('im/messenger/const');

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
			this.setCanBeChecked(false);
			this.setMessageAlign(MessageAlign.center);
			this.setTextAlign(MessageTextAlign.center);
			this.setFontColor('#FFFFFF');
			this.setBackgroundColor('#525C6966');
			this.setMarginTop(12);
			this.setMarginBottom(4);
		}

		getType()
		{
			return MessageType.systemText;
		}

		setMessage(date)
		{
			const text = DateFormatter.getDateGroupFormat(date);

			super.setMessage(text);

			return this;
		}

		setShowTail()
		{
			return this;
		}
	}

	module.exports = {
		DateSeparatorMessage,
	};
});
