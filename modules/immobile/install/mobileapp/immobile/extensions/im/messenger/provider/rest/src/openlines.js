/**
 * @module im/messenger/provider/rest/openlines
 */
jn.define('im/messenger/provider/rest/openlines', (require, exports, module) => {
	const { Type } = require('type');
	const { RestMethod } = require('im/messenger/const');

	/**
	 * @class OpenLinesRest
	 */
	class OpenLinesRest
	{
		getByUserCode(userCode)
		{
			if (!userCode || !Type.isStringFilled(userCode))
			{
				throw new Error('OpenLinesRest: userCode must be a filled string.');
			}

			return BX.rest.callMethod(RestMethod.openlinesDialogGet, { USER_CODE: userCode });
		}

		getBySessionId(sessionId)
		{
			if (!sessionId || !Type.isNumber(sessionId))
			{
				throw new Error('OpenLinesRest: sessionId must be a number.');
			}

			return BX.rest.callMethod(RestMethod.openlinesDialogGet, { SESSION_ID: sessionId });
		}
	}

	module.exports = {
		OpenLinesRest,
	};
});
