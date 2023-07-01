/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

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
	}

	module.exports = {
		OpenLinesRest,
	};
});
