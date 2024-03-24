/**
 * @module tasks/layout/checklist/list/src/menu/actions-menu
 */
jn.define('tasks/layout/checklist/list/src/menu/actions-menu', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Haptics } = require('haptics');
	const { animate } = require('animation');
	const { PureComponent } = require('layout/pure-component');
	const { directions } = require('tasks/layout/checklist/list/src/constants');
	const { IconView } = require('ui-system/blocks/icon');
	const { Random } = require('utils/random');
	const { UIScrollView } = require('layout/ui/scroll-view');

	const IS_ANDROID = Application.getPlatform() === 'android';
	const ICON_SIZE = 24;
	const ICON_MARGIN = 24;
	const BUTTON_TYPES = {
		important: 'important',
		members: 'members',
		attach: 'attach',
	};

	const ACTIVE_COLOR = AppTheme.colors.accentMainPrimary;
	const INACTIVE_COLOR = AppTheme.colors.base3;

	/**
	 * @class ChecklistActionsMenu
	 */
	class ChecklistActionsMenu extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.isShown = false;

			/** @type {CheckListFlatTreeItem} */
			this.item = null;
			/** @type {ChecklistActionsMenu} */
			this.menuRef = null;

			this.initialItem(props);
			this.handleOnToggleImportant = this.handleOnToggleImportant.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.initialItem(props);
		}

		initialItem(props)
		{
			let itemId = null;
			if (props.item)
			{
				this.item = props.item;
				itemId = this.item.getId();
			}

			this.state = {
				itemId,
				random: Random.getString(),
			};
		}

		handleOnToggleImportant()
		{
			const { onToggleImportant } = this.props;

			Haptics.impactLight();
			onToggleImportant();
			this.refreshExtension();
		}

		refreshExtension()
		{
			this.setState({
				random: Random.getString(),
			});
		}

		setItem(item)
		{
			this.item = item;
			const itemId = item.getId();
			const { itemId: stateItemId } = this.state;

			if (stateItemId !== itemId)
			{
				this.setState({ itemId });
			}
		}

		show()
		{
			if (!this.isShown)
			{
				this.animateToggleMenu({ show: true });
			}
		}

		hide()
		{
			if (this.isShown)
			{
				this.animateToggleMenu({ show: false });
			}
		}

		/**
		 * @public
		 * @param {boolean} show
		 */
		animateToggleMenu({ show })
		{
			animate(this.menuRef, {
				opacity: show ? 1 : 0,
				duration: 300,
			}).catch(console.error);

			this.isShown = show;
		}

		/**
		 * @private
		 * @param {BUTTON_TYPES} type
		 * @return boolean
		 */
		isActiveIconByType(type)
		{
			const activeMap = {
				[BUTTON_TYPES.important]: this.item?.getIsImportant(),
				[BUTTON_TYPES.members]: Boolean(this.item?.getMembersCount() > 0),
				[BUTTON_TYPES.attach]: Boolean(this.item?.getAttachmentsCount() > 0),
			};

			return Boolean(activeMap[type]);
		}

		/**
		 * @private
		 * @param {BUTTON_TYPES} type
		 * @return string
		 */
		getIconColor(type = null)
		{
			return this.isActiveIconByType(type) ? ACTIVE_COLOR : INACTIVE_COLOR;
		}

		renderMenu()
		{
			const { onMoveToCheckList, onAddFile, onBlur, openUserSelectionManager } = this.props;
			const { canUpdate, canAdd, canTabOut, canTabIn, hasAnotherCheckLists } = this.getPermissions();

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'flex-end',
					},
				},
				UIScrollView(
					{
						horizontal: true,
						style: {
							flex: 1,
							height: 24,
							flexDirection: 'row',
							alignItems: 'center',
						},
						children: [
							IconView({
								iconColor: this.getIconColor(BUTTON_TYPES.attach),
								icon: 'attach1',
								onClick: onAddFile,
								iconSize: ICON_SIZE,
								style: {
									marginRight: ICON_MARGIN,
								},
							}),
							IconView({
								iconColor: this.getIconColor(BUTTON_TYPES.members),
								icon: 'group',
								iconSize: ICON_SIZE,
								disabled: !canUpdate,
								onClick: openUserSelectionManager,
								style: {
									marginRight: ICON_MARGIN,
								},
							}),
							IconView({
								iconSize: ICON_SIZE,
								iconColor: this.isActiveIconByType(BUTTON_TYPES.important)
									? AppTheme.colors.accentMainWarning
									: INACTIVE_COLOR,
								icon: 'fire',
								disabled: !canUpdate,
								onClick: this.handleOnToggleImportant,
								style: {
									marginRight: ICON_MARGIN,
								},
							}),
							IconView({
								iconColor: this.getIconColor(),
								iconSize: ICON_SIZE,
								icon: 'shiftLeft',
								onClick: () => {
									this.onTabMove(directions.LEFT);
								},
								style: {
									marginRight: ICON_MARGIN,
								},
							}),
							IconView({
								iconColor: this.getIconColor(),
								iconSize: ICON_SIZE,
								icon: 'shiftRight',
								onClick: () => {
									this.onTabMove(directions.RIGHT);
								},
								style: {
									marginRight: ICON_MARGIN,
								},
							}),
							IconView({
								iconColor: this.getIconColor(),
								icon: 'moveToChecklist',
								disabled: (canUpdate && !canAdd && !hasAnotherCheckLists) || !canUpdate,
								iconSize: ICON_SIZE,
								onClick: () => {
									if (onMoveToCheckList)
									{
										onMoveToCheckList(this.item?.getMoveIds());
									}
								},
								style: {
									marginRight: ICON_MARGIN,
								},
							}),
						],
					},
				),
				View({
					style: {
						width: 1,
						height: 14,
						justifyContent: 'center',
						backgroundColor: AppTheme.colors.bgSeparatorPrimary,
						marginHorizontal: 10,
					},
				}),
				IconView({
					iconColor: ACTIVE_COLOR,
					icon: 'chevronDown',
					iconSize: ICON_SIZE,
					style: {
						alignSelf: 'flex-end',
						padding: 4,
					},
					onClick: onBlur,
				}),
			);
		}

		onTabMove(direction)
		{
			const { onTabMove } = this.props;

			if (onTabMove)
			{
				Haptics.impactLight();
				onTabMove(this.item, direction);
			}
		}

		getPermissions()
		{
			return {
				canTabOut: this.item?.checkCanTabOut(),
				canTabIn: this.item?.checkCanTabIn(),
				canAdd: this.item?.checkCanAdd(),
				canUpdate: this.item?.checkCanUpdate(),
				canAddAccomplice: this.item?.checkCanAddAccomplice(),
				hasAnotherCheckLists: this.item?.hasAnotherCheckLists(),
				canRemove: this.item?.checkCanRemove(),
			};
		}

		render()
		{
			return View(
				{
					ref: (menuRef) => {
						this.menuRef = menuRef;
					},
					safeArea: {
						bottom: true,
					},
					style: {
						opacity: 0,
						paddingVertical: 10,
						paddingHorizontal: 18,
						position: 'absolute',
						width: '100%',
						left: 0,
						bottom: IS_ANDROID ? 15 : 0,
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				this.renderMenu(),
			);
		}
	}

	module.exports = { ChecklistActionsMenu };
});
