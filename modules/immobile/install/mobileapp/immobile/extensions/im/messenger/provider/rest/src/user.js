/**
 * @module im/messenger/provider/rest/user
 */
jn.define('im/messenger/provider/rest/user', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @class UserRest
	 */
	class UserRest
	{
		resendInvite(options = {})
		{
			const methodParams = {
				data: {
					params: {},
				},
			};

			if (!Type.isNumber(Number(options.userId)))
			{
				throw new TypeError('UserRest.resendInvite: options.dialogId is invalid.');
			}

			methodParams.data.params.userId = options.userId;

			return BX.ajax.runAction('intranet.controller.invite.reinvite', methodParams);
		}

		cancelInvite(options = {})
		{
			const methodParams = {
				data: {
					params: {},
				},
			};

			if (!Type.isNumber(Number(options.userId)))
			{
				throw new TypeError('UserRest.resendInvite: options.dialogId is invalid.');
			}

			methodParams.data.params.userId = options.userId;

			return BX.ajax.runAction('intranet.controller.invite.deleteinvitation', methodParams);
		}
	}

	module.exports = {
		UserRest,
	};
});
