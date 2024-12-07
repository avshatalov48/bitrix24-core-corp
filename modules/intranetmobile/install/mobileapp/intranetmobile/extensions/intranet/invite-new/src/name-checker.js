/**
 * @module intranet/invite-new/src/name-checker
 */
jn.define('intranet/invite-new/src/name-checker', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { merge } = require('utils/object');
	const { Area } = require('ui-system/layout/area');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Text4 } = require('ui-system/typography/text');
	const { Color, Indent, Component } = require('tokens');
	const { NameCheckerItem } = require('intranet/invite-new/src/name-checker-item');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { showToast } = require('toast');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const store = require('statemanager/redux/store');
	const { usersUpserted } = require('statemanager/redux/slices/users');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { showErrorMessage } = require('intranet/invite-new/src/error');

	class NameChecker extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.scrollViewRef = null;
			this.inputs = [];
			this.focusedInputIndex = -1;
			this.usersToInvite = this.props.usersToInvite;
			this.sendInviteButtonClicked = false;
			this.state = {
				sendingRequest: false,
			};
		}

		componentDidMount()
		{
			super.componentDidMount();
			Keyboard.on(Keyboard.Event.Shown, () => {
				if (this.focusedInputIndex > -1)
				{
					this.scrollToFocusedInput(this.inputs[this.focusedInputIndex]);
				}
			});
		}

		get analytics()
		{
			return this.props.analytics ?? {};
		}

		get department()
		{
			return this.props.department;
		}

		render()
		{
			return Box(
				{
					resizableByKeyboard: true,
					safeArea: { bottom: true },
					footer: this.#renderInviteButton(),
					style: {
						width: '100%',
						flex: 1,
					},
				},
				this.#renderInfoArea(),
				this.#renderContactsListArea(),
			);
		}

		get testId()
		{
			return 'name-checker';
		}

		#renderInviteButton()
		{
			return BoxFooter(
				{
					safeArea: Application.getPlatform() === 'ios',
					keyboardButton: {
						text: Loc.getMessage('INTRANET_INVITE_BUTTON_TEXT'),
						loading: this.state.sendingRequest,
						onClick: this.onSendInviteButtonClick,
					},
				},
				Button({
					testId: `${this.testId}-invite-button`,
					text: Loc.getMessage('INTRANET_INVITE_BUTTON_TEXT'),
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					loading: this.state.sendingRequest,
					stretched: true,
					style: {
						paddingVertical: 0,
					},
					onClick: this.onSendInviteButtonClick,
				}),
			);
		}

		onSendInviteButtonClick = async () => {
			if (this.sendInviteButtonClicked)
			{
				return;
			}

			this.sendInviteButtonClicked = true;
			this.setState({
				sendingRequest: true,
			});

			const multipleInvitation = this.usersToInvite.length > 1;
			const response = await this.inviteUsersByPhoneNumbers();
			const preparedUsers = this.getUsersFromResponse(response);

			if (response && response.errors && response.errors.length === 0)
			{
				this.analytics.sendInvitationSuccessEvent(multipleInvitation, preparedUsers.map((user) => user.id));
				this.showSuccessInvitationToast(multipleInvitation);

				store.dispatch(usersUpserted(preparedUsers));

				if (this.props.onInviteSentHandler && preparedUsers.length > 0)
				{
					this.props.onInviteSentHandler(preparedUsers);
				}

				this.closeInviteBox();

				return;
			}
			this.analytics.sendInvitationFailedEvent(multipleInvitation, preparedUsers.map((user) => user.id));

			if (response && response.errors && response.errors.length > 0)
			{
				await showErrorMessage(response.errors[0]);
				this.props?.onInviteError?.(response.errors);
			}

			this.closeInviteBox();
		};

		showSuccessInvitationToast = (multipleInvitation) => {
			const message = multipleInvitation
				? Loc.getMessage('INTRANET_INVITE_MULTIPLE_SEND_SUCCESS_TOAST_TEXT')
				: Loc.getMessage('INTRANET_INVITE_SINGLE_SEND_SUCCESS_TOAST_TEXT');
			showToast(
				{
					message,
					svg: {
						content: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M20.361 6.35445C20.5953 6.58876 20.5953 6.96866 20.361 7.20298L9.79422 17.7697C9.6817 17.8822 9.52908 17.9454 9.36995 17.9454C9.21082 17.9454 9.05821 17.8822 8.94569 17.7697L4.14839 12.9723C3.91408 12.738 3.91408 12.3581 4.1484 12.1238C4.38271 11.8895 4.76261 11.8895 4.99692 12.1238L9.36996 16.4969L19.5124 6.35445C19.7468 6.12013 20.1267 6.12013 20.361 6.35445Z" fill="#333333"/></svg>',
					},
				},
			);
		};

		getUsersFromResponse = (response) => {
			if (response.data?.userList?.length > 0)
			{
				return response.data.userList.map((user) => {
					return {
						id: user.ID,
						name: user.NAME,
						lastName: user.LAST_NAME,
						fullName: user.FULL_NAME,
						personalMobile: user.PERSONAL_MOBILE,
					};
				});
			}

			return [];
		};

		closeInviteBox()
		{
			this.props.layout.close();
			this.props.parentLayout.close();
		}

		inviteUsersByPhoneNumbers()
		{
			const preparedUsers = this.usersToInvite.map((user) => {
				return {
					phone: user.phone,
					firstName: user.firstName,
					lastName: user.secondName,
					countryCode: user.countryCode,
				};
			});

			return new Promise((resolve) => {
				new RunActionExecutor('intranetmobile.invite.inviteUsersByPhoneNumbers', {
					users: preparedUsers,
					departmentId: this.department ? this.department.id : null,
				})
					.setHandler((result) => resolve(result))
					.call(false);
			});
		}

		renderDepartmentCard()
		{
			return Card(
				{
					testId: `${this.testId}-department-card`,
					border: false,
					style: {
						paddingTop: Component.cardPaddingT.toNumber(),
						paddingBottom: Component.cardPaddingB.toNumber(),
						marginBottom: Component.cardListGap.toNumber(),
						justifyContent: 'center',
						alignItems: 'center',
					},
					design: CardDesign.PRIMARY,
				},
				ChipButton({
					testId: `${this.testId}-department-chip-status`,
					compact: true,
					color: Color.accentMainPrimary,
					mode: ChipButtonMode.OUTLINE,
					design: ChipButtonDesign.PRIMARY,
					text: this.department?.title,
					dropdown: false,
					rounded: false,
					backgroundColor: Color.bgPrimary,
				}),
			);
		}

		focusNextInput()
		{
			if (this.focusedInputIndex > -1 && this.inputs.length > 0)
			{
				const isLastInput = this.focusedInputIndex === this.inputs.length - 1;
				this.focusedInputIndex = isLastInput ? 0 : (this.focusedInputIndex + 1);
				this.inputs[this.focusedInputIndex].setFocused(true);
				this.scrollToFocusedInput(this.inputs[this.focusedInputIndex]);
			}
		}

		scrollToFocusedInput(input, animated = true)
		{
			if (this.scrollViewRef)
			{
				const { y } = this.scrollViewRef.getPosition(input.contentFieldRef);
				// todo: fix after getPosition method native fix
				const positionY = Application.getPlatform() === 'ios' ? y - 20 : y - 140;
				this.scrollTo({ y: positionY }, animated);
			}
		}

		scrollTo(position, animated = true)
		{
			if (this.scrollViewRef)
			{
				this.scrollViewRef.scrollTo({ ...position, animated });
			}
		}

		#renderContactsListArea()
		{
			const inputs = {};
			let readyCount = 0;
			const renderedItems = this.usersToInvite.map((user, index) => new NameCheckerItem({
				user,
				index,
				onChange: (changedUserData) => {
					const targetIndex = this.usersToInvite.findIndex((userToInvite) => userToInvite.id === changedUserData.id);
					if (targetIndex > -1)
					{
						this.usersToInvite[targetIndex] = {
							...this.usersToInvite[targetIndex],
							...changedUserData,
						};
					}
				},
				onDidMount: (instance) => {
					inputs[index] = instance.getInputsRefs();
					readyCount++;
					if (readyCount === this.usersToInvite.length)
					{
						const result = [];
						for (let i = 0; i < this.usersToInvite.length; i++)
						{
							result.push(...inputs[i]);
						}
						this.inputs = result;
					}
				},
				onInputFocus: (input) => {
					this.focusedInputIndex = this.inputs.indexOf(input);
				},
				onInputSubmit: (input) => {
					this.focusNextInput();
				},
			}));

			if (this.department)
			{
				renderedItems.unshift(this.renderDepartmentCard());
			}

			return ScrollView(
				{
					ref: (ref) => {
						this.scrollViewRef = ref;
					},
					testId: `${this.testId}-contacts-list-scroll-view`,
					style: {
						flex: 1,
						width: '100%',
						borderBottomWidth: 1,
						borderBottomColor: Color.bgSeparatorPrimary.toHex(),
					},
					showsVerticalScrollIndicator: true,
				},
				Area(
					{
						testId: `${this.testId}-contacts-list-area`,
						style: {
							width: '100%',
						},
					},
					...renderedItems,
				),
			);
		}

		#renderInfoArea()
		{
			return Area(
				{
					testId: `${this.testId}-info-area`,
					isFirst: true,
					divider: true,
					style: {
						width: '100%',
						flexDirection: 'row',
					},
					onClick: () => {},
				},
				this.#renderInfoAreaGraphics(),
				this.#renderInfoAreaText(),
			);
		}

		#renderInfoAreaGraphics()
		{
			const uri = makeLibraryImagePath('name-checker-info.svg', 'invite', 'intranet');

			return Image({
				testId: `${this.testId}-info-area-image`,
				style: {
					width: 78,
					height: 78,
				},
				svg: {
					resizeMode: 'contain',
					uri,
				},
			});
		}

		#renderInfoAreaText()
		{
			return View(
				{
					style: {
						flex: 1,
						paddingLeft: Indent.XL3.toNumber(),
					},
				},
				Text4({
					testId: `${this.testId}-description-text`,
					color: Color.base1,
					text: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_DESCRIPTION'),
					style: {
						marginBottom: Indent.XS.toNumber(),
					},
				}),
				Text4({
					testId: `${this.testId}-sub-description-text`,
					color: Color.base3,
					text: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_SUB_DESCRIPTION'),
					style: {
						paddingTop: Indent.XS2.toNumber(),
					},
				}),
			);
		}
	}

	const openNameChecker = ({
		parentLayout = null,
		usersToInvite = [],
		department = null,
		analytics = {},
		onInviteSentHandler = null,
		onInviteError = null,
		onViewHiddenWithoutInvitingHandler = null,
		openWidgetConfig = {},
	}) => {
		const config = merge({
			enableNavigationBarBorder: false,
			titleParams: {
				text: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_TITLE'),
				type: 'dialog',
			},
			backdrop: {
				showOnTop: true,
				onlyMediumPosition: false,
				mediumPositionHeight: 500,
				bounceEnable: true,
				swipeAllowed: true,
				swipeContentAllowed: false,
				horizontalSwipeAllowed: false,
				shouldResizeContent: true,
				adoptHeightByKeyboard: true,
			},
			onReady: (readyLayout) => {
				readyLayout.showComponent(new NameChecker({
					layout: readyLayout,
					usersToInvite,
					department,
					parentLayout,
					openWidgetConfig,
					analytics,
					onInviteSentHandler,
					onInviteError,
					onViewHiddenWithoutInvitingHandler,
				}));
			},
		}, openWidgetConfig);

		if (parentLayout)
		{
			parentLayout.openWidget('layout', config);

			return;
		}

		PageManager.openWidget('layout', config);
	};

	module.exports = { openNameChecker };
});
