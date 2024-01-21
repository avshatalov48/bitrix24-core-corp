import Context from './context';
import Item from './item';
import Call from './items/call';
import Comment from './items/comment';
import Delivery from './items/delivery';
import Email from './items/email';
import GoToChat from './items/gotochat/gotochat';
import Market from './items/market';
import Meeting from './items/meeting';
import RestPlacement from './items/restplacement';
import Sharing from './items/sharing';
import Sms from './items/sms';
import Task from './items/task';
import ToDo from './items/todo';
import Visit from './items/visit';
import Wait from './items/wait';
import Zoom from './items/zoom';

export default class Factory
{
	static createItem(id: String, context: Context, settings: ?Object): Item
	{
		let item;

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
			case 'gotochat':
				item = new GoToChat();
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
			default:
				item = null;
		}

		if (!item && id.startsWith('activity_rest_'))
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
