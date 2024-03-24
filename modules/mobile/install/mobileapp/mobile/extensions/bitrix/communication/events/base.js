/**
 * @module communication/events/base
 */
jn.define('communication/events/base', (require, exports, module) => {
	const { inAppUrl } = require('in-app-url');
	const { isEmpty } = require('utils/object');

	class BaseEvent
	{
		constructor(props)
		{
			this.props = props;
		}

		open()
		{
			if (this.isEmpty())
			{
				return;
			}

			inAppUrl.open(this.getValue());
		}

		getValue()
		{
			return this.prepareValue(this.props);
		}

		prepareValue(value)
		{
			return value;
		}

		isEmpty()
		{
			const value = this.getValue();

			return !value || isEmpty(value);
		}
	}

	module.exports = { BaseEvent };
});
