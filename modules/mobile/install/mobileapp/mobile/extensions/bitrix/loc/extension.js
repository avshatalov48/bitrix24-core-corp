/**
 * @module loc
 */
jn.define('loc', (require, exports, module) => {

	const { Type } = jn.require('type');

	class Loc
	{
		/**
		 * Gets message by id
		 * @param {string} messageId
		 * @param {object} replacements
		 * @return {?string}
		 */
		static getMessage(messageId, replacements= null)
		{
			let mess = BX.message(messageId);
			if (Type.isString(mess) && Type.isPlainObject(replacements))
			{
				Object.keys(replacements).forEach((replacement) => {
					const globalRegexp = new RegExp(replacement, 'gi');
					mess = mess.replace(
						globalRegexp,
						() => {
							return Type.isNil(replacements[replacement]) ? '' : String(replacements[replacement]);
						}
					);
				});
			}

			return mess;
		}

		static hasMessage(messageId)
		{
			return Type.isString(messageId) && !Type.isNil(BX.message[messageId]);
		}

		/**
		 * Sets message
		 * @param {string} id
		 * @param {string} [value]
		 */
		static setMessage(id, value)
		{
			if (Type.isString(id) && Type.isString(value))
			{
				BX.message({[id]: value});
			}
		}
	}

	module.exports = {
		Loc,
	};
});
