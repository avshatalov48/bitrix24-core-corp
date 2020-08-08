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