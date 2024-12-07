/**
 * @module stafftrack/department-statistics/progress-bar
 */
jn.define('stafftrack/department-statistics/progress-bar', (require, exports, module) => {
	const { Color } = require('tokens');

	const { PureComponent } = require('layout/pure-component');

	class ProgressBar extends PureComponent
	{
		render()
		{
			return View(
				{
					testId: `stafftrack-progress-bar`,
					style: {
						backgroundColor: Color.base6.toHex(),
						borderRadius: 6,
						height: 6,
					},
				},
				View(
					{
						testId: `stafftrack-progress-bar-percent-${this.props.percent}`,
						style: {
							backgroundColor: Color.accentMainSuccess.toHex(),
							borderRadius: 6,
							height: 6,
							width: `${this.props.percent}%`,
						},
					},
				),
			);
		}
	}

	module.exports = { ProgressBar };
});