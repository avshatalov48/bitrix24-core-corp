/**
 * @module im/messenger/const/error
 */
jn.define('im/messenger/const/error', (require, exports, module) => {
	const ErrorType = {
		dialog: {
			accessError: 'ACCESS_ERROR',
			accessDenied: 'ACCESS_DENIED',
			chatNotFound: 'CHAT_NOT_FOUND',
			delete: {
				userInvitedFromStructure: 'USER_INVITED_FROM_STRUCTURE',
			},
		},
		planLimit: {
			MESSAGE_ACCESS_DENIED_BY_TARIFF: 'MESSAGE_ACCESS_DENIED_BY_TARIFF',
		},
	};

	const ErrorCode = {
		/* region ajax (runAction) */
		NETWORK_ERROR: 404,
		INTERNAL_SERVER_ERROR: 500,
		/* region rest (BX.rest.callMethod) */
		NO_INTERNET_CONNECTION: -2,
	};

	module.exports = { ErrorType, ErrorCode };
});
