import { TimestampConverter } from "./timestamp-converter";

/**
 * @memberOf BX.Crm.DateTime
 */
export class Factory
{
	/**
	 * Returns Date object with current time in user timezone.
	 *
	 * WARNING! In Bitrix user timezone !== browser timezone. Users can change their timezone from profile settings and
	 * will be different from browser timezone.
	 *
	 * If you need to get 'now' in a user's perspective, use this method instead of 'new Date()'
	 *
	 * Note that 'getTimezoneOffset' will not return correct user timezone, its always returns browser offset
	 *
	 * @returns {Date}
	 */
	static getUserNow(): Date
	{
		const userTimestamp = TimestampConverter.browserToUser(this.#getBrowserNowTimestamp());

		return new Date(userTimestamp * 1000);
	}

	/**
	 * Returns Date object with current time in server timezone
	 * Note that 'getTimezoneOffset' will not return correct server timezone, its always returns browser offset
	 *
	 * @returns {Date}
	 */
	static getServerNow(): Date
	{
		const serverTimestamp = TimestampConverter.browserToServer(this.#getBrowserNowTimestamp());

		return new Date(serverTimestamp * 1000);
	}

	static createFromTimestampInUserTimezone(timestamp): Date
	{
		const browserTimestamp = TimestampConverter.browserToUser(timestamp);

		return new Date(browserTimestamp * 1000);
	}

	static createFromTimestampInServerTimezone(timestamp): Date
	{
		const browserTimestamp = TimestampConverter.browserToServer(timestamp);

		return new Date(browserTimestamp * 1000);
	}

	static #getBrowserNowTimestamp(): number
	{
		return Math.floor(Date.now() / 1000);
	}
}
