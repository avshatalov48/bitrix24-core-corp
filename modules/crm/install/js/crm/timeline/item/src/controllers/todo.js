import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import {ButtonState} from 'ui.buttons';

export class ToDo extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType} = actionParams;
		if (actionType !== 'jsEvent')
		{
			return;
		}
		if (action === 'EditableDescription:StartEdit')
		{
			item.highlightContentBlockById('description', true);
		}
		if (action === 'EditableDescription:FinishEdit')
		{
			item.highlightContentBlockById('description', false);
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:ToDo');
	}
}
