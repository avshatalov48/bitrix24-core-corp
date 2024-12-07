import Item from './item';
import ItemAdminShared from './item-admin-shared';
import ItemAdminCustom from './item-admin-custom';
import ItemUserFavorites from './item-user-favorites';
import ItemUserSelf from './item-user-self';
import ItemSystem from './item-system';

const itemMappings = [
	Item,
	ItemAdminShared,
	ItemUserFavorites,
	ItemAdminCustom,
	ItemUserSelf,
	ItemSystem,
];

export default function getItem(itemData: Element): Item
{
	let itemClassName = Item;
	itemMappings.forEach((itemClass) => {
		if (itemClass.detect(itemData))
		{
			itemClassName = itemClass;
		}
	});
	return itemClassName;
}