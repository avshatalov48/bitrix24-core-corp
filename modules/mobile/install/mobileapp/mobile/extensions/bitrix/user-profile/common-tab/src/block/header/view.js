/**
 * @module user-profile/common-tab/src/block/header/view
 */
jn.define('user-profile/common-tab/src/block/header/view', (require, exports, module) => {
	const { Avatar, AvatarEntityType } = require('ui-system/blocks/avatar');
	const { connect } = require('statemanager/redux/connect');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { H3 } = require('ui-system/typography/heading');
	const { Text5, Text6 } = require('ui-system/typography/text');
	const { BBCodeText } = require('ui-system/typography/bbcodetext');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { createTestIdGenerator } = require('utils/test');
	const { Type } = require('type');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { DialogOpener } = require('im/messenger/api/dialog-opener');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { FormatterTypes } = require('layout/ui/friendly-date/formatter-factory');
	const { Moment } = require('utils/date');
	const store = require('statemanager/redux/store');
	const { UserStatus } = require('user-profile/common-tab/enum/user-status');
	const { BadgeButton, BadgeButtonDesign, BadgeButtonSize } = require('ui-system/blocks/badges/button');
	const { AvatarPicker } = require('avatar-picker');
	const { Alert } = require('alert');

	/**
	 * @typedef {Object} HeaderProps
	 * @property {number} id
	 * @property {string} fullName
	 * @property {string} workPosition
	 * @property {string} statusText
	 * @property {string} GMTString
	 * @property {Date} lastSeenDate
	 * @property {string} personalGender
	 * @property {string} onVacationDateTo
	 * @property {string} status
	 * @property {string} [testId]

	 * @class Header
	 */
	class Header extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: 'header-block',
				context: this,
			});
			this.avatarPicker = new AvatarPicker();
			this.#initState(props);
		}

		componentWillReceiveProps(props)
		{
			this.#initState(props);
		}

		#initState = (props) => {
			this.state = {
				image: props.image ?? null,
			};
		};

		get personalGender()
		{
			const { personalGender } = this.props;
			if (personalGender === 'M' || personalGender === 'F')
			{
				return personalGender;
			}

			return null;
		}

		render()
		{
			const { isEditMode } = this.props;

			const editStyles = isEditMode ? {
				paddingTop: Indent.XL.toNumber(),
				paddingBottom: Indent.XL2.toNumber(),
			} : {};

			return View(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
						...editStyles,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					this.#renderAvatar(),
					this.#renderTextContent(),
				),
				!isEditMode && this.#renderButtons(),
			);
		}

		#getAvatarEntityType()
		{
			const { isCollaber, isExtranet } = this.props;

			if (isCollaber)
			{
				return AvatarEntityType.COLLAB;
			}

			if (isExtranet)
			{
				return AvatarEntityType.EXTRANET;
			}

			return AvatarEntityType.USER;
		}

		#renderAvatar = () => {
			const { isEditMode } = this.props;
			const { image } = this.state;
			const avatarProps = {
				testId: this.getTestId(`avatar-edit-${isEditMode}`),
				size: 84,
				accent: !isEditMode,
				onClick: this.#onAvatarClick,
				uri: image?.previewUrl ?? null,
				entityType: this.#getAvatarEntityType(),
			};

			return View(
				{
					testId: this.getTestId('avatar-container'),
				},
				Avatar(avatarProps),
				isEditMode && this.#renderEditButton(),
				!isEditMode && this.#renderStatusIcon(),
			);
		};

		#onAvatarClick = () => {
			const { isEditMode } = this.props;
			if (isEditMode)
			{
				this.#pickAvatar();
			}
			else
			{
				this.#showAvatarInViewer();
			}
		};

		#showAvatarInViewer = () => {
			const { image } = this.state;
			const { fullName } = this.props;
			const url = image?.originalImage ?? image?.previewUrl;
			if (url)
			{
				viewer.openImage(url, fullName);
			}
		};

		#pickAvatar = () => {
			const { isEditMode, onChange } = this.props;
			if (!isEditMode)
			{
				return;
			}

			this.avatarPicker.open()
				.then((image) => {
					if (image)
					{
						this.setState({ image }, () => {
							onChange?.('header', {
								image,
							});
						});
					}
				})
				.catch((err) => {
					console.error(err);
					Alert.alert(
						Loc.getMessage('M_PROFILE_AVATAR_CHOOSE_ERROR_TITLE'),
						Loc.getMessage('M_PROFILE_AVATAR_CHOOSE_ERROR_TEXT'),
						() => {},
					);
				});
		};

		#renderEditButton = () => {
			return View(
				{
					style: {
						position: 'absolute',
						top: 0,
						right: 0,
					},
				},
				BadgeButton({
					testId: this.getTestId('edit-badge-button'),
					size: BadgeButtonSize.XL,
					icon: Icon.EDIT,
					design: BadgeButtonDesign.WHITE,
					onClick: this.#pickAvatar,
				}),
			);
		};

		#renderStatusIcon()
		{
			const { status } = this.props;
			const size = 30;

			return View(
				{
					testId: this.getTestId('status-icon-container'),
					style: {
						position: 'absolute',
						bottom: 0,
						right: 0,
						height: size,
						width: size,
						zIndex: 1,
						backgroundColor: Color.bgContentPrimary.toHex(),
						borderRadius: size / 2,
					},
				},
				IconView({
					testId: this.getTestId('status-icon'),
					icon: status.getIcon(),
					color: status.getIconColor(),
					size,
				}),
			);
		}

		#renderTextContent()
		{
			const { isEditMode } = this.props;

			return View(
				{
					testId: this.getTestId('avatar'),
					style: {
						paddingLeft: Indent.XL2.toNumber(),
						justifyContent: 'center',
						flex: 1,
					},
				},
				this.#renderFullName(),
				this.#renderWorkPosition(),
				!isEditMode && this.#renderStatusText(),
			);
		}

		#renderFullName()
		{
			const { fullName } = this.props;

			return H3({
				testId: this.getTestId('full-name'),
				accent: true,
				color: this.#getFullNameColor(),
				text: fullName,
			});
		}

		#getFullNameColor()
		{
			const { isCollaber, isExtranet } = this.props;

			if (isCollaber)
			{
				return Color.collabAccentPrimaryAlt;
			}

			if (isExtranet)
			{
				return Color.accentExtraOrange;
			}

			return Color.base1;
		}

		#renderWorkPosition()
		{
			const { workPosition } = this.props;
			if (Type.isNil(workPosition) || workPosition === '')
			{
				return null;
			}

			return Text5({
				testId: this.getTestId('work-position'),
				text: workPosition,
				color: Color.base2,
				style: {
					marginTop: Indent.XS2.toNumber(),
				},
			});
		}

		#renderStatusText()
		{
			const { status, lastSeenDate } = this.props;
			if ((status === UserStatus.OFFLINE) && !Type.isNil(lastSeenDate) && lastSeenDate !== 0)
			{
				return View(
					{
						style: {
							marginTop: Indent.XS2.toNumber(),
							flexDirection: 'row',
						},
					},
					new FriendlyDate({
						moment: Moment.createFromTimestamp(lastSeenDate),
						showTime: true,
						useTimeAgo: true,
						futureAllowed: false,
						formatType: FormatterTypes.HUMAN_DATE,
						renderContent: this.#renderOfflineStatusFriendlyDateContent,
					}),
				);
			}

			return BBCodeText({
				testId: this.getTestId('status-text'),
				value: this.#getStatusText(),
				color: Color.base3,
				size: 6,
				style: {
					marginTop: Indent.XS2.toNumber(),
				},
			});
		}

		#renderOfflineStatusFriendlyDateContent = ({ state }) => {
			const text = Type.isNil(this.personalGender)
				? Loc.getMessage('M_PROFILE_USER_STATUS_OFFLINE_WITH_TIME_NO_GENDER', {
					'#DATE#': state.text,
				})
				: Loc.getMessage(`M_PROFILE_USER_STATUS_OFFLINE_WITH_TIME_${this.personalGender}`, {
					'#DATE#': state.text,
				});

			return Text6({
				text,
				color: Color.base3,
			});
		};

		#getStatusText()
		{
			const {
				GMTString,
				onVacationDateTo,
				status,
			} = this.props;

			switch (status)
			{
				case UserStatus.FIRED:
					return Loc.getMessage('M_PROFILE_USER_STATUS_FIRED');
				case UserStatus.ON_VACATION:
					return Loc.getMessage('M_PROFILE_USER_STATUS_ON_VACATION', {
						'#DATE_TO#': onVacationDateTo,
					});
				case UserStatus.DND:
					return Loc.getMessage('M_PROFILE_USER_STATUS_DND', {
						'#COLOR#': Color.base5,
						'#GMT#': GMTString,
					});
				case UserStatus.ONLINE:
					return Loc.getMessage('M_PROFILE_USER_STATUS_ONLINE', {
						'#COLOR#': Color.base5,
						'#GMT#': GMTString,
					});
				case UserStatus.OFFLINE:
					return Loc.getMessage('M_PROFILE_USER_STATUS_OFFLINE');
				default:
					return '';
			}
		}

		#renderButtons()
		{
			const { status } = this.props;
			const isFiredUser = status === UserStatus.FIRED;
			const isProfileOfCurrentUser = this.#isProfileOfCurrentUser();

			return View(
				{
					testId: this.getTestId('buttons-view'),
					style: {
						flexDirection: 'row',
						marginTop: Indent.XL2.toNumber(),
					},
				},
				isProfileOfCurrentUser && Button({
					testId: this.getTestId('notes-button'),
					text: Loc.getMessage('M_PROFILE_HEADER_NOTES_BUTTON_TEXT'),
					design: ButtonDesign.OUTLINE_ACCENT_2,
					size: ButtonSize.L,
					stretched: true,
					leftIcon: Icon.BOOKMARK,
					onClick: this.#onNotesButtonClick,
				}),
				!isProfileOfCurrentUser && Button({
					testId: this.getTestId('chat-button'),
					text: isFiredUser
						? Loc.getMessage('M_PROFILE_HEADER_CHAT_BUTTON_FIRED_TEXT')
						: Loc.getMessage('M_PROFILE_HEADER_CHAT_BUTTON_TEXT'),
					design: ButtonDesign.OUTLINE_ACCENT_2,
					size: ButtonSize.L,
					stretched: true,
					style: {
						marginRight: Indent.XL.toNumber(),
						flex: 1,
					},
					onClick: this.#onChatButtonClick,
				}),
				!isProfileOfCurrentUser && !isFiredUser && Button({
					testId: this.getTestId('video-call-button'),
					text: Loc.getMessage('M_PROFILE_HEADER_VIDEO_CALL_BUTTON_TEXT'),
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					stretched: true,
					style: {
						flex: 1,
					},
					onClick: this.#onVideoCallButtonClick,
				}),
			);
		}

		#onVideoCallButtonClick = () => {
			this.createCall();
		};

		createCall()
		{
			const { id } = this.props;
			const calleeUser = usersSelector.selectById(store.getState(), id);
			const chatData = {
				dialogId: id,
				name: calleeUser.fullName,
				avatar: calleeUser.avatarSize100,
			};
			const eventData = {
				userId: id,
				video: true,
				chatData,
			};

			BX.postComponentEvent('onCallInvite', [eventData], 'calls');
		}

		#onNotesButtonClick = () => {
			void DialogOpener.open({
				dialogId: env.userId,
			});
		};

		#onChatButtonClick = () => {
			const { id } = this.props;

			void DialogOpener.open({
				dialogId: id,
			});
		};

		#isProfileOfCurrentUser()
		{
			const { id } = this.props;

			return String(id) === String(env.userId);
		}
	}

	const mapStateToProps = (state, {
		ownerId,
		GMTString,
		lastSeenDate,
		onVacationDateTo,
		status,
	}) => {
		const {
			id,
			fullName,
			workPosition,
			avatarSize100,
			avatarSizeOriginal,
			isCollaber,
			isExtranet,
		} = usersSelector.selectById(state, ownerId);

		return {
			id,
			fullName,
			workPosition,
			isCollaber,
			isExtranet,
			GMTString,
			lastSeenDate,
			onVacationDateTo,
			status,
			image: {
				previewUrl: avatarSize100,
				originalImage: avatarSizeOriginal,
			},
		};
	};

	module.exports = {
		Header: connect(mapStateToProps)(Header),
	};
});
