/**
 * @module disk/dialogs/base
 */
jn.define('disk/dialogs/base', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Component, Indent, Color } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');

	/**
	 * @abstract
	 */
	class BaseDialog extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {Object} [props.parentWidget]
		 * @param {string} [props.title]
		 * @param {number} [props.startingLayoutHeight]
		 */
		constructor(props)
		{
			super(props);

			this.layoutWidget = null;
		}

		get isLoading()
		{
			return false;
		}

		setLayoutWidget(layoutWidget)
		{
			this.layoutWidget = layoutWidget;
		}

		setStartingLayoutHeight(startingLayoutHeight)
		{
			this.startingLayoutHeight = startingLayoutHeight;
		}

		/**
		 * @abstract
		 * @param {string} suffix
		 * @returns {string}
		 */
		getTestId(suffix) {}

		/**
		 * @abstract
		 * @returns {boolean}
		 */
		isButtonDisabled() {}

		/**
		 * @returns {string}
		 */
		getButtonText()
		{
			return Loc.getMessage('M_DISK_BASE_DIALOG_BUTTON_TEXT');
		}

		componentDidMount() {
			super.componentDidMount();

			Keyboard.on(Keyboard.Event.WillHide, this.#onKeyboardWillHide);
		}

		componentWillUnmount() {
			super.componentWillUnmount();
			Keyboard.off(Keyboard.Event.WillHide, this.#onKeyboardWillHide);
		}

		#onKeyboardWillHide = () => {
			this.layoutWidget.setBottomSheetHeight(this.startingLayoutHeight);
		};

		close = () => {
			this.layoutWidget.close();
		};

		/**
		 * @abstract
		 */
		save = () => {};

		static getStartingLayoutHeight()
		{
			const AREA_PADDING = Component.areaPaddingTFirst.toNumber();
			const INPUT_HEIGHT = 42;
			const BUTTON_HEIGHT = 42 + Indent.XL2.toNumber() * 2;

			return AREA_PADDING
				+ INPUT_HEIGHT
				+ BUTTON_HEIGHT;
		}

		/**
		 * @public
		 * @param {Object} data
		 * @param {Object} parentWidget
		 */
		static async open(data, parentWidget = PageManager)
		{
			const component = new this(data);
			const startingLayoutHeight = BaseDialog.getStartingLayoutHeight();
			component.setStartingLayoutHeight(startingLayoutHeight);

			const bottomSheet = new BottomSheet({ component });
			bottomSheet
				.setParentWidget(parentWidget)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.hideNavigationBar()
				.disableShowOnTop()
				.disableOnlyMediumPosition()
				.setMediumPositionHeight(BaseDialog.getStartingLayoutHeight())
				.enableBounce()
				.enableSwipe()
				.disableHorizontalSwipe()
				.enableResizeContent()
				.enableAdoptHeightByKeyboard()
				.open()
				.then((layoutWidget) => {
					component.setLayoutWidget(layoutWidget);
				})
				.catch((error) => {
					console.error('Failed to open widget:', error);
				});
		}

		render()
		{
			return Box(
				{
					resizableByKeyboard: true,
					safeArea: { bottom: true },
					footer: this.renderFooter(),
				},
				Area(
					{
						isFirst: true,
						excludePaddingSide: { bottom: true },
						style: {
							flex: 1,
							justifyContent: 'space-between',
						},
					},
					this.renderContent(),
				),
			);
		}

		/**
		 * @abstract
		 */
		renderContent() {}

		renderFooter()
		{
			return BoxFooter(
				{
					safeArea: Application.getPlatform() !== 'android',
					keyboardButton: {
						text: this.getButtonText(),
						loading: this.isLoading,
						onClick: this.save,
						testId: this.getTestId('save-button'),
						disabled: this.isButtonDisabled(),
					},
				},
				Button({
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					text: this.getButtonText(),
					loading: this.isLoading,
					stretched: true,
					onClick: this.save,
					testId: this.getTestId('save-button'),
					disabled: this.isButtonDisabled(),
				}),
			);
		}
	}

	module.exports = { BaseDialog };
});
