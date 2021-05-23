BX.namespace("BX.Crm");

BX.Crm.showModalWithStatusAction = function (response, action)
{
	if (!response.message) {
		if (response.status == "success") {
			response.message = BX.message("CRM_JS_STATUS_ACTION_SUCCESS");
		}
		else {
			response.message = BX.message("CRM_JS_STATUS_ACTION_ERROR") + ". " + this.getFirstErrorFromResponse(response);
		}
	}
	var messageBox = BX.create("div", {
		props: {
			className: "bx-crm-alert"
		},
		children: [
			BX.create("span", {
				props: {
					className: "bx-crm-aligner"
				}
			}),
			BX.create("span", {
				props: {
					className: "bx-crm-alert-text"
				},
				text: response.message
			}),
			BX.create("div", {
				props: {
					className: "bx-crm-alert-footer"
				}
			})
		]
	});

	var currentPopup = BX.PopupWindowManager.getCurrentPopup();
	if(currentPopup)
	{
		currentPopup.destroy();
	}

	var idTimeout = setTimeout(function ()
	{
		var w = BX.PopupWindowManager.getCurrentPopup();
		if (!w || w.uniquePopupId != "bx-crm-status-action") {
			return;
		}
		w.close();
		w.destroy();
	}, 3000);
	var popupConfirm = BX.PopupWindowManager.create("bx-crm-status-action", null, {
		content: messageBox,
		onPopupClose: function ()
		{
			this.destroy();
			clearTimeout(idTimeout);
		},
		autoHide: true,
		zIndex: 10200,
		className: "bx-crm-alert-popup"
	});
	popupConfirm.show();

	BX("bx-crm-status-action").onmouseover = function (e)
	{
		clearTimeout(idTimeout);
	};

	BX("bx-crm-status-action").onmouseout = function (e)
	{
		idTimeout = setTimeout(function ()
		{
			var w = BX.PopupWindowManager.getCurrentPopup();
			if (!w || w.uniquePopupId != "bx-crm-status-action") {
				return;
			}
			w.close();
			w.destroy();
		}, 3000);
	};
};
BX.Crm.getFirstErrorFromResponse = function(reponse)
{
	reponse = reponse || {};
	if(!reponse.errors)
		return "";

	return reponse.errors.shift().message;
};


