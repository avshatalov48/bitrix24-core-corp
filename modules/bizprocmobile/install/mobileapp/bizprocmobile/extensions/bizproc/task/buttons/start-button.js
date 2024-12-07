/**
 * @module bizproc/task/buttons/start-button
 * */

jn.define('bizproc/task/buttons/start-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Button } = require('bizproc/task/buttons/button');
	const { Loc } = require('loc');
	const { merge } = require('utils/object');

	class StartButton extends Button
	{
		constructor(props)
		{
			super({
				text: Loc.getMessage('BPMOBILE_TASK_BUTTONS_DETAILS'),
				...props,
			});
		}

		initStyle()
		{
			super.initStyle();

			merge(this.style, DEFAULT_STYLE);
		}
	}

	const DEFAULT_STYLE = Object.freeze({
		borderColor: AppTheme.colors.accentMainPrimary,
		textColor: AppTheme.colors.accentMainPrimary,
	});

	module.exports = {
		StartButton,
	};
});
