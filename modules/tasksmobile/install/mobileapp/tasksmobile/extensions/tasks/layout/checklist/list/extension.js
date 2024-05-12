/**
 * @module tasks/layout/checklist/list
 */
jn.define('tasks/layout/checklist/list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { unique } = require('utils/array');
	const { showToast } = require('toast/base');
	const { throttle } = require('utils/function');
	const { useCallback } = require('utils/function');
	const { ListViewQueueWorker } = require('layout/list-view-queue-worker');
	const { UserSelectionManager } = require('layout/ui/user-selection-manager');
	const { MainChecklistItem } = require('tasks/layout/checklist/list/src/main-item');
	const { RootChecklistItem } = require('tasks/layout/checklist/list/src/root-item');
	const { ChecklistActionsMenu } = require('tasks/layout/checklist/list/src/menu/actions-menu');
	const { ChecklistsMenu } = require('tasks/layout/checklist/list/src/menu/checklists-menu');
	const { ButtonAdd, buttonAddItemType } = require('tasks/layout/checklist/list/src/buttons/button-add-item');
	const { ChecklistItemView } = require('tasks/layout/checklist/list/src/layout/item-view');
	const { EntitySelectorFactory, EntitySelectorFactoryType } = require('selector/widget/factory');
	const { ChecklistEmptyScreen, emptyScreenItemType } = require('tasks/layout/checklist/list/src/empty-screen');

	const IS_IOS = Application.getPlatform() === 'ios';
	const STUB_MENU = {
		key: 'stubMenu',
		type: 'stubMenu',
	};

	/**
	 * @class Checklist
	 */
	class Checklist extends LayoutComponent
	{
		/**
		 * @param {object} props
		 * @param {number} [props.userId]
		 * @param {number} [props.taskId]
		 * @param {string | number} [props.focusedItemId]
		 * @param {CheckListFlatTree} [props.checklist]
		 * @param {object} [props.parentWidget]
		 */
		constructor(props)
		{
			super(props);

			this.handleOnSubmit = this.handleOnSubmit.bind(this);
			this.handleOnButtonAppendItem = this.handleOnButtonAppendItem.bind(this);
			this.handleOnRemoveItem = this.handleOnRemoveItem.bind(this);
			this.handleOnToggleComplete = this.handleOnToggleComplete.bind(this);
			this.handleOnBlur = throttle(this.handleOnBlur, 300, this);
			this.handleOnFocus = throttle(this.handleOnFocus, 300, this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.updateRows = this.updateRows.bind(this);
			this.handleOnTabMove = this.handleOnTabMove.bind(this);
			this.openUserSelectionManager = this.openUserSelector.bind(this);
			this.handleOnShowChecklistsMenu = this.handleOnShowChecklistsMenu.bind(this);
			this.handleOnAddFile = this.handleOnAddFile.bind(this);
			this.handleOnToggleImportant = this.handleOnToggleImportant.bind(this);
			this.handleOnChangeMembers = this.handleOnChangeMembers.bind(this);
			this.handleOnChangeAttachments = this.handleOnChangeAttachments.bind(this);
			this.handleHideMenu = this.handleHideMenu.bind(this);
			this.handleShowMenu = this.handleShowMenu.bind(this);
			this.handleOnBlurFocusedItem = this.handleOnBlurFocusedItem.bind(this);
			this.updateRowByKey = this.updateRowByKey.bind(this);

			this.itemRefMap = new Map();
			this.focusedItemId = props.focusedItemId || null;
			this.parentWidget = props.parentWidget || null;

			this.checklist.setTaskId(props.taskId);
			this.checklist.setUserId(props.userId);
			this.setFocused(this.focusedItemId || props.checklist.getFocusedItemId());

			/** @type {ListViewQueueWorker} */
			this.checklistQueue = new ListViewQueueWorker();
			/** @type {ChecklistActionsMenu} */
			this.menuRef = null;

			this.initState(props);

			this.delayedScroll = null;
			this.isShowKeyboard = false;
			this.saveFocus = false;

			this.initialKeyboardHandlers();
		}

		componentWillReceiveProps(props)
		{
			this.initState(props);
		}

		initState(props)
		{
			this.state = {
				onlyMine: props.onlyMine,
				hideCompleted: props.hideCompleted,
			};
		}

		initialKeyboardHandlers()
		{
			Keyboard.on(Keyboard.Event.WillHide, () => {
				if (!this.isShowKeyboard)
				{
					return;
				}

				this.isShowKeyboard = false;
				this.handleHideMenu();
			});

			Keyboard.on(Keyboard.Event.WillShow, () => {
				this.isShowKeyboard = true;

				if (typeof this.delayedScroll === 'function')
				{
					this.delayedScroll()
						.then(() => {
							this.delayedScroll = null;
						})
						.catch(console.error);
				}
			});
		}

		/** @type {CheckListFlatTree} */
		get checklist()
		{
			const { checklist } = this.props;

			return checklist;
		}

		/**
		 * @public
		 * @param {object} params
		 * @param {boolean} [params.onlyMine]
		 * @param {boolean} [params.hideCompleted]
		 */
		reload(params)
		{
			this.checklist.setConditions(params, true);

			this.setState(params);
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 */
		reloadItem(item)
		{
			if (!item)
			{
				return;
			}

			const checklistItem = this.itemRefMap.get(item.getId());
			checklistItem.reload();
		}

		setParentWidget(parentWidget)
		{
			this.parentWidget = parentWidget;
		}

		getParentWidget()
		{
			return this.parentWidget;
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 * @param {number[]} itemIds
		 * @return {Promise<void>}
		 */
		updateCounter({ item, itemIds = [] })
		{
			if (!item)
			{
				return Promise.resolve();
			}

			const rootItem = this.checklist.getRootItem();
			this.reloadItem(rootItem);

			return Promise.resolve();

			// return this.updateRows({ itemIds: [rootItem?.getId(), ...itemIds] });
		}

		/**
		 * @public
		 * @param {CheckListFlatTreeItem} item
		 * @param {Boolean} shouldRender
		 */
		updateRowByKey(item, shouldRender)
		{
			const key = item.getKey();
			item.updateListViewType();
			const updateItem = item.getItem();

			void this.checklistQueue.updateRowByKey(key, updateItem, false, shouldRender);
		}

		/**
		 * @param {number[]} itemIds
		 * @param {string} animation
		 * @param {boolean} saveFocus
		 * @param {boolean} shouldRenderItem
		 */
		updateRows({ itemIds, animation, saveFocus, shouldRenderItem })
		{
			if (typeof saveFocus === 'boolean')
			{
				this.saveFocus = saveFocus;
			}

			const items = [];
			this.prepareArray(itemIds).forEach((id) => {
				const treeItem = this.checklist.getItemById(id);
				if (treeItem)
				{
					treeItem.updateListViewType();
					items.push(treeItem.getItem());
				}
			});

			if (itemIds === buttonAddItemType.key)
			{
				items.push(buttonAddItemType.key);
			}

			const updater = (shouldRender) => this.checklistQueue
				.updateRows(items, animation, shouldRender)
				.catch(console.error);

			const focusedItem = this.getFocusedItem();

			if (IS_IOS && saveFocus && focusedItem)
			{
				return updater().then(() => {
					this.focusToItem(focusedItem);
				}).catch(console.error);
			}

			return updater();
		}

		focusSwitcher(focusedItem, callback)
		{
			if (!focusedItem)
			{
				return Promise.resolve();
			}

			const closestElement = this.checklist.getClosestElement(focusedItem);

			this.focusToItem(closestElement);

			return new Promise((resolve) => {
				callback().then(() => {
					this.focusToItem(focusedItem);
					resolve();
				}).catch(console.error);
			});
		}

		/**
		 * @private
		 * @param {string[]} keys
		 * @param {string} animation
		 * @return {function(): Promise}
		 */
		deleteRows(keys, animation = 'fade')
		{
			return this.checklistQueue.deleteRowsByKeys(keys, animation);
		}

		/**
		 * @param {object} item
		 * @param {number} position
		 */
		insertRows(item, position)
		{
			return this.checklistQueue.insertRows(item, 0, position, 'none');
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 * @return Promise<void>
		 */
		async scrollToItem(item)
		{
			if (!item)
			{
				return Promise.reject();
			}

			const listViewRef = this.checklistQueue.getListViewRef();
			const itemKey = item.getKey();
			const isVisible = await listViewRef?.isItemVisible(itemKey);
			const position = this.checklistQueue.getElementPosition(itemKey);

			if (isVisible || !position)
			{
				return Promise.resolve();
			}

			const { section, index } = position;

			return this.checklistQueue.scrollTo(section, index, true);
		}

		isFilterEnabled()
		{
			const { onlyMine, hideCompleted } = this.state;

			return onlyMine || hideCompleted;
		}

		shouldEmptyScreen(checklistItems)
		{
			const isEmptyList = (items) => items.filter((item) => !item.isRoot()).length === 0;

			if (checklistItems)
			{
				return isEmptyList(checklistItems);
			}

			return isEmptyList(this.checklist.getFilteredItems(this.state));
		}

		getItems()
		{
			const checklistItems = this.isFilterEnabled()
				? this.checklist.getFilteredItems(this.state)
				: this.checklist.getChecklistItems();

			const primitiveItems = checklistItems.map((item) => item.getItem());
			primitiveItems.push(buttonAddItemType);

			if (this.isFilterEnabled() && this.shouldEmptyScreen(checklistItems))
			{
				primitiveItems.push(emptyScreenItemType);
			}

			return primitiveItems;
		}

		/**
		 * @return {View}
		 */
		render()
		{
			return View(
				{
					safeArea: {
						bottom: true,
					},
					onClick: () => {
						this.handleOnBlurFocusedItem();
					},
					style: {
						flex: 1,
						paddingBottom: IS_IOS ? 0 : 12,
						backgroundColor: Color.bgContentPrimary,
					},
					resizableByKeyboard: true,
				},
				ListView({
					ref: (ref) => {
						this.checklistQueue.setListViewRef(ref);
					},
					style: {
						flex: 1,
					},
					data: [
						{
							items: this.getItems(),
						},
					],
					isRefreshing: false,
					renderItem: (item) => this.renderChecklistItem(item),
				}),
				this.renderMenu(),
			);
		}

		/**
		 * @private
		 * @param {object} item
		 * @return {BaseChecklistItem|null}
		 */
		renderChecklistItem(item)
		{
			const { checklist } = this.props;

			if (item.key === buttonAddItemType.key)
			{
				return ButtonAdd({ onClick: this.handleOnButtonAppendItem });
			}

			if (item.key === emptyScreenItemType.key)
			{
				return this.renderEmptyScreen();
			}

			if (item.key === STUB_MENU.key)
			{
				return ChecklistItemView({
					style: {
						height: 50,
						width: '100%',
					},
				});
			}

			const checklistItem = checklist.getItemById(item.id);

			if (!checklistItem)
			{
				console.log(`no item ${checklistItem.getId()}`);

				return null;
			}

			const itemProps = this.getItemProps(checklistItem);

			return checklistItem.isRoot()
				? new RootChecklistItem(itemProps)
				: new MainChecklistItem(itemProps);
		}

		getItemProps(checklistItem)
		{
			const { diskConfig } = this.props;

			return {
				ref: ((itemRef) => {
					this.itemRefMap.set(checklistItem.getId(), itemRef);
				}),
				diskConfig,
				selectedOptions: this.state,
				parentWidget: this.getParentWidget(),
				item: checklistItem,
				isFocused: this.focusedItemId === checklistItem.getId(),
				onSubmit: this.handleOnSubmit,
				onRemove: this.handleOnRemoveItem,
				onFocus: this.handleOnFocus,
				onBlur: this.handleOnBlur,
				onChange: this.handleOnChange,
				updateRowByKey: this.updateRowByKey,
				hideMenu: this.handleHideMenu,
				showMenu: this.handleShowMenu,
				onChangeAttachments: this.handleOnChangeAttachments,
				onToggleComplete: this.handleOnToggleComplete,
				openUserSelectionManager: this.openUserSelectionManager,
			};
		}

		/**
		 * @private
		 * @returns {ChecklistActionsMenu}
		 */
		renderMenu()
		{
			const item = this.checklist.getItemById(this.focusedItemId);

			return new ChecklistActionsMenu({
				ref: useCallback((menuRef) => {
					this.menuRef = menuRef;
				}),
				item,
				parentWidget: this.getParentWidget(),
				onTabMove: this.handleOnTabMove,
				onToggleImportant: this.handleOnToggleImportant,
				onMoveToCheckList: this.handleOnShowChecklistsMenu,
				openUserSelectionManager: this.openUserSelectionManager,
				onAddFile: this.handleOnAddFile,
				onBlur: this.handleOnBlurFocusedItem,
			});
		}

		renderEmptyScreen()
		{
			return ChecklistEmptyScreen({
				title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_EMPTY_SCREEN_PERSONAL_TITLE'),
				imageName: 'list.svg',
				onClick: () => {
					const { onChangeFilter } = this.props;

					if (onChangeFilter)
					{
						onChangeFilter({ onlyMine: false, hideCompleted: false });
					}
				},
			});
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} focusedItem
		 */
		handleOnFocus(focusedItem)
		{
			this.setFocused(focusedItem.getId());
			this.menuRef.setItem(focusedItem);
			const scrollToFocusedItem = () => this.scrollToItem(focusedItem);
			if (this.isShowKeyboard)
			{
				scrollToFocusedItem();
			}
			else
			{
				this.delayedScroll = scrollToFocusedItem;
			}
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} blurItem
		 */
		handleOnBlur(blurItem)
		{
			if (!blurItem)
			{
				return Promise.resolve();
			}

			const shouldBeDeletedOnBlur = !this.saveFocus && !blurItem.hasItemTitle() && blurItem.shouldRemove();

			if ((shouldBeDeletedOnBlur && !blurItem.isRoot()))
			{
				return this.handleOnRemoveItem({ item: blurItem });
			}

			this.saveFocus = false;

			return Promise.resolve();
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 */
		focusToItem(item)
		{
			const focusedItem = this.itemRefMap.get(item.getId());
			if (focusedItem)
			{
				focusedItem.textInputFocus();
			}
		}

		/**
		 * @public
		 * @return {Promise<void>}
		 */
		handleOnToggleImportant()
		{
			return this.getFocusedItemRef()?.toggleImportant();
		}

		handleOnAddFile()
		{
			this.getFocusedItemRef()?.addFile();
		}

		handleOnChange()
		{
			const { onChange } = this.props;

			if (onChange)
			{
				onChange();
			}
		}

		isShowEmptyState()
		{
			return Boolean(this.checklistQueue.getElementPosition(emptyScreenItemType.key));
		}

		async handleOnButtonAppendItem()
		{
			const rootItem = this.checklist.getRootItem();

			if (this.isShowEmptyState())
			{
				void this.deleteRows([emptyScreenItemType.key]);
			}

			const focusedItem = this.getFocusedItem();

			if (!focusedItem)
			{
				return this.insertNewItem(rootItem);
			}

			if (focusedItem.isRoot() && !focusedItem?.hasItemTitle())
			{
				return this.insertNewItem(rootItem);
			}

			if (!focusedItem.hasItemTitle())
			{
				return null;
			}

			return this.insertNewItem(rootItem);
		}

		async handleOnSubmit(submitItem)
		{
			const focusedItem = this.getFocusedItem();

			const item = submitItem || focusedItem;

			if (item.isRoot())
			{
				return this.insertNewItem(item);
			}

			if (!item?.hasItemTitle())
			{
				return null;
			}

			return this.insertNewItem(item);
		}

		getFilteredPosition(item)
		{
			this.checklist.setConditions(this.state);
			const position = this.checklist.getInsertPosition(item);
			this.checklist.setConditions({});

			return position;
		}

		insertNewItem(item)
		{
			const position = this.getFilteredPosition(item);
			const newItem = this.checklist.addNewItem(item);

			const { onlyMine } = this.state;
			if (onlyMine)
			{
				newItem.setAlwaysShow(true);
			}

			if (!newItem)
			{
				return;
			}

			this.setFocused(newItem.getId());
			this.insertRows(newItem.getItem(), position).then(() => {
				this.afterInsert(newItem);
			}).catch(console.error);
		}

		afterInsert(item)
		{
			void this.updateCounter({ item });
			void this.scrollToItem(item);
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 * @param {boolean} force
		 */
		async handleOnRemoveItem({ item })
		{
			const removeKeys = this.checklist.removeItem(item);
			await this.deleteRows(removeKeys);

			if (this.isFilterEnabled() && !this.isShowEmptyState() && this.shouldEmptyScreen())
			{
				void this.checklistQueue.appendRows([emptyScreenItemType], 'none');
			}

			return this.updateCounter({ item });
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 */
		handleOnToggleComplete(item)
		{
			const { hideCompleted } = this.state;

			if (hideCompleted)
			{
				this.removeElementFilterIsEnabled(item);
			}

			void this.updateRowByKey(item, false);
			void this.updateCounter({ item });
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 * @param {'left' | 'right'} direction
		 */
		async handleOnTabMove(item, direction)
		{
			const isLeft = direction === 'left';
			const moved = isLeft ? item.tabOut() : item.tabIn();

			if (!moved)
			{
				return;
			}

			const moveIds = item.getMoveIds();

			// eslint-disable-next-line no-unused-vars
			const shouldBeUpdateRoot = () => {
				const parent = item.getParent();

				if (isLeft)
				{
					return parent.isRoot();
				}

				const prevPositionParentItem = parent.getParent();

				return prevPositionParentItem.isRoot();
			};

			this.reloadItem(item);

			await this.updateRows({ itemIds: moveIds });

			this.updateActionMenu();
		}

		handleOnShowChecklistsMenu(moveIds)
		{
			const { checklists } = this.props;

			ChecklistsMenu.open({
				parentWidget: this.getParentWidget(),
				moveItemToChecklist: (checkListId) => {
					this.moveItemToChecklist({ checkListId, moveIds });
				},
				checklists,
				sourceChecklistId: this.checklist.getId(),
			});
		}

		handleOnChangeMembers({ members, item, memberType })
		{
			if (members.length > 0)
			{
				const membersMap = members.map(({ id, title, imageUrl }) => ({
					id,
					name: title,
					image: imageUrl,
					type: memberType,
				}));

				// itemRef.setMembersToText(members.map(({ title }) => title));
				item.clearMemberByType(memberType);
				item.addMembers(membersMap);
			}
			else
			{
				item.clearMemberByType(memberType);
			}

			const hasMy = item.getMember(item.getUserId());
			const { onlyMine } = this.state;

			if (onlyMine && !hasMy)
			{
				this.removeElementFilterIsEnabled(item);
			}
		}

		handleOnChangeAttachments(item)
		{
			this.updateActionMenu();
			this.handleOnChange();
			this.updateRows({ itemIds: [item.getId()] });
		}

		/**
		 * @param {CheckListFlatTreeItem} item
		 */
		removeElementFilterIsEnabled(item)
		{
			if (this.timerId)
			{
				clearTimeout(this.timerId);
				this.timerId = null;

				return Promise.resolve();
			}

			const { onlyMine, hideCompleted } = this.state;

			const shouldFilter = (onlyMine && this.checklist.shouldFilterOnlyMine(item))
				|| (hideCompleted && this.checklist.shouldFilterHideCompleted(item));

			if (shouldFilter)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				this.timerId = setTimeout(async () => {
					await this.deleteRows([item.getKey()]);
					this.setState({}, resolve);
					this.timerId = null;
				}, 1000);
			});
		}

		async moveItemToChecklist({ checkListId, moveIds })
		{
			const { onMoveToCheckList } = this.props;
			this.handleOnBlurFocusedItem();

			const parentWidget = this.getParentWidget();

			const openChecklist = await onMoveToCheckList({
				moveIds,
				toCheckListId: checkListId,
				sourceChecklistId: this.checklist.getId(),
			});

			showToast({
				layoutWidget: parentWidget,
				message: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ITEM_MOVED'),
				buttonText: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_REDIRECT'),
				onButtonTap: openChecklist,
			});
		}

		/**
		 * @param {number|string} itemId
		 * @param {string} entityMemberType
		 */
		openUserSelector(itemId, entityMemberType)
		{
			const item = this.checklist.getItemById(itemId);
			const memberType = item.getMemberType(entityMemberType);
			const initSelectedIds = Object.values(item.getMembers())
				.filter(({ type }) => type === memberType)
				.map(({ id }) => id);

			const selector = EntitySelectorFactory.createByType(EntitySelectorFactoryType.USER, {
				provider: {
					context: `TASKS_MEMBER_SELECTOR_EDIT_${entityMemberType}`,
					options: {
						useLettersForEmptyAvatar: true,
					},
				},
				initSelectedIds,
				createOptions: {
					enableCreation: false,
				},
				allowMultipleSelection: true,
				events: {
					onClose: (members) => {
						this.handleOnChangeMembers({ members, item, memberType });
						this.handleOnChange();
					},
					onViewRemoved: () => {
						this.updateActionMenu();
						this.updateRows({ itemIds: [item.getId()], animation: 'fade', saveFocus: true });
					},
				},
				widgetParams: {
					title: Loc.getMessage(`TASKSMOBILE_LAYOUT_CHECKLIST_${entityMemberType.toUpperCase()}_SELECTOR_TITLE`),
					backdrop: {
						mediumPositionPercent: 70,
					},
				},
			});

			return selector.show({}, this.parentWidget);
		}

		openUserSelectionManager(itemId)
		{
			const item = this.checklist.getItemById(itemId);

			const sections = {
				accomplice: {
					isMultiple: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_TITLE_ACCOMPLICE'),
					providerContext: 'TASKS_MEMBER_SELECTOR_EDIT_accomplice',
				},
				auditor: {
					isMultiple: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_TITLE_AUDITOR'),
					providerContext: 'TASKS_MEMBER_SELECTOR_EDIT_auditor',
				},
			};

			const members = item.getMembers();
			const users = Object.keys(item.getMembers()).map((id) => {
				const { type, name = '', image = '' } = members[id];

				return {
					id: Number(id),
					section: item.getMemberType(type),
					title: name,
					image,
				};
			});

			this.saveFocus = true;
			void UserSelectionManager.open({
				users,
				sections,
				uniqueUserInSections: true,
				parentWidget: this.getParentWidget(),
				useLettersForEmptyAvatar: true,
				onChange: (changeMembers) => {
					this.handleOnChangeMembers({ members: changeMembers, item });
					this.updateActionMenu();
				},
				onClose: () => {
					this.updateRows({ itemIds: [itemId], animation: 'fade', saveFocus: true });
				},
			});
		}

		getFocusedItemRef()
		{
			if (this.focusedItemId)
			{
				return this.itemRefMap.get(this.focusedItemId);
			}

			return null;
		}

		/**
		 * @param {CheckListFlatTreeItem} focusedItem
		 */
		handleShowMenu(focusedItem)
		{
			if (!this.menuRef)
			{
				return;
			}

			const isStubMenu = this.checklistQueue.getElementPosition(STUB_MENU.key);
			if (!isStubMenu)
			{
				void this.checklistQueue.appendRows([STUB_MENU], 'none');
			}

			this.menuRef.setItem(focusedItem);
			this.menuRef.show();
		}

		handleHideMenu()
		{
			if (!this.menuRef)
			{
				return;
			}

			void this.deleteRows([STUB_MENU.key], 'none');
			this.menuRef.hide();
		}

		/**
		 * @private
		 * @param {number} focusedItemId
		 * @return {string[]}
		 */
		setFocused(focusedItemId)
		{
			const blurItemId = this.focusedItemId;
			this.focusedItemId = focusedItemId;

			this.checklist.getItemById(focusedItemId)?.focus();
			this.checklist.getItemById(blurItemId)?.blur();

			return [blurItemId, focusedItemId];
		}

		handleOnBlurFocusedItem()
		{
			const focusedItem = this.getFocusedItemRef();

			if (focusedItem)
			{
				focusedItem.textInputBlur();
			}
		}

		updateActionMenu()
		{
			this.menuRef?.refreshExtension();
		}

		/**
		 * @private
		 * @param values
		 * @return {*|*[]}
		 */
		prepareArray(values)
		{
			return unique(Array.isArray(values) ? values : [values]).filter(Boolean);
		}

		getFocusedItem()
		{
			return this.checklist.getItemById(this.focusedItemId);
		}
	}

	module.exports = { Checklist };
});
