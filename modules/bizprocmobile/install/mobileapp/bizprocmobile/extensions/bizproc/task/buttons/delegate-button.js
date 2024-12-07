/**
 * @module bizproc/task/buttons/delegate-button
 * */
jn.define('bizproc/task/buttons/delegate-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { merge } = require('utils/object');

	const { EntitySelectorFactory } = require('selector/widget/factory');

	const { Button } = require('bizproc/task/buttons/button');

	class DelegateButton extends Button
	{
		constructor(props)
		{
			super({
				text: Loc.getMessage('BPMOBILE_TASK_BUTTONS_DELEGATE_BUTTON'),
				icon: `
					<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path
							fill-rule="evenodd"
							clip-rule="evenodd"
							d="M10.4938 6.34343L15.0208 10.8705H5.34277V13.1299H15.0208L10.4938 17.6569L12.0912 19.2544L19.3454 12.0003L12.0912 4.74609L10.4938 6.34343Z"
							fill="${AppTheme.colors.base4}"
						/>
					</svg>
				`,
				testId: 'MBP_TASK_BUTTONS_DELEGATE_BUTTON',
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

			const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.USER, {
				provider: {},
				createOptions: {
					enableCreation: false,
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: (selectedUsers) => {
						const onCloseSelector = BX.prop.getFunction(this.props, 'onCloseSelector', () => {});

						if (!selectedUsers || selectedUsers.length === 0)
						{
							onCloseSelector(null);

							return;
						}

						onCloseSelector(selectedUsers.pop().id);
					},
				},
				widgetParams: {
					title: Loc.getMessage('BPMOBILE_TASK_BUTTONS_DELEGATE_BUTTON'),
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
			});

			selector.show({}, this.props.layout);
		}
	}

	const DEFAULT_STYLE = Object.freeze({
		borderColor: AppTheme.colors.base5,
		textColor: AppTheme.colors.base2,
	});

	module.exports = { DelegateButton };
});
