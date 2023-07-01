import Item from './item';
import ToDo from './items/todo';
import Comment from './items/comment';
import Sms from './items/sms';
import Call from './items/call';
import Email from './items/email';
import Meeting from './items/meeting';
import Task from './items/task';
import Sharing from './items/sharing';
import Wait from './items/wait';
import Zoom from './items/zoom';
import Delivery from './items/delivery';
import Visit from './items/visit';
import RestPlacement from './items/restplacement';
import Market from './items/market';
import Context from './context';

export default class Factory
{
	static createItem(id: String, context: Context, settings: ?Object): Item
	{
		let item = null;
		switch (id)
		{
			case 'todo':
				item = new ToDo();
				break;
			case 'comment':
				item = new Comment();
				break;
			case 'sms':
				item = new Sms();
				break;
			case 'call':
				item = new Call();
				break;
			case 'email':
				item = new Email();
				break;
			case 'meeting':
				item = new Meeting();
				break;
			case 'task':
				item = new Task();
				break;
			case 'sharing':
				item = new Sharing();
				break;
			case 'wait':
				item = new Wait();
				break;
			case 'zoom':
				item = new Zoom();
				break;
			case 'delivery':
				item = new Delivery();
				break;
			case 'visit':
				item = new Visit();
				break;
			case 'activity_rest_applist':
				item = new Market();
				break;
		}

		if (!item && id.match(/^activity_rest_/))
		{
			item = new RestPlacement();
		}

		if (item)
		{
			item.initialize(context, settings);
		}

		return item;
	}
}
