/**
 * @module selector/widget/entity/tree-selectors/shared/navigator
 */
jn.define('selector/widget/entity/tree-selectors/shared/navigator', (require, exports, module) => {
	const { clone } = require('utils/object');

	/**
	 * @class Navigator
	 */
	class Navigator
	{
		/**
		 * @typedef {{id: Number|String, entityId: String, [key]: any}} Node
		 */

		/**
		 * @public
		 * @param {{children: Array<Object>, [key]: any}} root
		 * @param {(Object) => Boolean} callback
		 * @returns {Object|null}
		 * findInTree searches for the first node, which callback returns true for
		 */
		static findInTree(root, callback)
		{
			const queue = [root];
			let currentNode = null;

			while (queue.length > 0)
			{
				currentNode = queue.shift();

				if (callback(currentNode))
				{
					return currentNode;
				}

				queue.push(
					...(Array.isArray(currentNode.children) ? currentNode.children : []),
				);
			}

			return null;
		}

		constructor(options = {})
		{
			this.options = options;

			this.idTree = null;
			this.entitiesMap = new Map();
		}

		/**
		 * @public
		 * @param {Node} treeRoot
		 */
		init(treeRoot)
		{
			this.#setIdTreeRoot(
				this.#initNode(treeRoot),
			);

			const treeRootId = this.#getIdTreeRoot().id;

			this.#setCurrentNodeByInternalId(treeRootId);
		}

		#prepareId({ id, entityId })
		{
			return `${id}/${entityId}`;
		}

		#initNode(node)
		{
			const internalId = this.#prepareId(node);

			const preparedNode = clone(node);
			preparedNode.children = null;

			this.#setNodeByInternalId(internalId, preparedNode);

			return {
				id: internalId,
				children: null,
				parentId: null,
			};
		}

		/**
		 * @public
		 * @param {Number|String} id
		 * @param {String} entityId
		 * @returns {Node}
		 */
		getNode({ id, entityId })
		{
			return this.#getNodeByInternalId(
				this.#prepareId({ id, entityId }),
			);
		}

		#getNodeByInternalId(internalId)
		{
			return this.entitiesMap.get(internalId);
		}

		#setNodeByInternalId(internalId, node)
		{
			return this.entitiesMap.set(internalId, node);
		}

		/**
		 * @public
		 * @returns {Node}
		 */
		getRootNode()
		{
			return this.#getNodeByInternalId(this.#getIdTreeRoot()?.id);
		}

		/**
		 * @public
		 * @param {Number|String} id
		 * @param {String} entityId
		 * @param {Array<Node>} children
		 */
		setChildren({ id, entityId, children })
		{
			this.#setChildrenByInternalId(
				this.#prepareId({ id, entityId }),
				children,
			);
		}

		/**
		 * @public
		 * @param {Number|String} id
		 * @param {String} entityId
		 */
		getChildren({ id, entityId })
		{
			return this.#getChildrenByInternalId(
				this.#prepareId({ id, entityId }),
			);
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isRoot(node)
		{
			return this.#getIdTreeRoot()?.id === this.#prepareId(node);
		}

		#setChildrenByInternalId(internalId, children)
		{
			const node = this.#findNodeInTreeByInternalId(internalId);
			if (!node)
			{
				console.warn(`Node with id '${internalId}' not found`);

				return;
			}

			node.children = children.map((child) => ({
				...this.#initNode(child),
				parentId: internalId,
			}));
		}

		#getChildrenByInternalId(internalId)
		{
			const node = this.#findNodeInTreeByInternalId(internalId);
			if (!node)
			{
				console.warn(`Node with id '${internalId}' not found`);

				return null;
			}

			return node.children?.map(({ id }) => this.#getNodeByInternalId(id));
		}

		#findNodeInTreeByInternalId(internalId)
		{
			return Navigator.findInTree(this.#getIdTreeRoot(), (currentNode) => (
				currentNode.id === internalId
			));
		}

		/**
		 * @public
		 */
		moveToParentNode()
		{
			let parent = this.#getTreeParentNode();
			if (!parent)
			{
				parent = this.#getIdTreeRoot();
			}

			this.#setCurrentNodeByInternalId(parent.id);
		}

		/**
		 * @public
		 * @returns {Node}
		 */
		getParentNote()
		{
			return this.#getNodeByInternalId(this.currentNode?.parentId);
		}

		#getTreeParentNode()
		{
			return this.#findNodeInTreeByInternalId(this.currentNode?.parentId);
		}

		/**
		 * @public
		 * @param {Number|String} id
		 * @param {String} entityId
		 */
		setCurrentNodeById({ id, entityId })
		{
			this.#setCurrentNodeByInternalId(
				this.#prepareId({ id, entityId }),
			);
		}

		#setCurrentNodeByInternalId(internalId)
		{
			const internalNode = this.#findNodeInTreeByInternalId(internalId);
			if (!internalNode)
			{
				console.warn(`Node with id '${internalId}' not found`);

				return;
			}

			if (
				this.currentNode
				&& this.currentNode.id !== internalId
				&& typeof this.options.onCurrentNodeChanged === 'function'
			)
			{
				this.options.onCurrentNodeChanged(
					this.#getNodeByInternalId(internalNode.id),
				);
			}

			this.currentNode = internalNode;
		}

		/**
		 * @public
		 * @returns {Node}
		 */
		getCurrentNode()
		{
			return this.#getNodeByInternalId(this.currentNode?.id);
		}

		/**
		 * @public
		 * @returns {Array<Node>}
		 */
		getCurrentNodeChildren()
		{
			return this.#getChildrenByInternalId(this.currentNode?.id);
		}

		/**
		 * @public
		 * @param {Array<Node>} children
		 */
		setCurrentNodeChildren(children)
		{
			this.#setChildrenByInternalId(this.currentNode?.id, children);
		}

		#getIdTreeRoot()
		{
			return this.idTree;
		}

		#setIdTreeRoot(rootNode)
		{
			this.idTree = rootNode;
		}
	}

	module.exports = { Navigator };
});
