/**
 * @module im/messenger/lib/ui/base/full-screen-shadow
 */
jn.define('im/messenger/lib/ui/base/full-screen-shadow', (require, exports, module) => {

	class FullScreenShadow extends LayoutComponent
	{
		/**
		 *
		 * @param {Object} props
		 * @param {boolean} props.isActive
		 */
		constructor(props)
		{
			super(props);
			this.state.isActive = props.isActive || false;

			if (props.ref)
			{
				props.ref(this);
			}
		}

		render()
		{
			return View(
					{
						clickable: this.state.isActive,
						style: {
							position: 'absolute',
							top: 0,
							left: 0,
							width: '100%',
							height: '100%',
							backgroundColor: '#000',
							opacity: this.state.isActive ? 0.5 : 0
						}
					},
			);
		}

		enable()
		{
			this.setState({ isActive: true });
		}

		disable()
		{
			this.setState({ isActive: false });
		}
	}

	module.exports = { FullScreenShadow };
});