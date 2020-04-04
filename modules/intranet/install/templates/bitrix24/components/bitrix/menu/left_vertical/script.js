BX.namespace("BX.Bitrix24.LeftMenuClass");

BX.Bitrix24.LeftMenuClass = {
	init: function (params)
	{
		params = typeof params === "object" ? params : {};

		this.ajaxPath = params.ajaxPath || null;
		this.isAdmin = params.isAdmin === "Y";
		this.hiddenCounters = params.hiddenCounters || {};
		this.allCounters = params.allCounters || {};
		this.isBitrix24 = params.isBitrix24 === "Y";
		this.siteId = params.siteId || null;
		this.siteDir = params.siteDir || null;
		this.isExtranet = params.isExtranet == "Y";
		this.isCompositeMode = params.isCompositeMode === true;
		this.isCollapsedMode = params.isCollapsedMode === true;
		this.activeItemsId = [];
		this.isCurrentPageInLeftMenu = false;
		this.currentPagePath = null;
		this.menuSelectedNode = null;
		this.showPresetPopup = params.showPresetPopup === "Y";
		this.isCustomPresetAvailable = params.isCustomPresetAvailable === "Y";
		this.customPresetExists = params.customPresetExists === "Y";
		this.isCurrentPageStandard = false;
		this.topMenuSelectedNode = null;
		this.topItemSelectedObj = null;
		this.isPublicConverted = params.isPublicConverted == "Y";

		this.menuContainer = BX("bx-left-menu");
		this.menuResizer = BX("left-menu-resizer");
		this.menuResizerButton = BX("left-menu-resizer-button");
		this.menuMoreButton = BX("left-menu-more-btn");
		this.menuItemsBlock = BX("left-menu-list");
		this.menuSettingsButton = BX("left-menu-settings");
		this.leftColumnBottom = null;
		this.timeout = {};

		if (!this.menuContainer)
		{
			return false;
		}

		this.highlight(document.location.pathname + document.location.search);

		//drag&drop
		jsDD.Enable();
		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			var items = this.menuItemsBlock.getElementsByClassName("menu-item-block");
			for (var i = 0; i < items.length; i++)
			{
				items[i].onbxdragstart = BX.proxy(this.menuItemDragStart, this);
				items[i].onbxdrag = BX.proxy(this.menuItemDragMove, this);
				items[i].onbxdragstop = BX.proxy(this.menuItemDragStop, this);
				items[i].onbxdraghover = BX.proxy(this.menuItemDragHover, this);
				jsDD.registerDest(items[i], 100);
				jsDD.registerObject(items[i]);

				BX.bind(items[i], "mouseenter", BX.proxy(function (){
					this.editByMouseEnter(BX.proxy_context)
				}, this));

				BX.bind(items[i], "mouseleave", BX.proxy(function (event){
					this.removeEditByMouseLeave(BX.proxy_context)
				}, this));
			}
		}

		BX.bind(this.menuContainer, "dblclick", BX.proxy(this.onMenuDoubleClick, this));
		BX.ready(function ()
		{
			this.leftColumnBottom = BX("layout-left-column-bottom");
			BX.bind(this.leftColumnBottom, "dblclick", BX.proxy(this.onMenuDoubleClick, this));
		}.bind(this));

		BX.bind(this.menuResizer, "mouseover", BX.proxy(this.onResizerMouseOver, this));
		BX.bind(this.menuResizer, "mouseout", BX.proxy(this.onResizerMouseOut, this));
		BX.bind(this.menuResizer, "click", BX.proxy(this.onResizerClick, this));

		BX.bind(BX("left-menu-hidden-separator"), "click", BX.proxy(this.showHideMoreItems, this));
		BX.bind(this.menuMoreButton, "click", BX.proxy(this.showHideMoreItems, this));
		BX.bind(this.menuSettingsButton, "click", BX.proxy(this.showSettingsPopup, this));

		BX.addCustomEvent("BX.Main.InterfaceButtons:onFirstItemChange", BX.proxy(function (firstPageLink, firstNode)
		{
			this.onTopMenuFirstItemChange(firstPageLink, firstNode);
		}, this));

		BX.addCustomEvent("BX.Main.InterfaceButtons:onHideLastVisibleItem", BX.proxy(function (bindElement)
		{
			this.showMessage(bindElement, BX.message("MENU_TOP_ITEM_LAST_HIDDEN"));
		}, this));

		BX.addCustomEvent("BX.Main.InterfaceButtons:onBeforeCreateEditMenu", function (contextMenu, dataItem, topMenu)
		{
			var isItemInLeftMenu = BX.type.isDomNode(BX("bx_left_menu_" + dataItem.DATA_ID));
			contextMenu.addMenuItem({
				text: BX.message(isItemInLeftMenu ? "MENU_DELETE_FROM_LEFT_MENU" : "MENU_ADD_TO_LEFT_MENU"),
				onclick: function (event, item)
				{
					var itemInfo = {
						id: dataItem.DATA_ID,
						text: BX.util.htmlspecialcharsback(dataItem.TEXT),
						subLink: dataItem.SUB_LINK,
						counterId: dataItem.COUNTER_ID,
						counterValue: dataItem.COUNTER
					};

					var link = document.createElement("a");
					link.href = dataItem.URL;
					itemInfo.link = BX.util.htmlspecialcharsback(link.pathname + link.search);

					if (isItemInLeftMenu)
					{
						this.deleteStandardItem(dataItem.DATA_ID);
					}
					else
					{
						var startX = "",
							startY = "";

						if (BX.type.isDomNode(dataItem.NODE))
						{
							var menuNodeCoord = dataItem.NODE.getBoundingClientRect();
							startX = menuNodeCoord.left;
							startY = menuNodeCoord.top;
						}
						this.addStandardItem(itemInfo, startX, startY);
					}

					BX.PopupMenu.destroy(contextMenu.id);

				}.bind(this)
			});
		}.bind(this));

		BX.addCustomEvent("BX.Main.InterfaceButtons:onBeforeResetMenu", function (promises)
		{
			promises.push(function ()
			{
				var p = new BX.Promise();

				BX.ajax({
					method: "POST",
					dataType: "json",
					url: this.ajaxPath,
					data: {
						sessid: BX.bitrix_sessid(),
						site_id: this.siteId,
						action: "clear_cache"
					},
					onsuccess: function ()
					{
						p.fulfill();
					},
					onfailure: function (error)
					{
						p.reject("Error: " + error);
					}
				});

				return p;
			}.bind(this));
		}.bind(this));

		BX.addCustomEvent("BX.Intranet.LeftMenu:onMenuPopupClose", BX.proxy(function (menuItemId)
		{
			this.hideEditButton(menuItemId);
		}, this));

		if (this.showPresetPopup)
		{
			this.showPresetPopupFunction("global");
		}

		this.menuSelectedNode = BX.findChild(this.menuContainer, {className: "menu-item-active"}, true, false);
		if (BX.type.isDomNode(this.menuSelectedNode))
		{
			var leftMenuSelectedUrl = this.menuSelectedNode.getAttribute("data-link");
		}

		var currentPath = document.location.pathname;
		var currentFullPath = document.location.pathname + document.location.search;

		if (leftMenuSelectedUrl == currentPath || leftMenuSelectedUrl == currentFullPath)
		{
			this.isCurrentPageInLeftMenu = true;
		}

		return true;
	},

	editByMouseEnter: function(item)
	{
		if (this.isEditMode())
			return;

		if (!BX.type.isDomNode(item))
			return;

		if (item.getAttribute("data-id") == "menu_all_groups")
			return;

		this.timeout[item.id] = setTimeout(function () {
			var editButton = BX.findChild(item, {className: "menu-fav-editable-btn-js"}, true, false);
			if (BX.type.isDomNode(editButton))
			{
				BX.addClass(editButton, "menu-fav-editable-btn-showed");
			}
		}, 600);
	},

	removeEditByMouseLeave: function(item)
	{
		if (this.isEditMode())
			return;

		var currentContextPopup = BX.PopupMenu.getCurrentMenu();
		if (currentContextPopup)
		{
			for (var i in BX.PopupWindowManager._popups)
			{
				if (BX.PopupWindowManager._popups[i].uniquePopupId.indexOf("menu-popup-popup_") !== -1)
				{
					var popupItemId = BX.PopupWindowManager._popups[i].uniquePopupId;
					popupItemId = popupItemId.replace("menu-popup-popup_", "");

					if (item.getAttribute("data-id") == popupItemId && BX.PopupWindowManager._popups[i].isShown())
						return;
				}
			}
		}

		clearTimeout(this.timeout[item.id]);
		var editButton = BX.findChild(item, {className: "menu-fav-editable-btn-js"}, true, false);
		if (BX.type.isDomNode(editButton))
		{
			BX.removeClass(editButton, "menu-fav-editable-btn-showed");
		}
	},

	hideEditButton: function(menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);
		var editButton = BX.findChild(itemNode, {className: "menu-fav-editable-btn-js"}, true, false);
		if (BX.type.isDomNode(editButton))
		{
			BX.removeClass(editButton, "menu-fav-editable-btn-showed");
		}
	},

	isEditMode: function ()
	{
		return BX.hasClass(this.menuContainer, 'menu-favorites-editable');
	},

	applyEditMode: function ()
	{
		var isEditMode = this.isEditMode();

		if (BX.type.isDomNode(this.menuContainer))
		{
			if (isEditMode)
				BX.removeClass(this.menuContainer, "menu-favorites-editable");
			else
				BX.addClass(this.menuContainer, "menu-favorites-editable");
		}

		if (!isEditMode)
		{
			BX.addClass(this.menuSettingsButton, 'menu-favorites-btn-active');

			var allActiveItems = BX.findChildren(this.menuContainer, {className: "menu-item-active"}, true);
			for (var obj in allActiveItems)
			{
				if (allActiveItems.hasOwnProperty(obj))
				{
					BX.removeClass(allActiveItems[obj], 'menu-item-active');
					this.activeItemsId.push(allActiveItems[obj].id);
				}
			}
		}
		else
		{
			BX.removeClass(this.menuSettingsButton, 'menu-favorites-btn-active');
			for (var key in this.activeItemsId)
			{
				BX.addClass(BX(this.activeItemsId[key]), 'menu-item-active');
			}
			this.activeItemsId = [];

			var showedEditBtns = BX.findChildren(this.menuItemsBlock, {className: "menu-fav-editable-btn-showed"}, true);
			if (showedEditBtns)
			{
				for (var i in showedEditBtns)
				{
					BX.removeClass(showedEditBtns[i], "menu-fav-editable-btn-showed");
				}
			}
		}
	},

	areMoreItemsShowed: function ()
	{
		return BX.hasClass(BX('left-menu-hidden-items-block'), 'menu-item-favorites-more-open') ? true : false;
	},

	animateShowingHiddenItems: function ()
	{
		var hiddenBlock = BX("left-menu-hidden-items-block");

		if (!BX.hasClass(hiddenBlock, "menu-item-favorites-more-open"))
		{
			hiddenBlock.style.height = "0px";
			hiddenBlock.style.opacity = 0;
			animation(true, hiddenBlock, hiddenBlock.scrollHeight);
		}
		else
		{
			animation(false, hiddenBlock, hiddenBlock.offsetHeight);
		}

		function animation(opening, hiddenBlock, maxHeight)
		{
			hiddenBlock.style.overflow = "hidden";
			(new BX.easing({
				duration: 200,
				start: {opacity: opening ? 0 : 100, height: opening ? 0 : maxHeight},
				finish: {opacity: opening ? 100 : 0, height: opening ? maxHeight : 0},
				transition: BX.easing.transitions.linear,
				step: function (state)
				{
					hiddenBlock.style.opacity = state.opacity / 100;
					hiddenBlock.style.height = state.height + "px";

				},
				complete: function ()
				{
					BX.toggleClass(BX('left-menu-hidden-items-block'), 'menu-item-favorites-more-open');
					hiddenBlock.style.overflow = "";
					hiddenBlock.style.height = "";
				}

			})).animate();
		}
	},

	showHideMoreItems: function (animate)
	{
		if (this.isEditMode())
			return;

		if (animate !== false)
		{
			this.animateShowingHiddenItems();
		}
		else
		{
			BX.toggleClass(BX('left-menu-hidden-items-block'), 'menu-item-favorites-more-open');
		}

		BX.toggleClass(this.menuMoreButton, 'menu-favorites-more-btn-open');
		BX.toggleClass(BX('menu-hidden-counter'), 'menu-hidden-counter');
		BX.firstChild(this.menuMoreButton).innerHTML =
			(BX.firstChild(this.menuMoreButton).innerHTML == BX.message('more_items_hide'))
				? BX.message('more_items_show')
				: BX.message('more_items_hide')
		;
	},

	openMenuPopup: function (bindElement, menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		var contextMenuItems = [];
		var itemDeletePerm = itemNode.getAttribute("data-delete-perm");
		var itemType = itemNode.getAttribute("data-type");

		//hide item
		if (itemNode.getAttribute("data-status") == "show")
		{
			contextMenuItems.push({
				text: BX.message("hide_item"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();
					this.hideItem(menuItemId);
					BX.PopupMenu.destroy("popup_" + menuItemId);
				}, this)
			});
		}

		//show item
		if (itemNode.getAttribute("data-status") == "hide")
		{
			contextMenuItems.push({
				text: BX.message("show_item"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();
					this.showItem(menuItemId);
					BX.PopupMenu.destroy("popup_" + menuItemId);
				}, this)
			});
		}

		//set main page
		if (!this.isExtranet && itemType !== "self" && BX.previousSibling(itemNode).id != "left-menu-empty-item" && this.isPublicConverted)
		{
			contextMenuItems.push({
				text: BX.message("MENU_SET_MAIN_PAGE"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();
					this.setMainPage(menuItemId);
					BX.PopupMenu.destroy("popup_" + menuItemId);
				}, this)
			});
		}

		if (itemType == "self")
		{
			contextMenuItems.push({
				text: BX.message("MENU_DELETE_SELF_ITEM"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();

					this.showConfirmWindow({
						id: "left-menu-delete-self-item",
						titleBar: BX.message("MENU_DELETE_SELF_ITEM"),
						okButtonText: BX.message("MENU_DELETE"),
						content: BX.message("MENU_DELETE_SELF_ITEM_CONFIRM"),
						onsuccess: BX.proxy(function ()
						{
							BX.proxy_context.popupWindow.close();
							this.deleteSelfItem(menuItemId);
							BX.PopupMenu.destroy("popup_" + menuItemId);
						}, this),
						onfailure: BX.proxy(function ()
						{
							BX.PopupMenu.destroy("popup_" + menuItemId);
						}, this)
					});

				}, this)
			});

			contextMenuItems.push({
				text: BX.message("MENU_EDIT_ITEM"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();
					var linkNode = BX.findChild(itemNode, {tagName: "a"}, true, false);

					var itemInfo = {
						id: menuItemId,
						text: itemNode.querySelector("[data-role='item-text']").innerText,
						link: itemNode.getAttribute("data-link"),
						openInNewPage: linkNode.getAttribute("target") == "_blank"
					};

					this.showSelfItemPopup(bindElement, itemInfo);
					BX.PopupMenu.destroy("popup_" + menuItemId);
				}, this)
			});
		}

		if (itemType == "standard")
		{
			contextMenuItems.push({
				text: BX.message("MENU_RENAME_ITEM"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					var itemInfo = {
						id: menuItemId,
						text: itemNode.querySelector("[data-role='item-text']").innerText
					};
					this.showStandardEditItemPopup(bindElement, itemInfo);
					BX.PopupMenu.destroy("popup_" + menuItemId);
				}, this)
			});

			contextMenuItems.push({
				text: BX.message("MENU_REMOVE_STANDARD_ITEM"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();
					this.deleteStandardItem(menuItemId);
					BX.PopupMenu.destroy("popup_" + menuItemId);
				}, this)
			});
		}

		if (this.isAdmin)
		{
			//add to favorite all
			if (itemDeletePerm == "Y")
			{
				contextMenuItems.push({
					text: BX.message("MENU_ADD_ITEM_TO_ALL"),
					className: "menu-popup-no-icon",
					onclick: BX.proxy(function ()
					{
						this.addItemToAll(menuItemId);
						BX.PopupMenu.destroy("popup_" + menuItemId);
					}, this)
				});
			}

			//delete from favorite all
			if (itemDeletePerm == "A")
			{
				if (itemType == "custom")
				{
					contextMenuItems.push({
						text: BX.message("MENU_DELETE_CUSTOM_ITEM_FROM_ALL"),
						className: "menu-popup-no-icon",
						onclick: BX.proxy(function ()
						{
							this.deleteCustomItemFromAll(menuItemId);
							BX.PopupMenu.destroy("popup_" + menuItemId);
						}, this)
					});
				}
				else
				{
					contextMenuItems.push({
						text: BX.message("MENU_DELETE_ITEM_FROM_ALL"),
						className: "menu-popup-no-icon",
						onclick: BX.proxy(function ()
						{
							this.deleteItemFromAll(menuItemId);
							BX.PopupMenu.destroy("popup_" + menuItemId);
						}, this)
					});
				}
			}

			//set rights for apps
			//if (itemNode.getAttribute("data-app-id"))
			//	contextMenuItems.push({text : BX.message("set_rights"), className : "menu-popup-no-icon", onclick : function() {this.popupWindow.close(); self.setRights(menuItemId); BX.PopupMenu.destroy("popup_"+menuItemId);}});
		}

		contextMenuItems.push({
			text: this.isEditMode() ? BX.message("MENU_EDIT_READY_FULL") : BX.message("MENU_SETTINGS_MODE"),
			className: "menu-popup-no-icon",
			onclick: BX.proxy(function ()
			{
				BX.PopupMenu.destroy("popup_" + menuItemId);
				this.applyEditMode();
			}, this)
		});

		BX.PopupMenu.show("popup_" + menuItemId, bindElement, contextMenuItems,
			{
				offsetTop: 0,
				offsetLeft: 12,
				angle: true,
				events: {
					onPopupClose: function ()
					{
						BX.removeClass(bindElement, 'menu-favorites-btn-active');
						BX.onCustomEvent(window, 'BX.Intranet.LeftMenu:onMenuPopupClose', [menuItemId]);
						//BX.PopupMenu.destroy("popup_" + menuItemId);
					}
				}
			});
		BX.addClass(bindElement, 'menu-favorites-btn-active');
	},

	showSettingsPopup: function (event)
	{
		var menuId = "leftMenuSettingsPopup";
		if (BX.PopupMenu.getMenuById(menuId))
		{
			BX.PopupMenu.destroy(menuId);
			return;
		}

		var self = this;

		var itemType = "default";
		if (BX.type.isDomNode(this.menuSelectedNode))
		{
			itemType = this.menuSelectedNode.getAttribute("data-type");
		}

		if (this.isCurrentPageInLeftMenu && itemType == "default")
		{
			itemPageToLeftMenu = {
				text: BX.message(this.isCurrentPageInLeftMenu ? "MENU_DELETE_PAGE_FROM_LEFT_MENU" : "MENU_ADD_PAGE_TO_LEFT_MENU"),
				className: "menu-popup-no-icon menu-popup-disable-text"
			};
		}
		else
		{
			var itemPageToLeftMenu = {
				text: BX.message(this.isCurrentPageInLeftMenu ? "MENU_DELETE_PAGE_FROM_LEFT_MENU" : "MENU_ADD_PAGE_TO_LEFT_MENU"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					BX.proxy_context.popupWindow.close();
					if (this.isCurrentPageInLeftMenu)
					{
						this.deleteStandardItem();
					}
					else
					{
						this.addStandardItem();
					}
				}, this)
			};
		}

		var menuItems = [
			{
				text: BX.message("SORT_ITEMS"),
				className: "menu-popup-no-icon",
				onclick: function ()
				{
					this.popupWindow.close();
					self.applyEditMode();
				}
			},
			{
				text: BX.message("MENU_COLLAPSE"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					BX.proxy_context.popupWindow.close();
					this.toggle(false);
				}, this)
			},
			itemPageToLeftMenu,
			{
				text: BX.message("MENU_ADD_SELF_PAGE"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					BX.proxy_context.popupWindow.close();
					this.showSelfItemPopup(this.menuSettingsButton);
				}, this)
			},
			{
				text: BX.message("MENU_SET_DEFAULT2"),
				className: "menu-popup-no-icon",
				onclick: function ()
				{
					this.popupWindow.close();
					self.setDefaultMenu(true);
				}
			},
			{
				text: BX.message("MENU_SET_DEFAULT"),
				className: "menu-popup-no-icon",
				onclick: function ()
				{
					this.popupWindow.close();
					self.setDefaultMenu();
				}
			}
		];

		//custom preset
		if (this.isCustomPresetAvailable && this.isAdmin)
		{
			menuItems.push({
				text: BX.message("MENU_SAVE_CUSTOM_PRESET"),
				className: "menu-popup-no-icon",
				onclick: function ()
				{
					this.popupWindow.close();
					self.showCustomPresetPopup();
				}
			});
		}

		BX.PopupMenu.show(menuId, this.menuSettingsButton, menuItems,
			{
				offsetTop: 0,
				offsetLeft: 12,
				angle: true,
				events: {
					onPopupClose: function ()
					{
						BX.PopupMenu.destroy(menuId);
					}
				}
			});
	},

	showMessage: function (bindElement, message, position)
	{
		var popup = BX.PopupWindowManager.create("left-menu-message", bindElement, {
			content: '<div class="left-menu-message-popup">' + message + '</div>',
			darkMode: true,
			offsetTop: position == "right" ? -45 : 2,
			offsetLeft: position == "right" ? 215 : 0,
			angle: position === "right" ? {position: "left"} : true,
			events: {
				onPopupClose: function ()
				{
					if (popup)
					{
						popup.destroy();
						popup = null;
					}
				}
			},
			autoHide: true
		});

		popup.show();

		setTimeout(function ()
		{
			if (popup)
			{
				popup.destroy();
				popup = null;
			}
		}, 3000);
	},

	showError: function (bindElement)
	{
		this.showMessage(bindElement, BX.message('edit_error'));
	},

	/*setRights =  function(menuItemId)
	 {
	 BX.rest.Marketplace.setRights(BX(menuItemId).getAttribute("data-app-id"), this.siteId);
	 };*/

	onTopMenuFirstItemChange: function (firstPageLink, firstNode)
	{
		if (!firstPageLink)
			return;

		var topMenuId = firstNode.getAttribute("data-top-menu-id");
		var leftMenuNode = this.menuItemsBlock.querySelector("[data-top-menu-id='" + topMenuId + "']");
		if (BX.type.isDomNode(leftMenuNode))
		{
			leftMenuNode.setAttribute("data-link", firstPageLink);

			var leftMenuLink = BX.findChild(leftMenuNode, {tagName: "a", className: "menu-item-link"}, true, false);
			if (leftMenuLink)
			{
				leftMenuLink.setAttribute("href", firstPageLink);
			}
		}

		if (BX.type.isDomNode(firstNode))
		{
			this.showMessage(firstNode, BX.message("MENU_ITEM_MAIN_SECTION_PAGE"));
		}

		if (BX.type.isDomNode(leftMenuNode) && BX.previousSibling(leftMenuNode) == BX("left-menu-empty-item"))
		{
			var ajaxData = {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				action: "set_first_page",
				firstPageUrl: firstPageLink
			};
		}
		else
		{
			ajaxData = {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				action: "clear_cache"
			};
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: ajaxData,
			onsuccess: BX.proxy(function ()
			{
			}, this),
			onfailure: function ()
			{
			}
		});
	},

	showSelfItemPopup: function (bindElement, itemInfo)
	{
		var isEditMode = false;
		if (typeof itemInfo === "object" && itemInfo)
		{
			isEditMode = true;
		}

		var popupContent = BX.create("form", {
			attrs: {
				name: "menuAddToFavoriteForm"
			},
			children: [
				BX.create("label", {
					attrs: {
						for: "menuPageToFavoriteName",
						className: "menu-form-label"
					},
					html: BX.message("MENU_ITEM_NAME")
				}),
				BX.create("input", {
					attrs: {
						value: isEditMode ? itemInfo.text : "",//document.title,
						name: "menuPageToFavoriteName",
						type: "text",
						className: "menu-form-input"
					}
				}),
				BX.create("br"), BX.create("br"),
				BX.create("label", {
					attrs: {
						for: "menuPageToFavoriteLink",
						className: "menu-form-label"
					},
					html: BX.message("MENU_ITEM_LINK")
				}),
				BX.create("input", {
					attrs: {
						value: isEditMode ? itemInfo.link : "",//document.location.pathname,
						name: "menuPageToFavoriteLink",
						type: "text",
						className: "menu-form-input"
					}
				}),
				BX.create("br"), BX.create("br"),
				BX.create("input", {
					attrs: {
						value: "",
						name: "menuOpenInNewPage",
						type: "checkbox",
						checked: !isEditMode || itemInfo.openInNewPage ? "checked" : "",
						id: "menuOpenInNewPage"
					}
				}),
				BX.create("label", {
					attrs: {
						for: "menuOpenInNewPage",
						className: "menu-form-label"
					},
					html: BX.message("MENU_OPEN_IN_NEW_PAGE")
				})
			]
		});

		if (isEditMode)
		{
			popupContent.appendChild(BX.create("input", {
				attrs: {
					name: "menuItemId",
					type: "hidden",
					value: itemInfo.id
				}
			}));
		}

		BX.PopupWindowManager.create("menu-self-item-popup", bindElement, {
			closeIcon: true,
			offsetTop: 1,
			//overlay : { opacity : 20 },
			lightShadow: true,
			draggable: {restrict: true},
			closeByEsc: true,
			titleBar: isEditMode ? BX.message("MENU_EDIT_SELF_PAGE") : BX.message("MENU_ADD_SELF_PAGE"),
			content: popupContent,
			buttons: [
				(button = new BX.PopupWindowButton({
					text: isEditMode ? BX.message("MENU_SAVE_BUTTON") : BX.message("MENU_ADD_BUTTON"),
					className: 'popup-window-button-create',
					events: {
						click: BX.proxy(function ()
						{
							BX.addClass(button.buttonNode, "popup-window-button-wait");

							var form = document.forms["menuAddToFavoriteForm"];
							var textField = form.elements["menuPageToFavoriteName"];
							var linkField = form.elements["menuPageToFavoriteLink"];
							var openNewTab = form.elements["menuOpenInNewPage"].checked;

							var text = BX.util.trim(textField.value);
							var link = this.refineUrl(linkField.value);

							if (!text || !link)
							{
								if (!link)
								{
									BX.addClass(linkField, "menu-form-input-error");
									linkField.focus();
								}

								if (!text)
								{
									BX.addClass(textField, "menu-form-input-error");
									textField.focus();
								}
							}
							else
							{
								BX.removeClass(textField, "menu-form-input-error");
								BX.removeClass(linkField, "menu-form-input-error");

								var itemNewInfo = {
									text: text,
									link: link,
									openInNewPage: openNewTab ? "Y" : "N"
								};

								if (isEditMode)
								{
									itemNewInfo.id = itemInfo.id;
								}

								this.saveSelfItem(
									isEditMode ? "edit" : "add",
									itemNewInfo,
									this.onSelfItemSave.bind(this)
								);
							}
						}, this)
					}
				})),
				new BX.PopupWindowButtonLink({
					text: BX.message('MENU_CANCEL'),
					className: "popup-window-button-link-cancel",
					events: {
						click: function ()
						{
							this.popupWindow.close();
						}
					}
				})
			],
			events: {
				onPopupClose: function ()
				{
					BX.PopupWindowManager.getCurrentPopup().destroy();
				},

				onPopupShow: function ()
				{
					var form = document.forms["menuAddToFavoriteForm"];
					var text = form.elements["menuPageToFavoriteName"];
					text && setTimeout(function ()
					{
						text.focus();
					}, 100);
				}
			}
		}).show();
	},

	onSelfItemSave: function (error)
	{
		if (error)
		{
			this.showConfirmWindow({
				alertMode: true,
				titleBar: BX.message("MENU_ERROR_OCCURRED"),
				content: error
			});
		}
		else
		{
			BX.PopupWindowManager.getCurrentPopup().destroy();
		}
	},

	saveSelfItem: function (mode, itemData, callback)
	{
		BX.ajax({
			method: "POST",
			dataType: "json",
			url: this.ajaxPath + (mode == "edit" ? "" : "?analyticsLabel=selfItem"),
			data: {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				itemData: itemData,
				action: mode == "edit" ? "update_self_item" : "add_self_item"
			},
			onsuccess: BX.proxy(function (json)
			{
				if (json.hasOwnProperty("error"))
				{
					callback(json.error);
				}
				else
				{
					var itemParams = {
						text: itemData.text,
						link: itemData.link,
						type: "self",
						openInNewPage: itemData.openInNewPage == "Y" ? "Y" : "N"
					};

					if (mode == "add" && json.hasOwnProperty("itemId"))
					{
						itemParams.id = json.itemId;
						this.generateItemHtml(itemParams);
					}
					else if (mode == "edit")
					{
						itemParams.id = itemData.id;
						this.updateItemHtml(itemParams);
					}

					callback("");
				}

			}, this),
			onfailure: function ()
			{
				callback();
			}
		});
	},

	deleteSelfItem: function (itemId)
	{
		var itemNode = BX("bx_left_menu_" + itemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		if (itemNode.getAttribute("data-delete-perm") == "A") //delete from all
		{
			this.deleteItemFromAll(itemId);
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				action: "delete_self_item",
				menu_item_id: itemId
			},
			onsuccess: BX.proxy(function (json)
			{
				if (json.error)
				{
					this.showError(itemNode);
				}
				else
				{
					BX.remove(itemNode);
				}
			}, this),
			onfailure: function ()
			{
			}
		});
	},

	refineUrl: function (url)
	{
		url = BX.util.trim(url);
		if (!BX.type.isNotEmptyString(url))
		{
			return "";
		}

		if (!url.match(/^https?:\/\//i) && !url.match(/^\//i))
		{
			//for external links like "google.com" (without a protocol)
			url = "http://" + url;
		}
		else
		{
			var link = document.createElement("a");
			link.href = url;

			if (document.location.host === link.host)
			{
				// http://portal.com/path/ => /path/
				url = link.pathname + link.search + link.hash;
			}
		}

		return url;
	},

	addItemToAll: function (menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		var itemLink = itemNode.getAttribute("data-link"),
			itemTextNode = itemNode.querySelector("[data-role='item-text']"),
			itemText = itemTextNode.innerText,
			itemCounterId = itemNode.getAttribute("data-counter-id"),
			itemLinkNode = BX.findChild(itemNode, {tagName: "a"}, true, false),
			openInNewPage = BX.type.isDomNode(itemLinkNode) && itemLinkNode.hasAttribute("target") && itemLinkNode.getAttribute("target") == "_blank";

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				action: "add_item_to_all",
				itemInfo: {
					id: menuItemId,
					link: itemLink,
					text: itemText,
					counterId: itemCounterId,
					openInNewPage: openInNewPage ? "Y" : "N"
				}
			},
			onsuccess: BX.proxy(function (json)
			{
				if (json.error)
				{
					this.showError(itemNode);
				}
				else
				{
					itemNode.setAttribute("data-delete-perm", "A");
					this.showMessage(itemNode, BX.message("MENU_ITEM_WAS_ADDED_TO_ALL"));
				}
			}, this)
		});
	},

	deleteItemFromAll: function (menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				action: "delete_item_from_all",
				menu_item_id: menuItemId
			},
			onsuccess: BX.proxy(function (json)
			{
				if (json.error)
				{
					this.showError(itemNode);
				}
				else
				{
					itemNode.setAttribute("data-delete-perm", "Y");
					this.showMessage(itemNode, BX.message("MENU_ITEM_WAS_DELETED_FROM_ALL"));
				}
			}, this)
		});
	},

	deleteCustomItemFromAll: function (menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		var itemType = itemNode.getAttribute("data-type");

		if (itemType != "custom")
			return;

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				action: "delete_custom_item_from_all",
				menu_item_id: menuItemId
			},
			onsuccess: BX.proxy(function (json)
			{
				if (json.error)
				{
					this.showError(itemNode);
				}
				else
				{
					BX.remove(itemNode);
				}
			}, this)
		});
	},

	updateCounters : function(counters, send)
	{
		send = send == false ? false : true;

		for (var id in counters)
		{
			this.allCounters[id] = counters[id];

			if (id == "**")
			{
				oCounter = {
					iCommentsMenuRead: 0
				};

				BX.onCustomEvent(window, 'onMenuUpdateCounter', [oCounter]);
				counters[id] -= oCounter.iCommentsMenuRead;
			}

			var counter = BX(id == "**" ? "menu-counter-live-feed" : "menu-counter-" + id.toLowerCase(), true);
			if (counter)
			{
				if (counters[id] > 0)
				{
					counter.innerHTML = id == "mail_unseen"
						? (counters[id] > 99 ? "99+" : counters[id])
						: (counters[id] > 50 ? "50+" : counters[id]);
					BX.addClass(counter.parentNode.parentNode.parentNode, "menu-item-with-index");
				}
				else
				{
					BX.removeClass(counter.parentNode.parentNode.parentNode, "menu-item-with-index");

					if (counters[id] < 0)
					{
						var warning = BX('menu-counter-warning-'+id.toLowerCase());
						if (warning)
						{
							warning.style.display = 'inline-block';
						}
					}
				}

				if (send)
				{
					BX.localStorage.set('lmc-'+id, counters[id], 5);
				}
			}
		}

		var sumHiddenCounters = 0;
		for(var i = 0, l = this.hiddenCounters.length; i < l; i++)
		{
			if (this.allCounters[this.hiddenCounters[i]])
			{
				sumHiddenCounters+= (+this.allCounters[this.hiddenCounters[i]]);
			}
		}

		if (BX.type.isDomNode(BX("menu-hidden-counter")))
		{
			BX("menu-hidden-counter").style.display = (sumHiddenCounters > 0) ? "inline-block" : "none";
			BX("menu-hidden-counter").innerHTML = sumHiddenCounters > 50 ? "50+" : sumHiddenCounters;
		}
	},

	recountHiddenCounters: function ()
	{
		var curSumCounters = 0;
		var hiddenItems = BX.findChildren(BX("left-menu-hidden-items-list"), {className: "menu-item-block"}, true);

		if (hiddenItems)
		{
			for (var i = 0, l = hiddenItems.length; i < l; i++)
			{
				var curCounter = hiddenItems[i].getAttribute("data-counter-id");
				if (this.allCounters[curCounter])
				{
					curSumCounters += Number(this.allCounters[curCounter]);
				}
			}
		}

		BX("menu-hidden-counter").innerHTML = curSumCounters > 50 ? "50+" : curSumCounters;
		BX("menu-hidden-counter").style.display = curSumCounters > 0 ? "inline-block" : "none";
	},

	checkMoreButton: function (status)
	{
		var btn = BX("left-menu-more-btn");
		if (status === true || status === false)
		{
			if (status)
			{
				BX.removeClass(btn, "menu-favorites-more-btn-hidden");
			}
			else
			{
				BX.addClass(btn, "menu-favorites-more-btn-hidden");
			}

			return status;
		}

		var hiddenItems = BX("left-menu-hidden-items-list").getElementsByClassName("menu-item-block");
		if (hiddenItems.length > 0)
		{
			BX.removeClass(btn, "menu-favorites-more-btn-hidden");
			return true;
		}
		else
		{
			BX.addClass(btn, "menu-favorites-more-btn-hidden");
			return false;
		}
	},

	hideItem: function (menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		itemNode.setAttribute("data-status", "hide");
		BX("left-menu-hidden-items-list").appendChild(itemNode);

		this.checkMoreButton(true);

		if (itemNode.getAttribute("data-counter-id"))
		{
			this.recountHiddenCounters();
		}

		this.saveItemsSort("?analyticsLabel=hideItem&itemId="+menuItemId);
	},

	showItem: function (menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			itemNode.setAttribute("data-status", "show");
			this.menuItemsBlock.insertBefore(itemNode, BX("left-menu-hidden-items-block"));
		}

		this.checkMoreButton();

		if (itemNode.getAttribute("data-counter-id"))
		{
			this.recountHiddenCounters();
		}

		this.saveItemsSort("?analyticsLabel=showItem&itemId="+menuItemId);
	},

	saveItemsSort: function (analytics)
	{
		var showMenuItems = [],
			hideMenuItems = [],
			firstItemLink = "";

		var items = BX.findChildren(this.menuContainer, {className: "menu-item-block"}, true);

		for (var i = 0; i < items.length; i++)
		{
			if (i == 0)
			{
				firstItemLink = items[i].getAttribute("data-link");
			}

			if (items[i].getAttribute("data-status") == "show")
			{
				showMenuItems.push(items[i].getAttribute("data-id"));
			}
			else if (items[i].getAttribute("data-status") == "hide")
			{
				hideMenuItems.push(items[i].getAttribute("data-id"));
			}
		}

		var menuItems = {"show": showMenuItems, "hide": hideMenuItems};

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath + (analytics ? analytics : ""),
			data: {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				action: "save_items_sort",
				items: menuItems,
				firstItemLink: firstItemLink
			},
			onsuccess: function (json)
			{
			},
			onfailure: function ()
			{
			}
		});
	},

	animateTopItemToLeft: function (itemInfo, startX, startY)
	{
		if (typeof itemInfo !== "object")
			return;

		var topMenuNode = BX.create("div", {
			text: itemInfo.text,
			attrs: {
				style: "position: absolute; z-index: 1000;"
			}
		});
		topMenuNode.style.top = startY + 25 + "px";

		document.body.appendChild(topMenuNode);

		var finishY = BX("left-menu-list").getBoundingClientRect().bottom;
		if (this.areMoreItemsShowed())
		{
			finishY -= BX("left-menu-hidden-items-list").offsetHeight;
		}

		/*(new BX.easing({
		 duration : 10000,
		 start : {  left: startX, top : startY + 25 },
		 finish : { left: 30, top: finishY},
		 transition : function(progress) {
		 return Math.pow(progress, 2) * ((-1.5 + 1) * progress + 1.5);
		 },
		 step : function(state){
		 topMenuNode.style.top = state.top + "px";
		 topMenuNode.style.left = state.left + "px";
		 },
		 complete : BX.proxy(function()
		 {


		 }, this)
		 })).animate();*/

		(new BX.easing({
			duration: 500,
			start: {left: startX},
			finish: {left: 30},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: function (state)
			{
				topMenuNode.style.left = state.left + "px";
			},
			complete: BX.proxy(function ()
			{
				(new BX.easing({
					duration: 500,
					start: {top: startY + 25},
					finish: {top: finishY},
					transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
					step: function (state)
					{
						topMenuNode.style.top = state.top + "px";
					},
					complete: BX.proxy(function ()
					{
						BX.remove(topMenuNode);
						itemInfo.type = "standard";
						this.isCurrentPageInLeftMenu = true;
						this.generateItemHtml(itemInfo);
						this.saveItemsSort();

					}, this)
				})).animate();
			}, this)
		})).animate();
	},

	addStandardItem: function (itemInfo, startX, startY)
	{
		if (typeof itemInfo !== "object")
		{
			if (this.isCurrentPageStandard && BX.type.isDomNode(this.topMenuSelectedNode))
			{
				var menuNodeCoord = this.topMenuSelectedNode.getBoundingClientRect();
				startX = menuNodeCoord.left;
				startY = menuNodeCoord.top;

				itemInfo = {
					id: this.topItemSelectedObj.DATA_ID,
					text: this.topItemSelectedObj.TEXT,
					link: BX.type.isNotEmptyString(this.currentPagePath) ? this.currentPagePath : this.topItemSelectedObj.URL,
					counterId: this.topItemSelectedObj.COUNTER_ID,
					counterValue: this.topItemSelectedObj.COUNTER,
					isStandardItem: true,
					subLink: this.topItemSelectedObj.SUB_LINK
				};
			}
			else
			{
				itemInfo = {
					text: BX("pagetitle").innerText,
					link: BX.type.isNotEmptyString(this.currentPagePath) ? this.currentPagePath : document.location.pathname + document.location.search,
					isStandardItem: false
				};
			}
		}

		if (!startX || !startY)
		{
			var titleCoord = BX("pagetitle").getBoundingClientRect();
			startX = titleCoord.left;
			startY = titleCoord.top;
		}

		var actionUrl = this.ajaxPath;
		actionUrl = BX.util.add_url_param(actionUrl, {
			b24statAction: 'addFavMenuItem'
		});

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: actionUrl,
			data: {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				itemData: itemInfo,
				action: "add_standard_item"
			},
			onsuccess: BX.proxy(function (json)
			{
				if (json.hasOwnProperty("error"))
				{
					this.showConfirmWindow({
						alertMode: true,
						titleBar: BX.message("MENU_ERROR_OCCURRED"),
						content: json.error
					});
				}
				else
				{
					if (json.hasOwnProperty("itemId"))
					{
						itemInfo.id = json.itemId;

						BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemAdded", [itemInfo, this]);

						this.animateTopItemToLeft(itemInfo, startX, startY);

						this.showMessage(BX("pagetitle-star"), BX.message("MENU_ITEM_WAS_ADDED_TO_LEFT"));
						BX("pagetitle-star").title = BX.message("MENU_DELETE_PAGE_FROM_LEFT_MENU");
						BX.addClass(BX("pagetitle-star"), "pagetitle-star-active");
						this.isCurrentPageInLeftMenu = true;
					}
				}
			}, this),
			onfailure: function ()
			{
			}
		});
	},

	deleteStandardItem: function (itemId)
	{
		if (itemId && BX.type.isDomNode(BX("bx_left_menu_" + itemId)))
		{
			var itemData = {
				id: itemId
			};
		}
		else if (this.isCurrentPageStandard && this.topItemSelectedObj.DATA_ID)
		{
			var itemData = {
				id: this.topItemSelectedObj.DATA_ID
			};
		}
		else
		{
			itemData = {
				link: document.location.pathname + document.location.search
			};
		}

		var actionUrl = this.ajaxPath;
		actionUrl = BX.util.add_url_param(actionUrl, {
			b24statAction: 'removeFavMenuItem'
		});

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: actionUrl,
			data: {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				itemData: itemData,
				action: "delete_standard_item"
			},
			onsuccess: BX.proxy(function (json)
			{
				if (json.hasOwnProperty("error"))
				{
					this.showConfirmWindow({
						alertMode: true,
						titleBar: BX.message("MENU_ERROR_OCCURRED"),
						content: json.error
					});
				}
				else
				{
					if (json.hasOwnProperty("itemId"))
					{
						BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [json, this]);

						var itemNode = BX("bx_left_menu_" + json.itemId);
						if (!BX.type.isDomNode(itemNode))
							return;

						if (itemNode.getAttribute("data-delete-perm") == "A") //delete from all
						{
							this.deleteItemFromAll(json.itemId);
						}

						this.showMessage(BX("pagetitle-star"), BX.message("MENU_ITEM_WAS_DELETED_FROM_LEFT"));
						BX("pagetitle-star").title = BX.message("MENU_ADD_PAGE_TO_LEFT_MENU");
						this.animateTopItemFromLeft("bx_left_menu_" + json.itemId);
						BX.removeClass(BX("pagetitle-star"), "pagetitle-star-active");
						this.isCurrentPageInLeftMenu = false;
					}
				}
			}, this),
			onfailure: function ()
			{
			}
		});
	},

	updateStandardItem: function (itemInfo)
	{
		BX.ajax({
			method: "POST",
			dataType: "json",
			url: this.ajaxPath,
			data: {
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId,
				itemText: itemInfo.text,
				itemId: itemInfo.id,
				action: "update_standard_item"
			},
			onsuccess: BX.proxy(function (json)
			{
				if (json.hasOwnProperty("error"))
				{
					this.showConfirmWindow({
						alertMode: true,
						titleBar: BX.message("MENU_ERROR_OCCURRED"),
						content: json.error
					});
				}
				else
				{
					this.updateItemHtml(itemInfo);
					BX.PopupWindowManager.getCurrentPopup().destroy();
				}

			}, this),
			onfailure: function ()
			{

			}
		});
	},

	showStandardEditItemPopup: function (bindElement, itemInfo)
	{
		var isEditMode = false;
		if (typeof itemInfo === "object" && itemInfo)
		{
			isEditMode = true;
		}

		var popupContent = BX.create("form", {
			attrs: {
				name: "menuAddToFavoriteForm"
			},
			children: [
				BX.create("label", {
					attrs: {
						for: "menuPageToFavoriteName",
						className: "menu-form-label"
					},
					html: BX.message("MENU_ITEM_NAME")
				}),
				BX.create("input", {
					attrs: {
						value: isEditMode ? itemInfo.text : "",//document.title,
						name: "menuPageToFavoriteName",
						type: "text",
						className: "menu-form-input"
					}
				}),
				BX.create("input", {
					attrs: {
						name: "menuItemId",
						type: "hidden",
						value: itemInfo.id
					}
				})
			]
		});

		BX.PopupWindowManager.create("menu-standard-item-popup-edit", bindElement, {
			closeIcon: true,
			offsetTop: 1,
			//overlay : { opacity : 20 },
			lightShadow: true,
			draggable: {restrict: true},
			closeByEsc: true,
			titleBar: BX.message("MENU_RENAME_ITEM"),
			content: popupContent,
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message("MENU_SAVE_BUTTON"),
					className: 'popup-window-button-create',
					events: {
						click: BX.proxy(function ()
						{
							var form = document.forms["menuAddToFavoriteForm"];
							var textField = form.elements["menuPageToFavoriteName"];
							var text = BX.util.trim(textField.value);
							if (!text)
							{
								BX.addClass(textField, "menu-form-input-error");
								textField.focus();
							}
							else
							{
								BX.removeClass(textField, "menu-form-input-error");

								var itemNewInfo = {
									text: text,
									id: itemInfo.id
								};

								this.updateStandardItem(itemNewInfo/*, this.onSelfItemSave.bind(this)*/);
							}
						}, this)
					}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message('MENU_CANCEL'),
					className: "popup-window-button-link-cancel",
					events: {
						click: function ()
						{
							BX.PopupWindowManager.getCurrentPopup().destroy();
						}
					}
				})
			],
			events: {
				onPopupClose: function ()
				{
					BX.PopupWindowManager.getCurrentPopup().destroy();
				}
			}
		}).show();
	},

	animateTopItemFromLeft: function (itemId)
	{
		if (!BX.type.isDomNode(BX(itemId)))
			return;

		(new BX.easing({
			duration: 700,
			start: {left: BX(itemId).offsetLeft, opacity: 1},
			finish: {left: 400, opacity: 0},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: function (state)
			{
				BX(itemId).style.paddingLeft = state.left + "px";
				BX(itemId).style.opacity = state.opacity;
			},
			complete: BX.proxy(function ()
			{
				BX.remove(BX(itemId));
				this.isCurrentPageInLeftMenu = false;
				this.saveItemsSort();
			}, this)
		})).animate();
	},

	generateItemHtml: function (itemParams)
	{
		if (!(typeof itemParams == "object" && itemParams))
			return;

		var itemChildren = [
			BX.create("span", {
				text: itemParams.text,
				attrs: {
					className: "menu-item-link-text",
					"data-role": "item-text"
				}
			})
		];

		var isCounterExisted = BX.type.isNotEmptyString(itemParams.counterId);
		if (isCounterExisted)
		{
			itemChildren.push(BX.create("span", {
				attrs: {className: "menu-item-index-wrap"},
				children: [
					BX.create("span", {
						attrs: {
							className: "menu-item-index",
							id: "menu-counter-" + itemParams.counterId
						},
						html: itemParams.counterValue
					})
				]
			}));
		}

		var self = this;
		var newItemNode = BX.create("li", {
			attrs: {
				className: "menu-item-block" + (isCounterExisted && itemParams.counterValue ? " menu-item-with-index" : ""),
				id: "bx_left_menu_" + itemParams.id,
				"data-type": itemParams.type == "standard" ? "standard" : "self",
				"data-delete-perm": "Y",
				"data-id": itemParams.id,
				"data-link": itemParams.link,
				"data-status": "show",
				"data-new-page" : itemParams.openInNewPage == "Y" ? "Y" : "N"
			},
			children: [
				BX.create("span", {
					attrs: {className: "menu-fav-editable-btn menu-favorites-btn menu-fav-editable-btn-js"},
					children: [
						BX.create("span", {
							attrs: {className: "menu-favorites-btn-icon"}
						})
					],
					events: {
						"click": BX.proxy(function ()
						{
							this.openMenuPopup(BX.proxy_context, itemParams.id);
						}, this)
					}
				}),
				BX.create("span", {
					attrs: {className: "menu-favorites-btn menu-favorites-draggable"},
					children: [
						BX.create("span", {
							attrs: {className: "menu-fav-draggable-icon"}
						})
					],
					events: {
						"onmousedown": function ()
						{
							BX.addClass(this.parentNode, 'menu-item-draggable');
						},
						"onmouseup": function ()
						{
							BX.removeClass(this.parentNode, 'menu-item-draggable');
						}
					}
				}),
				BX.create("a", {
					attrs: {
						href: itemParams.link,
						className: "menu-item-link",
						target: (itemParams.openInNewPage == "Y" ? "_blank" : "")
					},
					children: itemChildren
				})
			]
		});

		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			this.menuItemsBlock.insertBefore(newItemNode, BX('left-menu-hidden-items-block'));
		}

		newItemNode.onbxdragstart = BX.proxy(this.menuItemDragStart, this);
		newItemNode.onbxdrag = BX.proxy(this.menuItemDragMove, this);
		newItemNode.onbxdragstop = BX.proxy(this.menuItemDragStop, this);
		newItemNode.onbxdraghover = BX.proxy(this.menuItemDragHover, this);
		jsDD.registerDest(newItemNode, 100);
		jsDD.registerObject(newItemNode);
	},

	updateItemHtml: function (itemParams)
	{
		if (!(typeof itemParams == "object" && itemParams))
			return;

		var itemNode = BX("bx_left_menu_" + itemParams.id);

		if (!BX.type.isDomNode(itemNode))
			return;

		if (itemParams.link)
		{
			itemNode.setAttribute("data-link", itemParams.link);
			var linkNode = BX.findChild(itemNode, {tagName: "a"}, true, false);
			if (BX.type.isDomNode(linkNode))
			{
				linkNode.setAttribute("href", itemParams.link);
				linkNode.setAttribute("title", itemParams.text);
				if (itemParams.hasOwnProperty("openInNewPage"))
				{
					linkNode.setAttribute("target", itemParams.openInNewPage == "Y" ? "_blank" : "");
					itemNode.setAttribute("data-new-page", itemParams.openInNewPage == "Y" ? "Y" : "N");
				}
			}
		}
		if (itemParams.text)
		{
			var textNode = itemNode.querySelector("[data-role='item-text']");
			if (BX.type.isDomNode(textNode))
			{
				textNode.innerText = itemParams.text;
			}
		}
	},

	setMainPage: function (itemId)
	{
		var itemNode = BX("bx_left_menu_" + itemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			if (itemNode.getAttribute("data-status") == "hide")
			{
				itemNode.setAttribute("data-status", "show");
			}

			var startTop = itemNode.offsetTop;
			var dragElement = BX.create("div", {
				attrs: {className: "menu-draggable-wrap"},
				style: {top: startTop}
			});

			var insertBeforeElement = itemNode.nextElementSibling;
			if (insertBeforeElement)
			{
				itemNode.parentNode.insertBefore(dragElement, insertBeforeElement);
			}
			else
			{
				itemNode.parentNode.appendChild(dragElement);
			}

			dragElement.appendChild(itemNode);

			BX.addClass(itemNode, "menu-item-draggable");

			(new BX.easing({
				duration: 500,
				start: {top: startTop},
				finish: {top: 0},
				transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: function (state)
				{
					dragElement.style.top = state.top + "px";
				},
				complete: BX.proxy(function ()
				{
					this.menuItemsBlock.insertBefore(itemNode, BX("left-menu-empty-item").nextSibling);
					BX.removeClass(itemNode, "menu-item-draggable");
					BX.remove(dragElement);

					this.saveItemsSort("?analyticsLabel=mainPage&itemId=" + itemId);
				}, this)
			})).animate();
		}
	},

	showConfirmWindow: function (options)
	{
		options = options || {};
		var id = BX.type.isNotEmptyString(options.id) ? options.id : BX.util.getRandomString();

		var popup = BX.PopupWindowManager.create(id, null, {
			content: '<div class="left-menu-confirm-popup">' +
			(BX.type.isNotEmptyString(options.content) ? options.content : "") +
			'</div>',
			titleBar: BX.type.isNotEmptyString(options.titleBar) ? options.titleBar : false,
			closeByEsc: true,
			closeIcon: true,
			draggable: true,
			buttons: [
				new BX.PopupWindowButton({
					text: BX.type.isNotEmptyString(options.okButtonText) ?
						options.okButtonText :
						"OK",
					className: "popup-window-button-create",
					events: {
						click: BX.type.isFunction(options.onsuccess) ?
							options.onsuccess :
							function ()
							{
								this.popupWindow.destroy();
							}
					}
				}),

				options.alertMode !== true ?
					new BX.PopupWindowButtonLink({
						text: BX.type.isNotEmptyString(options.cancelButtonText) ?
							options.cancelButtonText :
							BX.message("MENU_CANCEL"),
						className: "popup-window-button-link-cancel",
						events: {
							click: function ()
							{
								if (BX.type.isFunction(options.onfailure))
								{
									options.onfailure();
								}

								this.popupWindow.destroy();
							}
						}
					}) : null
			]
		});

		popup.show();
	},

	setDefaultMenu: function (showPresetPopup)
	{
		if (this.isExtranet || showPresetPopup !== true)
		{
			if (!confirm(BX.message("MENU_SET_DEFAULT_CONFIRM")))
				return;

			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: this.ajaxPath + "?analyticsLabel=defaultMenu",
				data: {
					sessid: BX.bitrix_sessid(),
					site_id: this.siteId,
					action: "set_default_menu"
				},
				onsuccess: BX.proxy(function ()
				{
					document.location.reload();
				}, this),
				onfailure: function ()
				{
				}
			});
		}
		else
		{
			this.showPresetPopupFunction("personal");
		}
	},

	/* drag&drop starting*/
	menuItemDragStart: function ()
	{
		BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onDragStart");
		var dragElement = BX.proxy_context;

		//drag&drop
		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			var items = this.menuItemsBlock.getElementsByClassName("menu-item-block");
			for (var i = 0; i < items.length; i++) // hack for few drag&drops on page
			{
				jsDD.registerDest(items[i], 100);
				jsDD.registerObject(items[i]);
			}
		}

		if (dragElement.getAttribute("data-type") == "self")
		{
			jsDD.unregisterDest(BX("left-menu-empty-item"));
		}
		else
		{
			jsDD.registerDest(BX("left-menu-empty-item"), 100);
		}

		jsDD.registerDest(BX("left-menu-hidden-empty-item"), 100);
		jsDD.registerDest(BX("left-menu-hidden-separator"), 100);

		if (!this.isEditMode())
		{
			this.areMoreItemsShowedState = this.areMoreItemsShowed();
			if (!this.areMoreItemsShowedState)
			{
				this.showHideMoreItems();
			}
		}

		this.itemHeight = dragElement.offsetHeight;

		BX.addClass(dragElement, "menu-item-draggable");
		BX.addClass(this.menuContainer, "menu-drag-mode");

		this.itemDomBlank = dragElement.parentNode.insertBefore(BX.create('div', {style: {height: '0px'}}), dragElement); //remember original item place
		this.itemMoveBlank = BX.create('div', {style: {height: this.itemHeight + 'px'}}); //empty div

		this.draggableBlock = BX.create('div', {             //div to move
			attrs: {className: "menu-draggable-wrap"},
			children: [dragElement]
		});

		this.menuItemsBlockCoord = BX.pos(this.menuItemsBlock);
		this.menuItemsBlock.style.position = 'relative';
		this.menuItemsBlock.appendChild(this.draggableBlock);
	},

	menuItemDragMove: function (x, y)
	{
		y -= this.menuItemsBlockCoord.top;
		var menuItemsBlockHeight = this.menuItemsBlock.offsetHeight;

		if (y < 0)
			y = 0;

		if (y > menuItemsBlockHeight - this.itemHeight)
			y = menuItemsBlockHeight - this.itemHeight;

		this.draggableBlock.style.top = y + 'px';
	},

	menuItemDragHover: function (dest, x, y)
	{
		var dragElement = BX.proxy_context;

		if (dest == dragElement)
		{
			this.itemDomBlank.parentNode.insertBefore(this.itemMoveBlank, this.itemDomBlank);
		}
		else
		{
			if (BX.findParent(dest, {className: "menu-items"}))  //li is hovered
			{
				if (BX.nextSibling(dest))
					dest.parentNode.insertBefore(this.itemMoveBlank, BX.nextSibling(dest));
				else
					dest.parentNode.appendChild(this.itemMoveBlank);
			}
		}
	},

	menuItemDragStop: function ()
	{
		var dragElement = BX.proxy_context;

		BX.removeClass(this.menuContainer, "menu-drag-mode");
		BX.removeClass(dragElement, "menu-item-draggable");

		var firstItem = BX.findChild(this.menuContainer, {className: "menu-item-block"}, true, false);
		if (BX.type.isDomNode(firstItem) && firstItem.getAttribute("data-type") == "self")
		{
			this.showMessage(firstItem, BX.message("MENU_SELF_ITEM_FIRST_ERROR"), "right");
			this.menuItemsBlock.replaceChild(dragElement, this.itemDomBlank);
		}
		else if (this.itemMoveBlank && BX.findParent(this.itemMoveBlank, {className: "menu-items"}))
		{
			this.itemMoveBlank.parentNode.replaceChild(dragElement, this.itemMoveBlank);

			if (dragElement.parentNode.id == "left-menu-hidden-items-list")
			{
				if (dragElement.getAttribute("data-status") == "show" && dragElement.getAttribute("data-counter-id"))
				{
					this.recountHiddenCounters();
				}

				dragElement.setAttribute("data-status", "hide");
			}
			else
			{
				if (dragElement.getAttribute("data-status") == "hide" && dragElement.getAttribute("data-counter-id"))
				{
					this.recountHiddenCounters();
				}
				dragElement.setAttribute("data-status", "show");
			}

			var analyticsLabel = "?analyticsLabel=sortItem";

			var prevItem = BX.previousSibling(dragElement);
			if (BX.type.isDomNode(prevItem) && prevItem.id == "left-menu-empty-item" && !this.isExtranet)
			{
				this.showMessage(dragElement, BX.message("MENU_ITEM_MAIN_PAGE"), "right");
				analyticsLabel = "?analyticsLabel=mainPage&itemId=" + dragElement.getAttribute("data-id");
			}

			this.checkMoreButton();
			this.saveItemsSort(analyticsLabel);
		}
		else
		{
			this.menuItemsBlock.replaceChild(dragElement, this.itemDomBlank);
		}

		BX.remove(this.draggableBlock);
		BX.remove(this.itemDomBlank);
		BX.remove(this.itemMoveBlank);

		jsDD.enableDest(dragElement);
		this.menuItemsBlock.style.position = 'static';

		if (!this.isEditMode() && !this.areMoreItemsShowedState)
		{
			this.showHideMoreItems();
		}

		this.draggableBlock = null;
		this.menuItemsBlockCoord = null;
		this.itemDomBlank = null;
		this.itemMoveBlank = null;
		this.areMoreItemsShowedState = null;

		jsDD.refreshDestArea();
	},
	/* drag&drop finishing*/

	clearCompositeCache: function ()
	{
		BX.ajax.post(
			this.ajaxPath,
			{
				sessid: BX.bitrix_sessid(),
				action: "clear"
			},
			function (result)
			{

			}
		);
	},

	onResizerMouseOver: function ()
	{
		if (this.isCollapsedMode)
		{
			return;
		}

		if (this.resizerTimeout)
		{
			clearTimeout(this.resizerTimeout);
		}

		this.resizerTimeout = setTimeout(BX.proxy(function ()
		{
			this.menuResizerButton.style.opacity = 1;
		}, this), 150);
	},

	onResizerMouseOut: function ()
	{
		if (this.isCollapsedMode)
		{
			return;
		}

		if (this.resizerTimeout)
		{
			clearTimeout(this.resizerTimeout);
		}

		this.resizerTimeout = setTimeout(BX.proxy(function ()
		{
			this.menuResizerButton.style.opacity = 0;
		}, this), 200);
	},

	onResizerClick: function (event)
	{
		this.toggle();
		event.stopPropagation();
	},

	onMenuDoubleClick: function (event)
	{
		if (event.target === this.menuContainer)
		{
			this.toggle(false);
		}
		else if (event.target === this.leftColumnBottom)
		{
			this.toggle();
		}
	},

	toggle: function (flag)
	{
		var leftColumn = BX("layout-left-column");
		/* we have to modify bitrix24 template */
		var table = BX.findParent(leftColumn, {tagName: "table"});
		if (!leftColumn)
		{
			return;
		}

		var isOpened = !BX.hasClass(table, "menu-collapsed-mode");
		if ((flag === true && isOpened) || (flag === false && !isOpened))
		{
			return;
		}

		BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuToggle", [flag, this]);

		leftColumn.style.overflow = "hidden";
		if (!isOpened)
		{
			leftColumn.style.opacity = 0;
		}
		this.menuResizer.style.display = "none";
		this.menuResizerButton.style.opacity = 0;
		BX.addClass(leftColumn, "menu-animation-mode");

		(new BX.easing({
			duration: 400,
			start: {
				width: isOpened ? 240 : 40, /* these values are duplicated in style.css as well */
				opacity: isOpened ? 100 : 0
			},
			finish: {
				width: isOpened ? 40 : 240,
				opacity: isOpened ? 0 : 100
			},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: function (state)
			{
				leftColumn.style.width = state.width + "px";
				leftColumn.style.opacity = state.opacity / 100;
			},
			complete: BX.proxy(function ()
			{
				leftColumn.style.cssText = "";
				this.menuResizer.style.cssText = "";

				if (isOpened)
				{
					this.isCollapsedMode = true;
					this.menuResizerButton.style.cssText = "";
					BX.addClass(table, "menu-collapsed-mode");
					BX.removeClass(leftColumn, "menu-animation-mode");
				}
				else
				{
					this.isCollapsedMode = false;
					BX.removeClass(leftColumn, "menu-animation-mode");
					BX.removeClass(table, "menu-collapsed-mode");
				}

				BX.ajax({
					method: "POST",
					dataType: "json",
					url: this.ajaxPath,
					data: {
						sessid: BX.bitrix_sessid(),
						site_id: this.siteId,
						action: isOpened ? "collapse_menu" : "expand_menu"
					}
				});

				var event = document.createEvent("Event");
				event.initEvent("resize", true, true);
				window.dispatchEvent(event);

			}, this)
		})).animate();
	},

	highlight: function (currentUrl)
	{
		if (!BX.type.isNotEmptyString(currentUrl) || !this.menuContainer)
		{
			return false;
		}

		var items = this.menuContainer.getElementsByTagName("li");
		var curSelectedItem = -1;
		var curSelectedLen = -1;
		var curSelectedPriority = -1;
		var curSelectedUrl = null;

		for (var i = 0, length = items.length; i < length; i++)
		{
			var itemLinks = [];
			var dataLink = items[i].getAttribute("data-link");
			if (BX.type.isNotEmptyString(dataLink))
			{
				/*
				Custom items have more priority than standard items.
				Example:
					Calendar (standard item)
						data-link="/company/personal/user/1/calendar/"
						data-all-links="/company/personal/user/1/calendar/,/calendar/

					Company Calendar (custom item)
				 		data-link="/calendar/"

				We've got two items with the identical link /calendar/'.
				*/

				var itemType = items[i].getAttribute("data-type");
				itemLinks.push({
					priority: BX.util.in_array(itemType, ["standard", "admin"]) ? 3 : 2,
					url: dataLink
				});
			}

			var dataLinks = items[i].getAttribute("data-all-links");
			if (BX.type.isNotEmptyString(dataLinks))
			{
				dataLinks.split(",").forEach(function(link) {
					link = BX.util.trim(link);
					if (BX.type.isNotEmptyString(link))
					{
						itemLinks.push({
							priority: 1,
							url: link
						});
					}
				});
			}

			for (var j = 0, l = itemLinks.length; j < l; j++)
			{
				var itemLink = itemLinks[j].url;
				var itemPriority = itemLinks[j].priority;

				var isItemSelected = this.isItemSelected(itemLink, currentUrl);
				if (isItemSelected)
				{
					var newLength = itemLink.length;
					if (
						newLength > curSelectedLen ||
						(newLength === curSelectedLen && itemPriority > curSelectedPriority)
					)
					{
						curSelectedItem = i;
						curSelectedUrl = itemLink;
						curSelectedLen = newLength;
						curSelectedPriority = itemPriority;
					}
				}
			}
		}

		if (curSelectedItem < 0)
		{
			return;
		}

		var li = items[curSelectedItem];
		BX.addClass(li, "menu-item-active");

		//Show hidden item
		var moreItem = li.parentNode.parentNode;
		if (
			BX.hasClass(moreItem, "menu-item-favorites-more") && !BX.hasClass(moreItem, "menu-item-favorites-more-open")
		)
		{
			this.showHideMoreItems(false);
		}

		return true;
	},

	getSelectedItem: function (currentUrl, allLinks)
	{
		if (!BX.type.isNotEmptyString(currentUrl) || !this.menuContainer)
		{
			return false;
		}

		var items = this.menuContainer.getElementsByTagName("li");
		var curSelectedItem = -1;
		var curSelectedLen = -1;
		var curSelectedUrl = null;

		for (var i = 0, length = items.length; i < length; i++)
		{
			var itemLinks = [];

			if (allLinks)
			{
				var dataLinks = items[i].getAttribute("data-all-links");
				if (BX.type.isNotEmptyString(dataLinks))
				{
					itemLinks = itemLinks.concat(dataLinks.split(","));
				}
			}
			else
			{
				var dataLink = items[i].getAttribute("data-link");
				if (BX.type.isNotEmptyString(dataLink))
				{
					itemLinks.push(dataLink);
				}
			}

			for (var j = 0, l = itemLinks.length; j < l; j++)
			{
				var itemLink = itemLinks[j];
				if (!BX.type.isNotEmptyString(itemLink))
				{
					continue;
				}

				var isItemSelected = this.isItemSelected(itemLink, currentUrl);
				if (isItemSelected)
				{
					var newLength = itemLink.length;
					if (newLength > curSelectedLen)
					{
						curSelectedItem = i;
						curSelectedUrl = itemLinks[j];
						curSelectedLen = newLength;
					}
				}
			}
		}

		return curSelectedItem >= 0 ? items[curSelectedItem] : null;

	},

	isItemSelected: function (url, currentUrl)
	{
		var originalCurrentUrl = currentUrl;
		var questionPos = currentUrl.indexOf("?");
		if (questionPos !== -1)
		{
			currentUrl = currentUrl.substring(0, questionPos);
		}

		var currentUrlWithIndex = this.getUrlWithIndex(currentUrl);
		url = url.replace(/(\/index\.php)($|\?)/, "$2");

		if (currentUrl.indexOf(url) === 0 || currentUrlWithIndex.indexOf(url) === 0)
		{
			return true;
		}

		questionPos = url.indexOf("?");
		if (questionPos === -1)
		{
			return false;
		}

		var refinedUrl = url.substring(0, questionPos);
		if (refinedUrl !== currentUrl && refinedUrl !== currentUrlWithIndex)
		{
			return false;
		}

		var success = true;
		var params = this.getUrlParams(url);
		var globals = this.getUrlParams(originalCurrentUrl);

		for (var varName in params)
		{
			if (!params.hasOwnProperty(varName))
			{
				continue;
			}

			var varValues = params[varName];
			var globalValues = typeof(globals[varName]) !== "undefined" ? globals[varName] : [];
			for (var i = 0; i < varValues.length; i++)
			{
				var varValue = varValues[i];
				if (!BX.util.in_array(varValue, globalValues))
				{
					success = false;
					break;
				}
			}
		}

		return success;
	},

	getUrlParams: function (url)
	{
		var params = {};
		var questionPos = url.indexOf("?");
		if (questionPos === -1)
		{
			return params;
		}

		var tokens = url.substring(questionPos + 1).split("&");

		for (var i = 0; i < tokens.length; i++)
		{
			var token = tokens[i];
			var eqPos = token.indexOf("=");

			if (eqPos === 0)
			{
				continue;
			}

			var varName = eqPos === -1 ? token : token.substring(0, eqPos);
			varName = varName.replace("[]", "");
			var varValue = eqPos === -1 ? "" : token.substring(eqPos + 1);

			if (params[varName])
			{
				params[varName].push(varValue);
			}
			else
			{
				params[varName] = [varValue];
			}

		}

		return params;
	},

	getUrlWithIndex: function (url)
	{
		if (!BX.type.isNotEmptyString(url))
		{
			url = "";
		}

		var questionPos = url.indexOf("?");
		var queryString = questionPos >= 0 ? "?" + url.substring(questionPos + 1) : "";
		var path = questionPos >= 0 ? url.substring(0, questionPos) : url;

		if (path.match(/\.php$/))
		{
			return url;
		}

		if (path.slice(-1) !== "/")
		{
			path += "/";
		}

		return path + "index.php" + queryString;
	},

	checkCurrentPageInTopMenu: function ()
	{
		var currentFullPath = document.location.pathname + document.location.search;

		if (BX.Main && BX.Main.interfaceButtonsManager)
		{
			var menuCollection = BX.Main.interfaceButtonsManager.getObjects();
			var menuIds = Object.keys(menuCollection);

			if (menuIds[0])
			{
				var menu = menuCollection[menuIds[0]];
				this.topItemSelectedObj = menu.getActive();

				if (typeof this.topItemSelectedObj === "object" && this.topItemSelectedObj)
				{
					if (this.topItemSelectedObj.hasOwnProperty("NODE"))
					{
						this.topMenuSelectedNode = this.topItemSelectedObj.NODE;
					}

					var link = document.createElement("a");
					link.href = this.topItemSelectedObj.URL;
					this.topItemSelectedObj.URL = BX.util.htmlspecialcharsback(link.pathname + link.search);
					this.topItemSelectedObj.TEXT = BX.util.htmlspecialcharsback(this.topItemSelectedObj.TEXT);

					this.isCurrentPageStandard = (this.topItemSelectedObj.URL == currentFullPath) && this.topItemSelectedObj.URL.indexOf("workgroups") == -1;
				}
			}
		}

		return this.isCurrentPageStandard;
	},

	checkLinkInMenu: function (link)
	{
		if (!BX.type.isNotEmptyString(link))
			return;

		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			var items = this.menuItemsBlock.getElementsByClassName("menu-item-block");
			for (var i = 0; i < items.length; i++)
			{
				if (items[i].getAttribute("data-link") == link)
					return items[i];
			}
		}

		return false;
	},

	getStructureForHelper: function ()
	{
		var items = {menu: {}};
		if (BX.type.isDomNode(this.menuContainer))
		{
			var types = ["show", "hide"];
			for (var type in types)
			{
				var curItems = this.menuContainer.querySelectorAll("[data-status='" + types[type] + "']");
				if (curItems)
				{
					var curItemsId = [];
					for (var i = 0, l = curItems.length; i < l; i++)
					{
						if (curItems[i].getAttribute("data-type") != "default")
						{
							continue;
						}

						curItemsId.push(curItems[i].getAttribute("data-id"));
					}
				}
				items[types[type]] = curItemsId;
			}
		}

		return items;
	},

	/**
	 *
	 * @returns {boolean}
	 */
	initPagetitleStar: function ()
	{
		this.checkCurrentPageInTopMenu();

		var starContNode = BX("pagetitle-star");
		if (!starContNode)
		{
			return false;
		}

		var currentFullPath = document.location.pathname + document.location.search;

		var currentPageInMenu = this.checkLinkInMenu(currentFullPath);
		if (typeof currentPageInMenu == "object")
		{
			BX.addClass(starContNode, "pagetitle-star-active");
		}
		starContNode.title = BX.message(BX.hasClass(starContNode, "pagetitle-star-active") ? "MENU_DELETE_PAGE_FROM_LEFT_MENU" : "MENU_ADD_PAGE_TO_LEFT_MENU");

		//default page
		if (typeof currentPageInMenu == "object" && currentPageInMenu.getAttribute("data-type") == "default")
		{
			starContNode.title = BX.message("MENU_STAR_TITLE_DEFAULT_PAGE");
			BX.bind(starContNode, "click", BX.proxy(function ()
			{
				this.showMessage(BX.proxy_context, BX.message("MENU_STAR_TITLE_DEFAULT_PAGE_DELETE_ERROR"));
			}, this));

			return true;
		}

		//any page
		BX.bind(starContNode, "click", BX.proxy(function ()
		{
			if (BX.hasClass(starContNode, "pagetitle-star-active"))
			{
				this.deleteStandardItem();
			}
			else
			{
				this.addStandardItem();
			}
		}, this));

		return true;
	},

	initPreset: function ()
	{
		var container = BX("left-menu-preset-popup");

		if (!BX.type.isDomNode(container))
			return;

		this.presetItems = container.getElementsByClassName("js-left-menu-preset-item");
		if (typeof this.presetItems == "object")
		{
			for (var i = 0; i < this.presetItems.length; i++)
			{
				BX.bind(this.presetItems[i], "click", BX.proxy(function ()
				{
					this.selectPreset(BX.proxy_context);
				}, this));
			}

		}
	},

	selectPreset: function (selectedNode)
	{
		for (var i = 0; i < this.presetItems.length; i++)
		{
			BX.removeClass(this.presetItems[i], "left-menu-popup-selected");
		}

		if (BX.type.isDomNode(selectedNode))
		{
			BX.addClass(selectedNode, "left-menu-popup-selected");
		}
	},

	showPresetPopupFunction: function (mode)
	{
		BX.ready(function ()
		{
			var button = null;
			BX.PopupWindowManager.create("menu-preset-popup", null, {
				closeIcon: false,
				offsetTop: 1,
				overlay: true,
				lightShadow: true,
				contentColor: "white",
				draggable: {restrict: true},
				closeByEsc: true,
				content: BX("left-menu-preset-popup"),
				buttons: [
					(button = new BX.PopupWindowButton({
						text: BX.message("MENU_CONFIRM_BUTTON"),
						className: "popup-window-button-create",
						events: {
							click: BX.proxy(function ()
							{
								if (BX.hasClass(button.buttonNode, "popup-window-button-wait"))
								{
									return;
								}

								BX.addClass(button.buttonNode, "popup-window-button-wait");

								var form = document.forms["left-menu-preset-form"];
								var currentPreset = "";
								if (form)
								{
									presets = form.elements["presetType"];
									for (var i = 0; i < presets.length; i++)
									{
										if (presets[i].checked)
										{
											currentPreset = presets[i].value;
											break;
										}
									}
								}

								BX.ajax({
									method: 'POST',
									dataType: 'json',
									url: this.ajaxPath + "?analyticsLabel=" + currentPreset + (mode == "global" ? "&analyticsFirst=y" : ""),
									data: {
										sessid: BX.bitrix_sessid(),
										site_id: this.siteId,
										siteDir: this.siteDir,
										action: "set_preset",
										preset: currentPreset,
										mode: mode == "global" ? "global" : "personal"
									},
									onsuccess: BX.proxy(function (json)
									{
										if (json.hasOwnProperty("url"))
										{
											document.location.href = json.url;
										}
										else
										{
											document.location.reload();
										}
									}, this),
									onfailure: function ()
									{
										document.location.reload();
									}
								});
							}, this)
						}
					})),
					new BX.PopupWindowButton({
						text: BX.message('MENU_DELAY_BUTTON'),
						// className: "popup-window-button-link-cancel",
						events: {
							click: BX.proxy(function ()
							{
								BX.ajax({
									method: 'POST',
									dataType: 'json',
									url: this.ajaxPath + "?analyticsLabel=later" + (mode == "global" ? "&analyticsFirst=y" : "")
								});
								BX.proxy_context.popupWindow.close();
							}, this)
						}
					})
				]
			}).show();

			this.initPreset();
		}.bind(this));
	},

	showCustomPresetPopup: function ()
	{
		var content = BX.create("form", {
			attrs: {id: "customPresetForm", style: "min-width: 350px"},
			children: [
				BX.create("div", {
					attrs: {style: "margin: 15px 0 15px 9px;"},
					children: [
						BX.create("input", {
							attrs: {type: "radio", name: "customPresetSettings", id: "customPresetCurrentUser", value: "currentUser"}
						}),
						BX.create("label", {
							attrs: {for: "customPresetCurrentUser"},
							html: BX.message("MENU_CUSTOM_PRESET_CURRENT_USER")
						})
					]
				}),
				BX.create("div", {
					attrs: {style: "margin: 0 0 38px 9px;"},
					children: [
						BX.create("input", {
							attrs: {type: "radio", name: "customPresetSettings", id: "customPresetNewUser", value: "newUser", checked: "checked"}
						}),
						BX.create("label", {
							attrs: {for: "customPresetNewUser"},
							html: BX.message("MENU_CUSTOM_PRESET_NEW_USER")
						})
					]
				}),
				BX.create("hr", {attrs: {
					style: "background-color: #edeef0; border: none; color:  #edeef0; height: 1px;"
				}})
			]
		});

		var showMenuItems = [],
			hideMenuItems = [],
			customItems = [],
			firstItemLink = "";

		var items = BX.findChildren(this.menuContainer, {className: "menu-item-block"}, true);

		for (var i = 0; i < items.length; i++)
		{
			if (i == 0)
			{
				firstItemLink = items[i].getAttribute("data-link");
			}

			if (items[i].getAttribute("data-status") == "show")
			{
				showMenuItems.push(items[i].getAttribute("data-id"));
			}
			else if (items[i].getAttribute("data-status") == "hide")
			{
				hideMenuItems.push(items[i].getAttribute("data-id"));
			}

			if (
				items[i].getAttribute("data-type") == "self"
				|| items[i].getAttribute("data-type") == "standard"
				|| items[i].getAttribute("data-type") == "custom"
			)
			{
				var textNode = items[i].querySelector("[data-role='item-text']");
				var item = {
					ID: items[i].getAttribute("data-id"),
					LINK: items[i].getAttribute("data-link"),
					TEXT: BX.util.htmlspecialcharsback(textNode.innerHTML)
				};
				if (items[i].getAttribute("data-new-page") == "Y")
				{
					item.NEW_PAGE = "Y";
				}
				customItems.push(item);
			}
		}

		this.menuItemsCustomSort = {"show": showMenuItems, "hide": hideMenuItems};

		BX.PopupWindowManager.create("menu-custom-preset-popup", null, {
			closeIcon: true,
			offsetTop: 1,
			overlay: true,
			contentColor : "white",
			contentNoPaddings : true,
			lightShadow: true,
			draggable: {restrict: true},
			closeByEsc: true,
			titleBar: BX.message("MENU_CUSTOM_PRESET_POPUP_TITLE"),
			content: content,
			buttons: [
				(button = new BX.PopupWindowButton({
					text: BX.message("MENU_SAVE_BUTTON"),
					className: "popup-window-button-create",
					events: {
						click: BX.proxy(function ()
						{
							if (BX.hasClass(button.buttonNode, "popup-window-button-wait"))
							{
								return;
							}

							BX.addClass(button.buttonNode, "popup-window-button-wait");

							var form = BX("customPresetForm");
							if (BX.type.isDomNode(form))
							{
								var userSetting = form.elements["customPresetSettings"].value;
							}

							BX.ajax({
								method: 'POST',
								dataType: 'json',
								url: this.ajaxPath + "?analyticsLabel=customPreset",
								data: {
									sessid: BX.bitrix_sessid(),
									site_id: this.siteId,
									siteDir: this.siteDir,
									action: "save_custom_preset",
									userApply: userSetting,
									itemsSort: this.menuItemsCustomSort,
									customItems: customItems,
									firstItemLink: firstItemLink
								},
								onsuccess: BX.proxy(function ()
								{
									BX.removeClass(button.buttonNode, "popup-window-button-wait");
									BX.PopupWindowManager._currentPopup.close();
									this.customPresetExists = true;

									BX.PopupWindowManager.create("menu-custom-preset-success-popup", null, {
										closeIcon: true,
										contentColor : "white",
										titleBar: BX.message("MENU_CUSTOM_PRESET_POPUP_TITLE"),
										content: BX.message("MENU_CUSTOM_PRESET_SUCCESS")
									}).show();

								}, this),
								onfailure: function ()
								{
								}
							});
						}, this),
						close: function ()
						{
							this.popupWindow.destroy();
						}
					}
				})),
				new BX.PopupWindowButton({
					text: BX.message('MENU_CANCEL'),
					className: "popup-window-button-link popup-window-button-link-cancel",
					events: {
						click: function ()
						{
							this.popupWindow.close();
						}
					}
				})
			]
		}).show();
	},

	showLoader: function ()
	{
		var loader = BX.create("div", {html: '<div style="display: block" class="intranet-loader-container"> <svg class="intranet-loader-circular" viewBox="25 25 50 50"> <circle class="intranet-loader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10" /> </svg> </div>'});
		document.body.appendChild(loader);
	}
};

BX.namespace("BX.Intranet.ExpandTariff");
BX.Intranet.ExpandTariff =
{
	redirect: function(params)
	{
		if (typeof params !== "object")
			return;

		var url = params.COUNTER_URL || "",
			licensePath = params.LICENSE_PATH || "",
			host = params.HOST || "";

		BX.ajax.post(
			url,
			{
				action: "upgradeButton",
				host: host
			},
			BX.proxy(function(){
				document.location.href = licensePath;
			}, this)
		);
	}
};



