import Item from './item';

export default class ItemSystem extends Item
{
	static code = 'default';

	canDelete(): boolean
	{
		return false;
	}
}
