(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	/**
	 * @class PrimaryButton
	 */
	class PrimaryButton extends BaseButton
	{
		getStyle()
		{
			if (this.isRounded())
			{
				return {
					button: {
						borderColor: AppTheme.colors.accentMainPrimary,
						backgroundColor: AppTheme.colors.accentMainPrimary,
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
					color: AppTheme.colors.accentMainLinks,
				},
			};
		}
	}

	this.PrimaryButton = PrimaryButton;
})();
