(function() {

	"use strict";


	/**
	 * @typedef {object} BX.Disk.Tree.Node
	 * @property {int} id - ID
	 * @property {string} name - Name
	 * @property {bool} hasSubFolders
	 * @property {{bool}} canAdd
	 */

	/**
	 * @namespace BX.Disk.Tree
	 */
	BX.namespace("BX.Disk.Tree");

	/**
	 * @class
	 * @constructor
	 * @param {BX.Disk.Tree.Node} rootObject
	 * @param parameters
	 */
	BX.Disk.Tree.Structure = function(rootObject, parameters)
	{
		parameters = BX.type.isPlainObject(parameters)? parameters : {};
		parameters.events = parameters.events || {};

		this.multipleChoice = parameters.multipleChoice || false;
		this.rootObject = rootObject;
		this.parameters = parameters;
		this.ajaxUrl = parameters.ajaxUrl || '/bitrix/components/bitrix/disk.folder.list/ajax.php';
		this.rootNode = null;
		this.container = null;
		this.willLoadSubTrees = {};

		this.events = {
			onSelectFolder: this.parameters.events.onSelectFolder,
			onUnSelectFolder: this.parameters.events.onUnSelectFolder
		};

		this.setEvents();
	};

	BX.Disk.Tree.Structure.prototype =
	{
		setEvents: function ()
		{},

		buildByRoot: function ()
		{
			return this.buildByObject(this.rootObject);
		},

		buildByObject: function (rootObject)
		{
			this.rootNode = this.buildTreeNode(rootObject);

			this.loadSubFolders(rootObject.id).then(function (response) {
				var ul = BX.create('ul', {
					props: {
						className: 'bx-disk-wood-folder'
					}
				});
				this.rootNode.appendChild(ul);
				this.buildTree(this.rootNode, response);
				this.container = this.rootNode.parentNode;
			}.bind(this));

			return this.rootNode;
		},

		getRootNode: function ()
		{
			return this.rootNode;
		},

		loadSubFolders: function (objectId)
		{
			if (this.willLoadSubTrees.hasOwnProperty(objectId))
			{
				const result = BX.Promise();

				return result.fulfill(this.willLoadSubTrees[objectId]);
			}

			this.willLoadSubTrees[objectId] = BX.Disk.ajaxPromise({
				method: 'POST',
				dataType: 'json',
				url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSubFolders'),
				data: {
					objectId: objectId
				}
			}).then(response => {
				this.willLoadSubTrees[objectId] = response;

				return response;
			});

			return this.willLoadSubTrees[objectId];
		},

		showSubFolders: function (node)
		{
			if (!node)
			{
				return;
			}
			var objectId = node.getAttribute('data-object-id');
			if (!objectId)
			{
				return;
			}

			this.loadSubFolders(objectId).then(function(response) {
				this.buildTree(node, response);
			}.bind(this));
		},

		buildTree: function (rootNode, response, ignoreNode)
		{
			ignoreNode = ignoreNode || {};

			var ul = BX.create('ul', {
				props: {
					className: 'bx-disk-wood-folder'
				}
			});
			rootNode.appendChild(ul);

			if (response.items.length)
			{
				response.items.forEach(function (item) {
					if (item.id == ignoreNode.id)
					{
						return;
					}

					ul.appendChild(this.buildTreeNode(item));
				}, this);
			}
			else
			{
				BX.cleanNode(BX.findChildByClassName(rootNode, 'bx-disk-wf-arrow', true));
			}

			BX.removeClass(rootNode, 'bx-disk-close');
			BX.addClass(rootNode, 'bx-disk-open');
			BX.addClass(rootNode, 'bx-disk-loaded');
		},

		/**
		 * @param {BX.Disk.Tree.Node} object
		 * @returns {Element}
		 */
		buildTreeNode: function (object)
		{
			return BX.create('li', {
				props: {
					className: 'bx-disk-folder-container bx-disk-parent bx-disk-close ' + (!object.canAdd ? 'bx-disk-only-view' : 'bx-disk-can-select')
				},
				dataset: {
					objectId: object.id,
					hasSubFolders: object.hasSubFolders? 1 : '',
					canAdd: !!object.canAdd
				},
				children: [
					BX.create('div', {
						props: {
							className: 'bx-disk-folder-container'
						},
						children: [
							BX.create('table', {
								children: [
									BX.create('tr', {
										children: [
											BX.create('td', {
												props: {
													className: 'bx-disk-wf-arrow'
												},
												events: {
													click: this.onClickToExpand.bind(this)
												},
												children: [
													(object.hasSubFolders ? BX.create('span') : null)
												]
											}),
											BX.create('td', {
												props: {
													className: 'bx-disk-wf-folder-icon'
												},
												events: {
													click: this.onClickTreeNode.bind(this)
												},
												children: [
													BX.create('span')
												]
											}),
											BX.create('td', {
												props: {
													className: 'bx-disk-wf-folder-name disk-unselectable'
												},
												events: {
													click: this.onClickTreeNode.bind(this),
													dblclick: this.onClickToExpand.bind(this)											},
												children: [
													BX.create('span', {
														text: object.name
													})
												]
											})
										]
									})
								]
							})
						]
					})
				]
			});
		},

		/**
		 * @private
		 * @param {Event} e
		 */
		onClickToExpand: function (e)
		{
			var target = e.target || e.srcElement;
			var parent = BX.findParent(target, {
				className: 'bx-disk-parent'
			});

			if (this.isOpened(parent))
			{
				this.collapse(parent);
			}
			else
			{
				this.expand(parent);
			}
		},

		collapse: function (node)
		{
			if (BX.hasClass(node, 'bx-disk-open'))
			{
				BX.removeClass(node, 'bx-disk-open');
				BX.addClass(node, 'bx-disk-close');
			}
		},

		expand: function (node)
		{
			if (BX.hasClass(node, 'bx-disk-loaded'))
			{
				BX.removeClass(node, 'bx-disk-close');
				BX.addClass(node, 'bx-disk-open');
				return;
			}

			this.showSubFolders(node);
		},

		isSelectedNode: function (node)
		{
			return BX.hasClass(node, 'selected');
		},

		isOpened: function (node)
		{
			return BX.hasClass(node, 'bx-disk-open');
		},

		hasSubFolders: function (node)
		{
			return !!node.dataset.hasSubFolders;
		},

		unselectNode: function (node)
		{
			if (!node)
			{
				return;
			}

			BX.removeClass(node, 'selected');
			BX.onCustomEvent(this, 'Tree:onUnSelectFolder', [node, node.dataset.objectId]);
			this.lastUnselectedFolder = node;

			if(BX.type.isFunction(this.events.onUnSelectFolder))
			{
				this.events.onUnSelectFolder(node, node.dataset.objectId);
			}
		},

		selectNode: function (node)
		{
			if (!node)
			{
				return;
			}

			BX.addClass(node, 'selected');
			BX.onCustomEvent(this, 'Tree:onSelectFolder', [node, node.dataset.objectId, this.lastUnselectedFolder]);

			if(BX.type.isFunction(this.events.onSelectFolder))
			{
				this.events.onSelectFolder(node, node.dataset.objectId, this.lastUnselectedFolder);
			}
		},

		/**
		 * @private
		 * @param {Event} e
		 */
		onClickTreeNode: function (e)
		{
			var target = e.target || e.srcElement;
			var parent = BX.findParent(target, {
				className: 'bx-disk-parent'
			});

			if (this.isSelectedNode(parent))
			{
				this.unselectNode(parent);
			}
			else
			{
				if (!this.multipleChoice)
				{
					this.unselectAllNodes();
				}

				this.selectNode(parent);
			}
		},

		getSelectedNodes: function ()
		{
			return getByClass(this.container, 'selected');
		},

		getFirstSelectedNode: function ()
		{
			var nodes = this.getSelectedNodes();

			return nodes.length? nodes[0] : null;
		},

		getFirstSelectedId: function ()
		{
			var ids = this.getSelectedIds();

			return ids? ids[0] : null;
		},

		unselectAllNodes: function ()
		{
			var nodes = getByClass(this.container, 'selected');

			nodes.forEach(function (node) {
				this.unselectNode(node);
			}, this);
		},

		getSelectedIds: function ()
		{
			var nodes = getByClass(this.container, 'selected');

			return nodes.map(function(node) {
				return node.dataset.objectId;
			});
		}
	};

	/**
	 *
	 * @param {BX.Disk.Tree.Node|null} rootObject
	 * @param {object} parameters
	 * @constructor
	 */
	BX.Disk.Tree.Modal = function (rootObject, parameters)
	{
		parameters = BX.type.isPlainObject(parameters)? parameters : {};

		this.tree = parameters.tree || new BX.Disk.Tree.Structure(rootObject, parameters);
		this.modalParameters = "modalParameters" in parameters? parameters.modalParameters : {};
		this.modalWindow = null;
		this.enableKeyboardNavigation = "enableKeyboardNavigation" in parameters? parameters.enableKeyboardNavigation : true;

		this.heightNode = 30;
		this.paddingModal = 20;

		this.addHandlers();
	};

	BX.Disk.Tree.Modal.prototype =
	{
		show: function ()
		{
			if (this.modalWindow)
			{
				this.modalWindow.show();
				return;
			}

			var _keyPress = this.handleKeyPress.bind(this);
			this.modalWindow = BX.Disk.modalWindow({
				height: Math.min(document.documentElement.clientHeight - 100, 400),
				bindElement: this.modalParameters.bindElement || null,
				title: this.modalParameters.title || BX.message('DISK_FOLDER_LIST_TITLE_MODAL_TREE'),
				overlay: true,
				autoHide: true,
				modalId: this.modalParameters.modalId || 'bx-disk-toolbar-tree',
				content: [
					this.modalParameters.contentTitle? this.buildContentTitle(this.modalParameters.contentTitle) : null,
					BX.create('ul', {
						props: {
							className: 'bx-disk-wood-folder'
						},
						children: [this.tree.buildByRoot()]
					})
				],
				events: {
					onPopupShow: function () {
						this.tree.selectNode(this.tree.getRootNode());

						BX.bind(document, 'keydown', _keyPress);
					}.bind(this),
					onPopupClose: function () {
						BX.unbind(document, 'keydown', _keyPress);
					}
				},
				buttons: this.modalParameters.buttons || null
			});
		},

		/**
		 *
		 * @param {string} titleMessage
		 * @returns {Element}
		 */
		buildContentTitle: function(titleMessage)
		{
			return BX.create('div', {
				props: {
					className: 'bx-disk-popup-content-title'
				},
				text: titleMessage
			});
		},

		handleEnter: function(node, objectId, e)
		{},

		handleUpArrow: function(node, objectId, e)
		{
			this.tree.unselectAllNodes();

			this.tree.selectNode(node.previousSibling || BX.findParent(node, {
				className: 'bx-disk-open'
			}, 10));

			if (!this.tree.getFirstSelectedNode())
			{
				this.tree.selectNode(node);
			}

			e.preventDefault();
		},

		getNextSibling: function(node)
		{
			if (!node)
			{
				return null;
			}
			if (node.nextSibling)
			{
				return node.nextSibling;
			}

			return this.getNextSibling(BX.findParent(node, {
				className: 'bx-disk-open'
			}, 10));
		},

		handleDownArrow: function(node, objectId, e)
		{
			this.tree.unselectAllNodes();
			if (this.tree.isOpened(node))
			{
				this.tree.selectNode(getByClass(node, 'bx-disk-can-select', true));
			}
			else
			{
				this.tree.selectNode(this.getNextSibling(node));
			}

			if (!this.tree.getFirstSelectedNode())
			{
				this.tree.selectNode(node);
			}

			e.preventDefault();
		},

		handleLeftArrow: function(node, objectId, e)
		{
			if (this.tree.hasSubFolders(node) && this.tree.isOpened(node))
			{
				this.tree.collapse(node);
			}
			else
			{
				this.tree.unselectAllNodes();
				this.tree.selectNode(BX.findParent(node, {
					className: 'bx-disk-open'
				}, 10));

				if (!this.tree.getFirstSelectedNode())
				{
					this.tree.selectNode(node);
				}
			}

			e.preventDefault();
		},

		handleRightArrow: function(node, objectId, e)
		{
			if (this.tree.hasSubFolders(node))
			{
				this.tree.expand(node);
			}

			e.preventDefault();
		},

		/**
		 * @private
		 * @param {KeyboardEvent} e
		 */
		handleKeyPress: function (e)
		{
			if (!this.enableKeyboardNavigation || !this.tree.getFirstSelectedId())
			{
				return;
			}

			if (this.tree.multipleChoice)
			{
				//I don't know what do when you choose couple of folder and press arrows
				return;
			}

			var node = this.tree.getFirstSelectedNode();
			var objectId = node.dataset.objectId;

			var key = (e || window.event).keyCode || (e || window.event).charCode;
			if (key == 13)
			{
				this.handleEnter(node, objectId, e);
			}
			if (key == 38)
			{
				this.handleUpArrow(node, objectId, e);
			}
			else if (key == 40)
			{
				this.handleDownArrow(node, objectId, e);
			}
			else if (key == 37)
			{
				this.handleLeftArrow(node, objectId, e);
			}
			else if (key == 39)
			{
				this.handleRightArrow(node, objectId, e);
			}
		},

		handleSelectFolder: function(node, objectId, previousSelected)
		{
			if(
				this.isVisibleNode(previousSelected) &&
				!this.isVisibleNode(node) &&
				previousSelected.offsetTop > node.offsetTop
			)
			{
				this.modalWindow.contentContainer.scrollTop -= this.heightNode*2;
			}
			else if (
				this.isVisibleNode(previousSelected) &&
				this.getNextSibling(node) && !this.isVisibleNode(this.getNextSibling(node))
			)
			{
				this.modalWindow.contentContainer.scrollTop += this.heightNode*2;
			}
			else if (!this.isVisibleNode(node))
			{
				this.scrollToNode(node);
			}
		},

		addHandlers: function()
		{
			BX.addCustomEvent(this.tree, 'Tree:onSelectFolder', this.handleSelectFolder.bind(this));
		},

		scrollToNode: function(node)
		{
			if(this.modalWindow)
			{
				this.modalWindow.contentContainer.scrollTop = node.offsetTop - this.heightNode - this.paddingModal;
			}
		},

		isVisibleNode: function(node)
		{
			if (!node)
			{
				return false;
			}

			if(!this.modalWindow)
			{
				return false;
			}

			return (
				this.modalWindow.contentContainer.scrollTop + this.heightNode + this.paddingModal <= node.offsetTop &&
				this.modalWindow.contentContainer.scrollTop + this.heightNode + this.paddingModal >= node.offsetTop - 400
			);
		}
	};

	/**
	 *
	 * @param {BX.Disk.Tree.Structure} tree
	 * @param {object} parameters
	 */
	BX.Disk.Tree.Modal.buildByTree = function(tree, parameters)
	{
		parameters = BX.type.isPlainObject(parameters)? parameters : {};
		parameters.tree = tree;

		return new BX.Disk.Tree.Modal(null, parameters);
	};

	/**
	 *
	 * @param {BX.Disk.Tree.Node|null} rootObject
	 * @param {object} parameters
	 * @extends {BX.Disk.Tree.Modal}
	 * @constructor
	 */
	BX.Disk.Tree.NavigateModal = function (rootObject, parameters)
	{
		BX.Disk.Tree.Modal.apply(this, arguments);

		this.modalWindow = null;
	};

	BX.Disk.Tree.NavigateModal.prototype =
	{
		__proto__: BX.Disk.Tree.Modal.prototype,
		constructor: BX.Disk.Tree.NavigateModal,

		handleEnter: function (node, objectId, e)
		{
			window.location = BX.Disk.getUrlToShowObjectInGrid(objectId);
			e.preventDefault();
		}
	};

	/**
	 * Gets elements by class name
	 * @param rootElement
	 * @param className
	 * @param first
	 * @returns {Array|null}
	 */
	var getByClass = function(rootElement, className, first)
	{
		var result = [];

		if (className)
		{
			result = rootElement ? rootElement.getElementsByClassName(className) : [];

			if (first)
			{
				result = result.length ? result[0] : null;
			}
			else
			{
				result = [].slice.call(result);
			}
		}

		return result;
	};

})();
