/**
 * @module intranet/invite-new/src/email-invite-switcher-box
 */
jn.define('intranet/invite-new/src/email-invite-switcher-box', (require, exports, module) => {
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Box, BoxFooter } = require('ui-system/layout/box');
	const { createTestIdGenerator } = require('utils/test');
	const {
		Button,
		ButtonSize,
		ButtonDesign,
	} = require('ui-system/form/buttons/button');
	const { NumberedList } = require('intranet/invite-new/src/numbered-list');
	const { Indent, Component } = require('tokens');

	const DEFAULT_HEIGHT = 550;

	/**
	 * @param {Object} props
	 * @param {number} [props.height]
	 * @param {Function} [props.onInviteButtonClick]
	 * @param {Function} [props.onInviteByBitrix24ButtonClick]

	 * @class EmailInviteSwitcherBox
	 */
	class EmailInviteSwitcherBox extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: 'email-invite-switcher',
			});
		}

		get height()
		{
			return this.props.height ?? DEFAULT_HEIGHT;
		}

		/**
		 * @param {Object} params
		 * @param {Function} params.onClose
		 * @param {number} [params.height]
		 * @param {LayoutComponent} [params.parentWidget]
		 * @param {Function} [params.onInviteButtonClick]
		 * @param {Function} [params.onInviteByBitrix24ButtonClick]
		 * @returns {Promise<EmailInviteSwitcherBox>}
		 */
		static async open(params)
		{
			const { onClose, height } = params;
			const parentWidget = params.parentWidget ?? PageManager;
			const inviteSwitcherBox = new EmailInviteSwitcherBox(params);
			const backdropTitle = Loc.getMessage('INVITE_BY_EMAIL_SWITCHER_BOX_TOP_TITLE');

			const config = {
				enableNavigationBarBorder: false,
				titleParams: {
					text: backdropTitle,
					type: 'dialog',
				},
				modal: true,
				backdrop: {
					showOnTop: false,
					onlyMediumPosition: false,
					mediumPositionHeight: height ?? DEFAULT_HEIGHT,
					bounceEnable: true,
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
					shouldResizeContent: true,
					adoptHeightByKeyboard: true,
				},
				onReady: (readyLayout) => {
					inviteSwitcherBox.setLayout(readyLayout);
					readyLayout.showComponent(inviteSwitcherBox);

					readyLayout.on('onViewRemoved', () => {
						onClose?.();
					});
				},
			};

			await parentWidget.openWidget('layout', config);

			return inviteSwitcherBox;
		}

		/**
		 * @public
		 */
		getLayout()
		{
			return this.layout;
		}

		/**
		 * @public
		 * @param {PageManager} widget
		 */
		setLayout(widget = null)
		{
			this.layout = widget;
		}

		render()
		{
			return Box(
				{
					style: {
						height: this.height,
						maxHeight: this.height,
						paddingVertical: Indent.XL3.toNumber(),
					},
					footer: this.#renderButtons(),
					safeArea: {
						bottom: false,
					},
				},
				View(
					{
						style: {
							width: '100%',
							paddingHorizontal: Component.paddingLr.toNumber(),
						},
					},
					this.#renderImage(),
					this.#renderList(),
				),
			);
		}

		#renderList()
		{
			return NumberedList({
				style: {
					marginTop: Indent.XL.toNumber(),
				},
				items: [{
					text: Loc.getMessage('INVITE_BY_EMAIL_SWITCHER_BOX_NUM_LIST_STEP_1'),
				},
				{
					text: Loc.getMessage('INVITE_BY_EMAIL_SWITCHER_BOX_NUM_LIST_STEP_2'),
				},
				{
					text: Loc.getMessage('INVITE_BY_EMAIL_SWITCHER_BOX_NUM_LIST_STEP_3'),
				},
				{
					text: Loc.getMessage('INVITE_BY_EMAIL_SWITCHER_BOX_NUM_LIST_STEP_4'),
				}],
			});
		}

		#renderImage()
		{
			const uri = makeLibraryImagePath('invite-by-email.svg', 'invite', 'intranet');
			if (!uri)
			{
				return null;
			}

			return View(
				{
					style: {
						alignItems: 'center',
						paddingVertical: Indent.XL4.toNumber(),
					},
				},
				Image({
					resizeMode: 'contain',
					style: {
						width: 178,
						height: 140,
						marginTop: Indent.L.toNumber(),
					},
					svg: {
						uri,
					},
				}),
			);
		}

		#renderButtons()
		{
			return BoxFooter(
				{
					safeArea: true,
					testId: this.getTestId('box-footer'),
				},
				Button({
					testId: this.getTestId('invite-button'),
					size: ButtonSize.L,
					text: Loc.getMessage('INVITE_BY_EMAIL_SWITCHER_BOX_INVITE_BUTTON_TEXT'),
					design: ButtonDesign.PRIMARY,
					stretched: true,
					onClick: this.#onInviteButtonClick,
				}),
				Button(
					{
						testId: this.getTestId('invite-by-bitrix24-button'),
						text: Loc.getMessage('INVITE_BY_EMAIL_SWITCHER_BOX_INVITE_BY_BITRIX24_BUTTON_TEXT'),
						size: ButtonSize.S,
						design: ButtonDesign.PLAIN_NO_ACCENT,
						stretched: true,
						onClick: this.#onInviteByBitrix24ButtonClick,
						style: {
							marginTop: Indent.L.toNumber(),
						},
					},
				),
			);
		}

		#onInviteButtonClick = () => {
			this.props.onInviteButtonClick?.();
		};

		#onInviteByBitrix24ButtonClick = () => {
			this.props.onInviteByBitrix24ButtonClick?.();
		};
	}

	module.exports = {
		EmailInviteSwitcherBox,
	};
});
