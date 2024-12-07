/**
 * @module stafftrack/department-statistics/segment-button
 */
jn.define('stafftrack/department-statistics/segment-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Color, Corner, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');

	const isDarkTheme = AppTheme.id === 'dark';

	class SegmentButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				segments: props.segments,
			};
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						backgroundColor: this.getBackgroundColor(),
						borderRadius: Corner.S.toNumber(),
					},
				},
				...this.state.segments.map((segment) => View(
					{
						style: {
							flex: 1,
						},
						onClick: () => this.select(segment.id),
					},
					Text4({
						color: segment.selected
							? Color.base0
							: Color.base2,
						testId: segment.id,
						text: segment.title,
						accent: segment.selected,
						style: {
							textAlign: 'center',
							paddingVertical: Indent.XS2.toNumber(),
							borderRadius: Corner.XS.toNumber(),
							margin: Indent.XS.toNumber(),
							backgroundColor: segment.selected
								? this.getActiveColor()
								: this.getBackgroundColor()
							,
						},
					}),
				)),
			);
		}

		getBackgroundColor()
		{
			return isDarkTheme ? Color.base8.toHex() : Color.base7.toHex();
		}

		getActiveColor()
		{
			return isDarkTheme ? Color.base7.toHex() : Color.base8.toHex();
		}

		select(segmentId)
		{
			const { segments } = this.state;

			segments.forEach((segment) => {
				// eslint-disable-next-line no-param-reassign
				segment.selected = segment.id === segmentId;
			});

			this.setState({ segments });

			this.props.onSegmentSelected(segmentId);
		}
	}

	module.exports = { SegmentButton };
});
