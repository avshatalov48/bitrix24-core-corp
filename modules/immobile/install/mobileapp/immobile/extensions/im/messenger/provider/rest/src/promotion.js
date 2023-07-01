/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/provider/rest/promotion
 */
jn.define('im/messenger/provider/rest/promotion', (require, exports, module) => {

	const { RestMethod } = require('im/messenger/const');

	/**
	 * @class PromotionRest
	 */
	class PromotionRest
	{
		read(id)
		{
			return BX.rest.callMethod(RestMethod.imPromotionRead, { id });
		}
	}

	module.exports = {
		PromotionRest,
	};
});
