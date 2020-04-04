import {TreeItem} from './tree-item';

class CompositeTreeItem extends TreeItem
{
	constructor()
	{
		super();
		this.descendants = [];
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
		}
	}

	getDescendants()
	{
		return this.descendants;
	}

	getDescendantsCount()
	{
		return this.descendants.length;
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

	findChild(nodeId)
	{
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
}

export {CompositeTreeItem};