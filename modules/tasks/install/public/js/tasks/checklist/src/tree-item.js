class TreeItem
{
	static generateUniqueNodeId()
	{
		return Math.random().toString(36).substr(2, 9);
	}

	constructor()
	{
		this.setNodeId(TreeItem.generateUniqueNodeId());
		this.setParent(null);
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
}

export {TreeItem};