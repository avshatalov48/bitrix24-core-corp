export class AllItem
{
	constructor(parent)
	{
		this.parent = parent;
	}

	addItemToAll(menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		var itemLink = itemNode.getAttribute("data-link"),
			itemTextNode = itemNode.querySelector("[data-role='item-text']"),
			itemText = itemTextNode.innerText,
			itemCounterId = itemNode.getAttribute("data-counter-id"),
			itemLinkNode = BX.findChild(itemNode, {tagName: "a"}, true, false),
			openInNewPage = BX.type.isDomNode(itemLinkNode) && itemLinkNode.hasAttribute("target") && itemLinkNode.getAttribute("target") === "_blank";

		BX.ajax.runAction('intranet.leftmenu.addItemToAll', {
			data: {
				itemInfo: {
					id: menuItemId,
					link: itemLink,
					text: itemText,
					counterId: itemCounterId,
					openInNewPage: openInNewPage ? "Y" : "N"
				}
			}
		}).then((response) => {

			itemNode.setAttribute("data-delete-perm", "A");
			this.parent.showMessage(itemNode, BX.message("MENU_ITEM_WAS_ADDED_TO_ALL"));

		}, (response) => {

			this.parent.showError(itemNode);
		});
	}

	deleteItemFromAll(menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		BX.ajax.runAction('intranet.leftmenu.deleteItemFromAll', {
			data: {
				menu_item_id: menuItemId
			}
		}).then((response) => {

			itemNode.setAttribute("data-delete-perm", "Y");
			this.parent.showMessage(itemNode, BX.message("MENU_ITEM_WAS_DELETED_FROM_ALL"));

		}, (response) => {

			this.parent.showError(itemNode);
		});
	}

	deleteCustomItemFromAll(menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		var itemType = itemNode.getAttribute("data-type");

		if (itemType !== "custom")
			return;

		BX.ajax.runAction('intranet.leftmenu.deleteCustomItemFromAll', {
			data: {
				menu_item_id: menuItemId
			}
		}).then((response) => {

			BX.remove(itemNode);
		}, (response) => {

			this.parent.showError(itemNode);
		});
	}
}