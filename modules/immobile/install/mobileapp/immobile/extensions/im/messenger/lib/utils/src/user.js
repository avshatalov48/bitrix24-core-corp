/**
 * @module im/messenger/lib/utils/user
 */
jn.define('im/messenger/lib/utils/user', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Moment } = require('utils/date');
	const { DateUtils } = require('im/messenger/lib/utils/date');
	const { longDate, dayMonth, shortTime } = require('utils/date/formats');

	class UserUtils
	{
		/**
		 *
		 * @param {?UsersModelState} userData
		 * @param {boolean} [fullLastSeenText=false]
		 * @param {boolean} [fullLastSeenTextByNow=false]
		 * @return {string}
		 */
		getLastDateText(userData, fullLastSeenText = false, fullLastSeenTextByNow = false)
		{
			if (!userData || userData.bot || userData.network || !userData.lastActivityDate)
			{
				return '';
			}

			const isOnline = this.isOnline(new Moment(userData.lastActivityDate));

			const isMobileOnline = this.isMobileOnline(
				userData.lastActivityDate,
				userData.mobileLastDate,
			);

			let text = '';
			const lastSeenText = this.getLastSeenText(userData.lastActivityDate);

			// "away for X minutes"
			if (isOnline && userData.idle && !isMobileOnline)
			{
				text = '';
				text = Loc.getMessage('IMMOBILE_STATUS_AWAY_TITLE').replace('#TIME#', this.getIdleText(userData.idle));
			}
			// truly online, last activity date < 5 minutes ago - show status text
			else if (isOnline && !lastSeenText)
			{
				text = this.getStatusTextForLastDate(userData.status);
			}
			// last activity date > 5 minutes ago - "Was online X minutes ago"
			else if (lastSeenText)
			{
				if (fullLastSeenTextByNow)
				{
					const moment = new Moment(userData.lastActivityDate);
					const isNearNow = moment.isToday || moment.isYesterday;

					text = isNearNow
						? this.getShortLastSeenPhrase(lastSeenText, userData.gender)
						: this.getFullLastSeenPhrase(moment, userData.gender)
					;
				}
				else
				{
					text = fullLastSeenText
						? this.getFullLastSeenPhrase(new Moment(userData.lastActivityDate), userData.gender)
						: this.getShortLastSeenPhrase(lastSeenText, userData.gender)
					;
				}
			}

			return text;
		}

		getOnlineSecondsLimit()
		{
			// eslint-disable-next-line no-undef
			const { limitOnline } = jnExtensionData.get('im:messenger/lib/utils');

			const FIFTEEN_MINUTES = 15 * 60;

			return limitOnline ? Number.parseInt(limitOnline, 10) : FIFTEEN_MINUTES;
		}

		/**
		 *
		 * @param {Moment} lastActivityMoment
		 * @return {boolean}
		 */
		isOnline(lastActivityMoment)
		{
			return lastActivityMoment.secondsFromNow < this.getOnlineSecondsLimit();
		}

		/**
		 *
		 * @param {string} lastActivityDate
		 * @param {string} mobileLastDate
		 * @return {boolean}
		 */
		isMobileOnline(lastActivityDate, mobileLastDate)
		{
			if (!lastActivityDate || !mobileLastDate)
			{
				return false;
			}
			const lastActivityMoment = new Moment(lastActivityDate);
			const mobileLastMoment = new Moment(mobileLastDate);

			return (
				mobileLastMoment.secondsFromNow < this.getOnlineSecondsLimit()
				&& lastActivityMoment.minutesFromNow - mobileLastMoment.minutesFromNow < 15
			);
		}

		getStatusTextForLastDate(status)
		{
			const statusCode = status.toUpperCase();

			return Loc.getMessage(`IMMOBILE_STATUS_${statusCode}`) || statusCode;
		}

		getStatusText(status)
		{
			const statusCode = status.toUpperCase();

			return Loc.getMessage(`IMMOBILE_STATUS_TEXT_${statusCode}`) || statusCode;
		}

		/**
		 *
		 * @protected
		 * @param {string} lastActivityDate
		 * @return {string}
		 */
		getLastSeenText(lastActivityDate)
		{
			if (!lastActivityDate)
			{
				return '';
			}

			const moment = new Moment(lastActivityDate);

			if (moment.minutesFromNow > 5)
			{
				const dateUtils = new DateUtils();

				return dateUtils.formatLastActivityDate(moment);
			}

			return '';
		}

		/**
		 * @protected
		 * @param {string} lastSeenText
		 * @param {'M'|'Y'} gender
		 * @return {string}
		 */
		getShortLastSeenPhrase(lastSeenText, gender)
		{
			const phraseCode = `IMMOBILE_LAST_SEEN_SHORT_${gender.toUpperCase()}`;

			return Loc.getMessage(phraseCode).replace('#LAST_SEEN#', lastSeenText);
		}

		/**
		 * @protected
		 * @param {Moment}moment
		 * @param {'M'|'Y'} gender
		 */
		getFullLastSeenPhrase(moment, gender)
		{
			const phraseCode = `IMMOBILE_LAST_SEEN_${gender.toUpperCase()}`;

			if (moment.inThisYear)
			{
				return Loc.getMessage(phraseCode)
					.replace('#DATE#', moment.format(dayMonth))
					.replace('#TIME#', moment.format(shortTime))
				;
			}

			return Loc.getMessage(phraseCode)
				.replace('#DATE#', moment.format(longDate))
				.replace('#TIME#', moment.format(shortTime))
			;
		}

		/**
		 * @protected
		 * @param {string} idle
		 * @return {string}
		 */
		getIdleText(idle = '')
		{
			if (!idle)
			{
				return '';
			}

			const moment = new Moment(idle);

			return (new DateUtils()).formatIdleDate(moment);
		}
	}

	module.exports = { UserUtils };
});
