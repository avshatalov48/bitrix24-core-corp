/**
 * @module layout/ui/friendly-date
 */
jn.define('layout/ui/friendly-date', (require, exports, module) => {
	const { AutoupdatingDatetime } = require('layout/ui/friendly-date/autoupdating-datetime');
	const { Loc } = require('loc');
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');
	const { date, shortTime } = require('utils/date/formats');

	/**
	 * @class FriendlyDate
	 */
	class FriendlyDate extends AutoupdatingDatetime
	{
		constructor(props)
		{
			super(props);
		}

		get timeAgoTextBuilder()
		{
			return new TimeAgoFormat({
				defaultFormat: this.defaultTimeFormat,
				futureAllowed: this.futureAllowed,
				context: this.props.context,
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
				return this.getMessage('MOBILE_UI_FRIENDLY_DATE_YESTERDAY_MSGVER_1', moment);
			}

			if (moment.isToday)
			{
				if (this.useTimeAgo && !moment.isOverSeconds(this.skipTimeAgoAfterSeconds))
				{
					return this.timeAgoTextBuilder.format(moment);
				}

				return this.getMessage('MOBILE_UI_FRIENDLY_DATE_TODAY_MSGVER_1', moment);
			}

			if (moment.isTomorrow)
			{
				return this.getMessage('MOBILE_UI_FRIENDLY_DATE_TOMORROW_MSGVER_1', moment);
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
			return Loc.getMessage(code) + this.formatTime(moment);
		}

		/**
		 * @private
		 * @param {Moment} moment
		 */
		formatTime(moment)
		{
			if (this.showTime)
			{
				let time = '';

				if (typeof this.defaultTimeFormat === 'function')
				{
					time = this.defaultTimeFormat(moment);
				}
				else
				{
					time = moment.format(this.defaultTimeFormat).toLocaleLowerCase(env.languageId);
				}

				if (time)
				{
					return `${this.timeSeparator}${time}`;
				}
			}

			return '';
		}

		/**
		 * @private
		 * @param {Moment} moment
		 */
		formatDefault(moment)
		{
			if (typeof this.props.defaultFormat === 'function')
			{
				const context = this.props.context ?? this;

				return this.props.defaultFormat(moment, context);
			}

			if (this.props.defaultFormat)
			{
				return moment.format(this.props.defaultFormat);
			}

			const day = moment.format(date);
			const time = this.formatTime(moment);

			return `${day}${time}`;
		}
	}

	module.exports = { FriendlyDate };
});
