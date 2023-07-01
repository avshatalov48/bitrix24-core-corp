/**
 * @module imconnector/lib/ui/button-switcher
 */
jn.define('imconnector/lib/ui/button-switcher', (require, exports, module) => {
	/**
	 * @class ButtonSwitcher
	 */
	class ButtonSwitcher extends LayoutComponent
	{
		/**
		 * @param {ButtonSwitcherProps} props
		 */
		constructor(props)
		{
			super(props);
			this.buttonStates = new Map(Object.entries(props.states));
			this.state.buttonState = props.startingState;
		}

		render()
		{
			return View(
				{},
				this.buttonStates.has(this.state.buttonState)
					? this.buttonStates.get(this.state.buttonState)
					: null
				,
			);
		}

		switchTo(state)
		{
			if (this.buttonStates.has(state))
			{
				this.setState({ buttonState: state });
			}
		}
	}

	module.exports = { ButtonSwitcher };
});
