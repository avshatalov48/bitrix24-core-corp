import {Text, Type} from 'main.core';

class CheckListItemFields
{
	static snakeToCamelCase(string)
	{
		let camelCaseString = string;

		if (Type.isString(camelCaseString))
		{
			camelCaseString = camelCaseString.toLowerCase();
			camelCaseString = camelCaseString.replace(/[-_\s]+(.)?/g, (match, chr) => (chr ? chr.toUpperCase() : ''));

			return camelCaseString.substr(0, 1).toLowerCase() + camelCaseString.substr(1);
		}

		return camelCaseString;
	}

	static camelToSnakeCase(string)
	{
		let snakeCaseString = string;

		if (Type.isString(snakeCaseString))
		{
			snakeCaseString = snakeCaseString.replace(/(.)([A-Z])/g, '$1_$2').toUpperCase();
		}

		return snakeCaseString;
	}

	constructor(fields)
	{
		this.fields = [
			'id',
			'copiedId',
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

		this.id = null;
		this.parentId = null;
		this.title = '';
		this.sortIndex = 0;
		this.displaySortIndex = '';
		this.isComplete = false;
		this.isImportant = false;
		this.isSelected = false;
		this.isCollapse = false;
		this.completedCount = 0;
		this.totalCount = 0;
		this.members = new Map();
		this.attachments = {};

		this.setFields(fields);
	}

	setFields(fields)
	{
		Object.keys(fields).forEach((name) => {
			const camelCaseName = CheckListItemFields.snakeToCamelCase(name);

			if (this.fields.indexOf(name) !== -1)
			{
				const snakeCaseName = CheckListItemFields.camelToSnakeCase(name);
				const setMethod = this[CheckListItemFields.snakeToCamelCase(`SET_${snakeCaseName}`)].bind(this);
				setMethod(fields[name]);
			}
			else if (this.fields.indexOf(camelCaseName) !== -1)
			{
				const setMethod = this[CheckListItemFields.snakeToCamelCase(`SET_${name}`)].bind(this);
				setMethod(fields[name]);
			}
		});
	}

	getId()
	{
		return this.id;
	}

	setId(id)
	{
		this.id = id;
	}

	getCopiedId()
	{
		return this.copiedId;
	}

	setCopiedId(copiedId)
	{
		this.copiedId = copiedId;
	}

	getParentId()
	{
		return this.parentId;
	}

	setParentId(parentId)
	{
		this.parentId = parentId;
	}

	getTitle()
	{
		return this.title;
	}

	setTitle(title)
	{
		this.title = Text.encode(title);
	}

	getSortIndex()
	{
		return this.sortIndex;
	}

	setSortIndex(sortIndex)
	{
		this.sortIndex = sortIndex;
	}

	getDisplaySortIndex()
	{
		return this.displaySortIndex;
	}

	setDisplaySortIndex(displaySortIndex)
	{
		this.displaySortIndex = displaySortIndex;
	}

	getIsComplete()
	{
		return this.isComplete;
	}

	setIsComplete(isComplete)
	{
		this.isComplete = isComplete;
	}

	getIsImportant()
	{
		return this.isImportant;
	}

	setIsImportant(isImportant)
	{
		this.isImportant = isImportant;
	}

	getIsSelected()
	{
		return this.isSelected;
	}

	setIsSelected(isSelected)
	{
		this.isSelected = isSelected;
	}

	getIsCollapse()
	{
		return this.isCollapse;
	}

	setIsCollapse(isCollapse)
	{
		this.isCollapse = isCollapse;
	}

	getCompletedCount()
	{
		return this.completedCount;
	}

	setCompletedCount(completedCount)
	{
		this.completedCount = completedCount;
	}

	getTotalCount()
	{
		return this.totalCount;
	}

	setTotalCount(totalCount)
	{
		this.totalCount = totalCount;
	}

	getMembers()
	{
		return this.members;
	}

	setMembers(members)
	{
		const types = {
			A: 'accomplice',
			U: 'auditor',
		};

		this.members.clear();

		Object.keys(members).forEach((id) => {
			const { NAME, TYPE, IS_COLLABER } = members[id];
			this.members.set(id, { id, nameFormatted: Text.encode(NAME), type: types[TYPE], isCollaber: IS_COLLABER });
		});
	}

	addMember(member)
	{
		this.members.set(member.id, member);
	}

	removeMember(id)
	{
		this.members.delete(id);
	}

	getAttachments()
	{
		return this.attachments;
	}

	setAttachments(attachments)
	{
		this.attachments = attachments;
	}

	addAttachments(attachments)
	{
		Object.keys(attachments).forEach((id) => {
			this.attachments[id] = attachments[id];
		});
	}

	removeAttachment(id)
	{
		delete this.attachments[id];
	}
}

export {CheckListItemFields};