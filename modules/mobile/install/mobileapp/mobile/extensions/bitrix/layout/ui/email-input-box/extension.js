/**
 * @module layout/ui/email-input-box
 */
jn.define('layout/ui/email-input-box', (require, exports, module) => {
	const { Box } = require('ui-system/layout/box');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Area } = require('ui-system/layout/area');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Color } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { EmailInput } = require('layout/ui/email-input-box/src/email-input');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { Haptics } = require('haptics');
	const { Alert, ButtonType } = require('alert');

	const layoutHeight = 285;

	class EmailInputBox extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.layoutWidget = null;
			this.emailInputRef = null;
			this.state = {
				pending: false,
				emails: [],
			};
		}

		get testId()
		{
			return `${this.props.testId}-email-input-box`;
		}

		get inputPlaceholder()
		{
			return this.props.inputPlaceholder ?? '';
		}

		get bottomButtonText()
		{
			return this.props.bottomButtonText ?? '';
		}

		get dismissAlert()
		{
			return this.props.dismissAlert;
		}

		componentDidMount()
		{
			this.#focusInput();
			this.layoutWidget.on('preventDismiss', () => {
				if (this.props.dismissAlert && this.state.emails.length > 0)
				{
					this.#showConfirmOnBoxClosing();
				}
				else
				{
					this.layoutWidget.close();
				}
			});
		}

		render()
		{
			return Box(
				{
					testId: this.testId,
					safeArea: {
						bottom: true,
					},
					resizableByKeyboard: true,
					footer: this.renderInviteButton(),
				},
				AreaList(
					{
						testId: `${this.testId}-area-list`,
						style: {
							flex: 1,
							width: '100%',
						},
					},
					Area(
						{},
						Card(
							{
								testId: `${this.testId}-input-card`,
								border: true,
								design: CardDesign.PRIMARY,
							},
							EmailInput({
								ref: this.#bindEmailInputRef,
								testId: this.testId,
								inputPlaceholder: this.inputPlaceholder,
								onEmailsChanged: (emails) => {
									this.setState({
										emails,
									});
								},
							}),
						),
					),
				),
			);
		}

		#showConfirmOnBoxClosing()
		{
			Haptics.impactLight();

			Alert.confirm(
				this.dismissAlert.title,
				this.dismissAlert.description,
				[
					{
						type: ButtonType.DESTRUCTIVE,
						text: this.dismissAlert.destructiveButtonText,
						onPress: () => {
							this.layoutWidget.close();
						},
					},
					{
						type: ButtonType.DEFAULT,
						text: this.dismissAlert.defaultButtonText,
					}],
			);
		}

		#bindEmailInputRef = (ref) => {
			this.emailInputRef = ref;
		};

		#focusInput = () => {
			this.emailInputRef?.focus();
		};

		close = () => {
			this.layoutWidget?.close();
		};

		disableButtonLoading = () => {
			this.setState({
				pending: false,
			});
		};

		renderInviteButton = () => {
			return BoxFooter(
				{
					safeArea: Application.getPlatform() === 'ios',
					keyboardButton: {
						text: this.bottomButtonText,
						loading: this.state.pending,
						onClick: this.onSendInviteButtonClick,
						badge: this.renderCounter(),
						disabled: this.state.emails.length === 0,
						forwardRef: (ref) => {
							this.inviteButtonRef = ref;
						},
					},
				},
				Button({
					testId: `${this.testId}-invite-button`,
					text: this.bottomButtonText,
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					loading: this.state.pending,
					stretched: true,
					badge: this.renderCounter(),
					disabled: this.state.emails.length === 0,
					style: {
						paddingVertical: 0,
					},
					onClick: this.onSendInviteButtonClick,
				}),
			);
		};

		renderCounter()
		{
			if (this.state.emails.length === 0)
			{
				return null;
			}

			return BadgeCounter({
				testId: `${this.testId}-badge-counter`,
				value: String(this.state.emails.length),
				design: BadgeCounterDesign.WHITE,
			});
		}

		onSendInviteButtonClick = () => {
			if (this.state.pending)
			{
				return;
			}

			this.setState({
				pending: true,
			}, () => {
				if (this.props.onButtonClick)
				{
					this.props.onButtonClick(this.state.emails);
				}
			});
		};
	}

	/**
	 * @param {Object} props
	 * @param {String} props.testId
	 * @param {String} props.title
	 * @param {String} props.bottomButtonText
	 * @param {String} props.inputPlaceholder
	 * @param {Function} props.onButtonClick
	 * @param {LayoutComponent} props.parentLayout
	 * @return {Promise}
	 */
	const openEmailInputBox = (props) => {
		return new Promise((resolve) => {
			const parentLayout = props.parentLayout || PageManager;
			const controlInstance = new EmailInputBox(props);

			const bottomSheet = new BottomSheet({
				component: controlInstance,
				titleParams: {
					type: 'dialog',
					text: props.title,
					largeMode: true,
				},
			});
			bottomSheet
				.setParentWidget(parentLayout)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.disableShowOnTop()
				.disableOnlyMediumPosition()
				.setMediumPositionHeight(layoutHeight)
				.enableBounce()
				.disableSwipe()
				.disableHorizontalSwipe()
				.enableResizeContent()
				.enableAdoptHeightByKeyboard()
				.open()
				.then((layoutWidget) => {
					layoutWidget.preventBottomSheetDismiss(true);
					controlInstance.layoutWidget = layoutWidget;
					resolve(controlInstance);
				})
				.catch(() => resolve(null));
		});
	};

	module.exports = {
		openEmailInputBox,
	};
});
