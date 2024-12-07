/**
 * @module bizproc/task/buttons/decline-button
 * */

jn.define('bizproc/task/buttons/decline-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Button } = require('bizproc/task/buttons/button');
	const { merge } = require('utils/object');

	class DeclineButton extends Button
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
		const fill = AppTheme.colors.base2;

		return `
			<svg xmlns="http://www.w3.org/2000/svg" width="23" height="22" viewBox="0 0 23 22" fill="none">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M15.6939 17.1677L17.6673 15.1943L13.7205 11.2475L17.6673 7.30077L15.6939 5.32739L11.7472 9.27415L7.8004 5.32739L5.82703 7.30077L9.77378 11.2475L5.82703 15.1943L7.8004 17.1677L11.7472 13.2209L15.6939 17.1677Z" fill="${fill}"/>
			</svg>
		`;
	})();

	const DEFAULT_STYLE = Object.freeze({
		borderColor: AppTheme.colors.base5,
		textColor: AppTheme.colors.base2,
	});

	module.exports = {
		DeclineButton,
	};
});
