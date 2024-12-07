/**
 * @module intranet/invite-new/src/error
 */
jn.define('intranet/invite-new/src/error', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');

	const showErrorMessage = (error) => {
		return new Promise((resolve) => {
			if (!error)
			{
				resolve();
			}

			Alert.confirm(
				'',
				error.message,
				[{
					text: Loc.getMessage('INTRANET_INVITE_ERROR_OK_BUTTON_TEXT'),
					type: 'default',
					onPress: () => {
						resolve();
					},
				}],
			);
		});
	};

	module.exports = { showErrorMessage };
});
