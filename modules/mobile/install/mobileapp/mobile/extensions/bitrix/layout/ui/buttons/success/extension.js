(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	/**
	 * @class SuccessButton
	 */
	class SuccessButton extends BaseButton
	{
		getStyle()
		{
			if (this.isRounded())
			{
				return {
					button: {
						borderColor: AppTheme.colors.accentMainSuccess,
						backgroundColor: AppTheme.colors.accentMainSuccess,
					},
					icon: {},
					text: {
						color: AppTheme.colors.baseWhiteFixed,
					},
				};
			}

			return {
				button: {},
				icon: {},
				text: {
					fontWeight: '500',
					fontSize: 18,
					color: AppTheme.colors.accentMainSuccess,
				},
			};
		}
	}

	this.SuccessButton = SuccessButton;
})();
