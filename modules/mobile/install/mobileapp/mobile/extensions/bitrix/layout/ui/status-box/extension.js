/**
 * @module layout/ui/status-box
 */
jn.define('layout/ui/status-box', (require, exports, module) => {
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { BottomSheet } = require('bottom-sheet');
	const { Color } = require('tokens');
	const { Box, BoxFooter } = require('ui-system/layout/box');
	const {
		Button,
		ButtonSize,
		ButtonDesign,
	} = require('ui-system/form/buttons/button');

	const DEFAULT_HEIGHT = 425;

	/**
	 * @class StatusBox
	 */
	class StatusBox extends LayoutComponent
	{
		constructor(props) {
			super(props);

			this.setParentWidget();
		}

		/**
		 * @public
		 * @param widget
		 */
		setParentWidget(widget = null)
		{
			this.parentWidget = widget;
		}

		get testId()
		{
			return this.props.testId ?? '';
		}

		get title()
		{
			return this.props.title ?? null;
		}

		get description()
		{
			return this.props.description ?? null;
		}

		get buttonText()
		{
			return this.props.buttonText ?? null;
		}

		get buttonDesign()
		{
			return this.props.buttonDesign ?? ButtonDesign.OUTLINE_ACCENT_1;
		}

		get buttonTestId()
		{
			return this.testId ? `${this.testId}-button` : '';
		}

		get height()
		{
			return this.props.height ?? DEFAULT_HEIGHT;
		}

		get shouldCloseOnButtonClick()
		{
			return this.props.shouldCloseOnButtonClick ?? true;
		}

		get statusBlockButtons()
		{
			return this.props.statusBlockButtons ?? [];
		}

		get statusBlockStyle()
		{
			return this.props.statusBlockStyle ?? {};
		}

		#getTestId = (suffix) => {
			const prefix = this.testId;

			return suffix ? `${prefix}-${suffix}` : prefix;
		};

		onButtonClick = () => {
			const { onButtonClick, onClose } = this.props;
			onButtonClick?.(this.parentWidget);
			if (this.shouldCloseOnButtonClick)
			{
				onClose?.(this.parentWidget);
				this.parentWidget.close();
			}
		};

		/**
		 * @param {Object} data
		 * @param {string} data.backdropTitle
		 * @param {Object} [data.parentWidget]
		 * @param {string} [data.testId]
		 * @param {string} [data.imageUri]
		 * @param {string} [data.title]
		 * @param {string} [data.description]
		 * @param {string} [data.buttonText]
		 * @param {ButtonDesign} [data.buttonDesign]
		 * @param {Function} [data.onButtonClick]
		 * @param {Function} [data.onClose]
		 * @param {number} [data.height]
		 * @param {Array<Button>} [data.statusBlockButtons]
		 * @param {Object} [data.statusBlockStyle]
		 * @param {boolean} [data.shouldCloseOnButtonClick = true]
		 */
		static async open(data)
		{
			const { backdropTitle, height, onClose } = data;
			const parentWidget = data.parentWidget ?? PageManager;
			const statusBox = new StatusBox(data);

			return new Promise((resolve) => {
				new BottomSheet({
					titleParams: {
						text: backdropTitle,
						type: 'dialog',
						useLargeTitleMode: true,
					},
					component: (widget) => {
						statusBox.setParentWidget(widget);
						widget.preventBottomSheetDismiss(true);
						widget.on('preventDismiss', () => {
							onClose?.(widget);
							widget.close();
						});
						resolve(statusBox);

						return statusBox;
					},
				}).setParentWidget(parentWidget)
					.setBackgroundColor(Color.bgSecondary.toHex())
					.setNavigationBarColor(Color.bgSecondary.toHex())
					.setMediumPositionHeight(height ?? DEFAULT_HEIGHT)
					.disableResizeContent()
					.open()
					.then((layout) => {
						layout.on('onViewHidden', () => {
							data.onClose?.();
						});
					})
					.catch(console.error);
			});
		}

		render()
		{
			return Box(
				{
					style: {
						height: this.height,
						maxHeight: this.height,
					},
					footer: this.#renderButton(),
					safeArea: {
						bottom: false,
					},
				},
				StatusBlock({
					emptyScreen: false,
					testId: this.#getTestId('status-block'),
					title: this.title,
					image: this.renderImage,
					description: this.description,
					buttons: this.statusBlockButtons,
					style: this.statusBlockStyle,
				}),
			);
		}

		#renderButton()
		{
			return BoxFooter(
				{
					safeArea: true,
					testId: this.#getTestId('box-footer'),
				},
				Button({
					testId: this.#getTestId('button'),
					size: ButtonSize.L,
					text: this.buttonText,
					design: this.buttonDesign,
					stretched: true,
					onClick: this.onButtonClick,
				}),
			);
		}

		get renderImage()
		{
			const { imageUri: uri } = this.props;
			if (!uri)
			{
				return null;
			}

			return Image({
				resizeMode: 'contain',
				style: {
					width: 120,
					height: 120,
				},
				svg: {
					uri,
				},
			});
		}
	}

	module.exports = { StatusBox };
});
