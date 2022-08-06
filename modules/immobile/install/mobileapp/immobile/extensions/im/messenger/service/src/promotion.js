/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/service/promotion
 */
jn.define('im/messenger/service/promotion', (require, exports, module) => {

	const { RestMethod } = jn.require('im/messenger/const');

	/**
	 * @class PromotionService
	 */
	class PromotionService
	{
		read(id)
		{
			return BX.rest.callMethod(RestMethod.imPromotionRead, { id });
		}
	}

	module.exports = {
		PromotionService,
	};
});
