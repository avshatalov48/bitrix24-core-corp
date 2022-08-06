/* eslint-disable flowtype/require-return-type */

/**
 * @module utils/uuid
 */
jn.define('utils/uuid', (require, exports, module) => {

	/**
	 * @class Uuid
	 */
	class Uuid
	{
		getV4()
		{
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
				var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
				return v.toString(16);
			});
		}

		isV4(uuid)
		{
			if (typeof uuid !== 'string')
			{
				return false;
			}

			const uuidV4pattern =
				new RegExp(/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i)
			;

			return uuid.search(uuidV4pattern) === 0;
		}
	}

	module.exports = {
		Uuid: new Uuid(),
	};
});
