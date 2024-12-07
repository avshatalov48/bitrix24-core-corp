/**
 * @module ui-system/layout/dialog-footer
 */
jn.define('ui-system/layout/dialog-footer', (require, exports, module) => {
	const { isEmpty, isFunction, isObjectLike } = require('utils/object');
	const { Color, Component, Indent } = require('tokens');
	const { Button, ButtonSize } = require('ui-system/form/buttons/button');

	const IS_IOS = Application.getPlatform() === 'ios';
	const SAFE_AREA_HEIGHT = 34;

	/**
	 * @typedef {Object} DialogFooterProps
	 * @property {boolean} safeArea
	 * @property {Function} onLayoutFooterHeight
	 * @property {ButtonProps} keyboardButton
	 * @property {Color} backgroundColor
	 * @property {Object} children
	 * @property {boolean} [isShowKeyboard=false]
	 *
	 * @class DialogFooter
	 */
	class DialogFooter extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.buttonRef = null;

			this.#initialKeyboardHandlers();

			this.state = {
				footerHeight: 0,
				keyboardButtonHeight: 0,
				isShowKeyboard: props.isShowKeyboard ?? false,
			};
		}

		#initialKeyboardHandlers()
		{
			Keyboard.on(Keyboard.Event.WillHide, () => {
				this.updateFooter(false);
			});

			Keyboard.on(Keyboard.Event.WillShow, () => {
				this.updateFooter(true);
			});
		}

		render()
		{
			const { isShowKeyboard } = this.state;
			const { safeArea = true } = this.props;
			const footer = isShowKeyboard
				? this.renderKeyboardButton()
				: this.renderFooterButton();

			return View(
				{
					safeArea: {
						bottom: IS_IOS && safeArea,
					},
					style: {
						position: 'absolute',
						bottom: 0,
						paddingHorizontal: !isShowKeyboard && footer ? Component.paddingLrMore.toNumber() : 0,
						backgroundColor: this.#getBackgroundColor(),
						paddingBottom: this.#getPaddingBottom(),
					},
				},
				footer,
			);
		}

		renderKeyboardButton()
		{
			const { keyboardButton } = this.props;
			const keyboardButtonParams = this.getKeyboardButtonParams();

			if (isFunction(keyboardButton))
			{
				return keyboardButton(keyboardButtonParams);
			}

			return Button(keyboardButtonParams);
		}

		/**
		 * @returns {ButtonProps}
		 */
		getKeyboardButtonParams()
		{
			const { keyboardButton } = this.props;

			const baseParams = {
				forwardRef: (ref) => {
					this.buttonRef = ref;
				},
				testId: 'KEYBOARD_FOOTER_BUTTON',
				size: this.getKeyboardButtonSize(),
				stretched: true,
				borderRadius: 0,
			};

			if (isObjectLike(keyboardButton))
			{
				return { ...keyboardButton, ...baseParams };
			}

			return baseParams;
		}

		renderFooterButton()
		{
			const { children } = this.props;

			if (!Array.isArray(children) || isEmpty(children))
			{
				return null;
			}

			return View(
				{
					style: {
						marginVertical: this.#getPaddingVertical(),
					},
					onLayout: this.#handleOnLayoutFooter,
				},
				...children,
			);
		}

		updateFooter(isShowKeyboard)
		{
			if (isShowKeyboard)
			{
				this.#handleOnLayoutKeyboardButtonHeight({ isShowKeyboard });
			}
			else
			{
				this.#handleOnLayoutFooter({ isShowKeyboard });
			}
		}

		#handleOnLayoutKeyboardButtonHeight = ({ isShowKeyboard }) => {
			const keyboardButtonHeight = this.getKeyboardButtonSize().getHeight();

			this.setState({ keyboardButtonHeight, isShowKeyboard });
			this.onLayoutFooterHeight({ height: keyboardButtonHeight });
		};

		#handleOnLayoutFooter = ({ height, width, isShowKeyboard }) => {
			const { footerHeight: stateFooterHeight } = this.state;
			const footerHeight = height > 0 ? this.#getFooterHeight(height) : stateFooterHeight;

			this.setState({ footerHeight, isShowKeyboard });
			this.onLayoutFooterHeight({ height: footerHeight, width });
		};

		onLayoutFooterHeight(params)
		{
			const { onLayoutFooterHeight } = this.props;

			if (onLayoutFooterHeight)
			{
				onLayoutFooterHeight(params);
			}
		}

		#getFooterHeight(height)
		{
			return height + (this.#getPaddingVertical() * 2) + this.#getPaddingBottom();
		}

		#getPaddingVertical()
		{
			return Indent.XL.toNumber();
		}

		#getBackgroundColor()
		{
			const { backgroundColor } = this.props;

			return Color.resolve(backgroundColor, Color.bgPrimary).toHex();
		}

		/**
		 * @returns {number}
		 */
		#getPaddingBottom()
		{
			const { isShowKeyboard } = this.state;
			const { safeArea = true } = this.props;

			if (isShowKeyboard || IS_IOS || !safeArea || !device.isGestureNavigation)
			{
				return 0;
			}

			return SAFE_AREA_HEIGHT;
		}

		getKeyboardButtonSize()
		{
			return ButtonSize.XL;
		}
	}

	DialogFooter.defaultProps = {
		safeArea: true,
		isShowKeyboard: false,
	};

	DialogFooter.propTypes = {
		safeArea: PropTypes.bool,
		keyboardButton: PropTypes.oneOfType([PropTypes.func, PropTypes.object]),
		onLayoutFooterHeight: PropTypes.func,
		backgroundColor: PropTypes.instanceOf(Color),
		isShowKeyboard: PropTypes.bool,
		children: PropTypes.arrayOf(
			PropTypes.oneOfType([
				PropTypes.bool,
				PropTypes.object,
			]),
		),
	};

	module.exports = {
		/**
		 * @param {DialogFooterProps} props
		 * @param {View} children
		 * @returns {DialogFooter}
		 */
		DialogFooter: (props, ...children) => new DialogFooter({ ...props, children }),
		/**
		 * @param {DialogFooterProps} props
		 * @param {View} children
		 * @returns {function(*): DialogFooter}
		 */
		BoxFooter: (props, ...children) => (boxProps) => new DialogFooter({ ...props, ...boxProps, children }),
	};
});
