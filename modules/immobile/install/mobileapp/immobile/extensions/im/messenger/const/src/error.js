/**
 * @module im/messenger/const/error
 */
jn.define('im/messenger/const/error', (require, exports, module) => {
	const ErrorType = {
		dialog: {
			accessError: 'ACCESS_ERROR',
		},
	};

	const ErrorCode = {
		uploadManager: {
			NETWORK_ERROR: 404,
			INTERNAL_SERVER_ERROR: 500,
		},
	};

	module.exports = { ErrorType, ErrorCode };
});
