/**
 * @module layout/ui/snackbar-text
 */
jn.define('layout/ui/snackbar-text', (require, exports, module) => {

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
						backgroundColor: '#000000',
						textColor: '#ffffff',
						hideOnTap: true,
						autoHide: true,
					};

					const callback = () => {};

					dialogs.showSnackbar(params, callback);
				}
			},
			Text({
				text,
				...other,
			}),
		);
	}

	module.exports = { SnackbarText };

});