import Item from './item';
import ItemAdminShared from './item-admin-shared';
import ItemAdminCustom from './item-admin-custom';
import ItemUserFavorites from './item-user-favorites';
import ItemUserSelf from './item-user-self';
import ItemSystem from './item-system';
import ItemGroup from './item-group';
import ItemGroupSystem from './item-group-system';

const itemMappings = [
	Item,
	ItemAdminShared,
	ItemUserFavorites,
	ItemAdminCustom,
	ItemUserSelf,
	ItemSystem,
	ItemGroup,
	ItemGroupSystem,
];

export default function getItem(itemData): Item
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