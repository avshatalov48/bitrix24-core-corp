/**
 * @module layout/ui/snackbar-text
 */
jn.define('layout/ui/snackbar-text', (require, exports, module) => {
	const AppTheme = require('apptheme');

	function SnackbarText({ text, containerStyle = {}, ...other })
	{
		return View(
			{
				style: containerStyle,
				onClick: () => {
					const params = {
						title: text,
						showCloseButton: true,
						id: `SnackbarText_${Math.random()}`,
						backgroundColor: AppTheme.colors.bgPrimary,
						textColor: AppTheme.colors.base0,
						hideOnTap: true,
						autoHide: true,
					};

					const callback = () => {
					};

					dialogs.showSnackbar(params, callback);
				},
			},
			Text({
				text,
				...other,
			}),
		);
	}

	module.exports = { SnackbarText };
});
