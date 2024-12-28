/**
 * @module apptheme
 * @return {Object}
 */
jn.define('apptheme', (require, exports, module) => {
	const LIGHT = 'light';
	const DARK = 'dark';

	const CompatibleThemeMap = {
		newdark: 'dark',
		newlight: 'light',
	};

	const { colors: localColors, styles, typography } = require('apptheme/list');
	const nativeAppTheme = (Application.getApiVersion() >= 52) ? require('native/apptheme')?.AppTheme : undefined;

	const componentTokens = {
		[DARK]: {}, [LIGHT]: {},
	};

	class AppTheme
	{
		static cachedColors = {};
		static cachedFonts = null;
		static cachedStyles = null;

		static get colors()
		{
			let colors = {};
			if (AppTheme.cachedColors[AppTheme.id])
			{
				colors = AppTheme.cachedColors[AppTheme.id];
			}
			else
			{
				let systemColors = colors.light;
				const customTokens = componentTokens[AppTheme.id];
				if (nativeAppTheme)
				{
					systemColors = nativeAppTheme.getColors(AppTheme.id);
				}

				colors = { ...customTokens, ...systemColors };
				AppTheme.cachedColors[AppTheme.id] = colors;
			}

			return colors;
		}

		static get realColors()
		{
			if (!AppTheme.cachedColors.currentRealColors)
			{
				const colors = nativeAppTheme.getColors();
				const localThemeColors = localColors[nativeAppTheme.getId()] ?? {};
				AppTheme.cachedColors.currentRealColors = { ...localThemeColors, ...colors };
			}

			return AppTheme.cachedColors.currentRealColors;
		}

		static get typography()
		{
			if (AppTheme.cachedFonts === null)
			{
				AppTheme.cachedFonts = nativeAppTheme?.typography ?? typography;
			}

			return AppTheme.cachedFonts;
		}

		static get styles()
		{
			if (AppTheme.cachedStyles === null)
			{
				AppTheme.cachedStyles = nativeAppTheme?.styles ?? styles;
			}

			return AppTheme.cachedStyles;
		}

		static get id()
		{
			if (nativeAppTheme)
			{
				const realThemeId = nativeAppTheme.getId();

				return CompatibleThemeMap[realThemeId] ?? realThemeId;
			}

			return LIGHT;
		}

		static extend(namespace, values = {})
		{
			Object.keys(values).forEach((token) => {
				const colors = values[token];
				if (colors.length >= 2)
				{
					componentTokens[LIGHT][`${namespace}${token}`] = colors[0];
					componentTokens[DARK][`${namespace}${token}`] = colors[1];
				}
			});
		}

		static setId(id = null)
		{
			if (!nativeAppTheme)
			{
				return null;
			}

			return nativeAppTheme.setId(id);
		}

		/**
		 * @param {Function} onToggle
		 */
		static toggle(onToggle = null)
		{
			const currentId = AppTheme.id;
			const newId = currentId === DARK ? LIGHT : DARK;
			AppTheme.setId(newId);

			if (onToggle !== null && typeof onToggle === 'function')
			{
				onToggle(newId);
			}
			else
			{
				// eslint-disable-next-line no-undef
				reload();
			}
		}
	}

	module.exports = AppTheme;
});
