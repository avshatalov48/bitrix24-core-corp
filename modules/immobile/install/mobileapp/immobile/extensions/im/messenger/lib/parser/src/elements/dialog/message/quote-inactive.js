/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/parser/elements/dialog/message/quote-inactive
 */
jn.define('im/messenger/lib/parser/elements/dialog/message/quote-inactive', (require, exports, module) => {

	const { Type } = require('type');

	/**
	 * @class QuoteInactive
	 */
	class QuoteInactive
	{
		constructor(title = '', text = '')
		{
			this.type = QuoteInactive.getType();

			if (Type.isStringFilled(text))
			{
				this.text = text;
			}

			if (Type.isStringFilled(title))
			{
				this.title = title;
			}
		}

		static getType()
		{
			return 'quote-inactive';
		}
	}

	module.exports = {
		QuoteInactive,
	};
});
