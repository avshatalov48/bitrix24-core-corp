import Item from './item';
import ItemImage from './item-image';

const itemMappings = [
	Item,
	ItemImage,
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
	return new itemClassName(itemData);
}