/**
 * @module layout/ui/progress-bar/caption
 */
jn.define('layout/ui/progress-bar/caption', (require, exports, module) => {
	/**
	 * @class ProgressBarCaption
	 */
	class ProgressBarCaption extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { value, maxValue } = props;
			this.state = {
				value: value || 0,
				maxValue: maxValue || 0,
			};
		}

		setValue(value)
		{
			this.setState({ value });
		}

		render()
		{
			const { style } = this.props;
			const { value, maxValue } = this.state;

			return Text({
				style: {
					fontSize: 12,
					...style,
				},
				text: `${value} / ${maxValue}`,
			});
		}
	}

	module.exports = { ProgressBarCaption };
});
