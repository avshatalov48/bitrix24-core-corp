/**
 * @module im/messenger/lib/parser/functions/context
 */
jn.define('im/messenger/lib/parser/functions/context', (require, exports, module) => {
	const { Type } = require('type');
	const parserContext = {
		simplify(text)
		{
			text = text.replace(/\[context=(chat\d+|\d+:\d+)\/(\d+)](.*?)\[\/context]/gims, (whole, dialogId, messageId, message) => {
				if (Type.isStringFilled(dialogId))
				{
					return message;
				}
			});

			return text;
		},
	};

	module.exports = { parserContext };
});
