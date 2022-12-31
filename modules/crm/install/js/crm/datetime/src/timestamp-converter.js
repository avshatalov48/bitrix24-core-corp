import { Loc, Text } from "main.core";
import { TimezoneOffset } from "./dictionary/timezone-offset";

/**
 * @memberOf BX.Crm.DateTime
 */
export class TimestampConverter
{
	static serverToUser(serverTimestamp: number): number
	{
		serverTimestamp = this.#normalizeTimestampFromArgs(serverTimestamp);

		return serverTimestamp + TimezoneOffset.USER_TO_SERVER;
	}

	static userToServer(userTimestamp: number): number
	{
		userTimestamp = this.#normalizeTimestampFromArgs(userTimestamp);

		return userTimestamp - TimezoneOffset.USER_TO_SERVER;
	}

	static browserToUser(browserTimestamp: number): number
	{
		browserTimestamp = this.#normalizeTimestampFromArgs(browserTimestamp);

		return browserTimestamp + TimezoneOffset.USER_TO_SERVER;
	}

	static browserToServer(browserTimestamp: number): number
	{
		browserTimestamp = this.#normalizeTimestampFromArgs(browserTimestamp);

		return this.#browserToUtc(browserTimestamp) + TimezoneOffset.SERVER_TO_UTC;
	}

	static userToBrowser(userTimestamp: number): number
	{
		userTimestamp = this.#normalizeTimestampFromArgs(userTimestamp);

		return userTimestamp + TimezoneOffset.BROWSER_TO_UTC - TimezoneOffset.SERVER_TO_UTC - TimezoneOffset.USER_TO_SERVER;
	}

	static serverToBrowser(serverTimestamp: number): number
	{
		serverTimestamp = this.#normalizeTimestampFromArgs(serverTimestamp);

		return serverTimestamp + TimezoneOffset.BROWSER_TO_UTC - TimezoneOffset.SERVER_TO_UTC;
	}

	static #browserToUtc(browserTimestamp: number): number
	{
		browserTimestamp = this.#normalizeTimestampFromArgs(browserTimestamp);

		return browserTimestamp - TimezoneOffset.BROWSER_TO_UTC;
	}

	static #normalizeTimestampFromArgs(timestamp: any): number
	{
		const normalized = Text.toInteger(timestamp);
		if (normalized < 0)
		{
			throw new Error('BX.Crm.DateTime.TimestampConverter: input timestamp could not be negative');
		}

		return normalized;
	}
}
