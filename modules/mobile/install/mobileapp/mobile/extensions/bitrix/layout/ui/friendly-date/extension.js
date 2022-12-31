/**
 * @module layout/ui/friendly-date
 */
jn.define('layout/ui/friendly-date', (require, exports, module) => {

	const { Loc } = require('loc');
	const { Moment } = require('utils/date');
	const { date, shortTime } = require('utils/date/formats');
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');
	const { AutoupdatingDatetime } = require('layout/ui/friendly-date/autoupdating-datetime');

	/**
	 * @class FriendlyDate
	 */
	class FriendlyDate extends AutoupdatingDatetime
	{
		get timeAgoTextBuilder()
		{
			return new TimeAgoFormat({
				defaultFormat: this.defaultTimeFormat,
				futureAllowed: this.futureAllowed,
			});
		}

		get defaultTimeFormat()
		{
			const defaultFormat = (moment) => {
				return moment.format(shortTime).toLocaleLowerCase(env.languageId);
			};
			return this.props.defaultTimeFormat || defaultFormat;
		}

		get showTime()
		{
			return BX.prop.getBoolean(this.props, 'showTime', false);
		}

		get timeSeparator()
		{
			return BX.prop.getString(this.props, 'timeSeparator', ', ');
		}

		get useTimeAgo()
		{
			return BX.prop.getBoolean(this.props, 'useTimeAgo', false);
		}

		get skipTimeAgoAfterSeconds()
		{
			return BX.prop.getNumber(this.props, 'skipTimeAgoAfterSeconds', 3600);
		}

		get futureAllowed()
		{
			return BX.prop.getBoolean(this.props, 'futureAllowed', false);
		}

		/**
		 * @param {Moment} moment
		 * @return {string}
		 */
		makeText(moment)
		{
			if (moment.isYesterday)
			{
				return this.getMessage('MOBILE_UI_FRIENDLY_DATE_YESTERDAY', moment);
			}

			if (moment.isToday)
			{
				if (this.useTimeAgo && !moment.isOverSeconds(this.skipTimeAgoAfterSeconds))
				{
					return this.timeAgoTextBuilder.format(moment);
				}

				return this.getMessage('MOBILE_UI_FRIENDLY_DATE_TODAY', moment);
			}

			if (moment.isTomorrow)
			{
				return this.getMessage('MOBILE_UI_FRIENDLY_DATE_TOMORROW', moment);
			}

			return this.formatDefault(moment);
		}

		/**
		 * @param {string} code
		 * @param {Moment} moment
		 * @returns {string}
		 */
		getMessage(code, moment)
		{
			let message = Loc.getMessage(code);

			if (this.showTime)
			{
				message += `${this.timeSeparator}${this.formatTime(moment)}`;
			}

			return message;
		}

		/**
		 * @private
		 * @param {Moment} moment
		 */
		formatTime(moment)
		{
			if (typeof this.defaultTimeFormat === 'function')
			{
				return this.defaultTimeFormat(moment);
			}
			return moment.format(this.defaultTimeFormat).toLocaleLowerCase(env.languageId);
		}

		/**
		 * @private
		 * @param {Moment} moment
		 */
		formatDefault(moment)
		{
			if (typeof this.props.defaultFormat === 'function')
			{
				return this.props.defaultFormat(moment);
			}
			else if (this.props.defaultFormat)
			{
				return moment.format(this.props.defaultFormat);
			}

			const day = moment.format(date);
			const time = moment.format(shortTime).toLocaleLowerCase(env.languageId);

			return `${day}${this.timeSeparator}${time}`;
		}
	}

	module.exports = { FriendlyDate };

});