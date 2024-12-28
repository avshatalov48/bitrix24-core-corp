/**
 * @module intranet/reinvite
 */
jn.define('intranet/reinvite', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Indent, Component } = require('tokens');
	const { isPhoneNumber } = require('utils/phone');
	const { isValidEmail } = require('utils/email');
	const { BottomSheet } = require('bottom-sheet');
	const { showErrorToast } = require('toast');

	const { Icon } = require('ui-system/blocks/icon');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { InputSize, InputDesign, InputMode, PhoneInput } = require('ui-system/form/inputs/phone');
	const { EmailInput, InputDomainIconPlace } = require('ui-system/form/inputs/email');
	const { Chip } = require('ui-system/blocks/chips/chip');
	const { Text4 } = require('ui-system/typography/text');
	const { BBCodeText } = require('ui-system/typography/bbcodetext');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { InviteStatusBox } = require('intranet/invite-status-box');

	const store = require('statemanager/redux/store');
	const { selectWholeUserById } = require('intranet/statemanager/redux/slices/employees/selector');
	const { RunActionExecutor } = require('rest/run-action-executor');

	const isAndroid = Application.getPlatform() === 'android';

	const REINVITE_PHONE_TYPE = 'phone';
	const REINVITE_EMAIL_TYPE = 'email';

	/**
	 * @class Reinvite
	 */
	class Reinvite extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = null;
			this.canOpen = false;
			this.inviteStatusBoxIsOpened = false;

			this.user = selectWholeUserById(store.getState(), Number(this.userId)) ?? {};
			this.state = {
				phone: this.user.personalMobile,
				email: this.user.email,
				inputError: null,
			};
		}

		get userId()
		{
			return this.props.userId;
		}

		isEmailInvite()
		{
			return Type.isStringFilled(this.user.email);
		}

		isPhoneInvite()
		{
			return Type.isStringFilled(this.user.personalMobile);
		}

		/**
		 * @param {Object} data
		 * @param {Object} [data.parentWidget]
		 * @param {string} [data.title]
		 */
		static async open(data)
		{
			const parentWidget = data.parentWidget || PageManager;
			const reinvite = new Reinvite(data);
			await reinvite.fetchInviteSettings(parentWidget);

			if (!reinvite.canOpen)
			{
				return;
			}

			const bottomSheet = new BottomSheet({
				component: reinvite,
				titleParams: {
					type: 'dialog',
					text: data.title ?? Loc.getMessage('M_INTRANET_REINVITE_TITLE'),
					largeMode: true,
				},
			});
			bottomSheet
				.setParentWidget(parentWidget)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.disableShowOnTop()
				.disableOnlyMediumPosition()
				.setMediumPositionHeight(Reinvite.getStartingLayoutHeight())
				.enableBounce()
				.enableSwipe()
				.disableHorizontalSwipe()
				.enableResizeContent()
				.enableAdoptHeightByKeyboard()
				.open()
				.then((layoutWidget) => {
					reinvite.layoutWidget = layoutWidget;
				})
				.catch((error) => {
					console.error('Failed to open widget:', error);
				});
		}

		static getStartingLayoutHeight()
		{
			const TITLE_HEIGHT = 44;
			const AREA_PADDING = Component.areaPaddingTFirst.toNumber();
			const CHIP_HEIGHT = 32 + Indent.XL2.toNumber() + Indent.XS.toNumber();
			const INPUT_HEIGHT = 42 + Indent.M.toNumber() + Indent.XL2.toNumber();
			const BUTTON_HEIGHT = 42 + Indent.XL2.toNumber() * 2;
			const DESCRIPTION_HEIGHT = 66 + Indent.M.toNumber();

			return TITLE_HEIGHT
				+ AREA_PADDING
				+ CHIP_HEIGHT
				+ INPUT_HEIGHT
				+ BUTTON_HEIGHT
				+ DESCRIPTION_HEIGHT;
		}

		async fetchInviteSettings(parentWidget)
		{
			(new RunActionExecutor('intranetmobile.invite.getInviteSettings'))
				.setCacheTtl(3600 * 24)
				.setCacheId('inviteSettings')
				.setHandler((response) => this.fetchInviteSettingsHandler(response, parentWidget))
				.setCacheHandler((response) => this.fetchInviteSettingsHandler(response, parentWidget))
				.call(true);
		}

		fetchInviteSettingsHandler = (response, parentWidget) => {
			const responseHasErrors = response.errors && response.errors.length > 0;
			if (responseHasErrors)
			{
				showErrorToast(response.errors[0].message);
				this.canOpen = false;

				return;
			}

			const {
				isBitrix24Included,
				adminInBoxRedirectLink,
			} = response.data;

			if (!isBitrix24Included)
			{
				if (env.isAdmin)
				{
					this.openBoxAdminCanInviteInWeb(adminInBoxRedirectLink, parentWidget);
				}
				else
				{
					this.openBoxOnlyAdminCanInvite(parentWidget);
				}
				this.canOpen = false;

				return;
			}

			this.canOpen = true;
		};

		openBoxAdminCanInviteInWeb(adminInBoxRedirectLink, parentWidget)
		{
			if (this.inviteStatusBoxIsOpened)
			{
				return;
			}

			this.inviteStatusBoxIsOpened = true;

			InviteStatusBox.open({
				backdropTitle: Loc.getMessage('M_INTRANET_REINVITE_TITLE'),
				testId: 'status-box-reinvite-in-web',
				imageName: 'user-locked.svg',
				description: Loc.getMessage('M_INTRANET_REINVITE_ADMIN_ONLY_IN_WEB_BOX_TEXT'),
				buttonText: Loc.getMessage('M_INTRANET_REINVITE_GO_TO_WEB_BUTTON_TEXT'),
				parentWidget,
				onButtonClick: () => {
					setTimeout(() => {
						qrauth.open({
							redirectUrl: adminInBoxRedirectLink,
							showHint: true,
							analyticsSection: 'userList',
						});
					}, 500);
				},
			});
		}

		openBoxOnlyAdminCanInvite(parentWidget)
		{
			if (this.inviteStatusBoxIsOpened)
			{
				return;
			}

			this.inviteStatusBoxIsOpened = true;

			InviteStatusBox.open({
				backdropTitle: Loc.getMessage('M_INTRANET_REINVITE_TITLE'),
				testId: 'status-box-no-permission',
				imageName: 'user-locked.svg',
				parentWidget,
				description: Loc.getMessage('M_INTRANET_REINVITE_ADMIN_ONLY_BOX_TEXT'),
				buttonText: Loc.getMessage('M_INTRANET_REINVITE_DISABLED_BOX_BUTTON_TEXT'),
			});
		}

		componentDidMount()
		{
			super.componentDidMount();

			Keyboard.on(Keyboard.Event.WillHide, () => {
				this.layoutWidget.setBottomSheetHeight(Reinvite.getStartingLayoutHeight());
			});
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();
			Keyboard.on(Keyboard.Event.WillHide, () => {
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
					View(
						{
							style: {
								flexDirection: 'row',
								flexWrap: 'wrap',
								justifyContent: 'center',
								paddingHorizontal: Indent.M.toNumber(),
							},
						},
						this.renderUserChip(),
						this.renderInput(),
						this.renderDescription(),
					),
				),
			);
		}

		renderDescription()
		{
			const descriptionText = this.isEmailInvite()
				? Loc.getMessage('M_INTRANET_REINVITE_DESCRIPTION_EMAIL_TEXT')
				: Loc.getMessage('M_INTRANET_REINVITE_DESCRIPTION_PHONE_TEXT');
			const buttonText = Loc.getMessage('M_INTRANET_REINVITE_DESCRIPTION_BUTTON_TEXT');
			const articleCode = '17729332';
			const articleUrl = helpdesk.getArticleUrl(articleCode);

			return View(
				{
					style: {
						paddingTop: Indent.M.toNumber(),
						paddingHorizontal: Indent.XL2.toNumber(),
					},
				},
				BBCodeText({
					style: {
						textAlign: 'center',
					},
					color: Color.base2.toHex(),
					size: 4,
					linksUnderline: false,
					onLinkClick: () => {
						helpdesk.openHelpArticle(articleCode, 'helpdesk');
					},
					value: `${descriptionText} [COLOR=${Color.accentMainLink.toHex()}][URL=${articleUrl}]${buttonText}[/URL][/COLOR]`,
				}),
			);
		}

		renderFooter()
		{
			return BoxFooter(
				{
					safeArea: !isAndroid,
					keyboardButton: {
						text: Loc.getMessage('M_INTRANET_REINVITE_SEND_BUTTON'),
						loading: this.state.loading,
						onClick: this.save,
						testId: 'reinvite-keyboard-send-button',
					},
				},
				Button({
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					text: Loc.getMessage('M_INTRANET_REINVITE_SEND_BUTTON'),
					stretched: true,
					onClick: this.save,
					testId: 'reinvite-send-button',
					disabled: this.state.inputError !== null,
				}),
			);
		}

		renderUserChip()
		{
			return Chip({
				style: {
					marginBottom: Indent.XL2.toNumber(),
					marginTop: Indent.XS.toNumber(),
				},
				backgroundColor: Color.bgPrimary,
				borderColor: Color.bgSeparatorPrimary,
				compact: true,
				children: [
					Avatar({
						id: this.userId,
						withRedux: true,
					}),
					Text4(
						{
							text: this.user.fullName,
							style: {
								marginLeft: Indent.XS.toNumber(),
							},
						},
					),
				],
			});
		}

		renderInput()
		{
			const { phone, inputError, email } = this.state;

			if (this.isEmailInvite())
			{
				return EmailInput({
					value: email,
					style: {
						marginTop: Indent.M.toNumber(),
						marginBottom: Indent.XL2.toNumber(),
					},
					label: Loc.getMessage('M_INTRANET_EMAIL_INPUT_TITLE'),
					size: InputSize.L,
					design: InputDesign.GREY,
					mode: InputMode.STROKE,
					align: 'center',
					focus: true,
					error: inputError ?? null,
					errorText: inputError,
					domainIconPlace: InputDomainIconPlace.LEFT,
					leftContent: Icon.MAIL,
					onChange: this.onChangeEmail,
					forwardRef: this.handleInputRef,
					testId: 'reinvite-email-input',
				});
			}

			if (this.isPhoneInvite())
			{
				return PhoneInput({
					value: phone,
					style: {
						marginTop: Indent.M.toNumber(),
						marginBottom: Indent.XL2.toNumber(),
					},
					label: Loc.getMessage('M_INTRANET_PHONE_INPUT_TITLE'),
					size: InputSize.L,
					design: InputDesign.GREY,
					mode: InputMode.STROKE,
					align: 'center',
					focus: true,
					error: inputError ?? null,
					errorText: inputError,
					onChange: this.onChangePhone,
					forwardRef: this.handleInputRef,
					testId: 'reinvite-phone-input',
				});
			}
			console.error('User must have either phone or email to reinvite');

			return null;
		}

		onChangeEmail = (newEmail) => {
			this.setState({
				email: newEmail,
				inputError: Type.isStringFilled(newEmail)
					? null
					: Loc.getMessage('M_INTRANET_REINVITE_EMAIL_INPUT_EMPTY_EMAIL'),
			});
		};

		onChangePhone = (newPhone) => {
			this.setState({
				phone: newPhone,
				inputError: Type.isStringFilled(newPhone)
					? null
					: Loc.getMessage('M_INTRANET_REINVITE_PHONE_INPUT_EMPTY_NUMBER'),
			});
		};

		handleInputRef = (ref) => {
			this.inputRef = ref;
			if (this.inputRef)
			{
				this.inputRef.focus();
			}
		};

		save = () => {
			const { phone, email, inputError } = this.state;

			if (inputError)
			{
				return;
			}

			if (phone && !isPhoneNumber(phone))
			{
				this.setState({
					inputError: Loc.getMessage('M_INTRANET_REINVITE_PHONE_INPUT_INCORRECT_NUMBER'),
				});

				return;
			}

			if (email && !isValidEmail(email))
			{
				this.setState({
					inputError: Loc.getMessage('M_INTRANET_REINVITE_EMAIL_INPUT_INCORRECT_EMAIL'),
				});

				return;
			}

			const newValue = phone ?? email;
			const valueType = phone ? REINVITE_PHONE_TYPE : REINVITE_EMAIL_TYPE;

			if (this.props.onSave)
			{
				this.props.onSave(newValue, valueType);
			}

			this.layoutWidget.close();
		};
	}

	module.exports = { Reinvite };
});
