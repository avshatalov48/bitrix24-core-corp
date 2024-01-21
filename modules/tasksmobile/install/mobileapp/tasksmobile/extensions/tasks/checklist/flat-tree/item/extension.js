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
				action: {
					add: true,
					addAccomplice: true,
					modify: true,
					remove: true,
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

		updateItemType()
		{
			this.item.type = this.createHashType({
				attachments: this.getAttachments(),
				focused: this.isFocused(),
			});
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
			};

			return `${CheckListFlatTreeItem.getItemType()}-${hashCode(JSON.stringify(params))}`;
		}

		getItem()
		{
			return this.item;
		}

		getId()
		{
			return this.item.id;
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
			const maxDepth = 5;

			const depth = (displaySortIndex.match(/\./g) || []).length;

			if (depth > 0)
			{
				return Math.min(depth, maxDepth) * 15;
			}

			return 0;
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

		getTotalCount()
		{
			return this.fields.totalCount;
		}

		setTotalCount(totalCount)
		{
			this.fields.totalCount = totalCount;
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
			this.updateItemType();
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
			this.updateItemType();
		}

		updateAttachment(attachment)
		{
			this.fields.attachments[attachment.id] = attachment;
			this.updateItemType();
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

		hasAnotherCheckLists()
		{
			return true;
		}

		shouldBeDeletedOnBlur()
		{
			return !this.getTitle().trim();
		}

		blur()
		{
			this.item.focused = false;
		}

		focus()
		{
			this.item.focused = true;
		}

		getMembers()
		{
			return this.fields.members;
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

		removeMember(id)
		{
			delete this.getMember(id);
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

		updateTotalCount()
		{
			const totalCount = this.countTotalCount();
			this.setTotalCount(totalCount);
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
			this.updateTotalCount();
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

		updateComplete(complete)
		{
			const action = complete ? 'complete' : 'renew';

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
