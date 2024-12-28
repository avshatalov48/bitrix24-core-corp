/**
 * @module im/messenger/lib/parser/elements/dialog/message/code
 */
jn.define('im/messenger/lib/parser/elements/dialog/message/code', (require, exports, module) => {

	const { Type } = require('type');

	/**
	 * @class Code
	 */
	class Code
	{
		constructor(text = '')
		{
			this.type = Code.getType();

			if (Type.isStringFilled(text))
			{
				this.text = text;
			}
		}

		static getType()
		{
			return 'code';
		}
	}

	module.exports = {
		Code,
	};
});
