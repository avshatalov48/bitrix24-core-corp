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
			const { checklist, item } = props;
			/** @type {CheckListFlatTree} */
			this.checklist = checklist;
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
					prevTitle: '',
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
			return this.checklist;
		}

		setCheckList(checkList)
		{
			this.checklist = checkList;
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
				isRoot: this.isRoot(),
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
			return this.checklist.getItemById(this.getParentId());
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

		/**
		 * @return {string}
		 */
		getPrevTitle()
		{
			return this.fields.prevTitle;
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
			return this.checklist.getTaskId();
		}

		isRoot()
		{
			return !this.getParentId();
		}

		isFocused()
		{
			return this.item.focused;
		}

		isAlwaysShow()
		{
			return this.item.alwaysShow;
		}

		setAlwaysShow(value)
		{
			this.item.alwaysShow = value;
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

		shouldRemove()
		{
			return !this.hasAttachments() && !this.hasDescendants() && !this.hasMembers();
		}

		checkCanToggle()
		{
			return this.action.toggle;
		}

		checkCanTabIn()
		{
			const sortIndex = this.getSortIndex();
			const depth = this.getDepth();

			return depth <= 5 && sortIndex > 0;
		}

		checkCanTabOut()
		{
			return Boolean(this.getDepth());
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

		hasMembers()
		{
			return this.getMembersCount() > 0;
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

		/**
		 * @param {Array<Object>} members
		 */
		addMembers(members)
		{
			members.forEach((member) => {
				this.addMember(member);
			});
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

		clearMemberByType(memberType)
		{
			const members = {};
			Object.values(this.getMembers()).forEach((member) => {
				if (memberType !== member.type)
				{
					members[member.id] = member;
				}
			});

			this.fields.members = members;
		}

		/**
		 * @return {boolean}
		 */
		hasAuditor()
		{
			return this.hasMemberType(memberTypes.auditor);
		}

		/**
		 * @return {boolean}
		 */
		hasAccomplice()
		{
			return this.hasMemberType(memberTypes.accomplice);
		}

		/**
		 * @return {boolean}
		 */
		hasMemberType(memberType)
		{
			return Object.values(this.getMembers()).some(({ type }) => memberType === type);
		}

		getMemberType(type)
		{
			return memberTypes[type];
		}

		getUserId()
		{
			return this.checklist.getUserId();
		}

		getMoveIds()
		{
			const moveIds = [];
			this.getDescendants(true).forEach((descendant) => {
				moveIds.push(descendant.getId());
			});

			return moveIds;
		}

		getDescendants(deep = false)
		{
			return this.checklist.getDescendants(this.getId(), deep);
		}

		hasDescendants()
		{
			return this.getDescendantsCount() > 0;
		}

		getDescendantsCount(deep = false)
		{
			return this.checklist.getDescendantsCount(this.getId(), deep);
		}

		getCompleteCount()
		{
			return this.checklist.getCompleteCount(this.getId());
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
			this.updateCounter(this.getParent());
			this.updateListViewType();
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
			const oldParent = this.getParent();
			const newParent = this.getParent().getParent();

			this.setParentId(newParent.getId());
			this.tabMoveUpdateCounter([oldParent, newParent]);

			return true;
		}

		tabIn()
		{
			const oldParent = this.getParent();
			const newParent = this.checklist.getPrevSiblingById(this.getId());

			this.setParentId(newParent.getId());
			this.tabMoveUpdateCounter([oldParent, newParent]);
			this.updateListViewType();

			return true;
		}

		tabMoveUpdateCounter(items)
		{
			items.forEach((item) => {
				this.checklist.updateIndexes(item.getId());
				this.updateCounter(item);
			});
		}

		updateCounter(item)
		{
			item.updateCompletedCount();
		}

		updateCompletedCount()
		{
			const completedCount = this.countCompletedCount();
			this.setCompletedCount(completedCount);
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
