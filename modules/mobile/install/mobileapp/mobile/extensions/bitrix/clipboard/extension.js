jn.define('clipboard', (require, exports, module) => {
	const { showErrorToast } = require('toast/error');
	const { Icon } = require('assets/icons');

	const Clipboard = {
		get: () => {
			return Application.copyFromClipboard();
		},
		put: async (text) => {
			return new Promise((resolve, reject) => {
				const result = Application.copyToClipboard(text);
				if (result instanceof Promise)
				{
					result.then(() => resolve())
						.catch((error) => {
							if (error?.code === 1)
							{
								showErrorToast({ message: BX.message('COPY_DENIED'), iconName: Icon.BAN.getIconName() });
							}
							reject(error);
						});
				}
				else
				{
					resolve();
				}
			});
		},
	};
	module.exports = { Clipboard };
});
