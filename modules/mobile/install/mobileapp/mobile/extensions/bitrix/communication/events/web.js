/**
 * @module communication/events/web
 */
jn.define('communication/events/web', (require, exports, module) => {

	const { BaseEvent } = require('communication/events/base');
	const { stringify } = require('utils/string');

	class WebEvent extends BaseEvent
	{
		prepareValue(value)
		{
			return stringify(value);
		}
	}

	module.exports = { WebEvent };

});