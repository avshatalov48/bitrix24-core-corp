/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/service/openlines
 */
jn.define('im/messenger/service/openlines', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { RestMethod } = jn.require('im/messenger/const');

	/**
	 * @class OpenLinesService
	 */
	class OpenLinesService
	{
		getByUserCode(userCode)
		{
			if (!userCode || !Type.isStringFilled(userCode))
			{
				throw new Error('OpenLinesService: userCode must be a filled string.');
			}

			return BX.rest.callMethod(RestMethod.openlinesDialogGet, { USER_CODE: userCode });
		}
	}

	module.exports = {
		OpenLinesService,
	};
});
