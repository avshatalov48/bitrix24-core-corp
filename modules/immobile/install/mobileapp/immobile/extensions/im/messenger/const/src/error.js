/**
 * @module im/messenger/const/error
 */
jn.define('im/messenger/const/error', (require, exports, module) => {
	const ErrorType = {
		dialog: {
			accessError: 'ACCESS_ERROR',
			accessDenied: 'ACCESS_DENIED',
		},
	};

	const ErrorCode = {
		uploadManager: {
			NETWORK_ERROR: 404,
			INTERNAL_SERVER_ERROR: 500,
		},
		rest: {
			NO_INTERNET_CONNECTION: -2,
		},
	};

	module.exports = { ErrorType, ErrorCode };
});
