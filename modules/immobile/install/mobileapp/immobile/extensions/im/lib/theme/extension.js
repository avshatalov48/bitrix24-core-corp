/**
 * @module im/lib/theme
 */
jn.define('im/lib/theme', (require, exports, module) => {
	const appTheme = require('native/apptheme')?.AppTheme;

	class Theme
	{
		/**
		 * @return {Theme}
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		constructor()
		{
			this.appTheme = appTheme;
		}

		get isSupported()
		{
			return !!this.appTheme;
		}

		getId()
		{
			if (!this.isSupported)
			{
				return 'light';
			}

			return appTheme.getId();
		}

		getColors(id)
		{
			if (!this.isSupported)
			{
				return [];
			}

			return appTheme.getColors(id);
		}

		setId(id)
		{
			if (!this.isSupported)
			{
				return false;
			}

			return appTheme.setId(id);
		}
	}

	module.exports = {
		Theme,
	};
});
