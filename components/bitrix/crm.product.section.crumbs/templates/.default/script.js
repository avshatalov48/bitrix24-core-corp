BX.namespace("BX.Crm");

if (typeof(BX.Crm.showModalWithStatusAction) === "undefined")
{
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
}
if (typeof(BX.Crm.getFirstErrorFromResponse) === "undefined")
{
	BX.Crm.getFirstErrorFromResponse = function(reponse)
	{
		reponse = reponse || {};
		if(!reponse.errors)
			return "";

		return reponse.errors.shift().message;
	};
}

BX.Crm.ProductSectionCrumbsClass = (function ()
{

	var ProductSectionCrumbsClass = function (parameters)
	{
		this.containerId = parameters.containerId;
		this.catalogId = parameters.catalogId || 0;
		this.sectionId = parameters.catalogId || 0;
		this.crumbs = parameters.crumbs || [];
		this.componentId = parameters.componentId || '';
		this.collapsedCrumbs = [];
		this.childrenCrumbs = [];
		this.showOnlyDeleted = parameters.showOnlyDeleted || 0;
		this.jsEventsMode = !!parameters.jsEventsMode;
		this.container = BX(this.containerId);
		this.isExternalSectionSelectDisabled = false;
		this.isSelectSectionEventDisabled = false;
		this.ajaxUrl = "/bitrix/components/bitrix/crm.product.section.crumbs/ajax.php";
		this.jsEventsManagerId = parameters.jsEventsManagerId || "";
		this.jsEventsManager = BX.Crm[this.jsEventsManagerId] || null;

		this.container.style.opacity = 1;

		this.buildCrumbs(this.sectionId, this.crumbs);

		if (this.jsEventsMode)
		{
			this.jsEventsManager.registerEventHandler("CrmProduct_SelectSection", BX.delegate(this.onExternalSectionSelect, this));
		}
	};
	
	ProductSectionCrumbsClass.prototype = {
		setEvents: function ()
		{
			BX.bindDelegate(this.container, "click", {tag: "span", className: "icon-arrow"}, BX.proxy(this.onClickArrow, this));
			BX.bind(BX('root_dots_' + this.containerId), "click", BX.proxy(this.onClickDots, this));
		},
		unsetEvents: function ()
		{
			BX.unbindAll(this.container);
			BX.unbindAll(BX('root_dots_' + this.containerId));
		},
		expand: function (crumb, arrow, items)
		{
			var objectId = crumb.getAttribute('data-objectId');
			BX.PopupMenu.show(
				'crm_product_section_crumbs_' + objectId,
				arrow,
				items[objectId],
				{
					autoHide: true,
					//offsetTop: 0,
					//offsetLeft:25,
					angle: {offset: 0},
					events: {
						onPopupClose: function ()
						{
						}
					}
				}
			);
		},
		onClickDots: function (event)
		{
			var menu = BX.PopupMenu.getMenuById('crm_product_section_crumbs_0');
			if(menu && menu.popupWindow)
				BX.PopupMenu.destroy('crm_product_section_crumbs_0');

			var arrowTarget = event.srcElement || event.target;
			BX.PopupMenu.show(
				'crm_product_section_crumbs_0',
				arrowTarget,
				this.collapsedCrumbs,
				{
					autoHide: true,
					//offsetTop: 0,
					//offsetLeft:25,
					angle: {offset: 0},
					events: {
						onPopupClose: function ()
						{
						}
					}
				}
			);
		},
		onClickArrow: function (event)
		{
			var arrowTarget = event.srcElement || event.target;
			var crumb = BX.findParent(arrowTarget, {
				className: 'bx-crm-interface-product-section-crumbs-item-container'
			}, this.container);

			var objectId = crumb.getAttribute('data-objectId');
			var isRoot = crumb.getAttribute('data-isRoot');
			if (objectId) {
				var menu = BX.PopupMenu.getMenuById('crm_product_section_crumbs_' + objectId);
				if(menu && menu.popupWindow)
					BX.PopupMenu.destroy('crm_product_section_crumbs_' + objectId);

				this.expand(crumb, arrowTarget, this.childrenCrumbs);
			}
		},
		reloadCrumbs: function (sectionId)
		{
			BX.ajax({
				method: "POST",
				dataType: "json",
				url: this.ajaxUrl,
				data: {
					action: "getCrumbs",
					componentId: this.componentId,
					catalogId: this.catalogId,
					sectionId: sectionId,
					urlTemplate: "#section_id#",
					jsEventsMode: (this.jsEventsMode ? "Y" : "N"),
					sessid: BX.bitrix_sessid()
				},
				onsuccess: BX.delegate(function (response)
				{
					if(!response || response.status != "success")
					{
						BX.Crm.showModalWithStatusAction(response);
						return;
					}
					this.buildCrumbs(sectionId, response["response"]);
				}, this)
			})
		},
		onSectionSelect: function (params)
		{
			if (!this.isSelectSectionEventDisabled && this.jsEventsMode)
			{
				this.isExternalSectionSelectDisabled = true;
				this.jsEventsManager.fireEvent("CrmProduct_SelectSection", [params]);
				this.reloadCrumbs(params["sectionId"]);
				this.isExternalSectionSelectDisabled = false;
			}
		},
		onExternalSectionSelect: function (params)
		{
			if (this.isExternalSectionSelectDisabled)
				return;

			if (params && params.hasOwnProperty("sectionId"))
			{
				this.reloadCrumbs(params.sectionId);
			}
		},
		buildCrumbs: function (sectionId, crumbs)
		{
			var showedItems = [];
			var	collapsedCrumbs = [],
				childrenCrumbs = [],
				menuItem = {},
				children,
				crumb;
			var i, j;

			this.cleanCrumbs();

			if (crumbs instanceof Array)
			{
				showedItems = crumbs.splice(-3, 3);

				for (i = 0; i < crumbs.length; i++)
				{
					menuItem = {};
					menuItem["title"] = "";
					menuItem["text"] = BX.util.htmlspecialchars(crumbs[i]["NAME"]);
					menuItem["data"] = {
						menuId: "crm_product_section_crumbs_0",
						sectionId: "" + crumbs[i]["ID"]
					};
					if (this.jsEventsMode)
						menuItem["onclick"] = BX.proxy(this.onClickCrumbLink, this);//crumbs[i]["LINK"];
					else
						menuItem["href"] = crumbs[i]['LINK'];
					collapsedCrumbs[i] = menuItem;
				}
			}
			if (collapsedCrumbs.length > 0)
			{
				this.container.appendChild(
					BX.create(
						'SPAN',
						{
							attrs: {
								id: "root_dots_" + this.containerId,
								className: "bx-crm-interface-product-section-crumbs-item-container-arrow"
							}
						}
					)
				);
			}
			for (i = 0; i < showedItems.length; i++)
			{
				if (showedItems[i]["CHILDREN"] instanceof Array)
				{
					children = showedItems[i]["CHILDREN"];
					for (j = 0; j < children.length; j++)
					{
						menuItem = {};
						menuItem["title"] = "";
						menuItem["text"] = BX.util.htmlspecialchars(children[j]["NAME"]);
						menuItem["data"] = {
							menuId: "crm_product_section_crumbs_" + showedItems[i]["ID"],
							sectionId: children[j]["LINK"]
						};
						if (this.jsEventsMode)
							menuItem["onclick"] = BX.proxy(this.onClickCrumbLink, this);//children[j]["LINK"];
						else
							menuItem["href"] = children[j]["LINK"];
						if (!childrenCrumbs[showedItems[i]["ID"]])
							childrenCrumbs[showedItems[i]["ID"]] = [];
						childrenCrumbs[showedItems[i]["ID"]].push(menuItem);
					}
					crumb = BX.create(
						'SPAN',
						{
							attrs: {
								"class": "bx-crm-interface-product-section-crumbs-item-container",
								"data-isRoot": parseInt(showedItems[i]["ID"]) === 0 ? "1" : "",
								"data-objectId": BX.util.htmlspecialchars("" + showedItems[i]['ID']),
								"data-objectName": BX.util.htmlspecialchars(showedItems[i]['NAME'])
							}
						}
					);
					if (showedItems.length !== (i + 1))
					{
						crumb.appendChild(
							BX.create(
								'SPAN',
								{
									attrs: { className: "popup-control" },
									children:
										[
											BX.create(
												'SPAN',
												{
													attrs: { className: "popup-current" },
													children: [ BX.create('SPAN', { attrs: { className: "icon-arrow" } }) ]
												}
											)
										]
								}
							)
						);
					}
					if (this.jsEventsMode)
					{
						crumb.appendChild(
							BX.create(
								'SPAN',
								{
									attrs: {
										className: "bx-crm-interface-product-section-crumbs-item-link",
										style: "cursor: pointer;"
									},
									events: {
										click: BX.proxy(this.onClickCrumbLink, this)
									},
									children:
										[
											BX.create(
												'SPAN',
												{
													attrs: {
														className: "bx-crm-interface-product-section-crumbs-item-current"
													},
													html: BX.util.htmlspecialchars(showedItems[i]["NAME"])
												}
											)
										]
								}
							)
						);
					}
					else
					{
						crumb.appendChild(
							BX.create(
								'A',
								{
									attrs: {
										className: "bx-crm-interface-product-section-crumbs-item-link",
										style: "cursor: pointer;",
										href: showedItems[i]["LINK"]
									},
									children:
										[
											BX.create(
												'SPAN',
												{
													attrs: {
														className: "bx-crm-interface-product-section-crumbs-item-current"
													},
													html: BX.util.htmlspecialchars(showedItems[i]["NAME"])
												}
											)
										]
								}
							)
						);
					}
					crumb.appendChild(BX.create('SPAN', { attrs: { className: "clb" } }));
					this.container.appendChild(crumb);
				}
			}

			this.sectionId = sectionId;
			this.collapsedCrumbs = collapsedCrumbs;
			this.childrenCrumbs = childrenCrumbs;

			this.setEvents();
		},
		cleanCrumbs: function ()
		{
			this.unsetEvents();

			if (BX.type.isDomNode(this.container))
				BX.cleanNode(this.container)
		},
		onClickCrumbLink: function(event, menuItem)
		{
			if (event && !menuItem)
			{
				var target = BX.getEventTarget(event);
				if (target)
				{
					var crumb = BX.findParent(target, {
						className: 'bx-crm-interface-product-section-crumbs-item-container'
					}, this.container);
					var sectionId = crumb.getAttribute('data-objectId');
					var sectionName = crumb.getAttribute('data-objectName');
					var isRoot = crumb.getAttribute('data-isRoot');
					if (sectionId && sectionName)
						this.onSectionSelect({"sectionId": sectionId, "sectionName": sectionName});
				}
			}
			else if (menuItem
				&& typeof(menuItem) === "object"
				&& menuItem["data"]
				&& menuItem["data"]["menuId"]
				&& menuItem["data"]["sectionId"]
				&& menuItem["text"])
			{
				var menu = BX.PopupMenu.getMenuById(menuItem["data"]["menuId"]);
				if (menu)
				{
					if(menu && menu.popupWindow)
						BX.PopupMenu.destroy(menuItem["data"]["menuId"]);
				}
				this.onSectionSelect({sectionId: menuItem["data"]["sectionId"], sectionName: menuItem["text"]});
			}
		}
	};

	return ProductSectionCrumbsClass;
})();
