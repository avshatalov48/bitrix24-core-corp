/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/service/user
 */
jn.define('im/messenger/service/user', (require, exports, module) => {

	const { Type } = jn.require('type');

	/**
	 * @class UserService
	 */
	class UserService
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
				throw new Error('UserService.resendInvite: options.dialogId is invalid.');
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
				throw new Error('UserService.resendInvite: options.dialogId is invalid.');
			}

			methodParams.data.params.userId = options.userId;

			return BX.ajax.runAction('intranet.controller.invite.deleteinvitation', methodParams);
		}
	}

	module.exports = {
		UserService,
	};
});
