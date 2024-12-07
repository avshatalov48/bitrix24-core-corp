/**
 * @module call/calls-card/card-content/elements/status
 */
jn.define('call/calls-card/card-content/elements/status', (require, exports, module) => {
	const Status = ({ statusText = null, statusColor = '#FFFFFF', showBalloon = true}) => {
		return View(
			{
				style: {
					flexDirection: 'column',
					marginHorizontal: 5,
					height: 27,
				},
			},
			statusText && View(
				{
					style: {
						height: 27,
						paddingHorizontal: 12,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				Text({
					style: {
						color: statusColor,
						fontSize: 15,
					},
					text: statusText,
				}),
			),
			showBalloon && statusText && View(
				{
					style: {
						height: 27,
						marginTop: -27,
						borderRadius: 13.5,
						opacity: 0.2,
						backgroundColor: statusColor,
					},
				},
			),
		);
	};

	module.exports = { Status };
});