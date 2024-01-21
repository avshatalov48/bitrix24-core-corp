/**
 * @module crm/document/shared-utils
 */
jn.define('crm/document/shared-utils', (require, exports, module) => {
	const { hashCode } = require('utils/hash');
	const AppTheme = require('apptheme');

	/**
	 * @param {string|function():string|null} message
	 */
	function showTooltip(message)
	{
		const title = typeof message === 'function' ? message() : message;

		if (!title)
		{
			return;
		}

		const params = {
			title,
			showCloseButton: true,
			id: String(hashCode(title)),
			backgroundColor: AppTheme.colors.bgPrimary,
			textColor: AppTheme.colors.base0,
			hideOnTap: true,
			autoHide: true,
		};
		dialogs.showSnackbar(params, () => {});
	}

	module.exports = { showTooltip };
});
