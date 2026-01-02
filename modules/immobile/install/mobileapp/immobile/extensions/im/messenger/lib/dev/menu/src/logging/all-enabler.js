/**
 * @module im/messenger/lib/dev/menu/logging/all-enabler
 */
jn.define('im/messenger/lib/dev/menu/logging/all-enabler', (require, exports, module) => {
	const { Enabler } = require('im/messenger/lib/dev/menu/logging/enabler');

	class AllLoggerEnabler extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = { value: props.value ?? false };
		}

		render()
		{
			const enablerBoxView = new Enabler({
				checked: this.state.value,
				onClick: () => {
					this.setState({ value: !this.state.value });
					this.props.onClick(this.state.value);
				},
			});

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-end',
					},
				},
				Button({
					style: {
						flex: 1,
						alignItems: 'center',
						alignSelf: 'center',
						color: '#f3f8f3',
						fontSize: 16,
						height: 40,
						maxWidth: 140,
						backgroundColor: {
							default: '#4d935d',
							pressed: '#325b32',
						},
						borderWidth: 1,
						borderColor: '#282f28',
						borderRadius: 4,
					},
					text: 'Enable all',
					onClick: () => {
						this.setState({ value: !this.state.value });
						this.props.onClick(this.state.value);
					},
				}),
				enablerBoxView,
			);
		}
	}

	module.exports = {
		AllLoggerEnabler,
	};
});
