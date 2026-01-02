/**
 * @module layout/ui/email-input-box
 */
jn.define('layout/ui/email-input-box', (require, exports, module) => {
	const { Box } = require('ui-system/layout/box');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Area } = require('ui-system/layout/area');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');
	const { Color, Indent } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { Haptics } = require('haptics');
	const { Alert, ButtonType } = require('alert');
	const { MultiEmailInput, ChipDesign, MultiEmailInputDesign } = require('ui-system/form/inputs/multi-email');
	const { InviteButton } = require('layout/ui/email-input-box/src/invite-button');
	const { createTestIdGenerator } = require('utils/test');
	const { Text5 } = require('ui-system/typography/text');

	const layoutHeight = 285;

	class EmailInputBox extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.layoutWidget = null;
			this.multiEmailInput = null;
			this.getTestId = createTestIdGenerator({
				prefix: 'email-input-box',
				context: this,
			});
			this.#initState(props);
		}

		#initState = (props) => {
			this.state = {
				allEmails: props.allEmails ?? [],
				validEmails: props.validEmails ?? [],
				pending: props.pending ?? false,
				newEmailInputValue: props.newEmailInputValue ?? '',
			};
		};

		get dismissAlert()
		{
			return this.props.dismissAlert;
		}

		componentDidMount()
		{
			this.layoutWidget.on('preventDismiss', () => {
				const { dismissAlert } = this.props;
				const { allEmails } = this.state;
				if (dismissAlert && allEmails.length > 0)
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
			const { inputPlaceholder, emailChipDesign, description } = this.props;
			const { allEmails, newEmailInputValue } = this.state;

			return Box(
				{
					testId: this.getTestId(),
					safeArea: {
						bottom: true,
					},
					resizableByKeyboard: true,
					footer: this.renderInviteButton(),
				},
				AreaList(
					{
						testId: this.getTestId('area-list'),
						withScroll: false,
						style: {
							flex: 1,
							width: '100%',
						},
					},
					Area(
						{},
						MultiEmailInput({
							testId: this.getTestId('multi-email'),
							ref: this.#bindMultiEmailInputRef,
							emails: allEmails,
							newEmailInputValue,
							placeholder: inputPlaceholder,
							height: 136,
							focus: true,
							showEmailsCounter: false,
							design: MultiEmailInputDesign.LIGHT_GREY,
							chipDesign: emailChipDesign ?? ChipDesign.COLLAB,
							onChange: this.#multiEmailInputOnChange,
						}),
						description && Text5({
							text: description,
							color: Color.base3,
							style: {
								marginTop: Indent.M.toNumber(),
							},
						}),
					),
				),
			);
		}

		#bindMultiEmailInputRef = (ref) => {
			this.multiEmailInput = ref;
		};

		#multiEmailInputOnChange = ({ allEmails, validEmails, newEmailInputValue }) => {
			this.setState({
				allEmails: [...allEmails],
				validEmails: [...validEmails],
				newEmailInputValue,
			});
		};

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

		close = (callback) => {
			this.layoutWidget?.close(callback);
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
					keyboardButton: this.renderKeyboardButton,
				},
				this.renderButton(),
			);
		};

		#isInviteButtonDisabled = () => {
			const { allEmails, validEmails } = this.state;

			return validEmails.length === 0 || validEmails.length !== allEmails.length;
		};

		renderKeyboardButton = () => {
			const { bottomButtonText } = this.props;
			const { pending, validEmails } = this.state;

			return InviteButton({
				testId: this.getTestId('invite-keyboard-button'),
				text: bottomButtonText,
				pending,
				disabled: this.#isInviteButtonDisabled(),
				counter: validEmails.length,
				onButtonClick: this.onButtonClick,
			});
		};

		renderButton = () => {
			const { bottomButtonText } = this.props;
			const { pending, validEmails } = this.state;

			return Button({
				testId: this.getTestId('invite-button'),
				text: bottomButtonText,
				design: ButtonDesign.FILLED,
				size: ButtonSize.L,
				loading: pending,
				stretched: true,
				disabled: this.#isInviteButtonDisabled(),
				counter: validEmails.length,
				style: {
					paddingVertical: 0,
				},
				onClick: this.onButtonClick,
			});
		};

		onButtonClick = () => {
			if (this.multiEmailInput.isEditingSomeEmail())
			{
				this.multiEmailInput.focusNewEmailInput();
				setTimeout(() => {
					if (!this.#isInviteButtonDisabled())
					{
						this.onButtonClick();
					}
				}, 100);

				return;
			}

			const { onButtonClick } = this.props;
			const { pending, validEmails } = this.state;
			if (pending || validEmails.length === 0 || this.#isInviteButtonDisabled())
			{
				return;
			}

			this.setState({
				pending: true,
			});
			onButtonClick?.(validEmails);
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
	 * @param {ChipTextInputStatusDesign} props.emailChipDesign
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
		ChipDesign,
	};
});
