/**
 * @module tasks/checklist/tree
 */
jn.define('tasks/checklist/tree', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { NotifyManager } = require('notify-manager');

	/**
	 * @class CheckListTree
	 */
	class CheckListTree
	{
		/**
		 * @returns {CheckListTree}
		 */
		static buildTree(checkListTree)
		{
			const tree = new CheckListTree({});
			tree.setNodeId(0);

			if (
				Type.isUndefined(checkListTree)
				|| checkListTree.descendants.length === 0
			)
			{
				tree.addListItem(tree.buildDefaultList());

				tree.setActive(false);

				return tree;
			}

			Object.keys(checkListTree.descendants)
				.forEach((key) => {
					tree.add(CheckListTree.makeTree(checkListTree.descendants[key]));
				})
			;

			return tree;
		}

		static makeTree(checkListTree)
		{
			const fields = checkListTree.fields;
			const descendants = checkListTree.descendants;

			fields.action = checkListTree.action;

			const tree = new CheckListTree(fields);

			if (descendants)
			{
				Object.keys(descendants)
					.forEach((key) => {
						tree.add(CheckListTree.makeTree(descendants[key]));
					})
				;
			}

			return tree;
		}

		constructor(fields)
		{
			this.emitter = new JNEventEmitter();

			const { action } = fields;

			this.action = {
				canAdd: (action && 'add' in action) ? action.add : true,
				canAddAccomplice: (action && 'addAccomplice' in action) ? action.addAccomplice : true,
				canUpdate: (action && 'modify' in action) ? action.modify : true,
				canRemove: (action && 'remove' in action) ? action.remove : true,
				canToggle: (action && 'toggle' in action) ? action.toggle : true,
			};

			this.taskId = 0;
			this.active = true;
			this.loading = false;

			this.fields = {
				id: null,
				parentId: null,
				title: '',
				sortIndex: 0,
				displaySortIndex: '',
				isComplete: false,
				isImportant: false,
				isSelected: false,
				isCollapse: false,
				completedCount: 0,
				totalCount: 0,
				members: new Map(),
				attachments: {},
			};

			this.descendants = [];

			this.setFields(fields);

			this.focused = this.getTitle() === '';

			this.setNodeId(this.generateUniqueNodeId());
			this.setParent(null);
		}

		on(event, func)
		{
			this.emitter.on(event, func);

			return this;
		}

		off()
		{
			this.emitter.removeAll();
		}

		save(id, context = '')
		{
			if (!this.isRoot())
			{
				return Promise.resolve();
			}

			const taskId = Type.isUndefined(id) ? this.taskId : id;
			const items = this.getRequestData();

			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.checklist.save', {
					taskId,
					items: items.length > 0 ? items : null,
					parameters: {
						context,
					},
				}))
					.call()
					.then(
						(response) => {
							resolve(response.result);
						},
						(response) => {
							NotifyManager.showDefaultError();
							console.error(response);
							reject(response);
						},
					).catch(console.error);
			});
		}

		complete(itemId)
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.checklist.complete', {
					taskId: this.taskId,
					checkListItemId: itemId,
				}))
					.call()
					.then(
						(response) => {
							resolve(response.result);
						},
						(response) => {
							reject(response);
						},
					).catch(console.error);
			});
		}

		renew(itemId)
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.checklist.renew', {
					taskId: this.taskId,
					checkListItemId: itemId,
				}))
					.call()
					.then(
						(response) => {
							resolve(response.result);
						},
						(response) => {
							reject(response);
						},
					)
				;
			});
		}

		buildDefaultList(withEmpty = true)
		{
			const checkList = new CheckListTree({
				title: Loc.getMessage('TASKSMOBILE_CHECKLIST_PARENT_DEFAULT_TEXT')
					.replace('#number#', this.getDescendantsCount() + 1),
				totalCount: 1,
			});

			if (withEmpty)
			{
				const emptyInput = new CheckListTree({});
				checkList.add(emptyInput);
			}

			return checkList;
		}

		getRequestData(inputData = null)
		{
			const title = this.getTitle();
			let data = inputData || [];

			if (!this.isRoot() && title !== '' && title.length > 0)
			{
				data.push(this.getItemRequestData());
			}

			this.getDescendants().forEach((descendant) => {
				data = descendant.getRequestData(data);
			});

			return data;
		}

		getItemRequestData()
		{
			const itemRequestData = {
				NODE_ID: this.getNodeId(),
				PARENT_NODE_ID: this.getParent().getNodeId(),
				ID: this.getId(),
				PARENT_ID: this.getParentId(),
				TITLE: this.getTitle(),
				SORT_INDEX: this.getSortIndex(),
				IS_COMPLETE: this.getIsComplete(),
				IS_IMPORTANT: this.getIsImportant(),
				MEMBERS: {},
				ATTACHMENTS: this.getAttachments() || {},
			};

			this.getMembers().forEach((value, key) => {
				itemRequestData.MEMBERS[key] = { TYPE: value.type };
			});

			return itemRequestData;
		}

		addListItem(item = null, dependsOn = null, position = 'after')
		{
			const listItem = item || new CheckListTree({});

			if (dependsOn)
			{
				if (position === 'before')
				{
					this.addBefore(listItem, dependsOn);
				}
				else if (position === 'after')
				{
					this.addAfter(listItem, dependsOn);
				}
			}
			else
			{
				this.add(listItem);
			}

			this.updateIndexes();

			return listItem;
		}

		removeListItem()
		{
			const parent = this.getParent();

			parent.remove(this);
			parent.updateCounts();
			parent.updateIndexes();

			if (!parent.isRoot() && !parent.isList())
			{
				const list = this.getList();

				list.updateCounts();
				list.updateIndexes();
			}

			return true;
		}

		add(item, position = null)
		{
			item.setParent(this);

			if (position === null)
			{
				this.descendants.push(item);
			}
			else
			{
				this.descendants.splice(position, 0, item);
			}

			item.off();

			item.on('auditorAdd', (user) => this.emitter.emit('auditorAdd', [user]));
			item.on('accompliceAdd', (user) => this.emitter.emit('accompliceAdd', [user]));

			this.updateCounts();
		}

		addAfter(item, after)
		{
			const index = this.descendants.indexOf(after);
			if (index !== -1)
			{
				this.add(item, index + 1);
			}
		}

		addBefore(item, before)
		{
			const index = this.descendants.indexOf(before);
			if (index !== -1)
			{
				this.add(item, index);
			}
		}

		remove(item)
		{
			const index = this.descendants.indexOf(item);
			if (index !== -1)
			{
				this.descendants.splice(index, 1);

				this.updateCounts();
			}
		}

		isRoot()
		{
			return this.getNodeId() === 0 && this.getParent() === null;
		}

		isList()
		{
			return !this.isRoot() && this.getParent()?.isRoot();
		}

		isEmpty()
		{
			const isEmpty = this.isRoot()
				? this.getDescendantsCount() === 0
				: this.getList().getDescendantsCount() === 0
			;

			return isEmpty || !this.isActive();
		}

		getList()
		{
			let parent = this;

			while (!parent.getParent().isRoot())
			{
				parent = parent?.getParent();
			}

			return parent;
		}

		getRootNode()
		{
			let parent = this;

			while (parent?.getParent() !== null)
			{
				parent = parent?.getParent();
			}

			return parent;
		}

		makeChildOf(item, position = 'bottom')
		{
			if (item.getDescendantsCount() > 0)
			{
				const borderItems = {
					top: item.getFirstDescendant(),
					bottom: item.getLastDescendant(),
				};

				this.move(borderItems[position], position);
			}
			else
			{
				const oldParent = this.getParent();
				const newParent = item;

				oldParent.remove(this);
				newParent.add(this);

				this.updateParents(oldParent, newParent);
			}
		}

		move(item, position = 'bottom')
		{
			if (
				this.getNodeId() === item.getNodeId()
				|| this.findChild(item.getNodeId()) !== null
			)
			{
				return;
			}

			const oldParent = this.getParent();
			const newParent = item.getParent();

			oldParent.remove(this);

			if (position === 'top')
			{
				newParent.addBefore(this, item);
			}
			else
			{
				newParent.addAfter(this, item);
			}

			this.updateParents(oldParent, newParent);
		}

		tabIn()
		{
			if (!this.isFirstDescendant())
			{
				this.makeChildOf(this.getLeftSibling(), 'bottom');
			}
		}

		tabOut()
		{
			const parent = this.getParent();

			if (parent.isList())
			{
				return;
			}

			this.move(parent, 'bottom');
		}

		countCompletedCount(recursively = false)
		{
			let completedCount = 0;

			this.getDescendants().forEach((descendant) => {
				if (descendant.getIsComplete())
				{
					completedCount += 1;
				}

				if (recursively)
				{
					completedCount += descendant.countCompletedCount(recursively);
				}
			});

			return completedCount;
		}

		countTotalCount(recursively = false)
		{
			let totalCount = 0;

			if (recursively)
			{
				this.getDescendants().forEach((descendant) => {
					totalCount += 1;
					totalCount += descendant.countTotalCount(recursively);
				});
			}
			else
			{
				totalCount = this.getDescendantsCount();
			}

			return totalCount;
		}

		countRealCompletedCount()
		{
			let completedCount = Number(!this.isRoot() && !this.isList() && this.isActive() && this.getIsComplete());

			this.getDescendants().forEach((descendant) => {
				completedCount += descendant.countRealCompletedCount();
			});

			return completedCount;
		}

		countRealTotalCount()
		{
			let totalCount = Number(!this.isRoot() && !this.isList() && this.isActive());

			this.getDescendants().forEach((descendant) => {
				totalCount += descendant.countRealTotalCount();
			});

			return totalCount;
		}

		updateParents(oldParent, newParent)
		{
			if (oldParent === newParent)
			{
				newParent.updateIndexes();
			}
			else
			{
				oldParent.updateCounts();
				newParent.updateCounts();

				oldParent.updateIndexes();
				newParent.updateIndexes();
			}
		}

		updateCompletedCount()
		{
			const completedCount = this.countCompletedCount();
			this.setCompletedCount(completedCount);
		}

		updateTotalCount()
		{
			const totalCount = this.countTotalCount();
			this.setTotalCount(totalCount);
		}

		updateCounts()
		{
			this.updateCompletedCount();
			this.updateTotalCount();
		}

		updateIndexes()
		{
			this.updateSortIndexes();
			this.updateDisplaySortIndexes();
		}

		updateSortIndexes()
		{
			let sortIndex = 0;

			this.getDescendants().forEach((descendant) => {
				descendant.setSortIndex(sortIndex);
				sortIndex += 1;
			});
		}

		updateDisplaySortIndexes()
		{
			const parentSortIndex = (this.isList() || this.isRoot() ? '' : `${this.getDisplaySortIndex()}.`);
			let localSortIndex = 0;

			this.getDescendants().forEach((descendant) => {
				localSortIndex += 1;
				const newSortIndex = `${parentSortIndex}${localSortIndex}`;

				descendant.setDisplaySortIndex(newSortIndex);

				if (!descendant.isList())
				{
					// todo update layout with new index value = newSortIndex
				}

				descendant.updateDisplaySortIndexes();
			});
		}

		toggleComplete()
		{
			if (this.getList().getIsSelected() || !this.checkCanToggle())
			{
				return;
			}

			const isComplete = this.getIsComplete();

			this.setIsComplete(!isComplete);

			this.getParent().updateCounts();
			this.getList().updateCounts();
		}

		toggleImportant()
		{
			this.setIsImportant(!this.getIsImportant());
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

		getNodeId()
		{
			return this.nodeId;
		}

		setNodeId(nodeId)
		{
			this.nodeId = nodeId;
		}

		getParent()
		{
			return this.parent;
		}

		setParent(parent)
		{
			this.parent = parent;
		}

		getId()
		{
			return this.fields.id;
		}

		setId(id)
		{
			this.fields.id = id;
		}

		getParentId()
		{
			return this.fields.parentId;
		}

		setParentId(parentId)
		{
			this.fields.parentId = parentId;
		}

		getTitle()
		{
			return this.fields.title;
		}

		setTitle(title)
		{
			this.fields.title = title;
		}

		getSortIndex()
		{
			return this.fields.sortIndex;
		}

		setSortIndex(sortIndex)
		{
			this.fields.sortIndex = sortIndex;
		}

		getDisplaySortIndex()
		{
			return this.fields.displaySortIndex;
		}

		setDisplaySortIndex(displaySortIndex)
		{
			this.fields.displaySortIndex = displaySortIndex;
		}

		getIsComplete()
		{
			return this.fields.isComplete;
		}

		setIsComplete(isComplete)
		{
			this.fields.isComplete = isComplete;
		}

		getIsImportant()
		{
			return this.fields.isImportant;
		}

		setIsImportant(isImportant)
		{
			this.fields.isImportant = isImportant;
		}

		getIsSelected()
		{
			return this.fields.isSelected;
		}

		setIsSelected(isSelected)
		{
			this.fields.isSelected = isSelected;
		}

		getIsCollapse()
		{
			return this.fields.isCollapse;
		}

		setIsCollapse(isCollapse)
		{
			this.fields.isCollapse = isCollapse;
		}

		getCompletedCount()
		{
			return this.fields.completedCount;
		}

		setCompletedCount(completedCount)
		{
			this.fields.completedCount = completedCount;
		}

		getTotalCount()
		{
			return this.fields.totalCount;
		}

		setTotalCount(totalCount)
		{
			this.fields.totalCount = totalCount;
		}

		getMembers()
		{
			return this.fields.members;
		}

		setMembers(inputMembers)
		{
			const members = new Map();

			const types = {
				A: 'accomplice',
				U: 'auditor',
			};

			Object.keys(inputMembers).forEach((id) => {
				const { name, type } = inputMembers[id];
				members.set(
					id,
					{
						id,
						nameFormatted: name,
						type: types[type],
						avatar: '',
					},
				);
			});

			this.fields.members = members;
		}

		addMember(member)
		{
			this.emitter.emit(`${member.type}Add`, [member]);

			this.fields.members.set(member.id, member);
		}

		removeMember(id)
		{
			this.fields.members.delete(id);
		}

		getAttachments()
		{
			return this.fields.attachments;
		}

		getAttachmentsCount()
		{
			return Object.keys(this.fields.attachments).length;
		}

		setAttachments(attachments)
		{
			this.fields.attachments = attachments;
		}

		addAttachments(inputAttachments)
		{
			Object.keys(inputAttachments).forEach((id) => {
				this.fields.attachments[id] = inputAttachments[id];
			});
		}

		removeAttachment(id)
		{
			delete this.fields.attachments[id];
		}

		getDescendants()
		{
			return this.descendants;
		}

		getDescendantsCount()
		{
			return this.descendants.length;
		}

		getEmptyDescendant()
		{
			if (this.getTitle() === '')
			{
				return this;
			}

			let emptyDescendant = null;
			this.descendants.forEach((descendant) => {
				if (emptyDescendant === null)
				{
					emptyDescendant = descendant.getEmptyDescendant();
				}
			});

			return emptyDescendant;
		}

		removeEmptyDescendant()
		{
			const emptyDescendant = this.getEmptyDescendant();

			if (emptyDescendant)
			{
				emptyDescendant.removeListItem();
			}
		}

		getFocusedDescendant()
		{
			if (this.isFocused())
			{
				return this;
			}

			let focusedDescendant = null;
			this.descendants.forEach((descendant) => {
				if (focusedDescendant === null)
				{
					focusedDescendant = descendant.getFocusedDescendant();
				}
			});

			return focusedDescendant;
		}

		getFirstDescendant()
		{
			if (this.descendants.length > 0)
			{
				return this.descendants[0];
			}

			return false;
		}

		getLastDescendant()
		{
			if (this.descendants.length > 0)
			{
				return this.descendants[this.descendants.length - 1];
			}

			return false;
		}

		isFirstListDescendant()
		{
			return this.getParent()?.isList() && this.isFirstDescendant();
		}

		isFirstDescendant()
		{
			return (this === this.getParent()?.getFirstDescendant());
		}

		isLastDescendant()
		{
			return (this === this.getParent()?.getLastDescendant());
		}

		getLeftSibling()
		{
			if (this.isFirstDescendant())
			{
				return null;
			}

			const parentDescendants = this.getParent()?.getDescendants();
			const index = parentDescendants.indexOf(this);

			if (index !== -1)
			{
				return parentDescendants[index - 1];
			}

			return null;
		}

		getRightSibling()
		{
			if (this.isLastDescendant())
			{
				return null;
			}

			const parentDescendants = this.getParent().getDescendants();
			const index = parentDescendants.indexOf(this);

			if (index !== -1)
			{
				return parentDescendants[index + 1];
			}

			return null;
		}

		getLeftSiblingThrough()
		{
			if (this === this.getRootNode())
			{
				return null;
			}

			if (this.isFirstDescendant())
			{
				return this.getParent();
			}

			let leftSiblingThrough = this.getLeftSibling();
			while (leftSiblingThrough && leftSiblingThrough.getDescendantsCount() > 0)
			{
				leftSiblingThrough = leftSiblingThrough.getLastDescendant();
			}

			return leftSiblingThrough;
		}

		getRightSiblingThrough()
		{
			if (this.getDescendantsCount() > 0)
			{
				return this.getFirstDescendant();
			}

			if (!this.isLastDescendant())
			{
				return this.getRightSibling();
			}

			let parent = this;
			while (parent.getParent() !== null && parent.isLastDescendant())
			{
				parent = parent.getParent();
			}

			if (parent !== this.getRootNode())
			{
				return parent.getRightSibling();
			}

			return null;
		}

		findChild(nodeId)
		{
			if (!nodeId)
			{
				return null;
			}

			if (this.getNodeId().toString() === nodeId.toString())
			{
				return this;
			}

			let found = null;
			this.descendants.forEach((descendant) => {
				if (found === null)
				{
					found = descendant.findChild(nodeId);
				}
			});

			return found;
		}

		findById(id)
		{
			if (!id)
			{
				return null;
			}

			if (this.getId() && this.getId().toString() === id.toString())
			{
				return this;
			}

			let found = null;
			this.getDescendants().forEach((descendant) => {
				if (found === null)
				{
					found = descendant.findById(id);
				}
			});

			return found;
		}

		getFakeAttachmentsCount(filesToRemove, filesToAdd)
		{
			const attachmentsIds = Object.keys(this.getAttachments());
			const countWithRemovable = attachmentsIds.filter((id) => !filesToRemove.includes(id)).length;

			return countWithRemovable + filesToAdd.length;
		}

		countTreeSize()
		{
			let size = this.getDescendantsCount();

			this.descendants.forEach((descendant) => {
				size += descendant.countTreeSize();
			});

			return size;
		}

		getTreeSize()
		{
			return this.getRootNode().countTreeSize() + 1;
		}

		hasAnotherCheckLists()
		{
			const checkList = this.getList();
			const checkLists = this.getRootNode().getDescendants().filter((item) => item !== checkList);

			return checkLists.length > 0;
		}

		getAnotherCheckLists()
		{
			const anotherCheckLists = [];

			const checkList = this.getList();
			const checkLists = this.getRootNode().getDescendants().filter((item) => item !== checkList);

			checkLists.forEach((descendant) => {
				anotherCheckLists.push({
					id: descendant.getNodeId(),
					title: descendant.getTitle(),
				});
			});

			return anotherCheckLists;
		}

		generateUniqueNodeId()
		{
			return Math.random().toString(36).slice(2, 11);
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

		camelToSnakeCase(string)
		{
			let snakeCaseString = string;

			if (BX.type.isString(snakeCaseString))
			{
				snakeCaseString = snakeCaseString.replaceAll(/(.)([A-Z])/g, '$1_$2').toUpperCase();
			}

			return snakeCaseString;
		}

		isActive()
		{
			return this.active;
		}

		setActive(active)
		{
			this.active = active === true;
		}

		setTaskId(taskId)
		{
			this.taskId = parseInt(taskId, 10);
		}

		getTaskId()
		{
			if (this.isRoot())
			{
				return this.taskId;
			}

			return this.getParent().getTaskId();
		}

		setCanAdd(can)
		{
			this.action.canAdd = can === true;
		}

		checkCanAdd()
		{
			return this.action.canAdd;
		}

		checkCanAddAccomplice()
		{
			return this.action.canAddAccomplice;
		}

		checkCanUpdate()
		{
			return this.action.canUpdate;
		}

		checkCanRemove()
		{
			return this.action.canRemove;
		}

		checkCanToggle()
		{
			return this.action.canToggle;
		}

		isLoading()
		{
			return this.loading;
		}

		setLoading(value)
		{
			this.loading = value === true;
		}

		checkEditMode()
		{
			if (this.isRoot())
			{
				return this.taskId === 0;
			}

			return this.getParent()?.checkEditMode();
		}

		checkCanTabIn()
		{
			return !this.isRoot() && !this.isList() && !this.isFirstDescendant();
		}

		checkCanTabOut()
		{
			return !this.isRoot() && !this.isList() && !this.getParent()?.isList();
		}

		focus()
		{
			this.focused = true;
		}

		blur()
		{
			this.focused = false;
		}

		isFocused()
		{
			return this.focused;
		}
	}

	module.exports = { CheckListTree };
});
