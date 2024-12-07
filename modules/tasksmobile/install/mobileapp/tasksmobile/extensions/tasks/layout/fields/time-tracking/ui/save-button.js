/**
 * @module tasks/layout/fields/time-tracking/ui/save-button
 */
jn.define('tasks/layout/fields/time-tracking/ui/save-button', (require, exports, module) => {
	const { Button, ButtonSize } = require('ui-system/form/buttons');
	const { Component, Indent, Color } = require('tokens');

	class TimeTrackingSettingsWidgetSaveButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				disabled: props.disabled,
			};
		}

		/**
		 * @public
		 */
		enable()
		{
			this.setState({ disabled: false });
		}

		/**
		 * @public
		 */
		disable()
		{
			this.setState({ disabled: true });
		}

		componentWillReceiveProps(props)
		{
			this.state.disabled = props.disabled;
		}

		render()
		{
			return View(
				{
					style: {
						paddingHorizontal: Component.areaPaddingLr.getValue(),
						paddingVertical: Indent.XL.getValue(),
						position: 'absolute',
						bottom: 30,
						left: 0,
						right: 0,
					},
				},
				Button({
					testId: this.props.testId,
					size: ButtonSize.L,
					disabled: this.state.disabled,
					text: this.props.text,
					stretched: true,
					backgroundColor: Color.accentMainPrimary,
					onClick: () => this.props.onClick?.(),
				}),
			);
		}
	}

	module.exports = { TimeTrackingSettingsWidgetSaveButton };
});
