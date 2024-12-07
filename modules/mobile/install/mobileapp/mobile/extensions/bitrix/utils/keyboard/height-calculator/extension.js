/**
 * @module utils/keyboard/height-calculator
 */
jn.define('utils/keyboard/height-calculator', (require, exports, module) => {
	/**
	 * @class KeyboardHeightCalculator
	 * iOS Only
	 */
	class KeyboardHeightCalculator extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.hiddenBlockRef = null;
			this.keyboardHeight = 0;
		}

		/**
		 * @param {boolean} safeArea
		 * @param {boolean} bottomMenu
		 * @returns {number}
		 */
		getHeight({ safeArea = false, bottomMenu = false } = {})
		{
			let keyboardHeight = this.keyboardHeight;

			if (safeArea)
			{
				keyboardHeight -= device.screen.safeArea.bottom;
			}

			if (bottomMenu)
			{
				keyboardHeight -= 60;
			}

			return keyboardHeight;
		}

		getAbsolutePosition()
		{
			const position = this.hiddenBlockRef?.getAbsolutePosition();

			return position || { y: 0 };
		}

		calculateKeyboardHeight = ({ height }) => {
			const { y } = this.getAbsolutePosition();
			this.keyboardHeight = device.screen.height - y;
		};

		render()
		{
			return View({
				ref: (ref) => {
					this.hiddenBlockRef = ref;
				},
				onLayout: this.calculateKeyboardHeight,
				style: {
					position: 'absolute',
					bottom: 0,
					backgroundColor: '#fff333',
					height: 0,
					width: '100%',
				},
			});
		}
	}

	module.exports = {
		KeyboardHeightCalculator: (props) => new KeyboardHeightCalculator(props),
	};
});
