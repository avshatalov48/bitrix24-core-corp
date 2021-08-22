import {Type, Event, Loc} from 'main.core';

export class SelfItem
{
	constructor(parent)
	{
		this.parent = parent;
	}

	showSelfItemPopup(bindElement, itemInfo)
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

		var button;
		BX.PopupWindowManager.create("menu-self-item-popup", bindElement, {
			closeIcon: true,
			offsetTop: 1,
			offsetLeft: 20,
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
							var form = document.forms["menuAddToFavoriteForm"];
							var textField = form.elements["menuPageToFavoriteName"];
							var linkField = form.elements["menuPageToFavoriteLink"];
							var openNewTab = form.elements["menuOpenInNewPage"].checked;

							var text = BX.util.trim(textField.value);
							var link = this.parent.refineUrl(linkField.value);

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
								BX.addClass(button.buttonNode, "popup-window-button-wait");

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
									itemNewInfo
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
	}

	saveSelfItem(mode, itemData)
	{
		BX.ajax.runAction(`intranet.leftmenu.${mode === "edit" ? "update" : "add"}SelfItem`, {
			data: {
				itemData: itemData
			},
			analyticsLabel: {
				analyticsLabel: 'selfItem'
			}
		}).then((response) => {

			var itemParams = {
				text: itemData.text,
				link: itemData.link,
				type: "self",
				openInNewPage: itemData.openInNewPage === "Y" ? "Y" : "N"
			};

			if (mode === "add" && response.data.hasOwnProperty("itemId"))
			{
				itemParams.id = response.data.itemId;
				this.parent.generateItemHtml(itemParams);
			}
			else if (mode === "edit")
			{
				itemParams.id = itemData.id;
				this.parent.updateItemHtml(itemParams);
			}

			BX.PopupWindowManager.getCurrentPopup().destroy();

		}, (response) => {

			this.parent.showConfirmWindow({
				alertMode: true,
				titleBar: BX.message("MENU_ERROR_OCCURRED"),
				content: response.errors[0].message
			});
		});
	}

	deleteSelfItem(itemId)
	{
		var itemNode = BX("bx_left_menu_" + itemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		if (itemNode.getAttribute("data-delete-perm") === "A") //delete from all
		{
			this.parent.allItemObj.deleteItemFromAll(itemId);
		}

		BX.ajax.runAction('intranet.leftmenu.deleteSelfItem', {
			data: {
				menuItemId: itemId
			},
		}).then((response) => {
			BX.remove(itemNode);
		}, (response) => {
			this.parent.showError(itemNode);
		});
	}
}