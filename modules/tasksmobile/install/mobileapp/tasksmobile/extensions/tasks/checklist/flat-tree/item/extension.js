/**
 * @module tasks/checklist/flat-tree/item
 */
jn.define('tasks/checklist/flat-tree/item', (require, exports, module) => {
	const { hashCode } = require('utils/hash');
	const { merge } = require('utils/object');
	const { Random } = require('utils/random');

	const memberTypes = {
		A: 'accomplice',
		U: 'auditor',
		accomplice: 'A',
		auditor: 'U',
	};

	class CheckListFlatTreeItem
	{
		constructor(props)
		{
			this.emitter = new JNEventEmitter();
			const { checkList, item } = props;
			/** @type {CheckListFlatTree} */
			this.checkList = checkList;
			this.item = item;

			this.updateListViewType();
		}

		static createItem(prevItem = {})
		{
			const nodeId = Random.getString();

			return merge({
				id: nodeId,
				key: nodeId,
				type: CheckListFlatTreeItem.getItemType(),
				nodeId,
				focused: true,
				isNew: false,
				action: {
					add: true,
					addAccomplice: true,
					modify: true,
					remove: true,
					toggle: true,
				},
				fields: {
					id: nodeId,
					title: '',
					parentId: 0,
					sortIndex: 0,
					displaySortIndex: '',
					isComplete: false,
					isImportant: false,
					isSelected: false,
					isCollapse: false,
					completedCount: 0,
					totalCount: 0,
					members: {},
					attachments: {},
				},
				descendants: [],
			}, prevItem);
		}

		/**
		 * @returns {string} itemType
		 */
		static getItemType()
		{
			return 'checkListItem';
		}

		get fields()
		{
			return this.item.fields;
		}

		get action()
		{
			return this.item.action;
		}

		getCheckList()
		{
			return this.checkList;
		}

		setCheckList(checkList)
		{
			this.checkList = checkList;
		}

		getType()
		{
			return this.item.type;
		}

		updateListViewType()
		{
			this.item.type = this.createHashType();
		}

		createHashType()
		{
			const params = {
				focused: this.isFocused(),
				attachments: this.getAttachments(),
				members: this.getMembers(),
				isComplete: this.getIsComplete(),
				isImportant: this.getIsImportant(),
				displayDepth: this.getDepth(),
				totalCount: this.getDescendantsCount(),
			};

			if (this.isNew())
			{
				params.nodeId = this.getNodeId();
			}

			return `${CheckListFlatTreeItem.getItemType()}-${hashCode(JSON.stringify(params))}`;
		}

		getIndex()
		{
			return this.item.index;
		}

		getItem()
		{
			return this.item;
		}

		getId()
		{
			return this.item.id;
		}

		getKey()
		{
			return this.item.key;
		}

		getParent()
		{
			return this.checkList.getItemById(this.getParentId());
		}

		getParentId()
		{
			return this.fields.parentId;
		}

		getTitle()
		{
			return this.fields.title;
		}

		setTitle(title = '')
		{
			this.fields.title = title;
		}

		setParentId(id)
		{
			this.fields.parentId = id;
		}

		setSortIndex(sortIndex)
		{
			this.fields.sortIndex = sortIndex;
		}

		getSortIndex()
		{
			return this.fields.sortIndex;
		}

		setDisplaySortIndex(displaySortIndex)
		{
			this.fields.displaySortIndex = displaySortIndex;
		}

		getDisplaySortIndex()
		{
			return this.fields.displaySortIndex;
		}

		getDepth()
		{
			const displaySortIndex = this.getDisplaySortIndex();

			return (displaySortIndex.match(/\./g) || []).length;
		}

		getNodeId()
		{
			return this.item.nodeId;
		}

		setNodeId(id)
		{
			this.item.nodeId = id || Random.getString();
		}

		setCompletedCount(completedCount)
		{
			this.fields.completedCount = completedCount;
		}

		getCompletedCount()
		{
			return this.fields.completedCount;
		}

		getAttachments()
		{
			return this.fields.attachments;
		}

		getAttachmentsCount()
		{
			return Object.keys(this.getAttachments()).length;
		}

		hasAttachments()
		{
			return this.getAttachmentsCount() > 0;
		}

		setAttachments(attachments)
		{
			this.fields.attachments = attachments;
		}

		addAttachments(inputAttachments)
		{
			Object.keys(inputAttachments).forEach((id) => {
				this.updateAttachment(inputAttachments[id]);
			});
		}

		removeAttachment(id)
		{
			delete this.fields.attachments[id];
		}

		updateAttachment(attachment)
		{
			this.fields.attachments[attachment.id] = attachment;
		}

		getTaskId()
		{
			return this.checkList.getTaskId();
		}

		isRoot()
		{
			return !this.getParentId();
		}

		isFocused()
		{
			return this.item.focused;
		}

		isNew()
		{
			return this.item.isNew;
		}

		setIsNew(isNew)
		{
			this.item.isNew = isNew;
		}

		isFirstListDescendant()
		{
			return false;
		}

		getIsComplete()
		{
			return this.fields.isComplete;
		}

		getIsImportant()
		{
			return this.fields.isImportant;
		}

		checkCanAdd()
		{
			return this.action.canAdd;
		}

		checkCanAddAccomplice()
		{
			return this.action.addAccomplice;
		}

		checkCanUpdate()
		{
			return this.action.modify;
		}

		checkCanRemove()
		{
			return this.action.remove;
		}

		checkCanToggle()
		{
			return this.action.toggle;
		}

		checkCanTabIn()
		{
			return true;
		}

		checkCanTabOut()
		{
			return true;
		}

		/**
		 * @returns {boolean}
		 */
		hasAnotherCheckLists()
		{
			return true;
		}

		/**
		 * @returns {boolean}
		 */
		hasItemTitle()
		{
			return Boolean(this.getTitle().trim());
		}

		blur()
		{
			this.item.focused = false;
		}

		focus()
		{
			this.item.focused = true;
		}

		getMembersCount()
		{
			return Object.keys(this.getMembers()).length;
		}

		getMembers()
		{
			return this.fields.members;
		}

		setMembers(members)
		{
			this.fields.members = members;
		}

		getMember(id)
		{
			const members = this.getMembers();

			return members[id];
		}

		addMember(member)
		{
			this.emitter.emit(`${member.type}Add`, [member]);
			const members = this.getMembers();

			members[member.id] = member;
		}

		getMemberType(type)
		{
			return memberTypes[type];
		}

		getMoveIds()
		{
			const moveIds = [this.getId()];
			this.getDescendants(true).forEach((descendant) => {
				moveIds.push(descendant.getId());
			});

			return moveIds;
		}

		getDescendants(deep = false)
		{
			return this.checkList.getDescendants(this.getId(), deep);
		}

		getDescendantsCount()
		{
			return this.checkList.getDescendantsCount(this.getId());
		}

		getCompleteCount()
		{
			return this.checkList.getCompleteCount(this.getId());
		}

		toggleComplete()
		{
			if (this.getIsSelected() || !this.checkCanToggle())
			{
				return;
			}

			const isComplete = !this.getIsComplete();

			this.setIsComplete(isComplete);
			this.updateComplete(isComplete);
			this.updateCounter();
		}

		setIsComplete(isComplete)
		{
			this.fields.isComplete = isComplete;
		}

		getIsSelected()
		{
			return this.fields.isSelected;
		}

		toggleImportant(important)
		{
			this.fields.isImportant = important;
		}

		tabOut()
		{
			const parent = this.getParent();
			const parentId = parent ? parent.getParentId() : null;

			if (!parentId)
			{
				return false;
			}

			this.setParentId(parentId);
			this.checkList.updateIndexes(parentId);

			return true;
		}

		tabIn()
		{
			const prevItem = this.checkList.getPrevSiblingById(this.getId());
			if (!prevItem)
			{
				return false;
			}

			const parentId = prevItem.getId();
			this.setParentId(parentId);
			this.checkList.updateIndexes(parentId);

			return true;
		}

		updateCompletedCount()
		{
			const completedCount = this.countCompletedCount();
			this.setCompletedCount(completedCount);
		}

		updateCounter()
		{
			const parent = this.getParent();
			if (!parent)
			{
				return;
			}

			this.getParent().updateCounts();
		}

		updateCounts()
		{
			this.updateCompletedCount();
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

		updateComplete(complete)
		{
			const action = complete ? 'complete' : 'renew';

			if (!this.getTaskId())
			{
				return;
			}

			BX.ajax.runAction(
				`tasks.task.checklist.${action}`,
				{
					data: {
						taskId: this.getTaskId(),
						checkListItemId: this.getId(),
					},
				},
			).catch(console.error);
		}
	}

	module.exports = { CheckListFlatTreeItem };
});
