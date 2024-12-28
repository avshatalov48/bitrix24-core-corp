/**
 * @module layout/ui/name-checker-box
 */
jn.define('layout/ui/name-checker-box', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { merge } = require('utils/object');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Area } = require('ui-system/layout/area');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Text4, Text5 } = require('ui-system/typography/text');
	const { Color, Indent, Component } = require('tokens');
	const { NameCheckerItem } = require('layout/ui/src/name-checker-item');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { H5 } = require('ui-system/typography/heading');
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { Alert, ButtonType } = require('alert');
	const { AvatarEntityType } = require('ui-system/blocks/avatar');

	class NameChecker extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.scrollViewRef = null;
			this.inputs = [];
			this.focusedInputIndex = -1;
			this.state = {
				sendingRequest: false,
			};
		}

		get analytics()
		{
			return this.props.analytics ?? {};
		}

		get labelText()
		{
			return this.props.labelText;
		}

		get inviteButtonText()
		{
			return this.props.inviteButtonText ?? '';
		}

		get description()
		{
			return this.props.description ?? '';
		}

		get subdescription()
		{
			return this.props.subdescription ?? '';
		}

		get getItemFormattedSubDescription()
		{
			return this.props.getItemFormattedSubDescription ?? '';
		}

		get usersToInvite()
		{
			return this.props.usersToInvite ?? [];
		}

		get alreadyInvitedUsers()
		{
			return this.props.alreadyInvitedUsers ?? [];
		}

		get layout()
		{
			return this.props.layout;
		}

		get dismissAlert()
		{
			return this.props.dismissAlert;
		}

		get testId()
		{
			return 'name-checker';
		}

		get avatarEntityType()
		{
			return this.props.avatarEntityType;
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

			this.layout.on('preventDismiss', () => {
				if (this.props.dismissAlert)
				{
					this.showConfirmOnBoxClosing();
				}
				else
				{
					this.layout.close();
				}
			});
		}

		render()
		{
			const hasAlreadyInvitedUsers = this.#hasAlreadyInvitedUsers();

			return Box(
				{
					resizableByKeyboard: true,
					safeArea: { bottom: true },
					footer: this.#renderInviteButton(),
					style: {
						flex: 1,
					},
				},
				AreaList(
					{
						testId: this.#getTestId('area-list'),
						ref: this.#bindScrollViewRef,
						style: {
							flex: 1,
							borderBottomWidth: 1,
							borderBottomColor: Color.bgSeparatorPrimary.toHex(),
						},
						showsVerticalScrollIndicator: true,
					},
					this.#renderInfoArea(),
					...this.#renderContactsListAreas(),
					hasAlreadyInvitedUsers && this.#renderExistingsUsersArea(),
				),
			);
		}

		#getTestId = (suffix) => {
			const prefix = this.testId;

			return suffix ? `${prefix}-${suffix}` : prefix;
		};

		showConfirmOnBoxClosing()
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
							this.layout.close();
						},
					},
					{
						type: ButtonType.DEFAULT,
						text: this.dismissAlert.defaultButtonText,
					}],
			);
		}

		#hasAlreadyInvitedUsers()
		{
			return this.alreadyInvitedUsers.length > 0;
		}

		#renderExistingsUsersArea()
		{
			return Area(
				{
					testId: this.#getTestId('existing-users-area'),
				},
				this.#renderExistingUsersTitle(),
				this.#renderExistingUsersSubTitle(),
			);
		}

		#renderExistingUsersTitle = () => {
			return H5({
				testId: this.#getTestId('existing-users-title'),
				text: Loc.getMessage('NAME_CHECKER_EXISTING_USERS_TITLE'),
				color: Color.base2,
				numberOfLines: 2,
				ellipsize: 'end',
			});
		};

		#renderExistingUsersSubTitle = () => {
			return Text5({
				testId: this.#getTestId('existing-users-subtitle'),
				text: Loc.getMessage('NAME_CHECKER_EXISTING_USERS_SUBTITLE', {
					'#users#': this.#getAlreadyExistingUsersString(),
				}),
				color: Color.base2,
				style: {
					marginTop: Indent.XS.toNumber(),
				},
			});
		};

		#getAlreadyExistingUsersString = () => {
			if (this.props.getAlreadyInvitedUsersStringForSubtitle)
			{
				return this.props.getAlreadyInvitedUsersStringForSubtitle(this.alreadyInvitedUsers);
			}

			return '';
		};

		#bindScrollViewRef = (ref) => {
			this.scrollViewRef = ref;
		};

		#renderInviteButton()
		{
			return BoxFooter(
				{
					safeArea: Application.getPlatform() === 'ios',
					keyboardButton: {
						text: this.inviteButtonText,
						loading: this.state.sendingRequest,
						badge: BadgeCounter({
							value: this.usersToInvite?.length,
							design: BadgeCounterDesign.WHITE,
						}),
						onClick: this.onSendInviteButtonClick,
					},
				},
				Button({
					testId: this.#getTestId('invite-button'),
					text: this.inviteButtonText,
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					loading: this.state.sendingRequest,
					stretched: true,
					badge: BadgeCounter({
						value: this.usersToInvite?.length,
						design: BadgeCounterDesign.WHITE,
					}),
					style: {
						paddingVertical: 0,
					},
					onClick: this.onSendInviteButtonClick,
				}),
			);
		}

		onSendInviteButtonClick = async () => {
			if (this.state.sendingRequest)
			{
				return;
			}

			this.enableSendButtonLoadingIndicator(true);

			if (this.props.onSendInviteButtonClick)
			{
				this.props.onSendInviteButtonClick(this.usersToInvite, this.alreadyInvitedUsers);
			}
		};

		enableSendButtonLoadingIndicator(enable = true)
		{
			this.setState({
				sendingRequest: enable,
			});
		}

		close()
		{
			this.props.layout.close();
		}

		renderLabelCard()
		{
			return Card(
				{
					testId: this.#getTestId('label-card'),
					border: false,
					style: {
						marginTop: Component.areaPaddingT.toNumber(),
						justifyContent: 'center',
						alignItems: 'center',
					},
					design: CardDesign.PRIMARY,
				},
				ChipButton({
					testId: this.#getTestId('label-chip-status'),
					compact: true,
					color: Color.accentMainPrimary,
					mode: ChipButtonMode.OUTLINE,
					design: ChipButtonDesign.PRIMARY,
					text: this.labelText,
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

		#renderContactsListAreas()
		{
			this.tempInputs = {};
			this.readyCount = 0;
			const renderedItems = this.usersToInvite.map((user, index) => {
				return new NameCheckerItem({
					user,
					index,
					getItemFormattedSubDescription: this.getItemFormattedSubDescription,
					avatarEntityType: this.avatarEntityType,
					onChange: this.#nameCheckerItemOnChange,
					onDidMount: this.#nameCheckerItemOnDidMount,
					onInputFocus: this.#nameCheckerItemOnInputFocus,
					onInputSubmit: this.#nameCheckerItemOnInputSubmit,
				});
			});

			if (this.labelText)
			{
				renderedItems.unshift(this.renderLabelCard());
			}

			return renderedItems;
		}

		#nameCheckerItemOnDidMount = (instance, index) => {
			this.tempInputs[index] = instance.getInputsRefs();
			this.readyCount++;
			if (this.readyCount === this.usersToInvite.length)
			{
				const result = [];
				for (let i = 0; i < this.usersToInvite.length; i++)
				{
					result.push(...this.tempInputs[i]);
				}
				this.inputs = result;
			}
		};

		#nameCheckerItemOnInputSubmit = () => {
			this.focusNextInput();
		};

		#nameCheckerItemOnChange = (changedUserData) => {
			const targetIndex = this.usersToInvite.findIndex((userToInvite) => userToInvite.id === changedUserData.id);
			if (targetIndex > -1)
			{
				this.usersToInvite[targetIndex] = {
					...this.usersToInvite[targetIndex],
					...changedUserData,
				};
			}
		};

		#nameCheckerItemOnInputFocus = (input) => {
			this.focusedInputIndex = this.inputs.indexOf(input);
		};

		#renderInfoArea()
		{
			return Area(
				{
					testId: this.#getTestId('info-area'),
					isFirst: true,
					divider: true,
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				this.#renderInfoAreaGraphics(),
				this.#renderInfoAreaText(),
			);
		}

		#renderInfoAreaGraphics()
		{
			const uri = this.props.infoAreaGraphicsUri ?? makeLibraryImagePath('description-graphics.svg', 'name-checker-box');

			return Image({
				testId: this.#getTestId('info-area-image'),
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
					testId: this.#getTestId('description-text'),
					color: Color.base1,
					text: this.description,
					style: {
						marginBottom: Indent.XS.toNumber(),
					},
				}),
				this.subdescription !== '' && Text4({
					testId: this.#getTestId('sub-description-text'),
					color: Color.base3,
					text: this.subdescription,
					style: {
						paddingTop: Indent.XS2.toNumber(),
					},
				}),
			);
		}
	}

	/**
	 * @param {Object} params
	 * @param {Layout} [params.parentLayout]
	 * @param {Array} [params.usersToInvite]
	 * @param {Array} [params.alreadyInvitedUsers]
	 * @param {Object|AnalyticsEvent} [params.analytics]
	 * @param {Object} [params.openWidgetConfig]
	 * @param {String} [params.labelText]
	 * @param {String} [params.inviteButtonText]
	 * @param {String} [params.boxTitle]
	 * @param {String} [params.description]
	 * @param {String} [params.subdescription]
	 * @param {Function} [params.onSendInviteButtonClick]
	 * @param {Function} [params.getItemFormattedSubDescription]
	 * @param {Function} [params.getAlreadyInvitedUsersStringForSubtitle]
	 * @param {String} [params.infoAreaGraphicsUri]
	 * @param {Object} [params.dismissAlert]
	 * @param {String} [params.dismissAlert.title]
	 * @param {String} [params.dismissAlert.description]
	 * @param {String} [params.dismissAlert.destructiveButtonText]
	 * @param {String} [params.dismissAlert.defaultButtonText]
	 * @returns {Promise<NameChecker>}
	 */
	const openNameChecker = ({
		parentLayout = null,
		usersToInvite = [],
		alreadyInvitedUsers = [],
		analytics = {},
		openWidgetConfig = {},
		labelText = null,
		inviteButtonText = null,
		boxTitle = null,
		description = null,
		subdescription = null,
		onSendInviteButtonClick = null,
		getItemFormattedSubDescription = null,
		getAlreadyInvitedUsersStringForSubtitle = null,
		infoAreaGraphicsUri = null,
		dismissAlert = null,
		avatarEntityType = AvatarEntityType.USER,
	}) => {
		return new Promise((resolve) => {
			const config = merge({
				enableNavigationBarBorder: false,
				titleParams: {
					text: boxTitle,
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
					readyLayout.preventBottomSheetDismiss(true);
					const instance = new NameChecker({
						layout: readyLayout,
						usersToInvite,
						alreadyInvitedUsers,
						parentLayout,
						openWidgetConfig,
						analytics,
						labelText,
						inviteButtonText,
						description,
						subdescription,
						onSendInviteButtonClick,
						getItemFormattedSubDescription,
						getAlreadyInvitedUsersStringForSubtitle,
						infoAreaGraphicsUri,
						dismissAlert,
						avatarEntityType,
					});
					readyLayout.showComponent(instance);
					resolve(instance);
				},
			}, openWidgetConfig);

			if (parentLayout)
			{
				parentLayout.openWidget('layout', config);

				return;
			}

			PageManager.openWidget('layout', config);
		});
	};

	module.exports = { openNameChecker };
});
