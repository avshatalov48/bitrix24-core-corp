import { Type } from 'main.core';
import Context from './context';
import Item from './item';
import Call from './items/call';
import Comment from './items/comment';
import Delivery from './items/delivery';
import EInvoiceApp from './items/einvoice-app';
import Email from './items/email';
import GoToChat from './items/gotochat/gotochat';
import Market from './items/market';
import Meeting from './items/meeting';
import RestPlacementWithLayout from './items/restplacement/withlayout';
import RestPlacementWithSlider from './items/restplacement/withslider';
import Sharing from './items/sharing';
import Sms from './items/sms/sms';
import Whatsapp from './items/sms/whatsapp';
import Task from './items/task';
import ToDo from './items/todo/todo';
import Visit from './items/visit';
import Wait from './items/wait';
import Zoom from './items/zoom';

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
			case 'whatsapp':
				item = new Whatsapp();
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
			case 'einvoice_app_installer':
				item = new EInvoiceApp();
				break;
			default:
				item = null;
		}

		if (!item && id.startsWith('activity_rest_'))
		{
			if (Type.isPlainObject(settings) && Type.isBoolean(settings.useBuiltInInterface) && settings.useBuiltInInterface)
			{
				item = new RestPlacementWithLayout();
			}
			else
			{
				item = new RestPlacementWithSlider();
			}
		}

		if (item)
		{
			item.initialize(context, settings);
		}

		return item;
	}
}
