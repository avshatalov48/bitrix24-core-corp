/**
 * @module calendar/layout/sharing-settings/members
 */
jn.define('calendar/layout/sharing-settings/members', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { EntitySelectorFactory } = require('selector/widget/factory');

	const { SharingContext } = require('calendar/model/sharing');
	const { SharingSettingsCard } = require('calendar/layout/sharing-settings/card');
	const { Avatars } = require('calendar/layout/avatars');
	const { Icons } = require('calendar/layout/icons');
	const { Analytics } = require('calendar/sharing/analytics');

	/**
	 * @class SharingSettingsMembers
	 */
	class SharingSettingsMembers extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.openMembersEditBackdrop = this.openMembersEditBackdrop.bind(this);
			this.onJointMembersUpdated = this.onJointMembersUpdated.bind(this);
			this.onJointLinkCreated = this.onJointLinkCreated.bind(this);
			this.onMembersSelectorCloseHandler = this.onMembersSelectorCloseHandler.bind(this);
			this.onMembersSelectorChangeHandler = this.onMembersSelectorChangeHandler.bind(this);

			this.state = this.getState();
		}

		componentDidMount()
		{
			this.bindEvents();
		}

		componentWillUnmount()
		{
			this.unbindEvents();
		}

		bindEvents()
		{
			this.model.on('CalendarSharing:JointMembersUpdated', this.onJointMembersUpdated);
			this.model.on('CalendarSharing:JointLinkCreated', this.onJointLinkCreated);
		}

		unbindEvents()
		{
			this.model.off('CalendarSharing:JointMembersUpdated', this.onJointMembersUpdated);
			this.model.off('CalendarSharing:JointLinkCreated', this.onJointLinkCreated);
		}

		get model()
		{
			return this.props.model;
		}

		get layoutWidget()
		{
			return this.props.layoutWidget;
		}

		onJointMembersUpdated()
		{
			this.redraw();
		}

		onJointLinkCreated()
		{
			setTimeout(() => this.redraw(), 1000);
		}

		redraw()
		{
			this.setState(this.getState());
		}

		getState()
		{
			return {
				members: this.model.getMembers(),
			};
		}

		render()
		{
			return SharingSettingsCard(
				{
					clickable: true,
					onClick: this.openMembersEditBackdrop,
				},
				this.renderHeader(),
				this.renderMembers(),
			);
		}

		openMembersEditBackdrop()
		{
			void EntitySelectorFactory.createByType(EntitySelectorFactory.Type.USER, {
				provider: {
					context: 'CALENDAR_SHARING_MEMBERS',
				},
				canUseRecent: true,
				createOptions: {
					enableCreation: false,
				},
				selectOptions: {
					canUnselectLast: false,
				},
				initSelectedIds: this.state.members.map((user) => user.id),
				undeselectableIds: [this.model.getUserInfo().id],
				allowMultipleSelection: true,
				events: {
					onSelectedChanged: this.onMembersSelectorChangeHandler,
					onViewHidden: this.onMembersSelectorCloseHandler,
				},
				widgetParams: {
					title: Loc.getMessage('M_CALENDAR_SETTINGS_LINK_MEMBERS'),
					backdrop: {
						mediumPositionPercent: 100,
						horizontalSwipeAllowed: false,
					},
				},
			}).show({}, this.layoutWidget);
		}

		onMembersSelectorChangeHandler(items)
		{
			this.updateUsers(items);
		}

		onMembersSelectorCloseHandler()
		{
			Analytics.sendMembersAdded(this.model.getContext(), this.state.members.length);
		}

		updateUsers(items)
		{
			const users = items.map((item) => ({
				id: item.id,
				avatar: item.imageUrl,
				name: item.title,
			}));

			this.model.setMembers(users);
		}

		renderHeader()
		{
			return View(
				{
					style: styles.header,
				},
				this.renderPeopleIcon(),
				this.renderHeaderTitle(),
			);
		}

		renderPeopleIcon()
		{
			const isCalendarContext = this.model.getContext() === SharingContext.CALENDAR;

			return View(
				{
					style: styles.peopleIconContainer,
				},
				Image({
					tintColor: isCalendarContext ? AppTheme.colors.accentMainPrimary : AppTheme.colors.base2,
					svg: {
						content: Icons.people,
					},
					style: styles.peopleIcon,
				}),
			);
		}

		renderHeaderTitle()
		{
			return Text({
				style: styles.headerTitle,
				text: Loc.getMessage('M_CALENDAR_SETTINGS_CREATE_JOINT_SLOTS'),
			});
		}

		renderMembers()
		{
			return View(
				{
					style: styles.members,
				},
				new Avatars({
					avatars: this.state.members.map((user) => user.avatar),
					size: avatarSize,
				}),
				this.renderPlus(),
			);
		}

		renderPlus()
		{
			const isCalendarContext = this.model.getContext() === SharingContext.CALENDAR;
			const backgroundColor = isCalendarContext ? AppTheme.colors.accentSoftBlue2 : AppTheme.colors.base7;
			const iconColor = isCalendarContext ? AppTheme.colors.accentMainLinks : AppTheme.colors.base1;

			return View(
				{
					style: styles.plusContainer,
				},
				Image({
					svg: {
						content: icons.plus(backgroundColor, iconColor),
					},
					style: styles.plusIcon,
				}),
			);
		}
	}

	const avatarSize = 36;

	const styles = {
		avatarSize: 36,
		header: {
			flexDirection: 'row',
			marginBottom: 10,
		},
		peopleIconContainer: {
			width: 40,
		},
		peopleIcon: {
			width: 24,
			height: 24,
		},
		headerTitle: {
			fontSize: 15,
			color: AppTheme.colors.base1,
			flex: 1,
		},
		members: {
			paddingHorizontal: 40,
			flexDirection: 'row',
		},
		plusContainer: {
			width: avatarSize,
			marginLeft: 5,
			marginRight: 1,
		},
		plusIcon: {
			width: avatarSize,
			height: avatarSize,
		},
	};

	const icons = {
		plus: (backgroundColor, iconColor) => `<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="18" cy="18" r="18" fill="${backgroundColor}"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M18.0001 11.05C18.359 11.05 18.6501 11.3411 18.6501 11.7V17.35H24.3C24.659 17.35 24.95 17.6411 24.95 18.0001C24.95 18.359 24.659 18.6501 24.3 18.6501H18.6501V24.3C18.6501 24.659 18.359 24.95 18.0001 24.95C17.6411 24.95 17.3501 24.659 17.3501 24.3V18.6501H11.7C11.3411 18.6501 11.05 18.359 11.05 18.0001C11.05 17.6411 11.3411 17.35 11.7 17.35H17.3501V11.7C17.3501 11.3411 17.6411 11.05 18.0001 11.05Z" fill="${iconColor}"/></svg>`,
	};

	module.exports = { SharingSettingsMembers };
});
