/**
 * @module apptheme
 * @return {Object}
 */
jn.define('apptheme', (require, exports, module) => {
	const LIGHT = 'light';
	const DARK = 'dark';

	const { themes } = require('apptheme/list');
	const nativeAppTheme = (Application.getApiVersion() >= 52) ? require('native/apptheme')?.AppTheme : undefined;

	const componentTokens = {
		[DARK]: {},
		[LIGHT]: {},
	};

	class AppTheme
	{
		static get colors()
		{
			let systemColors = themes.light;
			const customTokens = componentTokens[AppTheme.id];
			if (nativeAppTheme)
			{
				systemColors = nativeAppTheme.getColors();
			}

			return { ...customTokens, ...systemColors };
		}

		static get id()
		{
			if (nativeAppTheme)
			{
				return nativeAppTheme.getId();
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

		static toggle(ontoggle = null)
		{
			const currentId = AppTheme.id;
			const newId = currentId === DARK ? LIGHT : DARK;
			AppTheme.setId(newId);

			if (ontoggle !== null && typeof ontoggle === 'function')
			{
				ontoggle.apply(null, [newId]);
			}
			else
			{
				reload();
			}
		}
	}

	module.exports = AppTheme;
});
