/**
 * @module tasks/layout/checklist/list
 */
jn.define('tasks/layout/checklist/list', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { unique } = require('utils/array');
	const { showToast } = require('toast/base');
	const { useCallback } = require('utils/function');
	const { ListViewQueueWorker } = require('layout/list-view-queue-worker');
	const { UserSelectionManager } = require('layout/ui/user-selection-manager');
	const { ChecklistItem } = require('tasks/layout/checklist/list/src/item');
	const { ChecklistActionsMenu } = require('tasks/layout/checklist/list/src/menu/actions-menu');
	const { ChecklistsMenu } = require('tasks/layout/checklist/list/src/menu/checklists-menu');
	const { buttonAddItemType, ButtonAdd } = require('tasks/layout/checklist/list/src/buttons/button-add-item');

	const IS_IOS = Application.getPlatform() === 'ios';

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

			this.handleOnAddItem = this.handleOnAddItem.bind(this);
			this.handleOnRemoveItem = this.handleOnRemoveItem.bind(this);
			this.handleOnToggleComplete = this.handleOnToggleComplete.bind(this);
			this.handleOnFocus = this.handleOnFocus.bind(this);
			this.handleOnBlur = this.handleOnBlur.bind(this);
			this.handleOnBlurItem = this.handleOnBlurItem.bind(this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.updateRows = this.updateRows.bind(this);
			this.handleOnTabMove = this.handleOnTabMove.bind(this);
			this.openUserSelectionManager = this.openUserSelectionManager.bind(this);
			this.handleOnShowChecklistsMenu = this.handleOnShowChecklistsMenu.bind(this);
			this.handleOnAddFile = this.handleOnAddFile.bind(this);
			this.handleOnToggleImportant = this.handleOnToggleImportant.bind(this);
			this.handleOnChangeMembers = this.handleOnChangeMembers.bind(this);

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

			this.state = {
				onlyMy: false,
				hideCompleted: false,
			};
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
		 * @param {boolean} [params.onlyMy]
		 * @param {boolean} [params.hideCompleted]
		 */
		reload(params)
		{
			this.setState(params);
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
		 * @param {number[]} itemsIds
		 */
		updateCounter({ item, itemsIds = [] })
		{
			return Promise.resolve();
			// if (!item)
			// {
			// 	return Promise.resolve();
			// }

			// const parentItem = item.getParent();
			// return this.updateRows([parentItem?.getId(), ...itemsIds]);
		}

		/**
		 * @param {number[]} ids
		 * @param {string} animation
		 */
		updateRows(ids, animation)
		{
			const items = [];
			this.prepareArray(ids).forEach((id) => {
				const treeItem = this.checklist.getItemById(id);
				if (treeItem)
				{
					treeItem.updateListViewType();
					items.push(treeItem.getItem());
				}
			});

			return this.checklistQueue.updateRows(items, animation);
		}

		/**
		 * @private
		 * @param {string[]} keys
		 * @return {function(): Promise}
		 */
		deleteRows(keys)
		{
			return this.checklistQueue.deleteRowsByKeys(keys, 'fade');
		}

		/**
		 * @param {object} item
		 * @param {number} position
		 */
		insertRows(item, position)
		{
			return this.checklistQueue.insertRows(item, 0, position, 'fade');
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 */
		scrollToItem(item)
		{
			const scrollTo = () => {
				const listViewRef = this.checklistQueue.getListViewRef();
				const position = listViewRef.getElementPosition(item.getKey());
				if (position)
				{
					const { section, index } = position;
					listViewRef.scrollTo(section, index, true);
				}
			};

			/**
			 * Need to wait until the item is added
			 */
			setTimeout(() => {
				if (IS_IOS)
				{
					this.handleOnFocusItem(item);
				}

				scrollTo();
			}, 300);
		}

		/**
		 * @private
		 * @return {CheckListFlatTreeItem[]}
		 */
		getItems()
		{
			const { checklist } = this.props;
			const { onlyMy, hideCompleted } = this.state;

			if (onlyMy)
			{
				return checklist.getOnlyMyItems();
			}

			if (hideCompleted)
			{
				return checklist.getUncompletedItems();
			}

			return checklist.getItems();
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
					style: {
						flex: 1,
						paddingBottom: 50,
						backgroundColor: AppTheme.colors.bgContentPrimary,
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
							items: [...this.getItems(), buttonAddItemType],
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
		 * @return {ChecklistItem|null}
		 */
		renderChecklistItem(item)
		{
			if (item.key === buttonAddItemType.key)
			{
				return ButtonAdd({ onClick: this.handleOnAddItem });
			}

			const { checklist, diskConfig } = this.props;
			const checklistItem = checklist.getItemById(item.id);

			if (!checklistItem)
			{
				console.log(`no item ${item.id}`);

				return null;
			}

			return new ChecklistItem({
				ref: ((itemRef) => {
					this.itemRefMap.set(checklistItem.getId(), itemRef);
				}),
				diskConfig,
				selectedOptions: this.state,
				parentWidget: this.getParentWidget(),
				item: checklistItem,
				isFocused: this.focusedItemId === item.id,
				onAdd: this.handleOnAddItem,
				onRemove: this.handleOnRemoveItem,
				onToggleComplete: this.handleOnToggleComplete,
				onFocus: this.handleOnFocus,
				onBlur: this.handleOnBlur,
				onChange: this.handleOnChange,
				updateRows: this.updateRows,
				openUserSelectionManager: this.openUserSelectionManager,
			});
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
				onBlur: this.handleOnBlurItem,
			});
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} focusedItem
		 */
		handleOnFocus(focusedItem)
		{
			const [blurItemId, focusedItemId] = this.setFocused(focusedItem.getId());
			const blurItem = this.checklist.getItemById(blurItemId);
			this.showMenu(focusedItem);

			const shouldBeDeletedOnBlur = blurItem && !blurItem.hasItemTitle() && (blurItemId !== focusedItemId);

			if (shouldBeDeletedOnBlur)
			{
				this.handleOnRemoveItem(blurItem);
			}

			// if (!IS_ANDROID)
			// {
			// 	this.scrollToItem(focusedItem);
			// }
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} blurItem
		 */
		handleOnBlur(blurItem)
		{
			const blurItemId = blurItem.getId();

			if (blurItemId === this.focusedItemId)
			{
				this.setFocused(null);
				this.hideMenu();

				if (!blurItem.hasItemTitle())
				{
					void this.handleOnRemoveItem(blurItem);
				}
			}
		}

		handleOnBlurItem()
		{
			const focusedItem = this.getFocusedItemRef();

			if (focusedItem)
			{
				focusedItem.textInputBlur();
			}
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 */
		handleOnFocusItem(item)
		{
			const focusedItem = this.itemRefMap.get(item.getId());

			if (focusedItem)
			{
				this.handleOnFocus(item);
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
				onChange({
					alert: {
						show: this.showPreventDismiss(),
						params: {
							title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ALERT_NOT_ROOT_TITLE'),
						},
					},
				});
			}
		}

		showPreventDismiss()
		{
			const rootItem = this.checklist.getRootItem();

			return !rootItem.hasItemTitle();
		}

		/**
		 * @private
		 */
		async handleOnAddItem(item)
		{
			const prevItem = item || this.checklist.getLastItem();
			if (!prevItem?.hasItemTitle())
			{
				return;
			}

			const { position, item: newItem } = this.checklist.addNewItem(prevItem, prevItem.getParentId());
			this.setFocused(newItem.getId());

			if (newItem)
			{
				await this.insertRows(newItem.getItem(), position);
				await this.updateCounter({ item: newItem });

				this.scrollToItem(newItem);
			}
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 */
		async handleOnRemoveItem(item)
		{
			if (!item.checkCanRemove())
			{
				return Promise.resolve();
			}
			const removeKeys = this.checklist.removeItem(item);
			await this.deleteRows(removeKeys);

			return this.updateCounter({ item });
		}

		/**
		 * @private
		 * @param {CheckListFlatTreeItem} item
		 */
		handleOnToggleComplete(item)
		{
			const { hideCompleted } = this.state;
			const itemId = item.getId();
			const itemRef = this.itemRefMap.get(itemId);
			itemRef.toggleCompleteText();

			if (hideCompleted)
			{
				setTimeout(() => {
					this.deleteRows([item.getKey()]);
				}, 1000);
			}

			this.updateCounter({ item });
		}

		async handleOnTabMove(item, direction)
		{
			const moved = direction === 'left' ? item.tabOut() : item.tabIn();

			if (!moved)
			{
				return;
			}

			await this.updateRows(item.getMoveIds());
			this.scrollToItem(item);
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
				sourceCheckListId: this.checklist.getId(),
			});
		}

		async moveItemToChecklist({ checkListId, moveIds })
		{
			const { onMoveToCheckList } = this.props;
			const focusedItemRef = this.getFocusedItemRef();
			focusedItemRef.textBlur();

			const parentWidget = this.getParentWidget();

			const openChecklist = await onMoveToCheckList({
				moveIds,
				toCheckListId: checkListId,
				sourceCheckListId: this.checklist.getId(),
			});

			showToast({
				layoutWidget: parentWidget,
				message: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ITEM_MOVED'),
				buttonText: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_REDIRECT'),
				onButtonTap: openChecklist,
			});
		}

		openUserSelectionManager(itemId)
		{
			const item = this.checklist.getItemById(itemId || this.focusedItemId);

			const sectionsData = {
				auditor: {
					isMultiple: false,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_TITLE_AUDITOR'),
				},
				accomplice: {
					isMultiple: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_TITLE_ACCOMPLICE'),
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

			void UserSelectionManager.open({
				users,
				sectionsData,
				uniqueUserInSections: true,
				parentWidget: this.getParentWidget(),
				onChange: (changeMembers) => {
					this.handleOnChangeMembers({ members: changeMembers, item });
				},
			});
		}

		handleOnChangeMembers({ members, item })
		{
			const membersMap = {};
			const itemId = item.getId();

			members.forEach(({ id, title, section, image }) => {
				membersMap[id] = {
					id,
					name: title,
					type: item.getMemberType(section),
					image,
				};
			});

			// itemRef.setMembersToText(members.map(({ title }) => title));
			item.setMembers(membersMap);
			this.updateRows([itemId]);
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
		showMenu(focusedItem)
		{
			if (!this.menuRef)
			{
				return;
			}

			this.menuRef.setItem(focusedItem);
			this.menuRef.show();
		}

		hideMenu()
		{
			if (!this.menuRef)
			{
				return;
			}

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

		/**
		 * @private
		 * @param values
		 * @return {*|*[]}
		 */
		prepareArray(values)
		{
			return unique(Array.isArray(values) ? values : [values]).filter(Boolean);
		}
	}

	module.exports = { Checklist };
});
