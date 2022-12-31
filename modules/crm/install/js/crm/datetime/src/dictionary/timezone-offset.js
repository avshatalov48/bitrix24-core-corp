import { Loc, Text } from "main.core";

/**
 * @memberOf BX.Crm.DateTime.Dictionary
 */
const TimezoneOffset = {
	SERVER_TO_UTC: Text.toInteger(Loc.getMessage('SERVER_TZ_OFFSET')),
	USER_TO_SERVER: Text.toInteger(Loc.getMessage('USER_TZ_OFFSET')),

	// Date returns timezone offset in minutes by default, change it to seconds
	// Also offset is negative in UTC+ timezones and positive in UTC- timezones.
	// By convention Bitrix uses the opposite approach, so change offset sign.
	BROWSER_TO_UTC: - Text.toInteger((new Date()).getTimezoneOffset() * 60),
};

Object.freeze(TimezoneOffset);

export {
	TimezoneOffset,
};
