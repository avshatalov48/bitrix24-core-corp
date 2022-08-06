/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/core/loc
 */
jn.define('im/messenger/lib/core/loc', (require, exports, module) => {

	const { Type } = jn.require('im/messenger/lib/core/type');

	class Loc
	{
		/**
		 * Gets message by id
		 * @param {string} messageId
		 * @param {object} replacements
		 * @return {?string}
		 */
		getMessage(messageId, replacements= null)
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

		hasMessage(messageId)
		{
			return Type.isString(messageId) && !Type.isNil(BX.message[messageId]);
		}

		/**
		 * Sets message
		 * @param {string} id
		 * @param {string} [value]
		 */
		setMessage(id, value)
		{
			if (Type.isString(id) && Type.isString(value))
			{
				BX.message({[id]: value});
			}
		}
	}

	module.exports = {
		Loc: new Loc(),
	};
});
