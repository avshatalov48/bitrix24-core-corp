/**
 * @module layout/ui/entity-editor/control/section/toggle-button
 */
jn.define('layout/ui/entity-editor/control/section/toggle-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	/**
	 * @class ToggleButton
	 */
	class ToggleButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state.isShown = true;
			this.init(props);
			this.onToggleButtonClick = this.onToggleClick.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.init(props)
		}

		init(props)
		{
			this.state.isShown = props.isShown;
			this.text = props.text;
		}

		render()
		{
			if (!this.state.isShown)
			{
				return View({});
			}

			return View(
				{
					style: styles.toggleModeButtonContainer,
					onClick: this.onToggleButtonClick,
				},
				View(
					{
						style: styles.toggleModeButtonWrapper,
					},
					Text({
						style: styles.toggleModeButtonText,
						text: this.text,
					}),
				),
			);
		}

		onToggleClick()
		{
			if (this.props.onToggleClick)
			{
				this.props.onToggleClick();
			}
		}

		hide()
		{
			this.setState({
				isShown: false,
			});
		}
	}

	const styles = {
		toggleModeButtonContainer: {
			paddingTop: 12,
			paddingBottom: 10,
			paddingLeft: 16,
		},
		toggleModeButtonWrapper: {
			borderBottomWidth: 1,
			borderBottomColor: AppTheme.colors.base6,
			borderStyle: 'dash',
			borderDashSegmentLength: 3,
			borderDashGapLength: 3,
		},
		toggleModeButtonText: {
			color: AppTheme.colors.base4,
			fontSize: 12,
		},
	};

	module.exports = { ToggleButton };
});
