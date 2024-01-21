/**
 * @module layout/ui/friendly-date/time-ago
 */
jn.define('layout/ui/friendly-date/time-ago', (require, exports, module) => {

	const { Moment } = require('utils/date');
	const { AutoupdatingDatetime } = require('layout/ui/friendly-date/autoupdating-datetime');
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');

	/**
	 * @class TimeAgo
	 */
	class TimeAgo extends AutoupdatingDatetime
	{
		get textBuilder()
		{
			return new TimeAgoFormat({
				defaultFormat: this.props.defaultFormat,
				skipAfterSeconds: this.props.skipAfterSeconds,
				justNowDelay: this.props.justNowDelay,
				onGetPhrase: this.props.onGetPhrase,
				futureAllowed: this.props.futureAllowed,
			});
		}

		/**
		 * @param {Moment} moment
		 * @return {string}
		 */
		makeText(moment)
		{
			return this.textBuilder.format(moment);
		}
	}

	module.exports = { TimeAgo };
});
