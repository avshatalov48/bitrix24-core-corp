jn.define('call:followup/efficiency-score', (require, exports, module) => {

	const { getScoreColor } = require('call:followup/util');
	const { Color } = require('tokens');

	class EfficiencyScore extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);

			this.state = {
				score: props.score,
			};
		}

		render()
		{
			const progress = ProgressView(
				{
					style: {
						width: 72,
						height: 72,
						backgroundColor: '#00000000',
						justifyContent: 'center',
						alignItems: 'center',
					},
					params: {
						type: 'circle',
						currentPercent: this.state.score,
						color: Color.accentMainSuccess.toHex(),
					},
					ref: (ref) => {
						this.testRef = ref
					}
				},
				View(
					{
						style: {
							position: 'absolute',
							width: 60,
							justifyContent: 'center',
							alignItems: 'center',
							borderRadius: 90,
							height: 60,
							opacity: 1,
							backgroundColor: Color.bgContentPrimary.toHex(),
						}
					},
					Text({
						style: {
							fontSize: 17,
							textAlign: 'center',
						},
						ref: (ref) => {
							this.textRef = ref;
						},
						text: `${this.state.score}%`,
					})
				)
			);
			return View(
				{
					style: {
						width: 72,
						height: 72,
						borderRadius: 100,
						backgroundColor: Color.base7.toHex(),
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				progress,
			);
		}
	}

	module.exports = {
		EfficiencyScore,
	};
});
