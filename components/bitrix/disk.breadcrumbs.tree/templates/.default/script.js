BX.namespace("BX.Disk");
BX.Disk.BreadcrumbsTreeClass = (function ()
{
	var lastNodes = [];
	var enableCollectLastNodes = true;
	function getLastNodes()
	{
		return lastNodes;
	}
	function pushLastNode(node)
	{
		lastNodes.push(node);
	}

	var BreadcrumbsTreeClass = function (parameters)
	{
		this.rootObject = parameters.rootObject || {};
		this.firstObject = parameters.firstObject || {};
		this.containerId = parameters.containerId;
		this.container = BX(this.containerId);
		this.storageBaseUrl = parameters.storageBaseUrl || '/';

		this.lastNode = null;

		this.ajaxUrl = '/bitrix/components/bitrix/disk.breadcrumbs.tree/ajax.php';

		this.setEvents();

		this.drawFirstNode(this.firstObject);
	};

	BreadcrumbsTreeClass.prototype.onSelectFolder = function (node)
	{
		var title = BX.findChild(node, {
			className: 'bx-disk-wf-folder-name'
		}, true);

		if (!title)
			return false;

		var path = [];
		var label = BX.findChild(title, {tagName: 'span'});
		if(label)
		{
			path.push(encodeURIComponent(label.textContent || label.innerText));
		}

		BX.findParent(node, function (node) {
				if(BX.type.isElementNode(node) && BX.hasClass(node, 'bx-disk-parent'))
				{
					var title = BX.findChild(node, {
						className: 'bx-disk-wf-folder-name'
					}, true);
					if(title)
					{
						var label = BX.findChild(title, {tagName: 'span'});
						if(label)
						{
							path.push(encodeURIComponent(label.textContent || label.innerText));
						}
					}
				}
			}, this.container
		);

		var href = this.storageBaseUrl + path.reverse().join('/');
		if(href.slice(-1) != '/')
		{
			href += '/';
		}
		document.location.href = href;
	};

	BreadcrumbsTreeClass.prototype.onUnSelectFolder = function (node)
	{

	};

	BreadcrumbsTreeClass.prototype.onRemoveRowFromDiskList = function (objectId)
	{
		var targetLiNiHao = BX.findChild(this.container, {
			tagName: 'li',
			className: 'bx-disk-folder-container',
			attribute: {
				'data-object-id': objectId
			}
		}, true);
		if(targetLiNiHao)
		{
			BX.remove(targetLiNiHao);
		}
	};

	BreadcrumbsTreeClass.prototype.setEvents = function ()
	{
		BX.addCustomEvent(this.container, "onSelectFolder", BX.proxy(this.onSelectFolder, this));
		BX.addCustomEvent(this.container, "onUnSelectFolder", BX.proxy(this.onUnSelectFolder, this));
		BX.addCustomEvent("onRemoveRowFromDiskList", BX.proxy(this.onRemoveRowFromDiskList, this));
	};

	BreadcrumbsTreeClass.prototype.drawFirstNode = function (firstObject)
	{
		var rootNode = this.buildTreeNode(firstObject);
		var ul = BX.create('ul', {
			props: {
				className: 'bx-disk-wood-folder'
			}
		});
		rootNode.appendChild(ul);

		this.lastNode = rootNode;

		BX.adjust(this.container, {
			children: [
				BX.create('ul', {
					props: {
						className: 'bx-disk-wood-folder'
					},
					children: [rootNode]
				})
			]
		});
	};

	BreadcrumbsTreeClass.prototype.loadSubFolders = function (node)
	{
		if (!node) {
			return;
		}
		var objectId = node.getAttribute('data-object-id');
		if (!objectId) {
			return;
		}

		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSubFoldersToAdd'),
			data: {
				objectId: objectId
			},
			onsuccess: BX.delegate(function (response) {
				if(!response || response.status != 'success')
				{
					BX.Disk.showModalWithStatusAction(response);
					return;
				}
				this.buildTree(node, response);
				window.jsDD.refreshDestArea();

			}, this)
		})
	};

	BreadcrumbsTreeClass.prototype.buildTree = function (rootNode, response)
	{
		if (!response || response.status != 'success') {
			BX.Disk.showModalWithStatusAction(response);
			return;
		}
		var ul = BX.create('ul', {
			props: {
				className: 'bx-disk-wood-folder'
			}
		});

		rootNode.appendChild(ul);
		if (response.items && response.items.length) {
			for (var i in response.items) {
				if (!response.items.hasOwnProperty(i)) {
					continue;
				}
				ul.appendChild(this.buildTreeNode(response.items[i]));
			}
		}
		else
		{
			var td = BX.findChild(rootNode, {
				className: 'bx-disk-wf-arrow'
			}, true);
			if(td)
			{
				BX.cleanNode(td);
			}
		}
		BX.removeClass(rootNode, 'bx-disk-close');
		BX.addClass(rootNode, 'bx-disk-open');
		BX.addClass(rootNode, 'bx-disk-loaded');
	};

	BreadcrumbsTreeClass.prototype.buildTreeNode = function (object)
	{
		this.lastNode = BX.create('li', {
			props: {
				className: 'bx-disk-folder-container bx-disk-parent bx-disk-close'
			},
			attrs: {
				'data-object-id': object.id
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
												click: BX.delegate(function (e)
												{
													var target = e.target || e.srcElement;
													var parent = BX.findParent(target, {
														className: 'bx-disk-parent'
													});
													if (BX.hasClass(parent, 'bx-disk-open')) {
														BX.removeClass(parent, 'bx-disk-open');
														BX.addClass(parent, 'bx-disk-close');
														return;
													}
													if (BX.hasClass(parent, 'bx-disk-loaded')) {
														BX.removeClass(parent, 'bx-disk-close');
														BX.addClass(parent, 'bx-disk-open');
														return;
													}
													this.loadSubFolders(parent);
												}, this)
											},
											children: [
												(object.hasSubFolders? BX.create('span') : null)
											]
										}),
										BX.create('td', {
											props: {
												className: 'bx-disk-wf-folder-icon'
											},
											children: [
												BX.create('span')
											]
										}),
										BX.create('td', {
											props: {
												className: 'bx-disk-wf-folder-name'
											},
											events: {
												click: BX.delegate(function (e)
												{
													var target = e.target || e.srcElement;
													var parent = BX.findParent(target, {
														className: 'bx-disk-parent'
													});
													if (BX.hasClass(parent, 'selected')) {
														BX.removeClass(parent, 'selected');
														BX.onCustomEvent(this.container, 'onUnSelectFolder', [parent]);
														return;
													}
													BX.addClass(parent, 'selected');
													BX.onCustomEvent(this.container, 'onSelectFolder', [parent]);
												}, this)
											},
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

		var dest = BX.findChild(this.lastNode, {
			className: 'bx-disk-folder-container'
		});
		if(!dest)
		{
			return this.lastNode;
		}

		dest.onbxdestdraghout = function ()
		{
			BX.removeClass(this.parentNode, 'selected');
		};
		dest.onbxdestdragfinish = BX.delegate(function (currentNode, x, y) {
			if(!currentNode.getAttribute('data-object-id'))
			{
				return false;
			}

			BX.Disk.ajax({
				method: 'POST',
				dataType: 'json',
				url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'moveTo'),
				data: {
					objectId: currentNode.getAttribute('data-object-id'),
					targetObjectId: BX.proxy_context.parentNode.getAttribute('data-object-id')
				},
				onsuccess: function (response) {
					BX.Disk.showModalWithStatusAction(response);
				}
			});

			return true;
		}, this);
		dest.onbxdestdraghover = function (currentNode, x, y)
		{
			if(!currentNode.getAttribute('data-object-id'))
			{
				return false;
			}

			if(BX.hasClass(this.parentNode, 'selected'))
			{
				return;
			}
			BX.addClass(this.parentNode, 'selected');

			if(BX.hasClass(this.parentNode, 'bx-disk-open'))
			{
				return;
			}

			var arrow = BX.findChild(this, {
				className: 'bx-disk-wf-arrow'
			}, true);
			if(!arrow)
				return;

			BX.fireEvent(arrow, 'click');

			return true;
		};
		window.jsDD.registerDest(dest);


		enableCollectLastNodes && pushLastNode(this.lastNode);
		return this.lastNode;
	};

	BreadcrumbsTreeClass.prototype.lazyLoadSubFolders = function (successCallback)
	{
		//todo refactor. Please. Don't  e
		enableCollectLastNodes = false;

		var lastNodes = BX.clone(getLastNodes());
		var lastNodesIds = [];
		var root = this.buildTreeNode(this.rootObject);
		lastNodes.unshift(root);

		for (var i in lastNodes) {
			if (!lastNodes.hasOwnProperty(i)) {
				continue;
			}
			var j = lastNodes[i].getAttribute('data-object-id');
			if(typeof j !== "undefined" && j != "undefined")
			{
				lastNodesIds.push(j);
			}
		}
		if (!lastNodesIds.length) {
			return;
		}
		
		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showManySubFolders'),
			data: {
				objectIds: lastNodesIds
			},
			onsuccess: BX.delegate(function (response) {
				if(!response || response.status != 'success')
				{
					BX.Disk.showModalWithStatusAction(response);
					return;
				}

				var counter = 0;
				var nodeObject = null;
				var prevNodeObject = null;
				var ul;
				var nextNodeObjectId;
				var nodeObjectId;

				while(lastNodesIds.length) {
					nodeObjectId = lastNodesIds.shift();
					nextNodeObjectId = lastNodesIds.shift();
					if (nextNodeObjectId) {
						lastNodesIds.unshift(nextNodeObjectId);
					}
					ul = BX.create('ul', {
						props: {
							className: 'bx-disk-wood-folder'
						}
					});

					prevNodeObject = nodeObject;
					if (response.items[nodeObjectId] && response.items[nodeObjectId].length) {
						for (var i in response.items[nodeObjectId]) {
							if (!response.items[nodeObjectId].hasOwnProperty(i)) {
								continue;
							}
							var child = this.buildTreeNode(response.items[nodeObjectId][i]);
							if (response.items[nodeObjectId][i].id == nextNodeObjectId) {
								nodeObject = child;
							}
							ul.appendChild(child);
						}
					}

					if (counter == 0) {
						BX.cleanNode(this.container);
						BX.adjust(this.container, {
							children: [
								ul
							]
						});
					}
					else if (prevNodeObject) {
						prevNodeObject.appendChild(ul);
						BX.removeClass(prevNodeObject, 'bx-disk-close');
						BX.addClass(prevNodeObject, 'bx-disk-open');
						BX.addClass(prevNodeObject, 'bx-disk-loaded');
					}
					counter++;
				}

				successCallback();
			}, this)
		});
	};

	return BreadcrumbsTreeClass;
})();
