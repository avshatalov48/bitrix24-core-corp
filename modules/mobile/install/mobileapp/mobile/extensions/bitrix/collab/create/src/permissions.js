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
	const { EntitySelectorFactory } = require('selector/widget/factory');

	const Permission = {
		OWNER: 'owner',
		MODERATORS: 'moderators',
		INVITERS: 'inviters',
		MESSAGE_WRITERS: 'messageWriters',
	};

	const PermissionValueType = {
		ALL: 'K',
		// EMPLOYEES: 'J',
		OWNER_AND_MODERATORS: 'E',
		OWNER: 'A',
	};

	class CollabCreatePermissions extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.settingsSelectorItemsRefsMap = new Map();
			this.invitersMenu = null;
			this.#initializeState();
		}

		#initializeState = () => {
			this.state = {
				owner: this.props.owner ?? {},
				moderators: this.props.moderators ?? [],
				inviters: this.props.inviters ?? PermissionValueType.ALL,
				messageWriters: this.props.messageWriters ?? PermissionValueType.ALL,
			};
		};

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
							id: Permission.INVITERS,
							title: Loc.getMessage('M_COLLAB_PERMISSIONS_INVITERS_ITEM_TITLE'),
							subtitle: this.#getSubTitleByPermissionValueType(this.state.inviters),
							design: SettingSelectorListItemDesign.OPENER,
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

		#getPermissionValueTypesItemsForMenu = (forInviters = true) => {
			const items = [];
			Object.keys(PermissionValueType).forEach((key) => {
				const iconName = this.#getIconNameForMenu(key, forInviters);
				items.push({
					id: PermissionValueType[key],
					testId: `${this.testId}-menu-item-${key.toLowerCase()}`,
					title: Loc.getMessage(`M_COLLAB_PERMISSIONS_${key}`),
					iconName,
					iconColor: Color.accentMainPrimary,
					onItemSelected: (event, item) => {
						if (forInviters)
						{
							this.setState({
								inviters: item.id,
							}, () => this.#callOnChange());
						}
						else
						{
							this.setState({
								messageWriters: item.id,
							}, () => this.#callOnChange());
						}
					},
				});
			});

			return items;
		};

		#callOnChange = () => {
			this.props.onChange?.({
				owner: this.state.owner,
				moderators: this.state.moderators,
				inviters: this.state.inviters,
				messageWriters: this.state.messageWriters,
			});
		};

		#getIconNameForMenu = (permissionValueTypeKey, forInviters = true) => {
			const valueType = PermissionValueType[permissionValueTypeKey];
			const currentValue = forInviters ? this.state.inviters : this.state.messageWriters;

			return valueType === currentValue ? Icon.CHECK : null;
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
			switch (item.id)
			{
				case Permission.OWNER:
					this.#showOwnerSelector();
					break;
				case Permission.MODERATORS:
					this.#showModeratorsSelector();
					break;
				case Permission.INVITERS:
					this.invitersMenu = new UIMenu(this.#getPermissionValueTypesItemsForMenu());
					this.invitersMenu.show({
						target: this.settingsSelectorItemsRefsMap.get(Permission.INVITERS).ref,
					});
					break;
				case Permission.MESSAGE_WRITERS:
					this.messageWritersMenu = new UIMenu(this.#getPermissionValueTypesItemsForMenu(false));
					this.messageWritersMenu.show({
						target: this.settingsSelectorItemsRefsMap.get(Permission.MESSAGE_WRITERS).ref,
					});
					break;
				default:
					break;
			}
		};

		#showOwnerSelector = () => {
			const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.USER, {
				provider: {
					options: {
						intranetUsersOnly: true,
					},
				},
				createOptions: {
					enableCreation: false,
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				initSelectedIds: [this.state.owner.id],
				events: {
					onClose: (selectedUsers) => {
						if (Type.isArrayFilled(selectedUsers))
						{
							this.setState({
								owner: {
									id: selectedUsers[0].id,
									firstName: selectedUsers[0].customData?.name ?? '',
									lastName: selectedUsers[0].customData?.lastName ?? '',
									fullName: selectedUsers[0].title,
								},
							}, () => this.#callOnChange());
						}
					},
				},
				widgetParams: {
					title: Loc.getMessage('M_COLLAB_PERMISSIONS_OWNER_SELECTOR_TITLE'),
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
			});

			selector.show({}, this.props.layoutWidget);
		};

		#showModeratorsSelector = () => {
			const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.USER, {
				provider: {
					options: {
						intranetUsersOnly: true,
					},
				},
				createOptions: {
					enableCreation: false,
				},
				allowMultipleSelection: true,
				closeOnSelect: true,
				initSelectedIds: this.state.moderators.map((user) => user.id),
				events: {
					onClose: (selectedUsers) => {
						const moderators = Type.isArrayFilled(selectedUsers)
							? selectedUsers.map((user) => ({
								id: user.id,
								firstName: user.customData?.name ?? '',
								lastName: user.customData?.lastName ?? '',
								fullName: user.title,
							}))
							: [];
						this.setState({
							moderators,
						}, () => this.#callOnChange());
					},
				},
				widgetParams: {
					title: Loc.getMessage('M_COLLAB_PERMISSIONS_MODERATORS_SELECTOR_TITLE'),
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
			});

			selector.show({}, this.props.layoutWidget);
		};
	}

	module.exports = {
		CollabCreatePermissions: (props) => new CollabCreatePermissions(props),
		PermissionValueType,
	};
});
