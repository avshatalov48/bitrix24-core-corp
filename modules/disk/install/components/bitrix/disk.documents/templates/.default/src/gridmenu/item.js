import {BaseEvent, EventEmitter} from 'main.core.events';
import {Menu, MenuItem} from 'main.popup';
import {Loader} from 'main.loader';

export default class Item extends EventEmitter
{
	trackedObjectId: Number;
	objectId: ?Number;
	data: Object = {};
	popupMenuItem: ?MenuItem;

	constructor(trackedObjectId, itemData)
	{
		super();
		this.setEventNamespace('Disk:Documents:');
		this.trackedObjectId = trackedObjectId;
		this.data = Object.assign({}, itemData);
		this.data['className'] = (this.data['className'] || '') + ' disk-folder-list-context-menu-item';
		if (!this.data['text'])
		{
			this.data['text'] = this.data['id'];
		}
		this.objectId = this.data['objectId'];
		delete this.data['objectId'];
	}

	getData(key:?string)
	{
		if (key)
		{
			return this.data[key];
		}
		return this.data;
	}

	showError(errors)
	{
		console.log('errors: ', errors);
	}

	addPopupMenuItem(popupMenu: Menu)
	{
		this.popupMenuItem = popupMenu.addMenuItem(this.data);
	}

	showLoad()
	{
		if (this.popupMenuItem)
		{
			this.loader = (this.loader || new Loader({target: this.popupMenuItem.getContainer(), size: 32}));
			this.loader.show();
		}
	}

	hideLoad()
	{
		if (this.loader)
		{
			this.loader.hide();
		}
	}
	static detect(itemData)
	{
		return true;
	}
}
