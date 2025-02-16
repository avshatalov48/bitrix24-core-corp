import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import ValueChange from '../components/content-blocks/value-change';
import ValueChangeItem from '../components/content-blocks/value-change-item';

export class Modification extends Base
{
	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			ValueChange,
			ValueChangeItem,
		};
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Modification')
			|| (item.getType() === 'TasksTaskModification')
			|| (item.getType() === 'RestartAutomation')
			;
	}
}
