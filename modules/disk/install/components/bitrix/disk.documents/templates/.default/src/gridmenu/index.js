import Item from './item';
import ItemOpen from './itemopen';
import ItemShareSection from './itemsharesection';
import ItemInternalLink from './iteminternallink';
import ItemExternalLink from './itemexternallink';
import ItemRename from './itemrename';
import ItemSharing from "./item-sharing";
import ItemDelete from "./item-delete";

const itemMappings = [
	ItemOpen,
	ItemShareSection,
	ItemSharing,
	ItemInternalLink,
	ItemExternalLink,
	ItemRename,
	ItemDelete,
];

export default function getMenuItem(trackedObjectId, itemData)
{
	let itemClassName = Item;
	itemMappings.forEach((itemClass) => {
		if (itemClass.detect(itemData))
		{
			itemClassName = itemClass;
		}
	});

	return new itemClassName(trackedObjectId, itemData);
}