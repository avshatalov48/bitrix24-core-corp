/**
 * @module ui-system/layout/dialog-footer
 */
jn.define('ui-system/layout/dialog-footer', (require, exports, module) => {
	const { Feature } = require('feature');
	const { PropTypes } = require('utils/validation');
	const { Color, Component, Indent } = require('tokens');
	const { isEmpty, isFunction, isObjectLike, merge } = require('utils/object');
	const { Button, ButtonSize } = require('ui-system/form/buttons/button');

	const IS_IOS = Application.getPlatform() === 'ios';
	const SAFE_AREA_HEIGHT = 18;

	/**
	 * @typedef {Object} DialogFooterProps
	 * @property {boolean} [safeArea]
	 * @property {Function} onLayoutFooterHeight
	 * @property {ButtonProps | Function} [keyboardButton]
	 * @property {Color} [backgroundColor=Color.bgPrimary]
	 * @property {Object} children
	 * @property {boolean} [isKeyboardShown=false]
	 * @property {Object} [style]
	 *
	 * @class DialogFooter
	 */
	class DialogFooter extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.#initialKeyboardHandlers();

			this.state = {
				footerHeight: 0,
				isKeyboardShown: props.isKeyboardShown ?? props.isShowKeyboard ?? false,
			};
		}

		#initialKeyboardHandlers()
		{
			Keyboard.on(Keyboard.Event.WillHide, () => {
				this.updateKeyboardState(false);
			});

			Keyboard.on(Keyboard.Event.WillShow, () => {
				this.updateKeyboardState(true);
			});
		}

		updateKeyboardState(isKeyboardShown)
		{
			const { isKeyboardShownCurrent } = this.state;

			if (isKeyboardShownCurrent !== isKeyboardShown)
			{
				this.setState({ isKeyboardShown });
			}
		}

		render()
		{
			const footerContent = this.renderFooterContent();

			if (!footerContent)
			{
				return null;
			}

			return View(
				{
					safeArea: {
						bottom: this.isSafeArea(),
					},
					style: {
						position: 'absolute',
						bottom: 0,
						backgroundColor: this.#getBackgroundColor(),
						paddingBottom: this.isSafeArea() ? 0 : this.#getPaddingBottom(),
						...this.getStyle(),
					},
				},
				footerContent,
			);
		}

		getStyle()
		{
			const { style = {} } = this.props;

			return style || {};
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
				testId: 'KEYBOARD_FOOTER_BUTTON',
				size: ButtonSize.XL,
				stretched: true,
				borderRadius: 0,
				onLayout: this.#handleOnLayoutFooter,
			};

			if (isObjectLike(keyboardButton))
			{
				return { ...keyboardButton, ...baseParams };
			}

			return baseParams;
		}

		renderFooterContent()
		{
			if (this.#isKeyboardShown())
			{
				return View(
					{
						onLayout: this.#handleOnLayoutFooter,
					},
					// workaround extra view is needed to change the layout structure
					View(
						{},
						this.renderKeyboardButton(),
					),
				);
			}

			const footerContent = this.#getChildren();

			if (!footerContent)
			{
				return null;
			}

			return View(
				{
					style: {
						paddingVertical: Indent.XL.toNumber(),
						paddingHorizontal: Component.paddingLrMore.toNumber(),
					},
					onLayout: this.#handleOnLayoutFooter,
				},
				...footerContent,
			);
		}

		#getChildren()
		{
			const { children } = this.props;

			if (!Array.isArray(children) || isEmpty(children))
			{
				return null;
			}

			const childrenValue = children
				.flatMap((child) => (isFunction(child) ? child() : child))
				.filter(Boolean);

			if (isEmpty(childrenValue))
			{
				return null;
			}

			return childrenValue;
		}

		#handleOnLayoutFooter = ({ height, width }) => {
			const { footerHeight: stateFooterHeight } = this.state;
			if (stateFooterHeight === height)
			{
				return;
			}

			this.setState({ footerHeight: height });
			this.onLayoutFooterHeight({ height, width });
		};

		onLayoutFooterHeight(params)
		{
			const { onLayoutFooterHeight } = this.props;

			onLayoutFooterHeight?.(params);
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
			const { safeArea = true } = this.props;
			const { isKeyboardShown } = this.state;
			const isButtonNavigation = !device.isGestureNavigation;

			if (isButtonNavigation || isKeyboardShown || IS_IOS || !safeArea)
			{
				return 0;
			}

			return SAFE_AREA_HEIGHT;
		}

		isSafeArea()
		{
			const { safeArea = true } = this.props;

			return IS_IOS ? Boolean(safeArea) : (Boolean(safeArea) && Feature.isSafeAreaSupportedOnAndroid());
		}

		#isKeyboardShown()
		{
			const { isKeyboardShown } = this.state;

			return Boolean(isKeyboardShown);
		}
	}

	DialogFooter.defaultProps = {
		safeArea: true,
		isKeyboardShown: false,
	};

	DialogFooter.propTypes = {
		safeArea: PropTypes.bool,
		keyboardButton: PropTypes.oneOfType([PropTypes.func, PropTypes.object]),
		backgroundColor: PropTypes.instanceOf(Color),
		onLayoutFooterHeight: PropTypes.func,
		isKeyboardShown: PropTypes.bool,
		children: PropTypes.arrayOf(
			PropTypes.oneOfType([
				PropTypes.bool,
				PropTypes.object,
				PropTypes.func,
			]),
		),
		style: PropTypes.object,
	};

	module.exports = {
		/**
		 * @param {DialogFooterProps} props
		 * @param {...*} [children]
		 * @returns {DialogFooter}
		 */
		DialogFooter: (props, ...children) => new DialogFooter({ ...props, children }),
		/**
		 * @param {DialogFooterProps} props
		 * @param {...*} [children]
		 * @returns {function(*): DialogFooter}
		 */
		BoxFooter: (props, ...children) => (boxProps) => new DialogFooter({ ...merge(props, boxProps), children }),
	};
});
