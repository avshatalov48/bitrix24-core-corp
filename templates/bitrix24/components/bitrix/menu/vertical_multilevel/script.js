BX.namespace("BX.Bitrix24");

BX.Bitrix24.MenuClass = (function()
{
	var MenuClass = function(params)
	{
		params = typeof params === "object" ? params : {};

		this.arFavouriteAll = params.arFavouriteAll || {};
		this.arFavouriteShowAll = params.arFavouriteShowAll || {};
		this.arTitles = params.arTitles || [];
		this.ajaxPath = params.ajaxPath || null;
		this.isAdmin =  params.isAdmin === "Y";
		this.hiddenCounters = params.hiddenCounters || {};
		this.allCounters = params.allCounters || {};
		this.isBitrix24 = params.isBitrix24 === "Y";
		this.siteId = params.siteId || null;
		this.isCompositeMode = params.isCompositeMode === true;

		this.activeItemsId = [];

		//show hidden items, if they are selected
		if (params.arHiddenItemsSelected)
		{
			for (var key in params.arHiddenItemsSelected)
			{
				this.showHideMoreItems(BX("more_btn_" + params.arHiddenItemsSelected[key]), params.arHiddenItemsSelected[key]);
			}
		}

		for (var i = 0, l = this.arTitles.length; i < l; i++)
		{
			var itemId = this.arTitles[i];
			var item = BX(itemId);
			if (!item || BX.hasClass(item, "menu-favorites"))
			{
				continue;
			}

			BX.bind(item, "click", BX.proxy(this.showHideMenuSection2, {element: item, self:this}));
			BX.bind(item.lastChild, "click", BX.proxy(this.showHideMenuSection, {element: item.lastChild, self:this } ));
		}
	};

	MenuClass.prototype.showHideMenuSection = function(event)
	{
		if (this.self.isCompositeMode)
		{
			this.self.clearCompositeCache();
		}

		event = event || window.event;
		BX.eventCancelBubble(event);
		B24.toggleMenu(this.element.parentNode, BX.message("menu_show"), BX.message("menu_hide"));
	};

	MenuClass.prototype.showHideMenuSection2 = function()
	{
		if (!this.self.isEditMode())
		{
			if (this.self.isCompositeMode)
			{
				this.self.clearCompositeCache();
			}

			B24.toggleMenu(this.element, BX.message("menu_show"), BX.message("menu_hide"));
		}
	};

	MenuClass.prototype.isEditMode = function()
	{
		return BX.hasClass(BX("div_menu-favorites"), 'menu-favorites-editable');
	};

	MenuClass.prototype.applyEditMode = function()
	{
		var isEditMode = this.isEditMode();

		var allTitleBlocks = BX.findChildren(BX("bx_b24_menu"), {className:"menu-items-block"}, true);
		for (var obj in allTitleBlocks)
		{
			if (isEditMode)
				BX.removeClass(allTitleBlocks[obj], "menu-favorites-editable");
			else
				BX.addClass(allTitleBlocks[obj], "menu-favorites-editable");
		}

		if (!isEditMode)
		{
			BX.addClass(BX("menu_favorites_settings"), 'menu-favorites-btn-active');

			var allActiveItems = BX.findChildren(BX("bx_b24_menu"), {className:"menu-item-active"}, true);
			for (obj in allActiveItems)
			{
				if (!isEditMode)
				{
					BX.removeClass(allActiveItems[obj], 'menu-item-active');
					this.activeItemsId.push(allActiveItems[obj].id);
				}
			}
		}
		else
		{
			BX.removeClass(BX("menu_favorites_settings"), 'menu-favorites-btn-active');
			for (var key in this.activeItemsId)
			{
				BX.addClass(BX(this.activeItemsId[key]), 'menu-item-active');
			}
			this.activeItemsId = [];
		}

		var moveItems = [];
		for (var j=0; j<this.arTitles.length; j++)
		{
			if (this.arTitles[j] != "menu-favorites")
			{
				BX(this.arTitles[j]).onbxdragstart = BX.proxy(this.sectionDragStart, this);
				BX(this.arTitles[j]).onbxdrag = BX.proxy(this.sectionDragMove, this);
				BX(this.arTitles[j]).onbxdragstop = BX.proxy(this.sectionDragStop, this);
				BX(this.arTitles[j]).onbxdraghover = BX.proxy(this.sectionDragHover, this);
				jsDD.registerObject(BX(this.arTitles[j]));
			}
			jsDD.registerDest(BX(this.arTitles[j]).parentNode, 200);

			//drag&drop
			if (!isEditMode)
			{
				jsDD.Enable();
				var liObj = BX.findChildren(BX("ul_"+this.arTitles[j]), {tagName:"li"}, true);
				for (var i=0; i<liObj.length; i++)
				{
					if (liObj[i].id == "separator_"+this.arTitles[j])
						break;

					if (((this.isBitrix24 && this.arTitles[j] == "menu-favorites") || this.arTitles[j] == "menu-groups") && liObj[i].id == "empty_li_"+this.arTitles[j])
						continue;

					if ((this.isBitrix24 && liObj[i].id == "menu_live_feed") || liObj[i].id == "menu_all_groups")
					{
						jsDD.registerDest(liObj[i]);
						continue;
					}
					moveItems.push(liObj[i].id);

					liObj[i].onbxdragstart = BX.proxy(this.menuItemDragStart, this);
					liObj[i].onbxdrag =  BX.proxy(this.menuItemDragMove, this);
					liObj[i].onbxdragstop =  BX.proxy(this.menuItemDragStop, this);
					liObj[i].onbxdraghover =  BX.proxy(this.menuItemDragHover, this);
					jsDD.registerDest(liObj[i], 100);
					jsDD.registerObject(liObj[i]);
				}
			}
			//--drag&drop

			var liObj = BX.findChildren(BX("hidden_items_ul_"+this.arTitles[j]), {tagName:"li"}, true);
			if (liObj.length > 0)
				BX("separator_"+this.arTitles[j]).style.display = (isEditMode)  ? "none" : "block";
			else
				BX("separator_"+this.arTitles[j]).style.display = "none";
		}
	};

	MenuClass.prototype.showHideMoreItems = function(element, titleItemId)
	{
		BX.toggleClass(BX('hidden_items_li_'+titleItemId), 'menu-item-favorites-more-open');
		BX.toggleClass(element, 'menu-favorites-more-btn-open');
		if (titleItemId == "menu-favorites")
			BX.toggleClass(BX('menu-hidden-counter'), 'menu-hidden-counter');
		BX.firstChild(element).innerHTML = (BX.firstChild(element).innerHTML == BX.message('more_items_hide')) ? BX.message('more_items_show') : BX.message('more_items_hide');
	};

	MenuClass.prototype.openMenuPopup = function(bindElement, menuItemId)
	{
		var menuItems = [];
		var self = this;

		var itemIsFavourite = false;
		for(var i = 0, l = this.arFavouriteAll.length; i < l; i++)
		{
			if (this.arFavouriteAll[i] == menuItemId)
				itemIsFavourite = true;
		}

		var can_delete_from_favorite = BX(menuItemId).getAttribute("data-can-delete-from-favorite");
		var title_item = BX(menuItemId).getAttribute("data-title-item");

		//add to favorite
		if (!itemIsFavourite && can_delete_from_favorite == "Y")
			menuItems.push({text : BX.message("add_to_favorite"), className : "menu-popup-no-icon", onclick :  function() {this.popupWindow.close(); self.addFavouriteItem(menuItemId, title_item, "N"); BX.PopupMenu.destroy("popup_"+menuItemId);}});
		//delete from favorite
		if (itemIsFavourite && can_delete_from_favorite == "Y")
			menuItems.push({text : BX.message("delete_from_favorite"), className : "menu-popup-no-icon", onclick : function() {this.popupWindow.close(); self.deleteFavouriteItem(menuItemId, title_item, "N"); BX.PopupMenu.destroy("popup_"+menuItemId);}});
		//hide item
		if (BX(menuItemId).getAttribute("data-status") == "show" /*&& !(itemIsFavourite && can_delete_from_favorite == "Y")*/)
			menuItems.push({text : BX.message("hide_item"), className : "menu-popup-no-icon", onclick : function() {this.popupWindow.close(); self.hideItem(menuItemId, title_item); BX.PopupMenu.destroy("popup_"+menuItemId);}});
		//show item
		if (BX(menuItemId).getAttribute("data-status") == "hide"/* && !(itemIsFavourite && can_delete_from_favorite == "Y")*/)
			menuItems.push({text : BX.message("show_item"), className : "menu-popup-no-icon", onclick : function() {this.popupWindow.close(); self.showItem(menuItemId, title_item); BX.PopupMenu.destroy("popup_"+menuItemId);}});

		if (this.isAdmin)
		{
			//add to favorite all
			if (!itemIsFavourite)
				menuItems.push({text : BX.message("add_to_favorite_all"), className : "menu-popup-no-icon", onclick : function() {self.addFavouriteItem(menuItemId, title_item, "Y"); BX.PopupMenu.destroy("popup_"+menuItemId);}});
			//delete from favorite all
			if (itemIsFavourite && can_delete_from_favorite == "A")
				menuItems.push({text : BX.message("delete_from_favorite_all"), className : "menu-popup-no-icon", onclick : function() {self.deleteFavouriteItem(menuItemId, title_item, "Y"); BX.PopupMenu.destroy("popup_"+menuItemId);}});

			//set rights for apps
			if (BX(menuItemId).getAttribute("data-app-id"))
				menuItems.push({text : BX.message("set_rights"), className : "menu-popup-no-icon", onclick : function() {this.popupWindow.close(); self.setRights(menuItemId); BX.PopupMenu.destroy("popup_"+menuItemId);}});
		}

		var MenuPopup = BX.PopupMenu.show("popup_"+menuItemId, bindElement, menuItems,
			{
				offsetTop:0,
				offsetLeft : 12,
				angle :true,
				events : {
					onPopupClose : function() {
						BX.removeClass(bindElement, 'menu-favorites-btn-active');
					}
				}
			});
		BX.addClass(bindElement, 'menu-favorites-btn-active');
	};

	MenuClass.prototype.showError = function(bindElement)
	{
		var errorPopup = BX.PopupWindowManager.create("menu-error", bindElement, {
			content: BX.message('edit_error'),
			angle: {offset : 10 },
			offsetTop:0,
			events : { onPopupClose: function() { BX.removeClass(this.bindElement, "filter-but-act")}},
			autoHide:true
		});
		errorPopup.setBindElement(bindElement);
		errorPopup.show();
	};

	MenuClass.prototype.setRights =  function(menuItemId)
	{
		BX.rest.Marketplace.setRights(BX(menuItemId).getAttribute("data-app-id"), this.siteid);
	};

	MenuClass.prototype.addFavouriteItem =  function(menuItemId, titleItem, forAll)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : (forAll == "Y") ? "add_favorite_admin" : "add_favorite",
				menu_item_id : menuItemId,
				title_item_id : titleItem,
				all_show_items : this.arFavouriteShowAll
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(BX(menuItemId));
				}
				else
				{
					BX.removeClass(BX.firstChild(BX(menuItemId)), 'menu-favorites-btn-active');
					var cloneObj = BX.clone(BX(menuItemId));
					BX(menuItemId).id = "hidden_"+menuItemId;
					BX("hidden_"+menuItemId).style.display = "none";
					BX("ul_menu-favorites").insertBefore(cloneObj, BX("separator_menu-favorites"));
					BX(menuItemId).setAttribute("data-title-item", "menu-favorites");
					BX(menuItemId).setAttribute("data-status", "show");
					if (forAll == "Y")
						BX(menuItemId).setAttribute("data-can-delete-from-favorite", "A");

					BX(menuItemId).onbxdragstart =  BX.proxy(this.menuItemDragStart, this);
					BX(menuItemId).onbxdrag =  BX.proxy(this.menuItemDragMove, this);
					BX(menuItemId).onbxdragstop =  BX.proxy(this.menuItemDragStop, this);
					BX(menuItemId).onbxdraghover =  BX.proxy(this.menuItemDragHover, this);
					jsDD.registerDest(BX(menuItemId));
					jsDD.registerObject(BX(menuItemId));

					var otherItems = BX.findChildren(BX("ul_"+titleItem), {tagName:"li"}, true);
					var otherItemsExist = false;
					for (var i=0; i<otherItems.length; i++)
					{
						if (
							otherItems[i].id != "hidden_"+menuItemId
								&& otherItems[i].style.display != "none"
								&& otherItems[i].id != "separator_"+titleItem
								&& otherItems[i].id != "empty_li_"+titleItem
								&& otherItems[i].id != "hidden_items_li_"+titleItem
							)
						{
							otherItemsExist = true;
							break;
						}
					}
					if (!otherItemsExist)
						BX("div_"+titleItem).style.display = "none";

					this.arFavouriteShowAll.push(menuItemId);
					this.arFavouriteAll.push(menuItemId);

					var otherHiddenItems = BX.findChildren(BX("hidden_items_ul_"+titleItem), {tagName:"li"}, true);
					var otherHiddenExist = false;
					for (var i=0; i<otherHiddenItems.length; i++)
					{
						if (otherHiddenItems[i].id != "hidden_"+menuItemId
							&& otherHiddenItems[i].style.display != "none"
							)
						{
							otherHiddenExist = true;
							break;
						}
					}
					if (!otherHiddenExist)
					{
						BX("more_btn_"+titleItem).style.display = "none";
						BX("separator_"+titleItem).style.display = "none";
					}
				}
			}, this)
		});
	};

	MenuClass.prototype.deleteFavouriteItem = function(menuItemId, oldTitleItem, forAll)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : (forAll == "Y") ? "delete_favorite_admin" : "delete_favorite",
				menu_item_id : menuItemId,
				title_item_id : oldTitleItem
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(BX(menuItemId));
				}
				else
				{
					BX.remove(BX(menuItemId));
					BX("hidden_"+menuItemId).id = menuItemId;
					BX(menuItemId).style.display = "block";
					if (forAll == "Y")
						BX(menuItemId).setAttribute("data-can-delete-from-favorite", "Y");
					var cur_title_item = BX(menuItemId).getAttribute("data-title-item");
					BX("div_"+cur_title_item).style.display = "block";

					for(var i = 0, l = this.arFavouriteAll.length; i < l; i++)
					{
						if (this.arFavouriteAll[i] == menuItemId)
						{
							this.arFavouriteAll.splice(i,1);
						}
					}
					for(i = 0, l = this.arFavouriteShowAll.length; i < l; i++)
					{
						if (this.arFavouriteShowAll[i] == menuItemId)
						{
							this.arFavouriteShowAll.splice(i,1);
						}
					}

					var curOtherHiddenItems = BX.findChildren(BX("hidden_items_ul_"+cur_title_item), {tagName:"li"}, true);
					var otherHiddenExist = false;
					for (i=0; i<curOtherHiddenItems.length; i++)
					{
						if (curOtherHiddenItems[i].style.display != "none")
						{
							otherHiddenExist = true;
							break;
						}
					}
					if (otherHiddenExist)
					{
						BX("more_btn_"+cur_title_item).style.display = "block";
						BX("separator_"+cur_title_item).style.display = "block";
					}
					//favorite block
					var otherHiddenItems = BX.findChildren(BX("hidden_items_ul_"+oldTitleItem), {tagName:"li"}, true);
					if (otherHiddenItems.length <= 0)
					{
						BX("more_btn_"+oldTitleItem).style.display = "none";
						BX("separator_"+oldTitleItem).style.display = "none";
					}
				}
			}, this)
		});
	};

	MenuClass.prototype.hideItem = function(menuItemId, titleItem)
	{
		for(var i = 0, l = this.arFavouriteShowAll.length; i < l; i++)
		{
			if (this.arFavouriteShowAll[i] == menuItemId)
			{
				this.arFavouriteShowAll.splice(i,1);
			}
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "hide",
				menu_item_id : menuItemId,
				title_item_id : titleItem,
				all_show_items : this.arFavouriteShowAll
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(BX(menuItemId));
				}
				else
				{
					BX("separator_"+titleItem).style.display = "block";
					BX(menuItemId).setAttribute("data-status", "hide");
					var cloneObj = BX.clone(BX(menuItemId));
					BX.remove(BX(menuItemId));
					BX("hidden_items_ul_"+titleItem).appendChild(cloneObj);
					BX("more_btn_"+titleItem).style.display = "block";

					if (BX(menuItemId).getAttribute("data-counter-id"))
					{
						this.hiddenCounters.push(BX(menuItemId).getAttribute("data-counter-id"));
						var curSumCounters = 0;
						for (var i=0; i<this.hiddenCounters.length; i++)
						{
							curSumCounters+= +(this.allCounters[this.hiddenCounters[i]]);
						}

						BX("menu-hidden-counter").innerHTML = curSumCounters > 50 ? "50+" : curSumCounters;
						if (curSumCounters > 0)
							BX("menu-hidden-counter").style.display = "inline-block";
					}
				}
			}, this)
		});
	};

	MenuClass.prototype.showItem = function(menuItemId, titleItem)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "show",
				menu_item_id : menuItemId,
				title_item_id : titleItem
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(BX(menuItemId));
				}
				else
				{
					this.arFavouriteShowAll.push(menuItemId);
					BX(menuItemId).setAttribute("data-status", "show");
					BX("ul_"+titleItem).insertBefore(BX(menuItemId), BX("separator_"+titleItem));
					BX(menuItemId).onbxdragstart = BX.proxy(this.menuItemDragStart, this);
					BX(menuItemId).onbxdrag = BX.proxy(this.menuItemDragMove, this);
					BX(menuItemId).onbxdragstop = BX.proxy(this.menuItemDragStop, this);
					BX(menuItemId).onbxdraghover = BX.proxy(this.menuItemDragHover, this);
					jsDD.registerDest(BX(menuItemId));
					jsDD.registerObject(BX(menuItemId));

					var otherHiddenItems = BX.findChildren(BX("hidden_items_ul_"+titleItem), {tagName:"li"}, true);
					if (otherHiddenItems.length <= 0)
					{
						BX("more_btn_"+titleItem).style.display = "none";
						BX("separator_"+titleItem).style.display = "none";
					}

					if (BX(menuItemId).getAttribute("data-counter-id"))
					{
						for(var i = 0, l = this.hiddenCounters.length; i < l; i++)
						{
							if (this.hiddenCounters[i] == BX(menuItemId).getAttribute("data-counter-id"))
								this.hiddenCounters.splice(i,1);
						}

						var curSumCounters = 0;
						for (i=0; i<this.hiddenCounters.length; i++)
						{
							curSumCounters+= +(this.allCounters[this.hiddenCounters[i]]);
						}

						BX("menu-hidden-counter").innerHTML = curSumCounters > 50 ? "50+" : curSumCounters;
						if (curSumCounters <= 0)
							BX("menu-hidden-counter").style.display = "none";

					}
				}
			}, this)
		});
	};

	MenuClass.prototype.sortItems = function(arTitleItems, titleItem)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "sort_items",
				title_item_id : titleItem,
				all_title_items : arTitleItems
			},
			onsuccess: BX.proxy(function(json)
			{
			}, this)
		});
	};

	MenuClass.prototype.sortSections = function(arSections)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "sort_sections",
				all_sections : arSections
			},
			onsuccess: BX.proxy(function(json)
			{
			}, this)
		});
	};

	//drag&drop
	MenuClass.prototype.menuItemDragStart = function()
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		this.bxparent = dragElement.parentNode;
		this.objHeight = 36;//dragElement.offsetHeight;

		BX.addClass(dragElement, "menu-item-draggable");

		this.bxblank = this.bxparent.insertBefore(BX.create('DIV', {style: {height: '0px'}}), dragElement);
		this.bxblank1 = BX.create('DIV', {style: {height: this.objHeight+'px'}}); //empty div
		jsDD.disableDest(this.bxparent);

		this.bxcp = BX.create('DIV', {             //div to move
			attrs:{className: "menu-draggable-wrap"},
			children: [dragElement]
		});
		this.bxpos = BX.pos(this.bxparent);

		var liObj = BX.findChildren(this.bxparent, {tagName:"li"}, true);

		var countObj = 0;
		var isHiddenSection = false;
		for (var i=0; i<liObj.length; i++)
		{
			if (liObj[i].id == "separator_"+dragElement.getAttribute("data-title-item"))
				break;
			if (liObj[i].id == "empty_li_"+dragElement.getAttribute("data-title-item"))
				continue;
			if (liObj[i].style.display == "none")
				continue;
			countObj++;
		}
		this.countObj = countObj > 0 ? countObj : 0;

		this.bxparent.style.position = 'relative';
		this.bxparent.appendChild(this.bxcp);
	};

	MenuClass.prototype.menuItemDragMove = function(x, y)
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		y -= this.bxpos.top;

		if (this.isBitrix24 && (dragElement.getAttribute("data-title-item") == "menu-favorites" || dragElement.getAttribute("data-title-item") == "menu-groups") && y<this.objHeight)
			y = this.objHeight;
		else if (y < 0)
			y = 0;
		if (y > this.countObj*this.objHeight)
			y = this.countObj*this.objHeight;

		this.bxcp.style.top = y + 'px';
	};

	MenuClass.prototype.menuItemDragHover = function(dest, x, y)
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		if (
			BX.hasClass(dragElement, "menu-items-title")
				|| BX.hasClass(dest, "menu-items-title")
				|| BX.hasClass(dest, "menu-items-block")
			)
			return;

		y -= this.bxpos.top;

		if (dest == dragElement)
		{
			this.bxparent.insertBefore(this.bxblank1, this.bxblank);
		}
		else if (dest.parentNode == this.bxparent)
		{

			if (this.bxparent.parentNode.id == dest.parentNode.parentNode.id)  //li is hovered
			{
				if (dest.nextSibling)
					this.bxparent.insertBefore(this.bxblank1, dest.nextSibling);
				else
					this.bxparent.appendChild(this.bxblank1);
			}
		}
	};

	MenuClass.prototype.menuItemDragStop = function()
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		BX.removeClass(dragElement, "menu-item-draggable");
		if (this.bxblank1 && this.bxblank1.parentNode == this.bxparent)
		{
			this.bxparent.replaceChild(dragElement, this.bxblank1);

			var arTitleItems = [];
			var liObj = BX.findChildren(dragElement.parentNode, {tagName:"li"}, true);
			for (var i=0; i<liObj.length; i++)
			{
				if (liObj[i].id == "empty_li_"+dragElement.getAttribute("data-title-item"))
					continue;

				if (liObj[i].id == "separator_"+dragElement.getAttribute("data-title-item"))
					break;

				arTitleItems.push(liObj[i].id);
			}

			this.sortItems(arTitleItems, dragElement.getAttribute("data-title-item"));
		}
		else
		{
			this.bxparent.replaceChild(dragElement, this.bxblank);
		}
		BX.remove(this.bxcp);
		BX.remove(this.bxblank);
		BX.remove(this.bxblank1);

		jsDD.enableDest(dragElement);
		this.bxparent.style.position = 'static';

		this.bxcp = null;
		this.bxpos = null;
		this.bxparent = null;
		this.bxblank = null;
		this.bxblank1 = null;
		jsDD.refreshDestArea();
	};

	//sections drag&drop
	MenuClass.prototype.sectionDragStart = function()
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		this.bxSectParent = dragElement.parentNode.parentNode;
		this.bxSectParentHeight = dragElement.parentNode.parentNode.offsetHeight;
		this.objSectHeight = dragElement.parentNode.offsetHeight;

		this.bxSectBlank = this.bxSectParent.insertBefore(BX.create('DIV', {style: {height: '0px'}}), dragElement.parentNode);
		this.bxSectBlank1 = BX.create('DIV', {style: {height: this.objSectHeight+"px"}}); //empty div
		jsDD.disableDest(this.bxSectParent);

		this.bxSectBlock = BX.create('DIV', {             //div to move
			style: {
				position: 'absolute',
				zIndex: '100',
				height:this.objSectHeight-14+"px",
				width:dragElement.parentNode.offsetWidth+"px",
				paddingTop: "10px",
				borderRadius:"3px",
				backgroundColor: 'rgba(206, 218, 220, .9)'
			},
			children: [dragElement.parentNode]
		});

		this.bxSectPos = BX.pos(this.bxSectParent);

		this.bxSectParent.style.position = 'relative';
		this.bxSectParent.appendChild(this.bxSectBlock);
	};

	MenuClass.prototype.sectionDragMove = function(x, y)
	{
		if (!this.isEditMode())
			return;

		y -= this.bxSectPos.top;

		if (y < 0)
			y = 0;

		if (y > this.bxSectParentHeight)
			y = this.bxSectParentHeight;

		this.bxSectBlock.style.top = y + 'px';
	};

	MenuClass.prototype.sectionDragHover = function(dest, x, y)
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		if (
			BX.hasClass(dragElement, "menu-item-block")
				|| BX.hasClass(dest, "menu-item-block")
				|| BX.hasClass(dest, "menu-items-empty-li")
			)
			return;

		if (dest == dragElement.parentNode)
		{
			this.bxSectParent.insertBefore(this.bxSectBlank1, this.bxSectBlank);
		}
		else
		{
			if (dest.nextSibling)
				this.bxSectParent.insertBefore(this.bxSectBlank1, dest.nextSibling);
			else
				this.bxSectParent.appendChild(this.bxSectBlank1);
		}
	};

	MenuClass.prototype.sectionDragStop = function()
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		if (this.bxSectBlank1 && this.bxSectBlank1.parentNode == this.bxSectParent)
		{
			this.bxSectParent.replaceChild(dragElement.parentNode, this.bxSectBlank1);

			var arSectionItems = [];
			var sectionsObj = BX.findChildren(dragElement.parentNode.parentNode, {className:"menu-items-title"}, true);
			for (var i=0; i<sectionsObj.length; i++)
			{
				arSectionItems.push(sectionsObj[i].id);
			}
			this.sortSections(arSectionItems, dragElement.getAttribute("data-title-item"));
		}
		else
		{
			this.bxSectParent.replaceChild(dragElement.parentNode, this.bxSectBlank);
		}
		BX.remove(this.bxSectBlock);
		BX.remove(this.bxSectBlank);
		BX.remove(this.bxSectBlank1);

		jsDD.enableDest(dragElement);

		this.bxSectBlock = null; this.bxSectBlank = null; this.bxSectBlank1 = null; this.bxSectParent = null;
		jsDD.refreshDestArea();
	};

	MenuClass.prototype.clearCompositeCache = function()
	{
		BX.ajax.post(
			this.ajaxPath,
			{
				sessid : BX.bitrix_sessid(),
				action : "clear"
			},
			function(result) {

			}
		);
	};

	MenuClass.highlight = function(url)
	{
		var menu = BX("bx_b24_menu");
		if (!BX.type.isNotEmptyString(url) || !menu)
		{
			return false;
		}

		var items = menu.getElementsByTagName("a");
		var curSelectedItem = -1;
		var curSelectedLen = -1;
		var curSelectedUrl = null;
		for (var i = 0, length = items.length; i < length; i++)
		{
			var itemUrl = items[i].getAttribute("href");
			if (!BX.type.isNotEmptyString(itemUrl))
			{
				continue;
			}

			if (url.indexOf(itemUrl) === 0)
			{
				var newLength = itemUrl.length;
				if (newLength > curSelectedLen)
				{
					curSelectedItem = i;
					curSelectedUrl = itemUrl;
					curSelectedLen = newLength;
				}
			}
		}

		var li = items[curSelectedItem].parentNode;
		if (curSelectedUrl == "/" && curSelectedUrl == url)
		{
			BX.addClass(li, "menu-item-active");
		}
		else if (curSelectedUrl !== null && curSelectedUrl != "/")
		{
			BX.addClass(li, "menu-item-active");
		}

		//Show hidden item
		var moreItem = li.parentNode.parentNode;
		if (BX.hasClass(moreItem, "menu-item-favorites-more") &&
			!BX.hasClass(moreItem, "menu-item-favorites-more-open"))
		{
			var id = BX.firstChild(moreItem.parentNode.parentNode).getAttribute("id");
			MenuClass.prototype.showHideMoreItems(BX("more_btn_" + id), id);
		}

		return true;
	};

	return MenuClass;

})();





