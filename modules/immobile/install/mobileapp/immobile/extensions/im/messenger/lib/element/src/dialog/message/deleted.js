/**
 * @module im/messenger/lib/element/dialog/message/deleted
 */
jn.define('im/messenger/lib/element/dialog/message/deleted', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Message } = require('im/messenger/lib/element/dialog/message/base');

	/**
	 * @class DeletedMessage
	 */
	class DeletedMessage extends Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			const message = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_DELETED');

			this.setMessage(message);
			this.setFontColor('#959CA4');
			this.setShowTail(true);
		}

		getType()
		{
			return 'deleted';
		}
	}

	module.exports = {
		DeletedMessage,
	};
});
