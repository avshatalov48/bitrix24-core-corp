export class StandartItem
{
	constructor(parent)
	{
		this.parent = parent;
	}

	addStandardItem(params)
	{
		var itemInfo = params.itemInfo;
		var startX = params.startX;
		var startY = params.startY;
		var useAnimation = (!params.context || params.context === top.window);

		var isCurrentPage = false;

		if (typeof itemInfo !== "object")
		{
			isCurrentPage = true;

			this.parent.checkCurrentPageInTopMenu();

			if (this.parent.isCurrentPageStandard && BX.type.isDomNode(this.parent.topMenuSelectedNode))
			{
				var menuNodeCoord = this.parent.topMenuSelectedNode.getBoundingClientRect();
				startX = menuNodeCoord.left;
				startY = menuNodeCoord.top;

				itemInfo = {
					id: this.parent.topItemSelectedObj.DATA_ID,
					text: this.parent.topItemSelectedObj.TEXT,
					link: BX.type.isNotEmptyString(this.parent.currentPagePath) ? this.parent.currentPagePath : this.parent.topItemSelectedObj.URL,
					counterId: this.parent.topItemSelectedObj.COUNTER_ID,
					counterValue: this.parent.topItemSelectedObj.COUNTER,
					isStandardItem: true,
					subLink: this.parent.topItemSelectedObj.SUB_LINK
				};
			}
			else
			{
				var pageTitle = BX.type.isNotEmptyString(params.pageTitle) ? params.pageTitle : document.getElementById('pagetitle').innerText;
				var pageLink = '';

				if (BX.type.isNotEmptyString(params.pageLink))
				{
					pageLink = params.pageLink;
				}
				else
				{
					pageLink = BX.type.isNotEmptyString(this.parent.currentPagePath) ? this.parent.currentPagePath : document.location.pathname + document.location.search;
				}

				itemInfo = {
					text: pageTitle,
					link: pageLink,
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

		BX.ajax.runAction('intranet.leftmenu.addStandartItem', {
			data: {
				itemData: itemInfo
			},
		}).then((response) => {

			if (response.data.hasOwnProperty("itemId"))
			{
				itemInfo.id = response.data.itemId;

				BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemAdded", [itemInfo, this]);

				this.animateTopItemToLeft(itemInfo, startX, startY, useAnimation);

				this.onStandardItemChangedSuccess({
					isCurrentPage: isCurrentPage,
					isActive: true,
					context: params.context,
				});

				this.parent.isCurrentPageInLeftMenu = true;
			}

		}, (response) => {

			this.parent.showConfirmWindow({
				alertMode: true,
				titleBar: BX.message("MENU_ERROR_OCCURRED"),
				content: response.errors[0].message
			});
		});
	}

	deleteStandardItem(params)
	{
		var itemId = params.itemId;
		var useAnimation = (!params.context || params.context === top.window);

		var itemData = {};
		this.parent.checkCurrentPageInTopMenu();

		if (itemId && BX.type.isDomNode(BX("bx_left_menu_" + itemId)))
		{
			itemData = {
				id: itemId
			};
		}
		else if (this.parent.isCurrentPageStandard && this.parent.topItemSelectedObj.DATA_ID)
		{
			itemData = {
				id: this.parent.topItemSelectedObj.DATA_ID
			};
		}
		else
		{
			itemData = {
				link: (BX.type.isNotEmptyString(params.pageLink) ? params.pageLink : document.location.pathname + document.location.search),
			};
		}

		BX.ajax.runAction('intranet.leftmenu.deleteStandartItem', {
			data: {
				itemData: itemData,
			},
		}).then((response) => {

			if (response.data.hasOwnProperty("itemId"))
			{
				BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [response.data, this]);

				var itemNode = BX("bx_left_menu_" + response.data.itemId);
				if (!BX.type.isDomNode(itemNode))
					return;

				if (itemNode.getAttribute("data-delete-perm") === "A") //delete from all
				{
					this.parent.allItemObj.deleteItemFromAll(response.data.itemId);
				}

				this.onStandardItemChangedSuccess({
					isCurrentPage: !itemId,
					isActive: false,
					context: params.context,
				});

				this.animateTopItemFromLeft("bx_left_menu_" + response.data.itemId, useAnimation);

				this.isCurrentPageInLeftMenu = false;
			}

		}, (response) => {

			this.parent.showConfirmWindow({
				alertMode: true,
				titleBar: BX.message("MENU_ERROR_OCCURRED"),
				content: response.errors[0].message
			});
		});
	}

	updateStandardItem(itemInfo)
	{
		BX.ajax.runAction('intranet.leftmenu.updateStandartItem', {
			data: {
				itemText: itemInfo.text,
				itemId: itemInfo.id,
			},
		}).then((response) => {

			this.parent.updateItemHtml(itemInfo);
			BX.PopupWindowManager.getCurrentPopup().destroy();

		}, (response) => {

			this.parent.showConfirmWindow({
				alertMode: true,
				titleBar: BX.message("MENU_ERROR_OCCURRED"),
				content: response.errors[0].message
			});
		});
	}

	showStandardEditItemPopup(bindElement, itemInfo)
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
	}

	onStandardItemChangedSuccess(params)
	{
		if (params.isCurrentPage)
		{
			BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
				isActive: params.isActive,
				context: params.context,
			}]);
		}
	}

	animateTopItemToLeft(itemInfo, startX, startY, useAnimation)
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

		var finishY = this.parent.menuItemsBlock.getBoundingClientRect().bottom;
		if (this.parent.areMoreItemsShowed())
		{
			finishY -= BX("left-menu-hidden-items-list").offsetHeight;
		}

		if (!useAnimation)
		{
			BX.remove(topMenuNode);
			itemInfo.type = "standard";
			this.isCurrentPageInLeftMenu = true;
			this.parent.generateItemHtml(itemInfo);
			this.parent.saveItemsSort({type: 'standard'});

			return;
		}

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
						this.parent.generateItemHtml(itemInfo);
						this.parent.saveItemsSort({type: 'standard'});

					}, this)
				})).animate();
			}, this)
		})).animate();
	}

	animateTopItemFromLeft(itemId, useAnimation)
	{
		if (!BX.type.isDomNode(BX(itemId)))
		{
			return;
		}

		if (!useAnimation)
		{
			BX.remove(BX(itemId));
			this.isCurrentPageInLeftMenu = false;
			this.parent.saveItemsSort({type: 'standard'});

			return;
		}

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
				this.parent.isCurrentPageInLeftMenu = false;
				this.parent.saveItemsSort({type: 'standard'});
			}, this)
		})).animate();
	}
}