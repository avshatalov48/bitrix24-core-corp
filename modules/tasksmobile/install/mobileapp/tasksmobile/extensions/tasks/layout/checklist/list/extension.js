/**
 * @module tasks/layout/checklist/list
 */
jn.define('tasks/layout/checklist/list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { unique } = require('utils/array');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { Item } = require('tasks/layout/checklist/list/src/item');
	const { EntitySelectorFactory } = require('selector/widget/factory');

	/**
	 * @class Checklist
	 */
	class Checklist extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {ListViewMethods} */
			this.listRef = null;
			this.focusedItemId = null;
			this.queueActionListView = [];
			this.inProgressUpdateListView = false;

			this.handleOnAddItem = this.handleOnAddItem.bind(this);
			this.handleOnRemoveItem = this.handleOnRemoveItem.bind(this);
			this.handleOnToggleComplete = this.handleOnToggleComplete.bind(this);
			this.handleOnShowSelector = this.handleOnShowSelector.bind(this);
			this.handleOnMemberSelected = this.handleOnMemberSelected.bind(this);
			this.handleOnFocus = this.handleOnFocus.bind(this);
			this.onShowChecklistsMenu = this.onShowChecklistsMenu.bind(this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnTabMove = this.handleOnTabMove.bind(this);

			/** @type {ContextMenu} */
			this.checklistsMenu = null;
			this.checklist.setTaskId(props.taskId);
			this.setFocused(props.checklist.getFocusedItemId());
		}

		/** @type {CheckListFlatTree} */
		get checklist()
		{
			const { checklist } = this.props;

			return checklist;
		}

		handleOnFocus(focusedItem)
		{
			const [focusedItemId, blurItemId] = this.setFocused(focusedItem.getId());
			const blurItem = this.checklist.getItemById(blurItemId);

			const shouldBeDeletedOnBlur = blurItem?.shouldBeDeletedOnBlur();
			const updateItems = shouldBeDeletedOnBlur ? [focusedItemId] : [focusedItemId, blurItemId];
			const actions = [this.updateItems(updateItems)];

			if (shouldBeDeletedOnBlur)
			{
				actions.push(this.deleteRowsByKeys(blurItem));
			}

			this.listViewUpdate(actions);
		}

		setFocused(focusedItemId)
		{
			const blurItemId = this.focusedItemId;
			this.focusedItemId = focusedItemId;

			const item = (id) => this.checklist.getItemById(id);

			item()?.focus();
			item()?.blur();

			return [focusedItemId, blurItemId];
		}

		handleOnAddItem(prevItem)
		{
			const { position, item } = this.checklist.addNewItem(prevItem, prevItem.getParentId());
			const itemsToChangeState = this.setFocused(item.getId());
			const insertRows = this.insertRows([item.getItem()], position);
			const updateCounters = this.updateCounter(item, itemsToChangeState);

			return this.listViewUpdate([insertRows, updateCounters]);
		}

		handleOnRemoveItem(item)
		{
			const deleteRows = this.deleteRowsByKeys(item);
			const updateCounters = this.updateCounter(item);

			return this.listViewUpdate([deleteRows, updateCounters]);
		}

		handleOnToggleComplete(item)
		{
			const updateCounters = this.updateCounter(item);

			return this.listViewUpdate(updateCounters);
		}

		updateItems(ids)
		{
			const itemIds = Array.isArray(ids) ? ids : [ids];
			const items = [];

			console.log(`focused item ${this.checklist.getItemById(this.focusedItemId)?.getTitle()}, (${this.focusedItemId})`);

			unique(itemIds).forEach((id) => {
				const treeItem = this.checklist.getItemById(id);
				if (treeItem)
				{
					items.push(treeItem.getItem());
				}
			});

			return () => this.updateRows(items);
		}

		updateCounter(item, itemsIds = [])
		{
			if (!item)
			{
				return Promise.reject();
			}

			const parentItem = item.getParent();

			return this.updateItems([item.getId(), parentItem?.getId(), ...itemsIds]);
		}

		listViewUpdate(actions)
		{
			if (!this.listRef)
			{
				return;
			}

			const promisesActions = Array.isArray(actions) ? actions : [actions];

			this.queueActionListView.push(...promisesActions);

			this.runUpdateListView();
		}

		runUpdateListView()
		{
			if (!this.inProgressUpdateListView && this.queueActionListView.length > 0)
			{
				this.inProgressUpdateListView = true;
				const promise = this.queueActionListView.shift();

				promise().then(() => {
					this.inProgressUpdateListView = false;
					this.runUpdateListView();
				}).catch(console.error);
			}
		}

		deleteRowsByKeys(item)
		{
			return () => new Promise((resolve) => {
				this.listRef.deleteRowsByKeys(this.checklist.removeItem(item), 'automatic', resolve);
			});
		}

		insertRows(items, position)
		{
			return () => this.listRef.insertRows(items, 0, position, 'automatic');
		}

		updateRows(items)
		{
			console.log(
				'--->updateRows<---',
				items.map((item) => item.fields.title),
			);

			return this.listRef.updateRows(items);
		}

		renderItem(item)
		{
			const { checklist, parentWidget, diskConfig } = this.props;
			const checkListItem = checklist.getItemById(item.id);

			if (!checkListItem)
			{
				console.log(`no item ${item.id}`);

				return null;
			}

			return new Item({
				item: checkListItem,
				diskConfig,
				parentWidget,
				isFocused: this.focusedItemId === item.id,
				onAdd: this.handleOnAddItem,
				onRemove: this.handleOnRemoveItem,
				onToggleComplete: this.handleOnToggleComplete,
				onTabMove: this.handleOnTabMove,
				onShowSelector: this.handleOnShowSelector,
				onMoveToCheckList: this.onShowChecklistsMenu,
				onFocus: this.handleOnFocus,
				onChange: this.handleOnChange,
			});
		}

		handleOnTabMove(item, direction)
		{
			const moved = direction === 'left' ? item.tabOut() : item.tabIn();
			if (!moved)
			{
				return;
			}

			this.listViewUpdate(this.updateItems(item.getMoveIds()));
		}

		onShowChecklistsMenu(moveIds)
		{
			const { parentWidget, checklist, onMoveToCheckList } = this.props;
			const closeList = (checkListId) => new Promise((resolve) => {
				const sourceCheckList = this.checklist.getRootItem();
				this.checklistsMenu.close(() => {
					onMoveToCheckList({
						moveIds,
						toCheckListId: checkListId,
						sourceCheckListId: sourceCheckList.getId(),
					});
				});
				resolve();
			});

			const actions = [];
			checklist.forEach((checkList) => {
				const rootItem = checkList.getRootItem();

				actions.push({
					id: rootItem.getId(),
					title: rootItem.getTitle(),
					onClickCallback: () => closeList(rootItem.getId()),
				});
			});

			actions.push({
				id: 'new-list',
				title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MOVE_TO_NEW'),
				// isDisabled: !rootItem.checkCanAdd(),
				onClickCallback: closeList,
			});

			this.checklistsMenu = new ContextMenu({
				actions,
				params: {
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MOVE_TO'), showCancelButton: false,
				},
			});

			this.checklistsMenu.show(parentWidget);
		}

		handleOnMemberSelected(member, title)
		{
			this.checklist.addMember(member);

			this.handleOnChangeTitle(title);
		}

		handleOnShowSelector(params)
		{
			const { type } = params;
			const { parentWidget } = this.props;

			const selector = EntitySelectorFactory.createByType('user', {
				provider: {
					context: `TASKS_MEMBER_SELECTOR_EDIT_${type}`,
				},
				createOptions: {
					enableCreation: false,
				},
				initSelectedIds: [],
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: (members) => {
						members.forEach(({ id, title = '', imageUrl = null }) => {
							this.addMember({
								...params,
								member: { id, type, nameFormatted: title, avatar: imageUrl },
							});
						});
					},
				},
				widgetParams: {
					title: Loc.getMessage(`TASKSMOBILE_LAYOUT_CHECKLIST_${type.toUpperCase()}_SELECTOR_TITLE`),
					backdrop: {
						mediumPositionPercent: 70,
					},
				},
			});

			return selector.show({}, parentWidget);
		}

		addMember(params)
		{
			const { member, item, textRef } = params;

			const itemTitle = item.getTitle().trim();
			/** @type {ItemTextField} */
			const ref = textRef();
			const getCursorPosition = () => {
				if (ref)
				{
					return ref.getCursorPosition();
				}

				return itemTitle.length;
			};

			const slicePosition = getCursorPosition();
			const startPosition = itemTitle.slice(0, slicePosition).trim();
			const endPosition = itemTitle.slice(slicePosition, itemTitle.length).trim();
			const title = `${startPosition} ${member.nameFormatted} ${endPosition}`;
			ref.handleOnChange(title);
			item.addMember(member);
		}

		render()
		{
			const { checklist } = this.props;

			return View(
				{
					style: {
						width: '100%',
						height: '100%',
					},
					resizableByKeyboard: true,
				},
				ListView({
					ref: (ref) => {
						this.listRef = ref;
					},
					style: {
						width: '100%',
						height: '100%',
					},
					data: [
						{
							items: checklist.getItems(),
						},
					],
					isRefreshing: false,
					renderItem: (item) => this.renderItem(item),
				}),
			);
		}

		handleOnChange()
		{
			const { onChange } = this.props;

			if (onChange)
			{
				onChange();
			}
		}
	}

	module.exports = { Checklist };
});
