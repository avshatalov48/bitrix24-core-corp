/**
 * @module push/message
 */
jn.define('push/message', (require, exports, module) => {
	/**
	 * @class Message
	 */
	class Message
	{
		constructor({ id, type, title, body, payload, imageUrl })
		{
			this.id = id;
			this.type = type;
			this.title = title;
			this.body = body;
			this.payload = payload;
			this.imageUrl = imageUrl;
		}

		/**
		 * @returns {boolean}
		 */
		hasBody()
		{
			return this.body.length > 0;
		}
	}

	module.exports = {
		Message,
	};
});
