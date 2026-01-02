/**
 * @module im/messenger/lib/parser/functions/date
 */
jn.define('im/messenger/lib/parser/functions/date', (require, exports, module) => {
	const { camelize } = require('utils/string');
	const { DateFormatter, DateFormat } = require('im/messenger/lib/date-formatter');

	const availableFormats = Object.keys(DateFormat);

	const parserDate = {
		/**
		 * @param {string} text
		 * @return {string}
		 */
		simplify(text)
		{
			return this.processTimestampBBCode(text);
		},

		/**
		 * @param {string} text
		 * @return {string}
		 */
		decode(text)
		{
			return this.processTimestampBBCode(text);
		},

		/**
		 * @param {string} text
		 * @private
		 * @return {string}
		 */
		processTimestampBBCode(text)
		{
			const regex = /\[timestamp=(?<timestamp>\d+)\s+format=(?<format>[_a-z]+)]/gi;

			return text.replaceAll(regex, (initialText, ...args) => {
				const { timestamp, format } = args[args.length - 1];

				if (!timestamp || !format)
				{
					return initialText;
				}

				const timestampInMilliseconds = Number(timestamp) * 1000;
				const date = new Date(timestampInMilliseconds);

				if (!availableFormats.includes(format))
				{
					return initialText;
				}

				return DateFormatter.format(date, format);
			});
		},
	};

	module.exports = {
		parserDate,
	};
});
