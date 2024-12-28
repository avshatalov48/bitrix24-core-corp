/**
 * @module calendar/model/collab
 */
jn.define('calendar/model/collab', (require, exports, module) => {
	/**
	 * @class CollabModel
	 */
	class CollabModel
	{
		constructor(props)
		{
			this.setFields(props);
		}

		setFields(props)
		{
			this.id = BX.prop.getNumber(props, 'ID', 0);
			this.name = BX.prop.getString(props, 'NAME', '');
			this.chatId = BX.prop.getNumber(props, 'CHAT_ID', 0);
		}

		getId()
		{
			return this.id;
		}

		getName()
		{
			return this.name;
		}

		getChatId()
		{
			return this.chatId;
		}
	}

	module.exports = { CollabModel };
});
