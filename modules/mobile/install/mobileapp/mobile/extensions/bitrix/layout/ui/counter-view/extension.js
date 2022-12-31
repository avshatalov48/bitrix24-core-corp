/**
 * @module layout/ui/counter-view
 */
jn.define('layout/ui/counter-view', (require, exports, module) => {

	const CounterView = value => {
		return View({
				style: {
					backgroundColor: '#FF5752',
					borderRadius: 10,
					paddingLeft: 7,
					paddingRight: 7,
					height: 20,
					justifyContent: 'center',
					alignItems: 'center',
				},
			},
			Text({
				style: {
					color: '#ffffff',
					fontSize: 12,
					textAlign: 'center',
				},
				text: String(value),
			}),
		);
	}

	module.exports = { CounterView };
});
