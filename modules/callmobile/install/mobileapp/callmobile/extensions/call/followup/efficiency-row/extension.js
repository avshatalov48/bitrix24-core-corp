jn.define('call:followup/efficiency-row', (require, exports, module) => {

	const { getScoreColor } = require('call:followup/util');

	class EfficiencyRow extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);

			this.state = {
				avatarPath: props.avatarPath,
				name: props.name,
				score: props.score,
				isWide: props.isWide,
			};
		}

		render()
		{
			return View(
				{
					style: {
						display: 'flex',
						flexDirection: 'row',
						justifyContent: this.state.isWide && 'space-between',
						width: '100%',
						marginTop: 10,
					},
				},
				View(
					{
						style: { display: 'flex', flexDirection: 'row' },
					},
					Avatar({
						uri: this.state.avatarPath,
						name: this.state.name,
						size: 24,
						backgroundColor: '#000',
					}),
					Text({
						style: { fontSize: 15, color: '#555', fontWeight: '400', marginLeft: 10, marginRight: 15 },
						text: this.state.name,
					}),
				),
				View(
					{
						style: { display: 'flex', flexDirection: 'row', alignItems: 'center' },
					},
					View(
						{
							style: {
								width: 10,
								height: 10,
								borderRadius: 100,
								backgroundColor: '#EDEEF0',
								alignItems: 'center',
								justifyContent: 'center',
								marginRight: 5,
							},
						},
						ProgressView(
							{
								style: {
									width: 10,
									height: 10,
									backgroundColor: '#00000000',
									justifyContent: 'center',
									alignItems: 'center',

								},
								params: {
									type: 'circle',
									currentPercent: this.state.score,
									color: getScoreColor(this.state.score),
								},
								ref: (ref) => {
									this.testRef = ref
								}
							},
							View(
								{
									style: {
										position: 'absolute',
										width: 7,
										justifyContent: 'center',
										alignItems: 'center',
										borderRadius: 90,
										height: 7,
										opacity: 1,
										backgroundColor: '#fff',
									}
								},
							)
						),
					),
					Text({
						style: {
							fontSize: 17,
						},
						ref: (ref) => {
							// this.textRef = ref
						},
						text: String(this.state.score),
					})
				)
			)
		}
	}

	module.exports = {
		EfficiencyRow,
	};
});
