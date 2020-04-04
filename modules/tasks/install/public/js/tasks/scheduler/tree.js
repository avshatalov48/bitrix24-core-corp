BX.namespace("BX.Scheduler");

BX.Scheduler.Tree = function(config)
{
	this.config = config || {};
	this.root = new BX.Scheduler.TreeNode();
	this.root.tree = this;
	this.byIdMap = {};

	BX.addCustomEvent(this, "onNodeAdded", BX.proxy(this.onNodeAdded, this));
	BX.addCustomEvent(this, "onNodeRemoved", BX.proxy(this.onNodeRemoved, this));

	this.load(this.config.data);
};

BX.Scheduler.Tree.prototype = {

	clearAll: function() {
		this.byIdMap = {};
		this.root.childNodes = [];
	},

	/**
	 *
	 * @returns {BX.Scheduler.TreeNode}
	 */
	getById: function (id) {
		return this.byIdMap[id] ? this.byIdMap[id] : null;
	},

	/**
	 *
	 * @returns {BX.Scheduler.TreeNode}
	 */
	getRoot: function() {
		return this.root;
	},

	load: function(data) {
		if (!BX.type.isArray(data))
		{
			return;
		}

		for (var i = 0; i < data.length; i++)
		{
			var item = data[i];
			var parentNode = item.parentId && this.getById(item.parentId) || this.getRoot();

			var type = (item.type && BX.Scheduler.Util.getClass(item.type)) || this.getDefaultDataType() || null;

			/** @var {BX.Scheduler.Resource} */
			var obj = type ? new type(item) : item;

			if (!this.getById(obj.getId()))
			{
				parentNode.appendChild(obj);
			}
		}
	},

	getDefaultDataType: function() {
		return null;
	},

	onNodeAdded: function(node) {
		node.getData().store = this;
		this.byIdMap[node.getId()] = node;
	},

	onNodeRemoved: function(node) {
		delete node.getData().store;
		delete this.byIdMap[node.getId()];
	}
};

BX.Scheduler.TreeNode = function(data)
{
	this.data = data || {};
	this.id = this.data.id || null;
	this.childNodes = [];
	this.parentNode = null;
};

BX.Scheduler.TreeNode.prototype = {

	/**
	 * 
	 * @param data
	 * @returns {BX.Scheduler.TreeNode}
	 */
	appendChild: function(data) {
		var node = new BX.Scheduler.TreeNode(data);
		node.parentNode = this;
		this.childNodes.push(node);

		BX.onCustomEvent(this.getTree(), "onNodeAdded", [node]);
		
		return node;
	},

	/**
	 * 
	 * @param newNode
	 * @param referenceNode
	 * @returns {BX.Scheduler.TreeNode}
	 */
	insertBefore: function(newNode, referenceNode) {

	},

	/**
	 * 
	 * @param node
	 * @returns {BX.Scheduler.TreeNode}
	 */
	removeChild: function(node) {

		for (var i = 0; i < this.childNodes.length; i++)
		{
			if (this.childNodes[i] === node)
			{
				this.childNodes.splice(i, 1);
				node.parentTask = null;

				BX.onCustomEvent(this.getTree(), "onNodeRemoved", [node]);
				break;
			}
		}
	},

	forEach: function(callback) {
		for (var i = 0, l = this.childNodes.length; i < l; i++)
		{
			callback(this.childNodes[i]);
		}
	},

	/**
	 *
	 * @returns {BX.Scheduler.Resource}
	 */
	getData: function() {
		return this.data;
	},

	/**
	 * 
	 * @returns {BX.Scheduler.TreeNode[]}
	 */
	getChildNodes: function() {
		return this.childNodes;
	},

	getId: function() {
		return this.id;
	},

	/**
	 *
	 * @returns {BX.Scheduler.TreeNode}
	 */
	getRoot: function() {
		var node = this;
		while (node.parentNode !== null)
		{
			node = node.parentNode;
		}

		return node;
	},

	/**
	 *
	 * @returns {BX.Scheduler.Tree}
	 */
	getTree: function() {
		return this.getRoot().tree;
	}
};