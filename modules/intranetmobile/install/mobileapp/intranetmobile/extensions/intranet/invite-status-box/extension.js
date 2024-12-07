/**
 * @module intranet/invite-status-box
 */
jn.define('intranet/invite-status-box', (require, exports, module) => {
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { BottomSheet } = require('bottom-sheet');
	const { Color, Indent, Component } = require('tokens');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Box } = require('ui-system/layout/box');
	const {
		Button,
		ButtonSize,
		ButtonDesign,
	} = require('ui-system/form/buttons/button');

	const IS_SAFE_AREA_BOTTOM_AVAILABLE = device.screen.safeArea.bottom > 0;
	const SAFE_AREA = 38;

	/**
	 * @class InviteStatusBox
	 */
	class InviteStatusBox extends LayoutComponent
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

		get imageName()
		{
			return this.props.imageName ?? null;
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

		get buttonTestId()
		{
			return this.testId ? `${this.testId}-button` : '';
		}

		onButtonClick = () => {
			if (this.props.onButtonClick)
			{
				this.props.onButtonClick();
			}

			this.parentWidget.close();
		};

		/**
		 * @param {Object} data
		 * @param {string} data.backdropTitle
		 * @param {Object} [data.parentWidget]
		 * @param {string} [data.testId]
		 * @param {string} [data.imageName]
		 * @param {string} [data.title]
		 * @param {string} [data.description]
		 * @param {string} [data.buttonText]
		 * @param {Function} [data.onButtonClick]
		 */
		static open(data)
		{
			const { backdropTitle } = data;
			const parentWidget = data.parentWidget ?? PageManager;
			const inviteStatusBox = new InviteStatusBox(data);

			void new BottomSheet({
				titleParams: {
					text: backdropTitle,
					type: 'dialog',
					useLargeTitleMode: true,
				},
				component: (widget) => {
					inviteStatusBox.setParentWidget(widget);

					return inviteStatusBox;
				},
			}).setParentWidget(parentWidget)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setMediumPositionHeight(425)
				.disableResizeContent()
				.open()
			;
		}

		render()
		{
			return Box(
				{
					style: {
						height: 375,
						maxHeight: 375,
						paddingBottom: IS_SAFE_AREA_BOTTOM_AVAILABLE ? 0 : SAFE_AREA,
					},
					safeArea: {
						bottom: true,
					},
				},
				StatusBlock({
					testId: this.testId,
					title: this.title,
					image: this.renderImage,
					description: this.description,
				}),
				View(
					{
						style: {
							width: '100%',
							paddingVertical: Indent.XL.toNumber(),
							paddingHorizontal: Component.paddingLrMore.toNumber(),
						},
					},
					Button({
						testId: this.buttonTestId,
						size: ButtonSize.L,
						text: this.buttonText,
						design: ButtonDesign.OUTLINE_ACCENT_1,
						stretched: true,
						onClick: this.onButtonClick,
					}),
				),
			);
		}

		get renderImage()
		{
			if (!this.props.imageName)
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
					uri: makeLibraryImagePath(this.props.imageName, 'invite-status-box', 'intranet'),
				},
			});
		}
	}

	module.exports = { InviteStatusBox };
});
