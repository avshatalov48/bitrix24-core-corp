/**
 * @module collab/create/src/permissions
 */
jn.define('collab/create/src/permissions', (require, exports, module) => {
	const { Box } = require('ui-system/layout/box');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Color } = require('tokens');
	const { Area } = require('ui-system/layout/area');
	const { SettingSelectorList, SettingSelectorListItemDesign } = require('layout/ui/setting-selector-list');
	const { UIMenu } = require('layout/ui/menu');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Icon } = require('assets/icons');
	const { MemberSelector } = require('im/messenger/controller/selector/member');
	const { showToast } = require('toast');

	const Permission = {
		OWNER: 'owner',
		MODERATORS: 'moderators',
		SHOW_HISTORY: 'showHistory',
		INVITERS: 'inviters',
		ALLOW_GUESTS_INVITATION: 'allowGuestsInvitation',
		MESSAGE_WRITERS: 'messageWriters',
	};

	const PermissionValueType = {
		ALL: 'K',
		OWNER_AND_MODERATORS: 'E',
		OWNER: 'A',
	};

	const PermissionBooleanValueType = {
		TRUE: 'Y',
		FALSE: 'N',
	};

	/**
	 * @typedef {Object} CollabCreatePermissionsProps
	 * @property {string} testId
	 * @property {Object} owner
	 * @property {Array<Object>} moderators
	 * @property {PermissionBooleanValueType} showHistory
	 * @property {PermissionValueType} inviters
	 * @property {PermissionBooleanValueType} allowGuestsInvitation
	 * @property {PermissionValueType} messageWriters
	 * @property {function} onChange
	 * @property {LayoutWidget} layoutWidget

	 * @class CollabCreatePermissions
	 */
	class CollabCreatePermissions extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.settingsSelectorItemsRefsMap = new Map();
			this.invitersMenu = null;
			this.messageWritersMenu = null;
			this.showHistoryMenu = null;
			this.#initializeState();
		}

		#initializeState()
		{
			const {
				owner,
				moderators,
				showHistory,
				inviters,
				allowGuestsInvitation,
				messageWriters,
			} = this.props;

			this.state = {
				owner: owner ?? {},
				moderators: moderators ?? [],
				showHistory: showHistory ?? PermissionBooleanValueType.TRUE,
				inviters: inviters ?? PermissionValueType.ALL,
				allowGuestsInvitation: allowGuestsInvitation ?? PermissionBooleanValueType.TRUE,
				messageWriters: messageWriters ?? PermissionValueType.ALL,
			};
		}

		get testId()
		{
			return `${this.props.testId}-security`;
		}

		render()
		{
			return Box(
				{
					testId: `${this.testId}-permissions-screen-box`,
					resizableByKeyboard: true,
					safeArea: { bottom: true },
					style: {
						width: '100%',
						flex: 1,
					},
				},
				AreaList(
					{
						testId: `${this.testId}-area-list`,
						style: {
							flex: 1,
							flexDirection: 'column',
							width: '100%',
						},
						resizableByKeyboard: true,
						showsVerticalScrollIndicator: true,
					},
					this.#renderPermissionsListArea(),
				),
			);
		}

		#renderPermissionsListArea()
		{
			const { canInviteCollabersInPortalSettings } = this.props;

			return Area(
				{
					testId: `${this.testId}-area-permissions-list`,
					isFirst: false,
					divider: false,
					style: {
						justifyContent: 'flex-start',
						alignItems: 'center',
					},
				},
				SettingSelectorList({
					items: [
						{
							id: Permission.OWNER,
							title: Loc.getMessage('M_COLLAB_PERMISSIONS_OWNER_ITEM_TITLE'),
							subtitle: this.state.owner?.fullName ?? '',
							design: SettingSelectorListItemDesign.OPENER,
						},
						{
							id: Permission.MODERATORS,
							title: Loc.getMessage('M_COLLAB_PERMISSIONS_MODERATORS_ITEM_TITLE'),
							subtitle: this.#getModeratorsSubTitle(),
							design: SettingSelectorListItemDesign.OPENER,
						},
						{
							id: Permission.SHOW_HISTORY,
							title: Loc.getMessage('M_COLLAB_PERMISSIONS_SHOW_HISTORY_ITEM_TITLE'),
							subtitle: this.#getSubTitleByPermissionBooleanValueType(this.state.showHistory),
							design: SettingSelectorListItemDesign.OPENER,
						},
						{
							id: Permission.INVITERS,
							title: Loc.getMessage('M_COLLAB_PERMISSIONS_INVITERS_ITEM_TITLE'),
							subtitle: this.#getSubTitleByPermissionValueType(this.state.inviters),
							design: SettingSelectorListItemDesign.OPENER,
						},
						{
							id: Permission.ALLOW_GUESTS_INVITATION,
							title: Loc.getMessage('M_COLLAB_PERMISSIONS_ALLOW_GUESTS_INVITATION_ITEM_TITLE'),
							subtitle: this.#getSubTitleByPermissionBooleanValueType(this.state.allowGuestsInvitation),
							design: SettingSelectorListItemDesign.OPENER,
							disabled: !canInviteCollabersInPortalSettings,
						},
						{
							id: Permission.MESSAGE_WRITERS,
							title: Loc.getMessage('M_COLLAB_PERMISSIONS_MESSAGE_WRITERS_ITEM_TITLE'),
							subtitle: this.#getSubTitleByPermissionValueType(this.state.messageWriters),
							design: SettingSelectorListItemDesign.OPENER,
						},
					],
					itemRef: this.#bindSettingsSelectorItemRef,
					onItemClick: this.#onPermissionsItemClick,
				}),
			);
		}

		#bindSettingsSelectorItemRef = (ref, item) => {
			this.settingsSelectorItemsRefsMap.set(item.id, {
				ref,
				item,
			});
		};

		#getPermissionValueTypesItemsForMenu(permissionKey)
		{
			return Object.keys(PermissionValueType).map((key) => ({
				id: PermissionValueType[key],
				testId: `${this.testId}-menu-item-${key.toLowerCase()}`,
				title: Loc.getMessage(`M_COLLAB_PERMISSIONS_${key}`),
				iconName: this.#getIconNameForMenu(PermissionValueType, key, permissionKey),
				iconColor: Color.accentMainPrimary,
				onItemSelected: (event, item) => {
					this.setState({
						[permissionKey]: item.id,
					}, () => this.#callOnChange());
				},
			}));
		}

		#getPermissionBooleanValueTypesItemsForMenu(permissionKey)
		{
			return Object.keys(PermissionBooleanValueType).map((key) => ({
				id: PermissionBooleanValueType[key],
				testId: `${this.testId}-menu-item-${key.toLowerCase()}`,
				title: Loc.getMessage(`M_COLLAB_PERMISSIONS_${key}`),
				iconName: this.#getIconNameForMenu(PermissionBooleanValueType, key, permissionKey),
				iconColor: Color.accentMainPrimary,
				onItemSelected: (event, item) => {
					this.setState({
						[permissionKey]: item.id,
					}, () => this.#callOnChange());
				},
			}));
		}

		#callOnChange = () => {
			const {
				owner,
				moderators,
				showHistory,
				inviters,
				allowGuestsInvitation,
				messageWriters,
			} = this.state;
			this.props.onChange?.({
				owner,
				moderators,
				showHistory,
				inviters,
				allowGuestsInvitation,
				messageWriters,
			});
		};

		#getIconNameForMenu = (targetEnum, permissionValueTypeKey, permissionKey) => {
			const valueType = targetEnum[permissionValueTypeKey];
			const currentValue = this.state[permissionKey];

			return valueType === currentValue ? Icon.CHECK : null;
		};

		#getSubTitleByPermissionBooleanValueType = (valueType) => {
			switch (valueType)
			{
				case PermissionBooleanValueType.TRUE:
					return Loc.getMessage('M_COLLAB_PERMISSIONS_TRUE');
				case PermissionBooleanValueType.FALSE:
					return Loc.getMessage('M_COLLAB_PERMISSIONS_FALSE');
				default:
					return '';
			}
		};

		#getSubTitleByPermissionValueType = (valueType) => {
			switch (valueType)
			{
				case PermissionValueType.ALL:
					return Loc.getMessage('M_COLLAB_PERMISSIONS_ALL');
				case PermissionValueType.EMPLOYEES:
					return Loc.getMessage('M_COLLAB_PERMISSIONS_EMPLOYEES');
				case PermissionValueType.OWNER_AND_MODERATORS:
					return Loc.getMessage('M_COLLAB_PERMISSIONS_OWNER_AND_MODERATORS');
				case PermissionValueType.OWNER:
					return Loc.getMessage('M_COLLAB_PERMISSIONS_OWNER');
				default:
					return '';
			}
		};

		#getModeratorsSubTitle = () => {
			if (!Type.isArrayFilled(this.state.moderators))
			{
				return Loc.getMessage('M_COLLAB_PERMISSIONS_MODERATORS_ITEM_SUBTITLE_NONE');
			}

			return this.state.moderators.map((moderator) => moderator.fullName).join(', ');
		};

		#onPermissionsItemClick = (item) => {
			const { canInviteCollabersInPortalSettings } = this.props;
			switch (item.id)
			{
				case Permission.OWNER:
					this.#showOwnerSelector();
					break;
				case Permission.MODERATORS:
					this.#showModeratorsSelector();
					break;
				case Permission.SHOW_HISTORY:
					this.showHistoryMenu = new UIMenu(this.#getPermissionBooleanValueTypesItemsForMenu(Permission.SHOW_HISTORY));
					this.showHistoryMenu.show({
						target: this.settingsSelectorItemsRefsMap.get(Permission.SHOW_HISTORY).ref,
					});
					break;
				case Permission.INVITERS:
					this.invitersMenu = new UIMenu(this.#getPermissionValueTypesItemsForMenu(Permission.INVITERS));
					this.invitersMenu.show({
						target: this.settingsSelectorItemsRefsMap.get(Permission.INVITERS).ref,
					});
					break;
				case Permission.ALLOW_GUESTS_INVITATION:
					if (canInviteCollabersInPortalSettings)
					{
						this.invitersMenu = new UIMenu(
							this.#getPermissionBooleanValueTypesItemsForMenu(Permission.ALLOW_GUESTS_INVITATION),
						);
						this.invitersMenu.show({
							target: this.settingsSelectorItemsRefsMap.get(Permission.ALLOW_GUESTS_INVITATION).ref,
						});
					}
					else
					{
						showToast(
							{
								message: Loc.getMessage('COLLAB_INVITE_TOAST_GUESTS_INVITE_DISABLED'),
								time: 5,
							},
						);
					}
					break;
				case Permission.MESSAGE_WRITERS:
					this.messageWritersMenu = new UIMenu(this.#getPermissionValueTypesItemsForMenu(Permission.MESSAGE_WRITERS));
					this.messageWritersMenu.show({
						target: this.settingsSelectorItemsRefsMap.get(Permission.MESSAGE_WRITERS).ref,
					});
					break;
				default:
					break;
			}
		};

		onSelectOwner = (owner) => {
			if (owner)
			{
				this.setState({
					owner: {
						id: Number(owner.id),
						fullName: owner.title,
					},
				}, () => this.#callOnChange());
			}
		};

		#showOwnerSelector = () => {
			const ownerSelector = new MemberSelector({
				title: Loc.getMessage('M_COLLAB_PERMISSIONS_OWNER_SELECTOR_TITLE'),
				onSelectItem: this.onSelectOwner,
				allowMultipleSelection: false,
				withCurrentUser: true,
			});

			ownerSelector.open(this.props.layoutWidget);
		};

		onSelectManagers = (selectedUsersIds, selectedUsers) => {
			const moderators = Type.isArrayFilled(selectedUsers)
				? selectedUsers.map((user) => ({
					id: Number(user.id),
					fullName: user.title,
				}))
				: [];
			this.setState({
				moderators,
			}, () => this.#callOnChange());
		};

		#showModeratorsSelector = () => {
			const moderatorSelector = new MemberSelector({
				title: Loc.getMessage('M_COLLAB_PERMISSIONS_MODERATORS_SELECTOR_TITLE'),
				initSelectedIds: this.state.moderators.map((user) => user.id),
				onSelectMembers: this.onSelectManagers,
				withCurrentUser: true,
			});

			moderatorSelector.open(this.props.layoutWidget);
		};
	}

	module.exports = {
		/**
		 * @param {CollabCreatePermissionsProps} props
		 * @returns {CollabCreatePermissions}
		 */
		CollabCreatePermissions: (props) => new CollabCreatePermissions(props),
		PermissionValueType,
	};
});
