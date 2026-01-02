/**
 * @module collab/create/src/task-permissions
 */
jn.define('collab/create/src/task-permissions', (require, exports, module) => {
	const { Box } = require('ui-system/layout/box');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Color } = require('tokens');
	const { Area } = require('ui-system/layout/area');
	const { SettingSelectorList, SettingSelectorListItemDesign } = require('layout/ui/setting-selector-list');
	const { UIMenu } = require('layout/ui/menu');
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');

	const Permission = {
		VIEW_ALL: 'view_all',
		SORT: 'sort',
		CREATE_TASKS: 'create_tasks',
		EDIT_TASKS: 'edit_tasks',
		DELETE_TASKS: 'delete_tasks',
	};

	const PermissionValueType = {
		ALL: 'K',
		EMPLOYEES: 'J',
		OWNER_AND_MODERATORS: 'E',
		OWNER: 'A',
	};

	/**
	 * @typedef {Object} CollabTaskPermissionsProps
	 * @property {string} testId
	 * @property {PermissionValueType} view_all
	 * @property {PermissionValueType} sort
	 * @property {PermissionValueType} create_tasks
	 * @property {PermissionValueType} edit_tasks
	 * @property {PermissionValueType} delete_tasks
	 * @property {function} onChange
	 *
	 * @class CollabTaskPermissions
	 */
	class CollabTaskPermissions extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.settingsSelectorItemsRefsMap = new Map();
			this.permissionsMenu = null;
			this.#initializeState(props);
		}

		#initializeState(props)
		{
			this.state = {
				view_all: props.view_all ?? PermissionValueType.ALL,
				sort: props.sort ?? PermissionValueType.ALL,
				create_tasks: props.create_tasks ?? PermissionValueType.ALL,
				edit_tasks: props.edit_tasks ?? PermissionValueType.ALL,
				delete_tasks: props.delete_tasks ?? PermissionValueType.ALL,
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
							id: Permission.VIEW_ALL,
							title: Loc.getMessage('M_COLLAB_TASK_PERMISSIONS_VIEW_ALL'),
							subtitle: this.#getSubTitleByPermissionValueType(this.state.view_all),
							design: SettingSelectorListItemDesign.OPENER,
						},
						{
							id: Permission.SORT,
							title: Loc.getMessage('M_COLLAB_TASK_PERMISSIONS_SORT'),
							subtitle: this.#getSubTitleByPermissionValueType(this.state.sort),
							design: SettingSelectorListItemDesign.OPENER,
						},
						{
							id: Permission.CREATE_TASKS,
							title: Loc.getMessage('M_COLLAB_TASK_PERMISSIONS_CREATE_TASKS'),
							subtitle: this.#getSubTitleByPermissionValueType(this.state.create_tasks),
							design: SettingSelectorListItemDesign.OPENER,
						},
						{
							id: Permission.EDIT_TASKS,
							title: Loc.getMessage('M_COLLAB_TASK_PERMISSIONS_EDIT_TASKS'),
							subtitle: this.#getSubTitleByPermissionValueType(this.state.edit_tasks),
							design: SettingSelectorListItemDesign.OPENER,
						},
						{
							id: Permission.DELETE_TASKS,
							title: Loc.getMessage('M_COLLAB_TASK_PERMISSIONS_DELETE_TASKS'),
							subtitle: this.#getSubTitleByPermissionValueType(this.state.delete_tasks),
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

		#getPermissionValueTypesItemsForMenu = (permissionKey) => {
			return Object.keys(PermissionValueType).map((key) => ({
				id: PermissionValueType[key],
				testId: `${this.testId}-menu-item-${key.toLowerCase()}`,
				title: Loc.getMessage(`M_COLLAB_PERMISSIONS_${key}`),
				iconName: this.#getIconNameForMenu(key, permissionKey),
				iconColor: Color.accentMainPrimary,
				onItemSelected: (event, item) => {
					this.setState({
						[permissionKey]: item.id,
					}, () => this.#callOnChange());
				},
			}));
		};

		#callOnChange = () => {
			this.props.onChange?.({
				view_all: this.state.view_all,
				sort: this.state.sort,
				create_tasks: this.state.create_tasks,
				edit_tasks: this.state.edit_tasks,
				delete_tasks: this.state.delete_tasks,
			});
		};

		#getIconNameForMenu = (permissionValueTypeKey, permissionKey) => {
			const valueType = PermissionValueType[permissionValueTypeKey];
			const currentValue = this.state[permissionKey];

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

		#onPermissionsItemClick = (item) => {
			this.permissionsMenu = new UIMenu(this.#getPermissionValueTypesItemsForMenu(item.id));
			this.permissionsMenu.show({
				target: this.settingsSelectorItemsRefsMap.get(item.id).ref,
			});
		};
	}

	module.exports = {
		/**
		 * @param {CollabTaskPermissionsProps} props
		 * @returns {CollabTaskPermissions}
		 */
		CollabTaskPermissions: (props) => new CollabTaskPermissions(props),
		PermissionValueType,
	};
});
