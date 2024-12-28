/**
 * @module ui-system/form/buttons/floating-action-button
 */
jn.define('ui-system/form/buttons/floating-action-button', (require, exports, module) => {
	const { Component } = require('tokens');
	const { Feature } = require('feature');
	const { PropTypes } = require('utils/validation');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { FloatingActionButtonMode } = require('ui-system/form/buttons/floating-action-button/src/mode-enum');
	const { FloatingActionButtonType } = require('ui-system/form/buttons/floating-action-button/src/type-enum');
	const { BaseEnum } = require('utils/enums/base');
	const { BaseIcon } = require('assets/icons/src/base');

	const BUTTON_SIZE = 58;

	/**
	 * @class FloatingActionButton
	 * @param {string} testId
	 * @param {boolean} accent
	 * @param {boolean} showLoader
	 * @param {boolean} safeArea
	 * @param {string} icon
	 * @param {string} type
	 * @param {object} parentLayout
	 * @param {Function} onClick
	 * @param {Function} onLongClick
	 */
	class FloatingActionButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.#initNativeButton();
		}

		static supportNative(layout)
		{
			return typeof layout?.setFloatingButton === 'function' && Feature.isAirStyleSupported();
		}

		/**
		 * @returns {FloatingActionButtonMode}
		 */
		getMode()
		{
			return this.isAccentButton()
				? FloatingActionButtonMode.ACCENT
				: FloatingActionButtonMode.BASE;
		}

		/**
		 * @returns {Object}
		 */
		getButtonColor()
		{
			return this.getMode().getButtonColor();
		}

		getIconColor()
		{
			return this.getMode().getIconColor();
		}

		getLayout()
		{
			const { parentLayout } = this.props;

			return parentLayout;
		}

		isAccentButton()
		{
			const { accentByDefault, accent } = this.props;

			if (typeof accent !== 'undefined')
			{
				if (Application.isBeta())
				{
					console.warn('The "accent" parameter is deprecated. Please use "accentByDefault" instead.');
				}

				return Boolean(accent);
			}

			return Boolean(accentByDefault);
		}

		shouldSafeArea()
		{
			const { safeArea } = this.props;

			return Boolean(safeArea);
		}

		#getIcon()
		{
			const { icon } = this.props;

			return Icon.resolve(icon, Icon.PLUS);
		}

		render()
		{
			if (FloatingActionButton.supportNative(this.getLayout()))
			{
				return null;
			}

			const { testId } = this.props;

			return View(
				{
					ref: (ref) => {
						this.ref = ref;
					},
					testId,
					style: {
						width: BUTTON_SIZE,
						height: BUTTON_SIZE,
						alignItems: 'center',
						justifyContent: 'center',
						backgroundColor: this.getButtonColor(),
						borderRadius: Component.popupCorner.toNumber(),
					},
					onClick: this.#handleOnClick,
					onLongClick: this.#handleOnLongClick,
				},
				IconView({
					size: 32,
					icon: this.#getIcon(),
					color: this.getIconColor(),
				}),
			);
		}

		#handleOnClick = () => {
			const { onClick } = this.props;

			if (onClick)
			{
				onClick();
			}
		};

		#handleOnLongClick = () => {
			const { onLongClick } = this.props;

			if (onLongClick)
			{
				onLongClick();
			}
		};

		#initNativeButton()
		{
			if (!FloatingActionButton.supportNative(this.getLayout()))
			{
				return;
			}

			const transformedProps = this.transformProps(this.props);

			this.setFloatingButton(transformedProps);

			this.#initListeners();
		}

		transformProps(props)
		{
			return Object.keys(props).reduce((accumulator, key) => {
				const value = props[key];

				if (value instanceof BaseIcon)
				{
					return { ...accumulator, [key]: value.getIconName() };
				}

				if (value instanceof BaseEnum)
				{
					return { ...accumulator, [key]: value.getValue() };
				}

				return { ...accumulator, [key]: value };
			}, {});
		}

		#initListeners()
		{
			this.getLayout().removeAllListeners('floatingButtonTap');
			this.getLayout().removeAllListeners('floatingButtonLongTap');

			this.getLayout().on('floatingButtonTap', this.#handleOnClick);
			this.getLayout().on('floatingButtonLongTap', this.#handleOnLongClick);
		}

		/**
		 * @param {Object} params
		 * @param {boolean} [params.accentByDefault]
		 * @param {boolean} [params.hide]
		 * @param {boolean}  [params.showLoader]
		 * @param {string} [params.type]
		 * @param {Object} [params.icon]
		 */
		setFloatingButton(params = {})
		{
			const transformedProps = this.transformProps(this.props);
			const {
				hide = false,
				safeArea = this.shouldSafeArea(),
				accentByDefault = this.isAccentButton(),
			} = params;
			const floatingActionButtonParams = hide
				? {}
				: {
					...transformedProps,
					accentByDefault,
					safeArea,
					callback: () => {},
				};

			this.getLayout().setFloatingButton(floatingActionButtonParams);
		}

		hide()
		{
			this.setFloatingButton({ hide: true });
		}

		show()
		{
			this.setFloatingButton({ hide: false });
		}
	}

	FloatingActionButton.defaultProps = {
		accentByDefault: false,
		showLoader: false,
		safeArea: false,
	};

	FloatingActionButton.propTypes = {
		safeArea: PropTypes.bool,
		testId: PropTypes.string.isRequired,
		accentByDefault: PropTypes.bool,
		onLongClick: PropTypes.func,
		onClick: PropTypes.func,
		parentLayout: PropTypes.object,
		showLoader: PropTypes.bool,
		type: PropTypes.instanceOf(FloatingActionButtonType),
		icon: PropTypes.instanceOf(Icon),
	};

	module.exports = {
		FloatingActionButton: (props) => new FloatingActionButton(props),
		FloatingActionButtonSupportNative: FloatingActionButton.supportNative,
		FloatingActionButtonType,
	};
});
