import Item from './item';
export default class ItemComment extends Item
{
	static checkForPaternity(data)
	{
		return data['TYPE_CODE'] === 'COMMENT';
	}
}