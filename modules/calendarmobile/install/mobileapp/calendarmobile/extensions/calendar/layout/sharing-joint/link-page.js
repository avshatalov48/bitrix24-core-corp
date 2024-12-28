/**
 * @module calendar/layout/sharing-joint/link-page
 */
jn.define('calendar/layout/sharing-joint/link-page', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { dots, chevronLeft } = require('assets/common');
	const { withPressed } = require('utils/color');
	const { copyToClipboard } = require('utils/copy');
	const { Moment } = require('utils/date');
	const { date } = require('utils/date/formats');
	const { Avatar } = require('layout/ui/user/avatar');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { openUserProfile } = require('user/profile');

	class LinkPage extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.onHeaderClickHandler = this.onHeaderClickHandler.bind(this);
			this.showMenu = this.showMenu.bind(this);
			this.onDeleteMenuItemClickHandler = this.onDeleteMenuItemClickHandler.bind(this);
			this.onCopyMenuItemClickHandler = this.onCopyMenuItemClickHandler.bind(this);

			this.layoutWidget = props.layoutWidget;
		}

		get link()
		{
			return this.props.link;
		}

		get deleteLink()
		{
			return this.props.deleteLink;
		}

		get sendLink()
		{
			return this.props.sendLink;
		}

		render()
		{
			return View(
				{
					safeArea: {
						bottom: true,
					},
				},
				this.renderHeaderContainer(),
				this.renderMembers(),
				this.renderSendButton(),
			);
		}

		renderHeaderContainer()
		{
			return View(
				{
					style: styles.headerContainer,
				},
				this.renderHeader(),
				this.renderDots(),
			);
		}

		renderHeader()
		{
			return View(
				{
					style: styles.header,
					onClick: this.onHeaderClickHandler,
				},
				this.renderLeftArrow(),
				View(
					{
						style: styles.headerInfo,
					},
					this.renderHeaderTitle(),
					this.renderDateCreate(),
				),
			);
		}

		onHeaderClickHandler()
		{
			this.layoutWidget.close();
		}

		renderLeftArrow()
		{
			return Image({
				tintColor: AppTheme.colors.base3,
				svg: {
					content: chevronLeft(),
				},
				style: styles.leftArrowIcon,
			});
		}

		renderHeaderTitle()
		{
			return Text({
				text: Loc.getMessage('CALENDARMOBILE_SHARING_JOINT_TITLE'),
				style: styles.headerTitle,
			});
		}

		renderDateCreate()
		{
			const moment = Moment.createFromTimestamp(new Date(this.link.dateCreate).getTime() / 1000);

			return Text({
				text: Loc.getMessage('CALENDARMOBILE_SHARING_LINK_CREATED_AT_DATE', {
					'#DATE#': moment.format(date),
				}),
				style: styles.dateCreate,
			});
		}

		renderDots()
		{
			return View(
				{
					style: styles.dotsContainer,
					onClick: this.showMenu,
				},
				Image({
					tintColor: AppTheme.colors.base6,
					svg: {
						content: dots(),
					},
					style: styles.dotsIcon,
				}),
			);
		}

		showMenu()
		{
			const actions = [
				{
					id: 'calendar_sharing_menu_action_copy_joint_link',
					title: Loc.getMessage('CALENDARMOBILE_SHARING_COPY_LINK'),
					onClickCallback: this.onCopyMenuItemClickHandler,
				},
				{
					id: 'calendar_sharing_menu_action_delete_joint_link',
					title: Loc.getMessage('CALENDARMOBILE_SHARING_DELETE_LINK'),
					onClickCallback: this.onDeleteMenuItemClickHandler,
				},
			];

			this.menu = new ContextMenu({ actions });

			this.menu.show(this.layoutWidget);
		}

		onCopyMenuItemClickHandler()
		{
			this.menu.close(() => {
				copyToClipboard(this.link.shortUrl, Loc.getMessage('CALENDARMOBILE_SHARING_LINK_COPIED'));
			});
		}

		async onDeleteMenuItemClickHandler()
		{
			await this.deleteLink();
			this.menu.close(() => this.layoutWidget.close());
		}

		renderMembers()
		{
			return View(
				{
					style: styles.membersContainer,
				},
				ScrollView(
					{
						style: {
							flex: 1,
						},
					},
					View(
						{},
						...this.link.members.map((user) => this.renderMember(user)),
					),
				),
			);
		}

		renderMember(user)
		{
			return View(
				{},
				View(
					{
						style: styles.member,
						onClick: () => {
							void openUserProfile({ userId: user.id, parentWidget: this.layoutWidget });
						},
					},
					this.renderMemberAvatar(user),
					this.renderMemberName(user),
				),
			);
		}

		renderMemberAvatar(user)
		{
			return Avatar({
				size: avatarSize,
				image: this.isAvatar(user.avatar) ? user.avatar : null,
				additionalStyles: {
					wrapper: styles.memberAvatarWrapper,
				},
			});
		}

		isAvatar(imageUrl)
		{
			return imageUrl !== '/bitrix/images/1.gif' && imageUrl !== '';
		}

		renderMemberName(user)
		{
			return Text({
				text: this.formatUserName(user),
				style: styles.memberName,
				numberOfLines: 1,
				ellipsize: 'end',
			});
		}

		formatUserName(user)
		{
			return `${user.name} ${user.lastName || ''}`.trim();
		}

		renderSendButton()
		{
			return View(
				{
					style: styles.sendButtonContainer,
					onClick: this.sendLink,
				},
				Text({
					style: styles.sendButtonText,
					text: Loc.getMessage('CALENDARMOBILE_SHARING_SEND_SLOTS'),
				}),
			);
		}
	}

	const avatarSize = 40;

	const styles = {
		headerContainer: {
			flexDirection: 'row',
			marginVertical: 12,
			paddingLeft: 16,
		},
		header: {
			flex: 1,
			flexDirection: 'row',
		},
		leftArrowIcon: {
			width: 23,
			height: 23,
		},
		headerInfo: {
			flex: 1,
			marginLeft: 10,
		},
		headerTitle: {
			fontSize: 17,
			color: AppTheme.colors.base1,
		},
		dateCreate: {
			fontSize: 12,
			color: AppTheme.colors.base3,
		},
		dotsContainer: {
			height: '100%',
			justifyContent: 'center',
			paddingHorizontal: 16,
		},
		dotsIcon: {
			width: 20,
			height: 6,
		},
		membersContainer: {
			flex: 1,
		},
		member: {
			flexDirection: 'row',
			paddingTop: 13,
			paddingBottom: 13,
			paddingLeft: 18,
			backgroundColor: withPressed(AppTheme.colors.bgContentPrimary),
			borderBottomWidth: 1,
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
		},
		memberAvatarWrapper: {
			width: avatarSize,
			height: avatarSize,
			backgroundColor: AppTheme.colors.base5,
			borderRadius: avatarSize,
		},
		memberName: {
			color: AppTheme.colors.base1,
			fontSize: 16,
			marginLeft: 12,
			flex: 1,
		},
		sendButtonContainer: {
			flex: 0,
			alignItems: 'center',
			marginVertical: 11,
			marginHorizontal: 40,
			paddingVertical: 11,
			borderRadius: 12,
			backgroundColor: withPressed(AppTheme.colors.accentMainPrimaryalt),
		},
		sendButtonText: {
			fontSize: 17,
			fontWeight: '500',
			ellipsize: 'end',
			numberOfLines: 1,
			color: AppTheme.colors.baseWhiteFixed,
		},
	};

	module.exports = { LinkPage };
});