BX.Crm.ProductSectionTreeClass = (function ()
{
	var ProductSectionTreeClass = function (parameters)
	{
		this.catalogId = parseInt(parameters["catalogId"]);
		this.sectionId = parseInt(parameters["sectionId"]);
		this.treeInfo = parameters["treeInfo"];
		this.productListUri = parameters["productListUri"];
		this.jsEventsMode = false;
		this.isExternalSectionSelectDisabled = false;
		this.isSelectSectionEventDisabled = false;
		if (typeof(parameters["jsEventsMode"]) !== "undefined" && parameters["jsEventsMode"] !== null)
			this.jsEventsMode = !!parameters["jsEventsMode"];
		this.containerId = parameters.containerId;
		this.container = BX(this.containerId);
		this.ajaxUrl = "/bitrix/components/bitrix/crm.product.section.tree/ajax.php";
		this.jsEventsManagerId = parameters.jsEventsManagerId || "";
		this.jsEventsManager = BX.Crm[this.jsEventsManagerId] || null;

		this.setEvents();

		this.selectedNodes = [];
		this.sectionInfo = {
			"0": {
				node: BX.findPreviousSibling(this.container, {"tag": "div", "class": "tal"}),
				name: BX.message("CRM_PRODUCT_SECTION_TREE_TITLE")
			}
		};
		this.buildTree(this.container, this.treeInfo);
		this.container.style.display = "block";

		if (this.container)
		{
			var tal = this.sectionInfo["0"].node;
			if (tal)
			{
				BX.bind(tal, "click", BX.delegate(this.handleTitleClick, this));
				tal.style.cursor = "pointer";
			}
		}
	};

	ProductSectionTreeClass.prototype = {
		setEvents: function ()
		{
			BX.addCustomEvent("onBeforeSelectSection", BX.proxy(this.onBeforeSelectSection, this));
			BX.addCustomEvent(this.container, "onSelectSection", BX.proxy(this.onSelectSection, this));
			//BX.addCustomEvent(this.container, "onUnSelectSection", BX.proxy(this.onUnSelectSection, this));
			BX.addCustomEvent("onRemoveRowFromProductList", BX.proxy(this.onRemoveRowFromProductList, this));

			if (this.jsEventsMode)
			{
				this.jsEventsManager.registerEventHandler("CrmProduct_SelectSection", BX.proxy(this.onExternalSectionSelect, this));
			}
		},
		buildTreeNode: function (sectionInfo)
		{
			var node;
			node = BX.create("li", {
				props: {
					className: "bx-crm-section-container bx-crm-parent bx-crm-close"
				},
				attrs: {
					"data-object-id": sectionInfo.id
				},
				children: [
					BX.create("div", {
						props: {
							className: "bx-crm-section-container"
						},
						children: [
							BX.create("table", {
								children: [
									BX.create("tr", {
										children: [
											BX.create("td", {
												props: {
													className: "bx-crm-wf-arrow"
												},
												events: {
													click: BX.delegate(this.handleArrowClick, this)
												},
												children: [
													BX.create("span")
												]
											}),
											BX.create("td", {
												props: {
													className: "bx-crm-wf-section-icon"
												},
												children: [
													BX.create("span")
												]
											}),
											BX.create("td", {
												props: {
													className: "bx-crm-wf-section-name"
												},
												events: {
													click: BX.delegate(function (e)
													{
														var target = e.target || e.srcElement;
														var parent = BX.findParent(target, {
															className: "bx-crm-parent"
														});
														BX.onCustomEvent("onBeforeSelectSection", [parent]);
														//if (BX.hasClass(parent, "selected")) {
															/*BX.removeClass(parent, "selected");
															BX.onCustomEvent(this.container, "onUnSelectSection", [parent]);*/
															//return;
														//}
														BX.onCustomEvent(this.container, "onSelectSection", [parent]);
													}, this)
												},
												children: [
													BX.create("span", {
														text: sectionInfo.name
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

			if (sectionInfo["selected"] === "Y")
			{
				BX.addClass(node, "selected");
				this.selectedNodes.push({id: sectionInfo.id, node: node});
			}

			if (sectionInfo["hasChildren"] !== "Y")
			{
				var td;
				if(td = BX.findChild(node, {className: "bx-crm-wf-arrow"}, true))
					BX.addClass(td, "bx-crm-wf-section-empty");
			}

			this.sectionInfo[sectionInfo.id] = {node: node, name: sectionInfo.name};

			var dest = BX.findChild(node, {
				className: "bx-crm-section-container"
			});
			if(!dest)
			{
				return node;
			}

			dest.onbxdestdraghout = function ()
			{
				BX.removeClass(this.parentNode, "selected");
			};
			dest.onbxdestdragfinish = BX.delegate(
				function (currentNode, x, y) {
				BX.ajax({
					method: "POST",
					dataType: "json",
					url: this.ajaxUrl,
					data: {
						action: "moveTo",
						catalogId: this.catalogId,
						sectionId: currentNode.getAttribute("data-object-id"),
						targetSectionId: BX.proxy_context.parentNode.getAttribute("data-object-id"),
						sessid: BX.bitrix_sessid()
					},
					onsuccess: function (response) {
						BX.Crm.showModalWithStatusAction(response);
					}
				});

				return true;
			},
				this
			);
			dest.onbxdestdraghover = function (currentNode, x, y)
			{
				if(BX.hasClass(this.parentNode, "selected"))
				{
					return;
				}
				BX.addClass(this.parentNode, "selected");

				if(BX.hasClass(this.parentNode, "bx-crm-open"))
				{
					return;
				}

				var arrow = BX.findChild(this, {
					className: "bx-crm-wf-arrow"
				}, true);
				if(!arrow)
					return;

				BX.fireEvent(arrow, "click");

				return true;
			};
			window.jsDD.registerDest(dest);

			return node;
		},
		buildTree: function(node, treeInfo, checkChildren, buildUp)
		{
			buildUp = !!buildUp;

			if (!node || !treeInfo)
				return;

			var el, id, i, ul, td;

			ul = null;
			if (treeInfo instanceof Array)
			{
				for (i = 0; i < treeInfo.length; i++)
				{
					if (buildUp)
					{
						if (this.sectionInfo[treeInfo[i]["ID"]])
						{
							var childNode = this.sectionInfo[treeInfo[i]["ID"]].node;
							this.buildTree(childNode, treeInfo[i]["CHILDREN"], false, true);
						}
						else
						{
							this.buildTree(node, treeInfo);
						}
					}
					else
					{
						if (i === 0)
						{
							while (el = BX.findChild(node, {"tag": "ul", "class": "bx-crm-wood-section"}))
								node.removeChild(el);
							ul = BX.create("ul", {props: {className: "bx-crm-wood-section"}});
						}

						if (ul)
						{
							el = this.buildTreeNode({
								"id": treeInfo[i]["ID"],
								"name": treeInfo[i]["NAME"],
								"selected": treeInfo[i]["SELECTED"],
								"hasChildren": treeInfo[i]["HAS_CHILDREN"]
							});
							if (treeInfo[i]["SELECTED"] === "Y" && !treeInfo[i]["CHILDREN"].length)
							{
								if(td = BX.findChild(el, {className: "bx-crm-wf-arrow"}, true))
									BX.addClass(td, "bx-crm-wf-section-empty");
							}
							ul.appendChild(el);
							if (treeInfo[i]["CHILDREN"])
							{
								this.buildTree(el, treeInfo[i]["CHILDREN"]);
							}
							node.appendChild(ul);
							if (node !== this.container)
							{
								BX.removeClass(node, "bx-crm-close");
								BX.addClass(node, "bx-crm-open");
								BX.addClass(node, "bx-crm-loaded");
							}
						}
					}
				}
				if (i === 0 && !!checkChildren)
				{
					td = BX.findChild(node, {
						className: "bx-crm-wf-arrow"
					}, true);
					if(td)
						BX.addClass(td, "bx-crm-wf-section-empty");
				}
			}
		},
		loadSubsections: function (node)
		{
			if (!node)
				return;

			var sectionId = node.getAttribute("data-object-id");
			if (!sectionId)
				return;

			BX.ajax({
				method: "POST",
				dataType: "json",
				url: this.ajaxUrl,
				data: {
					action: "getSubsections",
					catalogId: this.catalogId,
					sectionId: sectionId,
					sessid: BX.bitrix_sessid()
				},
				onsuccess: BX.delegate(function (response)
				{
					if(!response || response.status != "success")
					{
						BX.Crm.showModalWithStatusAction(response);
						return;
					}
					this.buildTree(node, response["response"], true);
					window.jsDD.refreshDestArea();

				}, this)
			})
		},
		expandTree: function (sectionId)
		{
			BX.ajax({
				method: "POST",
				dataType: "json",
				url: this.ajaxUrl,
				data: {
					action: "getInitialTree",
					catalogId: this.catalogId,
					sectionId: sectionId,
					sessid: BX.bitrix_sessid()
				},
				onsuccess: BX.delegate(function (response)
				{
					if(!response || response.status != "success")
					{
						BX.Crm.showModalWithStatusAction(response);
						return;
					}
					this.handleExpandTreeAjaxResponse(response);
					window.jsDD.refreshDestArea();

				}, this)
			})
		},
		handleExpandTreeAjaxResponse: function (response)
		{
			if (response["response"] && response["response"] instanceof Array && response["response"].length)
				this.buildTree(this.container, response["response"], false, true);
		},
		onBeforeSelectSection: function (nodeSelected)
		{
			var nodeInfo;

			while (this.selectedNodes.length > 0)
			{
				nodeInfo = this.selectedNodes.shift();
				if (BX.type.isDomNode(nodeInfo.node))
					BX.removeClass(nodeInfo.node, "selected");
			}
		},
		onExternalSectionSelect: function (params)
		{
			if (this.isExternalSectionSelectDisabled)
				return;

			this.isSelectSectionEventDisabled = true;

			if (params && params.hasOwnProperty("sectionId"))
			{
				BX.onCustomEvent("onBeforeSelectSection", [null]);
				if (parseInt(params.sectionId) === 0)
				{
					this.selectedNodes.push({id: "0", node: null});
				}
				else
				{
					if (!this.sectionInfo[params.sectionId])
					{
						this.expandTree(params.sectionId);
					}
					if (this.sectionInfo[params.sectionId])
					{
						var node = this.sectionInfo[params.sectionId].node;
						this.expandParents(node);
						this.onSelectSection(node);
					}
				}
			}

			this.isSelectSectionEventDisabled = false;
		},
		onSelectSection: function (node)
		{
			if (!BX.type.isDomNode(node))
				return;

			BX.addClass(node, "selected");

			var sectionId = node.getAttribute("data-object-id");
			if (!sectionId)
				return;

			this.selectedNodes.push({id: sectionId, node: node});

			this.sectionId = sectionId;

			if (this.jsEventsMode)
			{
				var arrowNode = BX.findChild(node, {tagName: "td", className: "bx-crm-wf-arrow"}, true);
				if (arrowNode)
				{
					var arrowParent = BX.findParent(arrowNode, {className: "bx-crm-parent"});
					if (!BX.hasClass(arrowParent, "bx-crm-open"))
						BX.fireEvent(arrowNode, "click");
				}

				var params = {
					catalogId: this.catalogId,
					sectionId: sectionId,
					sectionName: this.sectionInfo[sectionId].name
				};
				if (!this.isSelectSectionEventDisabled)
				{
					this.isExternalSectionSelectDisabled = true;
					this.jsEventsManager.fireEvent("CrmProduct_SelectSection", [params]);
					this.isExternalSectionSelectDisabled = false;
				}
			}
			else
			{
				var url = this.productListUri;
				document.location.href = url.replace(/#section_id#/g, sectionId);
			}
		},
		/*onUnSelectSection: function (node)
		{
		},*/
		onRemoveRowFromProductList: function (sectionId)
		{
			BX.remove(
				BX.findChild(
					this.container,
					{
						tagName: "li",
						className: "bx-crm-section-container",
						attribute: {"data-object-id": sectionId}
					}, true
				)
			);
		},
		handleTitleClick: function(e)
		{
			BX.onCustomEvent("onBeforeSelectSection", [null]);
			this.selectedNodes.push({id: "0", node: null});
			if (this.jsEventsMode)
			{
				var params = {
					catalogId: this.catalogId,
					sectionId: "0",
					sectionName: this.sectionInfo["0"].name
				};
				if (!this.isSelectSectionEventDisabled)
				{
					this.isExternalSectionSelectDisabled = true;
					BX.onCustomEvent(this.jsEventsManager, "CrmProduct_SelectSection", [params]);
					this.isExternalSectionSelectDisabled = false;
				}
			}
			else
			{
				var url = this.productListUri;
				document.location.href = url.replace(/#section_id#/g, "0");
			}
		},
		handleArrowClick: function (e)
		{
			var target = e.target || e.srcElement;
			var parent = BX.findParent(target, {
				className: "bx-crm-parent"
			});
			if (BX.hasClass(parent, "bx-crm-open")) {
				BX.removeClass(parent, "bx-crm-open");
				BX.addClass(parent, "bx-crm-close");
				return;
			}
			if (BX.hasClass(parent, "bx-crm-loaded")) {
				BX.removeClass(parent, "bx-crm-close");
				BX.addClass(parent, "bx-crm-open");
				return;
			}
			this.loadSubsections(parent);
		},
		expandParents: function (node)
		{
			if (!BX.type.isDomNode(node))
				return;

			var parent, arrow;
			parent = node;
			while(parent = BX.findParent(parent, {tag: "li", className: "bx-crm-parent"}, this.container))
			{
				if (BX.hasClass(parent, "bx-crm-close") && parent.hasAttribute("data-object-id"))
				{
					arrow = BX.findChild(parent, {tagName: "td", className: "bx-crm-wf-arrow"}, true);
					if (arrow)
					{
						BX.fireEvent(arrow, "click");
					}
				}
			}
		}
	};

	return ProductSectionTreeClass;
})();
