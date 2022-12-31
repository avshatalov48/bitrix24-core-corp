/**
 * @module communication/events/email
 */

jn.define('communication/events/email', (require, exports, module) => {

	const { BaseEvent } = require('communication/events/base');
	const { stringify } = require('utils/string');

	class EmailEvent extends BaseEvent
	{
		prepareValue(value)
		{
			if (typeof value === 'string' && Boolean(value.trim()))
			{
				return `mailto:${stringify(value)}`;
			}

			return null;
		}
	}

	module.exports = { EmailEvent };

});