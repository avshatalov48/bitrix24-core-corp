/**
 * @module tasks/checklist
 */
jn.define('tasks/checklist', (require, exports, module) => {

	const {Loc} = require('loc');
	const {Type} = require('type');

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

			const {action} = fields;

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

		save(taskId, context = '')
		{
			if (!this.isRoot())
			{
				return Promise.resolve();
			}

			if (Type.isUndefined(taskId))
			{
				taskId = this.taskId;
			}

			const items = this.getRequestData();
			return new Promise((resolve, reject) => {
				(new RequestExecutor('tasks.task.checklist.save', {
					taskId: taskId,
					items: items.length > 0 ? items : null,
					parameters: {
						context: context,
					},
				}))
					.call()
					.then(
						(response) => {
							resolve(response.result);
						},
						(response) => {
							reject(response);
						}
					)
				;
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
						}
					)
				;
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
						}
					)
				;
			});
		}

		buildDefaultList(withEmpty = true)
		{
			const checkList = new CheckListTree({
				title: Loc.getMessage('TASKSMOBILE_CHECKLIST_PARENT_DEFAULT_TEXT')
					.replace('#number#', this.getDescendantsCount() + 1)
				,
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
			if (!item)
			{
				item = new CheckListTree({})
			}

			if (dependsOn)
			{
				if (position === 'before')
				{
					this.addBefore(item, dependsOn);
				}
				else if (position === 'after')
				{
					this.addAfter(item, dependsOn);
				}
			}
			else
			{
				this.add(item);
			}

			this.updateIndexes();

			return item;
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
			const index = this.descendants.findIndex(descendant => descendant === after);
			if (index !== -1)
			{
				this.add(item, index + 1);
			}
		}

		addBefore(item, before)
		{
			const index = this.descendants.findIndex(descendant => descendant === before);
			if (index !== -1)
			{
				this.add(item, index);
			}
		}

		remove(item)
		{
			const index = this.descendants.findIndex(descendant => descendant === item);
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
			return !this.isRoot() && this.getParent().isRoot();
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
				parent = parent.getParent();
			}

			return parent;
		}

		getRootNode()
		{
			let parent = this;

			while (parent.getParent() !== null)
			{
				parent = parent.getParent();
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

			if (!recursively)
			{
				totalCount = this.getDescendantsCount();
			}
			else
			{
				this.getDescendants().forEach((descendant) => {
					totalCount += 1;
					totalCount += descendant.countTotalCount(recursively);
				});
			}

			return totalCount;
		}

		updateParents(oldParent, newParent)
		{
			if (oldParent !== newParent)
			{
				oldParent.updateCounts();
				newParent.updateCounts();

				oldParent.updateIndexes();
				newParent.updateIndexes();
			}
			else
			{
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
			const availableFields = [
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
			];

			Object.keys(fields).forEach((name) => {
				const camelCaseName = this.snakeToCamelCase(name);

				if (availableFields.indexOf(name) !== -1)
				{
					const snakeCaseName = this.camelToSnakeCase(name);
					const setMethod = this[this.snakeToCamelCase(`SET_${snakeCaseName}`)].bind(this);
					setMethod(fields[name]);
				}
				else if (availableFields.indexOf(camelCaseName) !== -1)
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
			this.parent = parent
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
				const {name, type} = inputMembers[id];
				members.set(
					id,
					{
						id,
						nameFormatted: name,
						type: types[type],
						avatar: '',
					}
				);
			});

			this.fields.members = members;
		}

		addMember(member)
		{
			this.emitter.emit(member.type + 'Add', [member]);

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
			return Object.keys(this.fields.attachments).length
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
			return (this.getParent().isList() && this.isFirstDescendant());
		}

		isFirstDescendant()
		{
			return (this === this.getParent().getFirstDescendant());
		}

		isLastDescendant()
		{
			return (this === this.getParent().getLastDescendant());
		}

		getLeftSibling()
		{
			if (this.isFirstDescendant())
			{
				return null;
			}

			const parentDescendants = this.getParent().getDescendants();
			const index = parentDescendants.findIndex(descendant => descendant === this);

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
			const index = parentDescendants.findIndex(descendant => descendant === this);

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
			const countWithRemovable = attachmentsIds.filter(id => !filesToRemove.includes(id)).length;

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
			const checkLists = this.getRootNode().getDescendants().filter(item => item !== checkList);

			return checkLists.length > 0;
		}

		getAnotherCheckLists()
		{
			const anotherCheckLists = [];

			const checkList = this.getList();
			const checkLists = this.getRootNode().getDescendants().filter(item => item !== checkList);

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
			return Math.random().toString(36).substr(2, 9);
		}

		snakeToCamelCase(string)
		{
			let camelCaseString = string;

			if (BX.type.isString(camelCaseString))
			{
				camelCaseString = camelCaseString.toLowerCase();

				camelCaseString = camelCaseString
					.replace(
						/[-_\s]+(.)?/g,
						(match, chr) => {
							return (chr ? chr.toUpperCase() : '');
						}
					)
				;

				return camelCaseString.substr(0, 1).toLowerCase() + camelCaseString.substr(1);
			}

			return camelCaseString;
		}

		camelToSnakeCase(string)
		{
			let snakeCaseString = string;

			if (BX.type.isString(snakeCaseString))
			{
				snakeCaseString = snakeCaseString.replace(/(.)([A-Z])/g, '$1_$2').toUpperCase();
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
			else
			{
				return this.getParent().getTaskId();
			}
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
			else
			{
				return this.getParent().checkEditMode();
			}
		}

		checkCanTabIn()
		{
			return !this.isRoot() && !this.isList() && !this.isFirstDescendant();
		}

		checkCanTabOut()
		{
			return !this.isRoot() && !this.isList() && !this.getParent().isList();
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

	class CheckListFilesQueue
	{
		constructor()
		{
			this.queue = new Map();
		}

		getQueue()
		{
			return this.queue;
		}

		addChecklistQueue(checklistItemId)
		{
			if (!this.queue.has(checklistItemId))
			{
				this.queue.set(checklistItemId, new Set());
			}
		}

		removeChecklistQueue(checklistItemId)
		{
			if (this.queue.has(checklistItemId))
			{
				this.queue.delete(checklistItemId);
			}
		}

		getChecklistQueue(checklistItemId)
		{
			this.addChecklistQueue(checklistItemId);
			return this.queue.get(checklistItemId);
		}

		getArrayChecklistQueue(checklistItemId)
		{
			return Array.from(this.getChecklistQueue(checklistItemId));
		}

		addFile(checklistItemId, file)
		{
			this.getChecklistQueue(checklistItemId).add(file);
		}

		removeFile(file)
		{
			this.queue.forEach(checklistQueue => checklistQueue.delete(file));
		}
	}

	class CheckListFilesList extends BaseList
	{
		static id()
		{
			return 'checklistFiles';
		}

		static method()
		{
			return 'mobile.disk.getattachmentsdata';
		}

		static getTypeByFileName(name)
		{
			let extension = name.split('.').pop();
			if (extension)
			{
				extension = extension.toLowerCase();
			}

			return CheckListFilesList.getTypeByFileExtension(extension);
		}

		static getTypeByFileExtension(extension = '')
		{
			const types = {
				jpg: 'image',
				jpeg: 'image',
				png: 'image',
				gif: 'image',
				tiff: 'image',
				bmp: 'image',
				avi: 'video',
				mov: 'video',
				mpeg: 'video',
				mp4: 'video',
			};

			return types[extension.toLowerCase()] || 'document';
		}

		static getIconByFileName(name)
		{
			let extension = name.split('.').pop();
			if (extension)
			{
				extension = extension.toLowerCase();
			}

			return CheckListFilesList.getIconByFileExtension(extension);
		}

		static getIconByFileExtension(extension = '')
		{
			const icons = {
				pdf: 'pdf.png',
				jpg: 'img.png',
				png: 'img.png',
				doc: 'doc.png',
				docx: 'doc.png',
				ppt: 'ppt.png',
				pptx: 'ppt.png',
				rar: 'rar.png',
				xls: 'xls.png',
				csv: 'xls.png',
				xlsx: 'xls.png',
				zip: 'zip.png',
				txt: 'txt.png',
				avi: 'movie.png',
				mov: 'movie.png',
				mpeg: 'movie.png',
				mp4: 'movie.png',
			};
			let fileExtension = extension;

			if (fileExtension)
			{
				fileExtension = fileExtension.toLowerCase();
			}

			return (icons[fileExtension] ? `${icons[fileExtension]}?2` : 'blank.png?21');
		}

		static getFileSize(size)
		{
			let fileSize = size / 1024;

			if (fileSize < 1024)
			{
				fileSize = `${Math.ceil(fileSize)} ${Loc.getMessage('TASKSMOBILE_CHECKLIST_FILES_LIST_ITEM_SIZE_KB')}`;
			}
			else
			{
				fileSize = `${(fileSize / 1024).toFixed(1)} ${Loc.getMessage('TASKSMOBILE_CHECKLIST_FILES_LIST_ITEM_SIZE_MB')}`;
			}

			return fileSize;
		}

		prepareItem(file)
		{
			const fileSize = file.SIZE || CheckListFilesList.getFileSize(file.size);
			const fileType = (file.EXTENSION
					? CheckListFilesList.getTypeByFileExtension(file.EXTENSION)
					: CheckListFilesList.getTypeByFileName(file.NAME || file.name)
			);
			const preparedItem = {
				id: String(file.ID || file.id),
				title: file.NAME || file.name,
				subtitle: `${fileSize} ${(new Date(file.UPDATE_TIME || file.updateTime)).toLocaleString()}`,
				sectionCode: 'checklistFiles',
				styles: {
					image: {
						image: {
							borderRadius: 0,
						},
					},
				},
				params: {
					previewUrl: file.URL || file.links.download,
					type: fileType,
				},
				type: 'info',
			};

			if (this.canUpdate)
			{
				preparedItem.actions = [{
					title: Loc.getMessage('TASKSMOBILE_CHECKLIST_FILES_LIST_REMOVE'),
					color: '#fb5d54',
					identifier: 'remove',
				}];
			}

			if (fileType === 'image')
			{
				preparedItem.imageUrl = file.URL || file.links.download;
				preparedItem.styles.image.image.borderRadius = 10;
			}
			else
			{
				const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/disk/';
				const iconName = (file.EXTENSION
						? CheckListFilesList.getIconByFileExtension(file.EXTENSION)
						: CheckListFilesList.getIconByFileName(file.NAME || file.name)
				);

				preparedItem.imageUrl = `${pathToExtension}/images/${iconName}`;
			}

			return preparedItem;
		}

		static prepareLoadingItem(file)
		{
			const preparedItem = {
				id: file.id,
				title: file.name,
				subtitle: Loc.getMessage('TASKSMOBILE_CHECKLIST_FILES_LIST_ITEM_LOADING'),
				sectionCode: 'checklistFiles',
				unselectable: true,
				styles: {
					image: {
						image: {
							borderRadius: 0,
						},
					},
				},
				params: {
					previewUrl: file.previewUrl,
					type: CheckListFilesList.getTypeByFileName(file.name),
				},
				type: 'info',
			};

			if (preparedItem.params.type === 'image')
			{
				preparedItem.imageUrl = file.previewUrl;
				preparedItem.styles.image.image.borderRadius = 10;
			}
			else
			{
				const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/disk/';
				const iconName = CheckListFilesList.getIconByFileName(file.name);

				preparedItem.imageUrl = `${pathToExtension}/images/${iconName}`;
			}

			return preparedItem;
		}

		static prepareDiskItem(file)
		{
			const fileType = CheckListFilesList.getTypeByFileName(file.NAME);
			const preparedItem = {
				id: file.ID,
				title: file.NAME,
				subtitle: file.TAGS,
				sectionCode: 'checklistFiles',
				styles: {
					image: {
						image: {
							borderRadius: 0,
						},
					},
				},
				params: {
					previewUrl: file.URL.URL,
					type: fileType,
				},
				actions: [{
					title: Loc.getMessage('TASKSMOBILE_CHECKLIST_FILES_LIST_REMOVE'),
					color: '#fb5d54',
					identifier: 'remove',
				}],
				type: 'info',
			};

			if (fileType === 'image')
			{
				preparedItem.imageUrl = file.URL.URL;
				preparedItem.styles.image.image.borderRadius = 10;
			}
			else
			{
				const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/disk/';
				const iconName = CheckListFilesList.getIconByFileName(file.NAME);

				preparedItem.imageUrl = `${pathToExtension}/images/${iconName}`;
			}

			return preparedItem;
		}

		static openFile(url, previewUrl = '', type, name = '')
		{
			if (type === 'video')
			{
				viewer.openVideo(url);
			}
			else if (type === 'image')
			{
				viewer.openImageCollection([{url, previewUrl, name}]);
			}
			else
			{
				viewer.openDocument(url, name);
			}
		}

		params()
		{
			return {
				attachmentsIds: this.attachmentsIds,
			};
		}

		prepareItems(items)
		{
			const preparedItems = [];

			this.files.forEach((file) => {
				if (
					file.id.indexOf('taskChecklist-') !== 0
					&& !items.find(item => item.ID === file.id)
					&& !this.filesStorage.getArrayFiles().find(item => item.id === file.id)
					&& !this.filesToShow.has(file.id)
				)
				{
					preparedItems.push(file);
				}
			});
			this.files = new Map();

			items.forEach((item) => {
				const preparedItem = this.prepareItem(item);
				this.files.set(preparedItem.id, preparedItem);
				preparedItems.push(preparedItem);
			});

			if (this.isEditMode())
			{
				this.filesToShow.forEach((file) => {
					if (file.dataAttributes)
					{
						if (file.checkListItemId === this.checkListItemId)
						{
							const preparedDiskItem = CheckListFilesList.prepareDiskItem(file.dataAttributes);
							this.files.set(preparedDiskItem.id, preparedDiskItem);
							preparedItems.push(preparedDiskItem);
						}
					}
					else if (file.extra.params.ajaxData.checkListItemId === this.checkListItemId)
					{
						const preparedItem = this.prepareItem(file);
						this.files.set(preparedItem.id, preparedItem);
						preparedItems.push(preparedItem);
					}
				});
			}

			this.filesStorage.getArrayFiles().forEach((file) => {
				if (file.params.ajaxData.checkListItemId === this.checkListItemId)
				{
					const preparedLoadingItem = CheckListFilesList.prepareLoadingItem(file);
					this.files.set(preparedLoadingItem.id, preparedLoadingItem);
					preparedItems.push(preparedLoadingItem);
				}
			});

			return preparedItems;
		}

		get list()
		{
			return this._list;
		}

		constructor(listObject, userId, checklistData, checklistController)
		{
			super(listObject);

			this.emitter = new JNEventEmitter();

			this.userId = userId;
			this.checklistData = checklistData;
			this.checklistController = checklistController;

			this.canUpdate = checklistData.canUpdate;
			this.attachmentsIds = checklistData.attachmentsIds;
			this.checkListItemId = checklistData.ajaxData.checkListItemId;
			this.filesToRemoveQueue = checklistController.filesToRemoveQueue;
			this.filesToAddQueue = checklistController.filesToAddQueue;
			this.filesToShow = checklistController.filesToShow;
			this.filesStorage = checklistController.filesStorage;
			this.mode = checklistController.mode;

			this.files = new Map();

			this.setListeners();
			this.setTopButtons();
		}

		on(event, func)
		{
			this.emitter.on(event, func);

			return this;
		}

		setTopButtons()
		{
			this.list.setLeftButtons([{
				name: Loc.getMessage('TASKSMOBILE_CHECKLIST_FILES_LIST_BACK'),
				callback: () => {
					this.list.close();
				},
			}]);

			if (this.canUpdate)
			{
				this.list.setRightButtons([{
					name: Loc.getMessage('TASKSMOBILE_CHECKLIST_FILES_LIST_ADD'),
					callback: () => {
						const {nodeId} = this.checklistData;
						this.checklistController.addFile(this.checklistData, {nodeId});
					},
				}]);
			}
		}

		setListeners()
		{
			const listeners = {
				onViewRemoved: this.onViewRemoved,
				onItemSelected: this.onItemSelected,
				onItemAction: this.onItemAction,
			};

			this.list.setListener((eventName, data) => {
				if (listeners[eventName])
				{
					listeners[eventName].apply(this, [data]);
				}
			});
		}

		isEditMode()
		{
			return (this.mode === 'edit');
		}

		addDiskFile(file)
		{
			const preparedDiskItem = CheckListFilesList.prepareDiskItem(file);

			this.list.addItems([preparedDiskItem]);
			this.files.set(preparedDiskItem.id, preparedDiskItem);
		}

		addLoadingFile(taskId, file)
		{
			file.id = taskId;

			const preparedLoadingItem = CheckListFilesList.prepareLoadingItem(file);

			this.list.addItems([preparedLoadingItem]);
			this.files.set(preparedLoadingItem.id, preparedLoadingItem);
		}

		addRealFile(taskId, file)
		{
			const preparedItem = Object.assign(this.prepareItem(file), {unselectable: false});

			this.list.findItem({id: taskId}, (item) => {
				if (item)
				{
					this.list.updateItem({id: taskId}, preparedItem);
				}
				else
				{
					this.list.addItems([preparedItem]);
				}
			});
			this.files.delete(taskId);
			this.files.set(preparedItem.id, preparedItem);
		}

		onViewRemoved()
		{
			this.checklistController.filesList = null;
		}

		onItemSelected(item)
		{
			if (item.unselectable)
			{
				return;
			}

			const {previewUrl, type} = item.params;
			CheckListFilesList.openFile(previewUrl, previewUrl, type, item.title);
		}

		onItemAction(eventData)
		{
			const fileId = eventData.item.id;

			if (eventData.action.identifier === 'remove')
			{
				this.onItemActionDelete(fileId);
			}
		}

		onItemActionDelete(fileId)
		{
			if (!this.canUpdate)
			{
				return;
			}

			const {ajaxData} = this.checklistData;
			const {checkListItemId} = ajaxData;

			this.list.removeItem({id: fileId});
			this.filesToRemoveQueue.addFile(checkListItemId, fileId);
			this.filesToShow.delete(fileId);
			this.files.delete(fileId);

			this.emitter.emit('fakeRemoveFiles', [{
				nodeId: this.checklistData.nodeId,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
			}]);

			if (this.isEditMode())
			{
				this.removeFileInEditMode(ajaxData, fileId);
			}
			else
			{
				this.removeFileInViewMode(ajaxData, fileId);
			}

			if (this.files.size === 0)
			{
				this.list.close();
			}
		}

		removeFileInViewMode(ajaxData, fileId)
		{
			const {entityId, entityTypeId, checkListItemId} = ajaxData;

			BX.ajax.runAction('tasks.task.checklist.removeAttachments', {
				data: {
					checkListItemId,
					[entityTypeId]: entityId,
					attachmentsIds: [fileId],
				},
			}).then((response) => {
				if (response.status === 'success')
				{
					const {attachments} = response.data.checkListItem;

					this.attachmentsIds = this.attachmentsIds.filter(id => id !== fileId);
					this.filesToRemoveQueue.removeFile(fileId);

					this.emitter.emit('removeFiles', [{
						attachments,
						nodeId: this.checklistData.nodeId,
						filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
						filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
					}]);
				}
				else
				{
					this.filesToRemoveQueue.removeFile(fileId);
					this.reload();

					this.emitter.emit('fakeRemoveFiles', [{
						nodeId: this.checklistData.nodeId,
						filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
						filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
					}]);
				}
			});
		}

		removeFileInEditMode(ajaxData, fileId)
		{
			const {checkListItemId} = ajaxData;

			this.filesToRemoveQueue.removeFile(fileId);

			this.emitter.emit('removeAttachment', [{
				nodeId: checkListItemId,
				attachmentId: fileId,
			}]);

			this.emitter.emit('fakeRemoveFiles', [{
				nodeId: this.checklistData.nodeId,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
			}]);
		}
	}

	/**
	 * @class CheckListController
	 */
	class CheckListController
	{
		static getGuid()
		{
			function s4()
			{
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
			}

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		constructor(props)
		{
			this.taskId = props.taskId;
			this.userId = props.userId;
			this.taskGuid = props.taskGuid;
			this.diskConfig = props.diskConfig;
			this.mode = props.mode;

			this.emitter = new JNEventEmitter();

			this.filesList = null;
			this.filesToAddQueue = new CheckListFilesQueue();
			this.filesToRemoveQueue = new CheckListFilesQueue();
			this.filesToShow = new Map();

			this.filesStorage = new TaskChecklistUploadFilesStorage();
			this.filesStorage.getArrayFiles().forEach((file) => {
				if (this.checkEvent(file.params.taskId))
				{
					const {checkListItemId} = file.params.ajaxData;
					this.filesToAddQueue.addFile(checkListItemId, file.id);
				}
			});

			this.setListeners();
		}

		setMode(mode)
		{
			this.mode = mode === 'edit' ? 'edit' : 'view';
		}

		on(event, func)
		{
			this.emitter.on(event, func);

			return this;
		}

		setListeners()
		{
			BX.addCustomEvent('onChecklistInit', this.onChecklistInit.bind(this));
			BX.addCustomEvent('onFileUploadStatusChanged', this.onFileUploadStatusChange.bind(this));
		}

		checkEvent(taskId = null, taskGuid = null)
		{
			let idCheck = true;
			let guidCheck = true;

			if (taskId !== null)
			{
				idCheck = (Number(this.taskId) === Number(taskId));
			}
			if (taskGuid !== null)
			{
				guidCheck = (this.taskGuid === taskGuid);
			}

			return idCheck && guidCheck;
		}

		sendOnChecklistInitQueueData()
		{
			this.filesToAddQueue.getQueue().forEach((queue, checkListItemId) => {
				this.emitter.emit('fakeAttachFiles', [{
					checkListItemId,
					nodeId: null,
					filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
					filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
				}]);
			});
		}

		onChecklistInit(eventData)
		{
			const {taskId, taskGuid} = eventData;

			if (this.checkEvent(taskId, taskGuid))
			{
				this.sendOnChecklistInitQueueData();
			}
		}

		initFileList(list, params)
		{
			params.ajaxData.entityId = this.taskId;

			this.filesToAddQueue.addChecklistQueue(params.checkListItemId);

			this.filesList = new CheckListFilesList(list, this.userId, params, this);
			this.filesList.init(false);

			this.filesList.on('removeAttachment', (data) => {
				this.emitter.emit('removeAttachment', [data]);
			});
			this.filesList.on('removeFiles', (data) => {
				this.emitter.emit('removeFiles', [data]);
			});
			this.filesList.on('fakeRemoveFiles', (data) => {
				this.emitter.emit('fakeRemoveFiles', [data]);
			});
		}

		addFile(checklistData, webEventData)
		{
			const diskUrl = `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${this.userId}`;

			dialogs.showImagePicker(
				{
					settings: {
						resize: {
							targetWidth: -1,
							targetHeight: -1,
							sourceType: 1,
							encodingType: 0,
							mediaType: 2,
							allowsEdit: true,
							saveToPhotoAlbum: true,
							cameraDirection: 0,
						},
						maxAttachedFilesCount: 3,
						previewMaxWidth: 640,
						previewMaxHeight: 640,
						attachButton: {
							items: [
								{
									id: 'disk',
									name: Loc.getMessage('TASKSMOBILE_CHECKLIST_IMAGE_PICKER_BITRIX24_DISK'),
									dataSource: {
										multiple: true,
										url: diskUrl,
									},
								},
								{
									id: 'mediateka',
									name: Loc.getMessage('TASKSMOBILE_CHECKLIST_IMAGE_PICKER_GALLERY'),
								},
							],
						},
					},
				},
				(filesMetaArray) => {
					this.onImagePickerFileChoose(checklistData, webEventData, filesMetaArray);
				}
			);
		}

		onImagePickerFileChoose(checklistData, webEventData, files)
		{
			const {ajaxData} = checklistData;
			const diskAttachments = [];
			const diskAttachmentsIds = [];
			const localAttachments = [];
			let filesFrom = 'mediateka';

			files.forEach((file) => {
				if (file.dataAttributes)
				{
					filesFrom = 'disk';
					diskAttachments.push(file);
					diskAttachmentsIds.push(file.dataAttributes.ID);
				}
				else
				{
					const taskId = `taskChecklist-${CheckListController.getGuid()}`;

					file.ajaxData = ajaxData;
					file.taskId = this.taskId;

					localAttachments.push({
						taskId,
						id: taskId,
						params: file,
						name: file.name,
						type: file.type,
						url: file.url,
						previewUrl: file.previewUrl,
						folderId: this.diskConfig.folderId,
						onDestroyEventName: TaskChecklistUploaderEvents.FILE_SUCCESS_UPLOAD,
					});
				}
			});

			if (filesFrom === 'disk')
			{
				ajaxData.attachmentsIds = diskAttachmentsIds;
				this.attachDiskFiles(ajaxData, diskAttachments, webEventData);
			}
			else
			{
				this.filesStorage.addFiles(localAttachments);

				BX.postComponentEvent(
					'onFileUploadTaskReceived',
					[{files: localAttachments}],
					'background'
				);
			}
		}

		attachDiskFiles(ajaxData, diskAttachments, webEventData)
		{
			this.sendFakeAttachFilesEvent(ajaxData, diskAttachments, webEventData);

			if (this.mode === 'edit')
			{
				this.attachDiskFilesInEditMode(ajaxData, diskAttachments, webEventData);
			}
			else
			{
				this.runAjaxAttachingFilesFromDisk(ajaxData, diskAttachments, webEventData);
			}
		}

		attachDiskFilesInEditMode(ajaxData, diskAttachments, webEventData)
		{
			const {checkListItemId} = ajaxData;

			diskAttachments.forEach((file) => {
				const fileId = file.dataAttributes.ID;

				file.checkListItemId = checkListItemId;

				this.filesToShow.set(fileId, file);
				this.filesToAddQueue.removeFile(fileId);

				if (this.filesList && this.filesList.checkListItemId === checkListItemId)
				{
					this.filesList.addDiskFile(file.dataAttributes);
				}

				const params = {
					nodeId: webEventData.nodeId,
					filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
					filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
					attachment: {
						[fileId]: fileId,
					},
				};

				this.emitter.emit('addAttachment', [params]);
				this.emitter.emit('fakeAttachFiles', [params]);
			});
		}

		sendFakeAttachFilesEvent(ajaxData, diskAttachments, webEventData)
		{
			const {checkListItemId} = ajaxData;

			diskAttachments.forEach(file => this.filesToAddQueue.addFile(checkListItemId, file.dataAttributes.ID));

			this.emitter.emit('fakeAttachFiles', [{
				nodeId: webEventData.nodeId,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
			}]);
		}

		runAjaxAttachingFilesFromDisk(ajaxData, diskAttachments, webEventData)
		{
			const {entityTypeId, entityId, checkListItemId, attachmentsIds} = ajaxData;

			BX.ajax.runAction('tasks.task.checklist.addAttachmentsFromDisk', {
				data: {
					checkListItemId,
					[entityTypeId]: entityId,
					filesIds: attachmentsIds,
				},
			}).then((response) => {
				if (response.status === 'success')
				{
					const {attachments} = response.data.checkListItem;

					diskAttachments.forEach((file) => {
						const fileId = file.dataAttributes.ID;

						if (Object.values(attachments).includes(`n${fileId}`))
						{
							this.filesToAddQueue.removeFile(fileId);
							if (this.filesList && this.filesList.checkListItemId === checkListItemId)
							{
								file.dataAttributes.ID = Object.keys(attachments).find(id => attachments[id] === `n${fileId}`);
								this.filesList.addDiskFile(file.dataAttributes);
							}
						}
					});

					this.emitter.emit('attachFiles', [{
						attachments,
						nodeId: webEventData.nodeId,
						filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
						filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
					}]);
				}
			});
		}

		onFileUploadStatusChange(eventName, eventData, taskId)
		{
			if (taskId.indexOf('taskChecklist-') !== 0)
			{
				return false;
			}

			switch (eventName)
			{
				default:
					console.log('onFileUploadStatusChange::default event warning!');
					break;

				case BX.FileUploadEvents.FILE_CREATED:
				case BX.FileUploadEvents.FILE_UPLOAD_PROGRESS:
				case BX.FileUploadEvents.ALL_TASK_COMPLETED:
				case BX.FileUploadEvents.TASK_TOKEN_DEFINED:
				case BX.FileUploadEvents.TASK_CREATED:
					// do nothing
					break;

				case BX.FileUploadEvents.FILE_UPLOAD_START:
					this.onFileUploadStart(eventData, taskId);
					break;

				case TaskChecklistUploaderEvents.FILE_SUCCESS_UPLOAD:
					this.onFileUploadSuccess(eventData, taskId);
					break;

				case BX.FileUploadEvents.TASK_STARTED_FAILED:
				case BX.FileUploadEvents.FILE_CREATED_FAILED:
				case BX.FileUploadEvents.FILE_UPLOAD_FAILED:
				case BX.FileUploadEvents.TASK_CANCELLED:
				case BX.FileUploadEvents.TASK_NOT_FOUND:
				case BX.FileUploadEvents.FILE_READ_ERROR:
				case TaskChecklistUploaderEvents.FILE_FAIL_UPLOAD:
					this.onFileUploadError(eventData, taskId);
					break;
			}

			return true;
		}

		onFileUploadStart(eventData, taskId)
		{
			const file = eventData.file.params;
			const {checkListItemId, mode} = file.ajaxData;

			if (!this.checkEvent(file.taskId) || mode !== this.mode)
			{
				return;
			}

			this.filesToAddQueue.addFile(checkListItemId, taskId);
			if (this.filesList && this.filesList.checkListItemId === checkListItemId)
			{
				this.filesList.addLoadingFile(taskId, file);
			}

			const params = {
				checkListItemId,
				nodeId: checkListItemId,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
			};
			delete params[(this.mode === 'edit' ? 'checkListItemId' : 'nodeId')];

			this.emitter.emit('fakeAttachFiles', [params]);
		}

		handleSuccessUploadInViewMode(eventData, taskId)
		{
			const {file} = eventData;

			const {checkListItem} = eventData.result;
			const {attachments} = checkListItem;
			const checkListItemId = checkListItem.id;

			if (this.filesList && this.filesList.checkListItemId === checkListItemId)
			{
				const fileId = Object.keys(attachments).find(id => attachments[id] === `n${file.id}`);
				if (fileId)
				{
					file.id = fileId;
					this.filesList.addRealFile(taskId, file);
				}
			}

			this.filesToAddQueue.removeFile(taskId);
			this.filesStorage.removeFiles([taskId]);

			this.emitter.emit('attachFiles', [{
				checkListItemId,
				nodeId: null,
				attachments: checkListItem.attachments,
			}]);
		}

		handleSuccessUploadInEditMode(eventData, taskId)
		{
			const {file} = eventData;
			const {checkListItemId} = eventData.result;

			if (this.filesList && this.filesList.checkListItemId === checkListItemId)
			{
				this.filesList.addRealFile(taskId, file);
			}

			file.id = String(file.id);

			this.filesToShow.set(file.id, file);
			this.filesToAddQueue.removeFile(taskId);
			this.filesStorage.removeFiles([taskId]);

			const params = {
				nodeId: checkListItemId,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
				attachment: {
					[file.id]: file.id,
				},
			};

			this.emitter.emit('addAttachment', [params]);
			this.emitter.emit('fakeAttachFiles', [params]);
		}

		onFileUploadSuccess(eventData, taskId)
		{
			const fileParams = eventData.file.extra.params;
			const {mode} = fileParams.ajaxData;

			if (!this.checkEvent(fileParams.taskId) || mode !== this.mode)
			{
				return;
			}

			if (mode === 'edit')
			{
				this.handleSuccessUploadInEditMode(eventData, taskId);
			}
			else
			{
				this.handleSuccessUploadInViewMode(eventData, taskId);
			}
		}

		onFileUploadError(eventData, taskId)
		{
			this.filesToAddQueue.removeFile(taskId);
			this.filesStorage.removeFiles([taskId]);
		}
	}

	module.exports = { CheckListTree, CheckListController };

});