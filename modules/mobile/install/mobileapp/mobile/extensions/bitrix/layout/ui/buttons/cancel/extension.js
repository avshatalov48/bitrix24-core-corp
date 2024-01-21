(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	/**
	 * @class CancelButton
	 */
	class CancelButton extends BaseButton
	{
		getStyle()
		{
			if (this.isRounded())
			{
				return {
					button: {
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
					icon: {},
					text: {
						color: AppTheme.colors.base2,
					},
				};
			}

			return {
				button: {},
				icon: {},
				text: {
					fontWeight: '500',
					fontSize: 18,
					color: AppTheme.colors.base2,
				},
			};
		}
	}

	this.CancelButton = CancelButton;
})();

