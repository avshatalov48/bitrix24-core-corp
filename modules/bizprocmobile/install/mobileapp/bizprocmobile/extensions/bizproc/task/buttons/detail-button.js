/**
 * @module bizproc/task/buttons/detail-button
 * */

jn.define('bizproc/task/buttons/detail-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Button } = require('bizproc/task/buttons/button');
	const { Loc } = require('loc');
	const { merge } = require('utils/object');

	class DetailButton extends Button
	{
		constructor(props)
		{
			super({
				text: Loc.getMessage('BPMOBILE_TASK_BUTTONS_DETAILS_LINK'),
				style: {
					fontSize: 14,
					textColor: AppTheme.colors.base3,
				},
				...props,
			});
		}

		initStyle()
		{
			super.initStyle();

			merge(this.style, DEFAULT_STYLE);
		}

		onClick()
		{
			if (this.props.onClick)
			{
				super.onClick();

				return;
			}

			void requireLazy('bizproc:task/details').then(({ TaskDetails }) => {
				void TaskDetails.open(
					this.props.layout,
					{
						taskId: BX.prop.getNumber(this.props, 'taskId', 0),
						title: BX.prop.getString(this.props, 'title', ''),
					},
				);
			});
		}
	}

	const DEFAULT_STYLE = Object.freeze({
		flexShrink: null,
		borderRadius: null,
		borderColor: null,
		borderWidth: null,
	});

	module.exports = {
		DetailButton,
	};
});
