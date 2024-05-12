/**
 * @module tasks/checklist/flat-tree
 */
jn.define('tasks/checklist/flat-tree', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Random } = require('utils/random');
	const { CheckListFlatTreeItem } = require('tasks/checklist/flat-tree/item');

	/**
	 * @class CheckListFlatTree
	 */
	class CheckListFlatTree
	{
		constructor(props)
		{
			const { checklistFlatTree, checklist, userId } = props;

			this.isSaved = false;
			this.userId = userId;
			this.taskId = 0;
			this.checklistFlatTree = checklistFlatTree || this.createFlatTree(checklist);
			this.checklist = this.createChecklistFlatTree();
			this.conditions = {};
		}

		/**
		 * @param {object} params
		 * @param {boolean} [params.addBlankItem]
		 * @param {number} [params.number]
		 * @return {CheckListFlatTree}
		 */
		static buildDefaultList(params = {})
		{
			const { addBlankItem = false, items = [], number = 0 } = params;

			const checklistNumber = number >= 1 ? number + 1 : '';
			const title = Loc.getMessage(
				'TASKSMOBILE_CHECKLIST_PARENT_DEFAULT_TEXT',
				{ '#number#': checklistNumber },
			).trim();

			const rootItem = CheckListFlatTreeItem.createItem({
				focused: false,
				fields: {
					title,
				},
			});

			const flatCheckList = new CheckListFlatTree({ checklist: rootItem });
			if (addBlankItem)
			{
				flatCheckList.addNewItem(flatCheckList.getTreeItem(rootItem));
			}

			items.forEach((item) => {
				flatCheckList.addNewItem(item);
			});

			return flatCheckList;
		}

		createFlatTree(checklistTree)
		{
			const flatList = [];
			const traverse = (descendant) => {
				const { descendants, ...item } = descendant;

				if (!item.key)
				{
					const nodeId = Random.getString();
					const fields = item.fields || {};
					const attachments = this.prepareAttachments(fields.attachments);

					item.isNew = false;
					item.index = flatList.length;
					item.focused = false;
					item.key = nodeId;
					item.type = CheckListFlatTreeItem.getItemType();
					item.nodeId = nodeId;
					item.id = fields?.id;
					fields.attachments = attachments;
					fields.totalCount = descendants.length;
				}

				item.fields.prevTitle = item.fields.title;

				flatList.push(item);

				if (descendants)
				{
					descendants.forEach((descendantItem) => traverse(descendantItem));
				}
			};

			traverse(checklistTree);

			return flatList;
		}

		createChecklistFlatTree()
		{
			return this.getChecklistFlatTree().map((item) => new CheckListFlatTreeItem({
				item,
				checklist: this,
			}));
		}

		getChecklist()
		{
			const { onlyMine, hideCompleted } = this.conditions || {};

			if (onlyMine || hideCompleted)
			{
				return this.checklist.filter((item) => {
					if (item.isRoot() || item.isAlwaysShow())
					{
						return true;
					}

					const conditionOnlyMine = this.shouldFilterOnlyMine(item);
					const conditionHideCompleted = this.shouldFilterHideCompleted(item);

					if (onlyMine && hideCompleted)
					{
						return conditionOnlyMine && conditionHideCompleted;
					}

					if (onlyMine)
					{
						return conditionOnlyMine;
					}

					if (hideCompleted)
					{
						return conditionHideCompleted;
					}

					return true;
				});
			}

			return this.checklist;
		}

		getChecklistItems()
		{
			return this.getChecklist();
		}

		shouldFilterOnlyMine(item)
		{
			if (item.isAlwaysShow())
			{
				return true;
			}

			const userId = this.getUserId();
			const checkOnlyMine = (checkItem) => checkItem.getMember(userId);

			return this.filterCondition(checkOnlyMine, item);
		}

		shouldFilterHideCompleted(item)
		{
			const checkHideCompleted = (checkItem) => !checkItem.getIsComplete();

			return this.filterCondition(checkHideCompleted, item);
		}

		filterCondition(callback, item)
		{
			const filterChildren = this.checklist
				.filter((child) => child.getParentId() === item.getId())
				.some((descendant) => callback(descendant));

			if (filterChildren)
			{
				return true;
			}

			return callback(item);
		}

		getChecklistFlatTree()
		{
			return this.checklistFlatTree;
		}

		/**
		 * @public
		 * @return {CheckListFlatTreeItem}
		 */
		getLastItem()
		{
			return this.checklist[this.getLength() - 1];
		}

		prepareAttachments(attachments)
		{
			const attachmentsFileInfo = {};
			Object.keys(attachments).forEach((id) => {
				const value = attachments[id];
				attachmentsFileInfo[id] = typeof attachments[id] === 'string' ? null : value;
			});

			return attachmentsFileInfo;
		}

		setUserId(userId)
		{
			this.userId = parseInt(userId, 10);
		}

		getUserId()
		{
			return this.userId;
		}

		setTaskId(taskId)
		{
			this.taskId = parseInt(taskId, 10);
		}

		getTaskId()
		{
			return this.taskId;
		}

		/**
		 * @public
		 * @param {object} conditions
		 * @param {boolean} [conditions.onlyMine]
		 * @param {boolean} [conditions.hideCompleted]
		 * @return {CheckListFlatTreeItem[]}
		 */
		getFilteredItems(conditions)
		{
			this.setConditions(conditions);
			const items = [...this.getChecklist()];
			this.setConditions({});

			return items;
		}

		/**
		 * @public
		 * @return {(CheckListFlatTreeItem|null)[]}
		 */
		getTreeItems()
		{
			return this.checklist;
		}

		/**
		 * @public
		 * @param {object} item
		 * @return {CheckListFlatTreeItem|null}
		 */
		getTreeItem(item)
		{
			return item ? new CheckListFlatTreeItem({ item, checklist: this }) : null;
		}

		addItem(item)
		{
			item.setCheckList(this);
			const parent = item.getParent();
			const parentId = parent?.getId() || this.getRootItem().getId();

			if (!parent)
			{
				item.setParentId(parentId);
			}

			this.checklist.push(item);

			this.updateIndexes(parentId);
		}

		/**
		 * @public
		 * @param {CheckListFlatTreeItem} prevItem
		 * @returns CheckListFlatTreeItem
		 */
		addNewItem(prevItem)
		{
			const position = this.getInsertPosition(prevItem);

			return this.insertItemToChecklist(prevItem, position);
		}

		/**
		 * @public
		 * @param {CheckListFlatTreeItem} prevItem
		 * @param {number} position
		 * @returns CheckListFlatTreeItem
		 */
		insertItemToChecklist(prevItem, position)
		{
			const id = prevItem.getId();
			const parentId = prevItem.getParentId() || id;
			const newItem = this.getTreeItem(
				CheckListFlatTreeItem.createItem({
					isNew: true,
					fields: {
						parentId,
						displaySortIndex: prevItem.getDisplaySortIndex(),
					},
				}),
			);

			newItem.updateListViewType();

			if (position === null)
			{
				this.checklist.push(newItem);
			}
			else
			{
				this.checklist.splice(position, 0, newItem);
			}

			this.updateIndexes(parentId);

			return newItem;
		}

		getInsertPosition(item)
		{
			if (!item)
			{
				return this.getLength() + 1;
			}

			const id = item.getId();
			const currentIndex = this.getIndexById(id);
			const descendantsCount = this.getDescendantsCount(id, true);

			return currentIndex + descendantsCount + 1;
		}

		/**
		 * @public
		 * @param {CheckListFlatTreeItem} item
		 * @return {string[]}
		 */
		removeItem(item)
		{
			if (!this.hasItem(item))
			{
				return;
			}

			const removeIds = [item.getKey()];
			const descendants = this.getDescendants(item.getId(), true);
			if (descendants.length > 0)
			{
				descendants.forEach((descendant) => {
					this.removeById(descendant.getId());
					removeIds.push(descendant.getKey());
				});
			}

			this.removeById(item.getId());
			this.updateIndexes(item.getParentId());

			return removeIds.filter(Boolean);
		}

		removeById(id)
		{
			const position = this.getIndexById(id);
			this.checklist.splice(position, 1);

			return position;
		}

		getIndexById(id)
		{
			return this.getChecklist().findIndex((item) => item.getId() === id);
		}

		getIndexByNodeId(nodeId)
		{
			return this.checklist.findIndex(({ nodeId: itemNodeId }) => nodeId === itemNodeId);
		}

		getClosestElement(item)
		{
			const position = this.getIndexById(item.getId());
			const next = this.getChecklist()[position + 1];
			const prev = this.getChecklist()[position - 1];

			return prev || next;
		}

		getSiblings(item)
		{
			const parentId = item.getParentId();

			return this.checklist.filter((checklistItem) => parentId === checklistItem.getParentId());
		}

		getPrevSiblingById(id)
		{
			const item = this.getItemById(id);
			const siblings = this.getSiblings(item);
			const currentIndex = siblings.findIndex((sibling) => sibling.getId() === item.getId());
			const prevIndex = currentIndex - 1;

			if (prevIndex < 0)
			{
				return null;
			}

			return siblings[prevIndex];
		}

		/**
		 * @public
		 * @param {number|string} id
		 * @return {CheckListFlatTreeItem}
		 */
		getItemById(id)
		{
			return this.checklist?.find((item) => item.getId() === id);
		}

		/**
		 * @public
		 * @param {CheckListFlatTreeItem} item
		 * @return {Boolean}
		 */
		hasItem(item)
		{
			return Boolean(this.getItemById(item.getId()));
		}

		getLength()
		{
			return this.getChecklist().length;
		}

		setCollapsed(nodeId, isCollapse)
		{
			const item = this.checklist.find(({ nodeId: itemNodeId }) => itemNodeId === nodeId);

			item.fields.isCollapse = isCollapse;
		}

		getDescendants(id, deep = false)
		{
			const childItems = [];
			const traverse = (item) => {
				const children = this.getChecklist().filter((child) => child.getParentId() === item.getId());
				childItems.push(...children);

				if (deep)
				{
					children.forEach((child) => traverse(child));
				}
			};

			const parentElement = this.getItemById(id);
			if (parentElement)
			{
				traverse(parentElement);
			}

			return childItems;
		}

		getChecklistItemCount()
		{
			return this.getDescendants(this.getId(), true).length;
		}

		getDescendantsCount(id, deep = false)
		{
			return this.getDescendants(id, deep).length;
		}

		getCompleteCount(id)
		{
			const descendants = this.getDescendants(id);

			return descendants.filter(({ fields }) => fields.isComplete).length;
		}

		updateIndexes(id)
		{
			if (!id)
			{
				return;
			}

			this.updateSortIndexes(id);
			this.updateDisplaySortIndexes(id);
		}

		updateSortIndexes(id)
		{
			let sortIndex = 0;
			this.getDescendants(id).forEach((descendant) => {
				descendant.setSortIndex(sortIndex);
				sortIndex += 1;
			});
		}

		updateDisplaySortIndexes(id, sortIndex)
		{
			const item = this.getItemById(id);
			if (!id || !item)
			{
				return;
			}

			item.setDisplaySortIndex(sortIndex || item.getDisplaySortIndex());
			const parentSortIndex = item.getDisplaySortIndex() ? `${item.getDisplaySortIndex()}.` : '';

			let localSortIndex = 0;
			this.getDescendants(id).forEach((descendant) => {
				localSortIndex += 1;
				const newSortIndex = `${parentSortIndex}${localSortIndex}`;
				this.updateDisplaySortIndexes(descendant.getId(), newSortIndex);
			});
		}

		setFields(fields)
		{
			const availableFields = new Set([
				'id',
				'parentId',
				'title',
				'sortIndex',
				'displaySortIndex',
				'isComplete',
				'isImportant',
				'isSelected',
				'isCollapse',
				'completedCount',
				'totalCount',
				'members',
				'attachments',
			]);

			Object.keys(fields).forEach((name) => {
				const camelCaseName = this.snakeToCamelCase(name);

				if (availableFields.has(name))
				{
					const snakeCaseName = this.camelToSnakeCase(name);
					const setMethod = this[this.snakeToCamelCase(`SET_${snakeCaseName}`)].bind(this);
					setMethod(fields[name]);
				}
				else if (availableFields.has(camelCaseName))
				{
					const setMethod = this[this.snakeToCamelCase(`SET_${name}`)].bind(this);
					setMethod(fields[name]);
				}
			});
		}

		/**
		 * @public
		 * @param {object} conditions
		 * @param {boolean} [conditions.onlyMine]
		 * @param {boolean} [conditions.hideCompleted]
		 * @param {boolean} reload
		 */
		setConditions(conditions, reload)
		{
			if (reload)
			{
				this.checklist.forEach((item) => {
					item.setAlwaysShow(false);
				});
			}

			this.conditions = conditions;
		}

		camelToSnakeCase(string)
		{
			let snakeCaseString = string;

			if (BX.type.isString(snakeCaseString))
			{
				snakeCaseString = snakeCaseString.replaceAll(/(.)([A-Z])/g, '$1_$2').toUpperCase();
			}

			return snakeCaseString;
		}

		snakeToCamelCase(string)
		{
			let camelCaseString = string;

			if (BX.type.isString(camelCaseString))
			{
				camelCaseString = camelCaseString.toLowerCase();

				camelCaseString = camelCaseString
					.replaceAll(
						/[\s_-]+(.)?/g,
						(match, chr) => {
							return (chr ? chr.toUpperCase() : '');
						},
					)
				;

				return camelCaseString.slice(0, 1).toLowerCase() + camelCaseString.slice(1);
			}

			return camelCaseString;
		}

		/**
		 * @return {CheckListFlatTreeItem}
		 */
		getRootItem()
		{
			return this.checklist.find((item) => item.getParentId() === 0);
		}

		getId()
		{
			return this.getRootItem()?.getId();
		}

		getFocusedItemId()
		{
			const focusedItem = this.checklist.find((item) => item.isFocused());

			return focusedItem && focusedItem.getId();
		}

		getRequestData()
		{
			return this.checklist.map((item) => {
				let title = item.getTitle();

				if (!title)
				{
					title = item.getPrevTitle() || Loc.getMessage('TASKSMOBILE_TREE_CHECKLIST_ITEM_DEFAULT_TITLE');
					item.setTitle(title);
				}

				const parent = item.getParent();
				const itemId = item.getId();
				const parentId = item.getParentId();

				const itemRequestData = {
					NODE_ID: item.getNodeId(),
					PARENT_NODE_ID: parent ? parent.getNodeId() : 0,
					ID: Type.isInteger(itemId) ? itemId : null,
					PARENT_ID: Type.isInteger(parentId) ? parentId : null,
					TITLE: title,
					SORT_INDEX: item.getSortIndex(),
					IS_COMPLETE: item.getIsComplete() ? 1 : 0,
					IS_IMPORTANT: item.getIsImportant() ? 1 : 0,
					ATTACHMENTS: {},
					MEMBERS: {},
				};

				if (item.hasAttachments())
				{
					const attachments = item.getAttachments();
					Object.keys(attachments).forEach((id) => {
						if (attachments[id])
						{
							const { serverFileId } = attachments[id];

							itemRequestData.ATTACHMENTS[serverFileId] = serverFileId;
						}
					});
				}

				const members = item.getMembers();
				Object.keys(item.getMembers()).forEach((id) => {
					const { type, name } = members[id];
					itemRequestData.MEMBERS[id] = { TYPE: type, NAME: name };
				});

				return itemRequestData;
			}).filter(Boolean);
		}

		getShowItems()
		{
			return this.getChecklist().filter((item) => !item.isRoot()).length === 0;
		}
	}

	module.exports = { CheckListFlatTree };
});
