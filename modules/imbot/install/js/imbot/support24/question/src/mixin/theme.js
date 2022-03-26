export const Theme = {
	computed:
	{
		darkTheme()
		{
			return BX.MessengerTheme.isDark();
		}
	},
	methods:
	{
		getClassWithTheme(baseClass: string)
		{
			const classWithTheme = {};

			classWithTheme[baseClass] = true;
			classWithTheme[baseClass + '-dark'] = this.darkTheme;

			return classWithTheme;
		}
	}
};