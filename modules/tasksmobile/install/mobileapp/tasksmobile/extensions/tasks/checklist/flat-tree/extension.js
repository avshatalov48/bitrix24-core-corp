/**
 * @module tasks/checklist/flat-tree
 */
jn.define('tasks/checklist/flat-tree', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { isNil } = require('utils/type');
	const { escapeRegExp } = require('utils/string');
	const { Random } = require('utils/random');
	const { mergeImmutable } = require('utils/object');
	const { CheckListFlatTreeItem } = require('tasks/checklist/flat-tree/item');

	/**
	 * {typedef} CheckListFlatTreeProps
	 * @property {boolean} [autoCompleteItem=true]
	 * @property {number} [userId]
	 * @property {number} [taskId]
	 * @property {boolean} [hideCompleted]
	 * @property {Array} [checklistFlatTree]
	 * @property {Object} [checklist]
	 *
	 * @class CheckListFlatTree
	 */
	class CheckListFlatTree
	{
		constructor(props)
		{
			const {
				taskId,
				userId,
				hideCompleted,
			} = props;

			this.props = props;

			this.isSaved = false;
			this.userId = userId;
			this.taskId = taskId;
			this.conditions = { hideCompleted };
			this.checklist = this.createChecklist(props);
			this.rootItem = this.findRootItem();

			this.updateCompletedItems();
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
					sortIndex: checklistNumber,
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

		createChecklist(props)
		{
			const { checklistFlatTree, checklist } = props;
			const flatTree = checklistFlatTree || this.createFlatTree(checklist);

			return flatTree.map((item) => new CheckListFlatTreeItem({
				item,
				checklist: this,
			}));
		}

		createFlatTree(checklistTree)
		{
			const flatList = [];
			const copiedIdMap = {};

			const traverse = (descendant, isRoot = false) => {
				const { descendants, ...item } = descendant;

				if (!item.key)
				{
					const nodeId = Number(item.nodeId) > 0 ? Random.getString() : item.nodeId;
					const fields = item.fields || {};
					const attachments = this.prepareAttachments(fields.attachments);

					item.isRoot = isRoot;
					item.isNew = false;
					item.index = flatList.length;
					item.focused = false;
					item.key = String(nodeId);
					item.type = CheckListFlatTreeItem.getItemType();
					item.nodeId = nodeId;
					item.id = fields?.id || nodeId;
					fields.title = this.#preparingTitleForView(item.fields);
					fields.attachments = attachments;
					fields.totalCount = descendants.length;

					if (fields.copiedId)
					{
						copiedIdMap[fields.copiedId] = item.id;
					}
				}

				flatList.push(item);

				if (descendants)
				{
					descendants.forEach((descendantItem) => traverse(descendantItem));
				}
			};

			traverse(checklistTree, true);

			return flatList.map((item) => {
				const parentId = item.fields.parentId;

				if (parentId > 0 && copiedIdMap[parentId])
				{
					return mergeImmutable(item, {
						fields: {
							parentId: copiedIdMap[parentId],
							parentNodeId: copiedIdMap[parentId],
							parentCopiedId: parentId,
						},
					});
				}

				return item;
			});
		}

		getFlatTree()
		{
			return this.getChecklist().map((checklistItem) => checklistItem.getItem());
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

		/**
		 * @param {CheckListFlatTreeItem} item
		 * @param {Array<string | number>} moveIds
		 */
		addMovedItem(item, moveIds = [])
		{
			item.setCheckList(this);
			if (!moveIds.includes(item.getParentId()))
			{
				item.setParentId(this.getId());
				item.setDisplaySortIndex('');
				item.setSortIndex(0);
			}

			this.checklist.push(item);

			this.updateCounters(this.rootItem);
			this.updateIndexes(this.rootItem);
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
			let parent = prevItem.getParent();
			let parentId = parent?.getId();

			if (prevItem.isRoot())
			{
				parent = prevItem;
				parentId = id;
			}

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

			parent.updateTotalCount();
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
				return null;
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
			const parent = item.getParent();
			this.updateIndexes(parent.getId());
			this.updateCounters(parent);

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
		 * @param {number} index
		 * @return {CheckListFlatTreeItem}
		 */
		getItemByIndex(index)
		{
			return this.checklist[index];
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

		/**
		 * @return {number}
		 */
		getDescendantsCount(id, deep = false)
		{
			return this.getDescendants(id, deep).length;
		}

		/**
		 * @return {number}
		 */
		getCompleteCount()
		{
			return this.getRootItem().getCompletedCount();
		}

		/**
		 * @return {number}
		 */
		getUncompleteCount()
		{
			const rootItem = this.getRootItem();
			const totalCount = rootItem.getTotalCount();
			const completeCount = rootItem.getCompletedCount();

			return totalCount - completeCount;
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

		/**
		 *
		 * @param {CheckListFlatTreeItem} item
		 */
		updateCounters(item)
		{
			if (!item)
			{
				return;
			}

			item.updateTotalCount();
			item.updateCompletedCount();
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

		findRootItem()
		{
			return this.checklist.find((item) => item.isRoot());
		}

		/**
		 * @return {CheckListFlatTreeItem}
		 */
		getRootItem()
		{
			return this.rootItem;
		}

		getId()
		{
			return this.rootItem.getId();
		}

		getFocusedItemId()
		{
			const focusedItem = this.checklist.find((item) => item.isFocused());

			return focusedItem && focusedItem.getId();
		}

		getRequestData()
		{
			return this.checklist
				.map((item) => this.prepareChecklistItemDataForSaving(item))
				.filter(Boolean);
		}

		prepareChecklistItemDataForSaving(item)
		{
			const parent = item.getParent();
			const itemId = item.getFieldId();
			const copiedId = item.getCopiedId();
			const title = this.#getTitleForSaving(item);
			const members = Object.values(item.getMembers());
			const parentId = item.getParentId();

			if (!title || isNil(parentId))
			{
				return null;
			}

			const itemRequestData = {
				NODE_ID: item.getNodeId(),
				PARENT_NODE_ID: parent ? parent.getNodeId() : 0,
				PARENT_ID: Type.isInteger(parentId) ? parentId : null,
				TITLE: title,
				SORT_INDEX: item.getSortIndex(),
				IS_COMPLETE: Number(item.getIsComplete()),
				IS_IMPORTANT: Number(item.getIsImportant()),
			};

			if (Type.isInteger(itemId))
			{
				itemRequestData.ID = itemId;
			}

			if (copiedId)
			{
				itemRequestData.COPIED_ID = copiedId;
			}

			if (item.hasAttachments())
			{
				itemRequestData.ATTACHMENTS = {};
				const attachments = item.getAttachments();

				Object.keys(attachments).forEach((id) => {
					const { serverFileId, token } = attachments[id];
					const attachmentKey = token && serverFileId ? serverFileId : id;
					itemRequestData.ATTACHMENTS[attachmentKey] = serverFileId;
				});
			}

			if (members.length > 0)
			{
				itemRequestData.MEMBERS = {};
				members.forEach(({ id, type, name }) => {
					itemRequestData.MEMBERS[id] = { TYPE: type, NAME: name };
				});
			}

			return itemRequestData;
		}

		#getTitleForSaving(item)
		{
			let title = item.getTitle();
			const members = Object.values(item.getMembers());

			if (members.length > 0)
			{
				return members.reduce(
					(memberTitle, { name }) => (title.includes(name) ? memberTitle : `${memberTitle} ${name}`),
					title,
				);
			}

			if (!title)
			{
				title = Loc.getMessage('TASKSMOBILE_TREE_CHECKLIST_ITEM_DEFAULT_TITLE');
				item.setTitle(title);
			}

			return title;
		}

		#preparingTitleForView(item)
		{
			const { title, members } = item;

			let modifiedText = title.trim();
			let foundMember = false;

			do
			{
				foundMember = false;
				for (const member of Object.values(members))
				{
					const regex = new RegExp(`${escapeRegExp(member.name)}\\s*$`);
					if (regex.test(modifiedText))
					{
						modifiedText = modifiedText.replace(regex, '').trim();
						foundMember = true;
						break;
					}
				}
			}
			while (foundMember);

			return modifiedText;
		}

		getShowItems()
		{
			return this.getChecklist().filter((item) => !item.isRoot()).length === 0;
		}

		canAdd()
		{
			return this.rootItem.checkCanAdd();
		}

		canUpdate()
		{
			return this.rootItem.checkCanUpdate();
		}

		canRemove()
		{
			return this.rootItem.checkCanRemove();
		}

		canAddAccomplice()
		{
			return this.rootItem.checkCanAddAccomplice();
		}

		getAccessRestrictions()
		{
			return {
				add: this.canAdd(),
				update: this.canUpdate(),
				remove: this.canRemove(),
				addAccomplice: this.canAddAccomplice(),
			};
		}

		isAutoCompleteItem()
		{
			const { autoCompleteItem } = this.props;

			return Boolean(autoCompleteItem);
		}

		updateCompletedItems()
		{
			this.getChecklistItems().forEach((item) => {
				if (item.isRoot())
				{
					item.updateCompletedCount();
				}
			});
		}
	}

	module.exports = { CheckListFlatTree };
});
