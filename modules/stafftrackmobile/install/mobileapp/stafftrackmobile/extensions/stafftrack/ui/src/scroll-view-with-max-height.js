/**
 * @module stafftrack/ui/scroll-view-with-max-height
 */
jn.define('stafftrack/ui/scroll-view-with-max-height', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @class ScrollViewWithMaxHeight
	 */
	class ScrollViewWithMaxHeight extends PureComponent
	{
		render()
		{
			this.calcSize ??= Animated.newCalculatedValue2D(0, 0);

			return ScrollView(
				{
					style: {
						...this.props.style,
						height: this.calcSize.getValue2(),
					},
					testId: this.props.testId,
				},
				View(
					{
						onTouchesBegan: ({ x, y }) => {
							this.startPosition = { x, y };
						},
						onTouchesEnded: ({ x, y }) => {
							if (this.startPosition?.x === x && this.startPosition?.y === y && this.props.onClick)
							{
								this.props.onClick();
							}
						},
						onLayoutCalculated: {
							contentSize: this.calcSize,
						},
					},
					this.props.renderContent(),
				),
			);
		}
	}

	module.exports = { ScrollViewWithMaxHeight };
});
