import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import ValueChange from '../components/content-blocks/value-change';

export class Modification extends Base
{
	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			ValueChange,
		};
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Modification');
	}
}
