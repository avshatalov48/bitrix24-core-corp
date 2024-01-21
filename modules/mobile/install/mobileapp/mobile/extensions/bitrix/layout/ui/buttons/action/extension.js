(() => {

	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	class ActionButton extends BaseButton
	{
		getStyle()
		{
			return {
				button: {
					borderColor: AppTheme.colors.accentMainPrimary,
					backgroundColor: AppTheme.colors.bgContentPrimary,
				},
				icon: {},
				text: {
					color: AppTheme.colors.base2,
				},
			};
		}
	}

	this.ActionButton = ActionButton;
})();

