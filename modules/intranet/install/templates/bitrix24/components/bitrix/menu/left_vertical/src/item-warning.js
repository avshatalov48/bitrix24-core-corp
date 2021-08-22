export class ItemWarning
{
	constructor(parent)
	{
		this.parent = parent;
	}

	showItemWarning(options)
	{
		options = BX.type.isPlainObject(options) ? options : {};

		var itemId = BX.type.isNotEmptyString(options.itemId) ? options.itemId : "";
		var itemNode = BX("bx_left_menu_" + itemId);
		if (!BX.type.isDomNode(itemNode))
		{
			return;
		}

		this.removeItemWarning(itemId);

		var warningNode = BX.create(
			'a',
			{
				props: {
					className: "menu-post-warn-icon"
				},
				attrs: {
					title: BX.type.isNotEmptyString(options.title) ? options.title : "",
				},
				events: BX.type.isNotEmptyObject(options.events) ? options.events : {}
			}
		);

		var link = itemNode.querySelector(".menu-item-link");
		if (link)
		{
			BX.addClass(itemNode, "menu-item-warning-state");
			link.appendChild(warningNode);
		}
	}

	removeItemWarning(itemId)
	{
		var itemNode = BX("bx_left_menu_" + itemId);
		if (!BX.type.isDomNode(itemNode))
		{
			return;
		}

		var warningNode = itemNode.querySelector(".menu-post-warn-icon");
		if (warningNode)
		{
			BX.remove(warningNode);
		}

		BX.removeClass(itemNode, "menu-item-warning-state");
	}
}