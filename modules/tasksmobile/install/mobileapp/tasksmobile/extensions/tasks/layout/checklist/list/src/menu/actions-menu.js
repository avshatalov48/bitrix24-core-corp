/**
 * @module tasks/layout/checklist/list/src/menu/actions-menu
 */
jn.define('tasks/layout/checklist/list/src/menu/actions-menu', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Haptics } = require('haptics');
	const { animate } = require('animation');
	const { PureComponent } = require('layout/pure-component');
	const { directions } = require('tasks/layout/checklist/list/src/constants');
	const { IconView, iconTypes } = require('ui-system/blocks/icon');
	const { Random } = require('utils/random');
	const { UIScrollView } = require('layout/ui/scroll-view');
	const { MEMBER_TYPE } = require('tasks/layout/checklist/list/src/constants');

	const ICON_SIZE = 24;
	const ICON_MARGIN = 16;
	const BUTTON_TYPES = {
		important: 'important',
		accomplice: 'accomplice',
		auditor: 'auditor',
		attach: 'attach',
	};

	const ACTIVE_COLOR = Color.accentMainPrimary;
	const INACTIVE_COLOR = Color.base3;

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
				[BUTTON_TYPES.attach]: Boolean(this.item?.getAttachmentsCount() > 0),
				[BUTTON_TYPES.auditor]: this.item?.hasAuditor(),
				[BUTTON_TYPES.accomplice]: this.item?.hasAccomplice(),
			};

			return Boolean(activeMap[type]);
		}

		renderIconView(iconProps)
		{
			const { onClick, style, testId, ...restProps } = iconProps;

			return View(
				{
					testId,
					style: {
						width: 30,
						height: 30,
						...style,
					},
					onClick,
				},
				IconView(restProps),
			);
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
					testId: this.getTestId(),
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
					},
					this.renderIconView({
						testId: this.getTestId('attach'),
						iconColor: this.getIconColor(BUTTON_TYPES.attach),
						icon: iconTypes.outline.attach1,
						onClick: onAddFile,
						iconSize: ICON_SIZE,
						style: {
							marginRight: ICON_MARGIN,
						},
					}),
					this.renderIconView({
						testId: this.getTestId(MEMBER_TYPE.accomplice),
						iconColor: this.getIconColor(BUTTON_TYPES.accomplice),
						icon: iconTypes.outline.group,
						iconSize: ICON_SIZE,
						disabled: !canUpdate,
						style: {
							marginRight: ICON_MARGIN,
						},
						onClick: () => {
							openUserSelectionManager(this.item.getId(), MEMBER_TYPE.accomplice);
						},
					}),
					this.renderIconView({
						testId: this.getTestId(MEMBER_TYPE.auditor),
						iconColor: this.getIconColor(BUTTON_TYPES.auditor),
						icon: iconTypes.outline.observer,
						iconSize: ICON_SIZE,
						disabled: !canUpdate,
						style: {
							marginRight: ICON_MARGIN,
						},
						onClick: () => {
							openUserSelectionManager(this.item.getId(), MEMBER_TYPE.auditor);
						},
					}),
					this.renderIconView({
						testId: this.getTestId('importance'),
						iconSize: ICON_SIZE,
						iconColor: this.isActiveIconByType(BUTTON_TYPES.important)
							? Color.accentMainWarning
							: INACTIVE_COLOR,
						icon: iconTypes.outline.fire,
						disabled: !canUpdate,
						style: {
							marginRight: ICON_MARGIN,
						},
						onClick: this.handleOnToggleImportant,
					}),
					this.renderIconView({
						testId: this.getTestId('toLeft'),
						iconColor: this.getIconColor(),
						iconSize: ICON_SIZE,
						icon: iconTypes.outline.pointLeft,
						disabled: !canTabOut,
						style: {
							marginRight: ICON_MARGIN,
						},
						onClick: () => {
							if (canTabOut)
							{
								this.onTabMove(directions.LEFT);
							}
						},
					}),
					this.renderIconView({
						testId: this.getTestId('toRight'),
						iconColor: this.getIconColor(),
						iconSize: ICON_SIZE,
						icon: iconTypes.outline.pointRight,
						disabled: !canTabIn,
						style: {
							marginRight: ICON_MARGIN,
						},
						onClick: () => {
							if (canTabIn)
							{
								this.onTabMove(directions.RIGHT);
							}
						},
					}),
					this.renderIconView({
						testId: this.getTestId('toChecklist'),
						iconColor: this.getIconColor(),
						icon: iconTypes.outline.moveToChecklist,
						disabled: (canUpdate && !canAdd && !hasAnotherCheckLists) || !canUpdate,
						iconSize: ICON_SIZE,
						onClick: () => {
							if (onMoveToCheckList)
							{
								onMoveToCheckList(this.item?.getMoveIds());
							}
						},
					}, true),
				),
				View({
					style: {
						width: 1,
						height: 14,
						justifyContent: 'center',
						backgroundColor: Color.bgSeparatorPrimary,
						marginHorizontal: 10,
					},
				}),
				this.renderIconView({
					testId: this.getTestId('hide'),
					iconColor: ACTIVE_COLOR,
					icon: iconTypes.outline.chevronDown,
					iconSize: ICON_SIZE,
					style: {
						alignSelf: 'flex-end',
						alignItems: 'center',
						justifyContent: 'center',
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
						bottom: 0,
						backgroundColor: Color.bgContentPrimary,
					},
				},
				this.renderMenu(),
			);
		}

		getTestId(suffix)
		{
			const prefix = 'checklist_toolbar';

			return suffix ? `${prefix}_${suffix}` : prefix;
		}
	}

	module.exports = { ChecklistActionsMenu };
});
