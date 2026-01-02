/**
 * @module layout/ui/friendly-date/formatter-factory
 */
jn.define('layout/ui/friendly-date/formatter-factory', (require, exports, module) => {
	const { HumanDateFormatter } = require('layout/ui/friendly-date/human-date-formatter');
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');

	const FormatterTypes = {
		HUMAN_DATE: 'humanDate',
		DEFAULT: 'timeAgo',
	};

	class FormatterFactory
	{
		static create(type, props)
		{
			switch (type)
			{
				case FormatterTypes.HUMAN_DATE:
					return new HumanDateFormatter(props);

				case FormatterTypes.DEFAULT:
					return this.createDefault(props);

				default:
					throw new Error(`Unknown formatter type: ${type}`);
			}
		}

		static createDefault(props)
		{
			return new TimeAgoFormat(props);
		}
	}

	module.exports = {
		FormatterFactory,
		FormatterTypes,
	};
});
