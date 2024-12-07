/**
 * @module bizproc/task/buttons/accept-button
 * */

jn.define('bizproc/task/buttons/accept-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Button } = require('bizproc/task/buttons/button');
	const { merge } = require('utils/object');

	class AcceptButton extends Button
	{
		constructor(props)
		{
			super({
				icon,
				...props,
			});
		}

		initStyle()
		{
			super.initStyle();

			merge(this.style, DEFAULT_STYLE);
		}
	}

	const icon = (() => {
		const fill = AppTheme.colors.accentMainSuccess;

		return `
			<svg xmlns="http://www.w3.org/2000/svg" width="23" height="22" viewBox="0 0 23 22" fill="none">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M5.33044 12.102L9.88012 16.7472L18.1638 8.28963L16.5714 6.66382L9.88012 13.4955L6.92283 10.4762L5.33044 12.102Z" fill="${fill}"/>
			</svg>
		`;
	})();

	const DEFAULT_STYLE = Object.freeze({
		borderColor: AppTheme.colors.accentMainSuccess,
		textColor: AppTheme.colors.accentMainSuccess,
	});

	module.exports = {
		AcceptButton,
	};
});
