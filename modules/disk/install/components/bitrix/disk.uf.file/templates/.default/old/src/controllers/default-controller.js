import {Cache} from 'main.core';
import {EventEmitter} from "main.core.events";

export default class DefaultController  extends EventEmitter {
	hasContainer: boolean = false;
	container: Element;
	eventObject: Element;
	cache = new Cache.MemoryCache();
	properties = {
		pluggedIn: false,
	};

	constructor({container, eventObject})
	{
		super();
		this.setEventNamespace('Disk:UF:');
		this.container = container;
		this.eventObject = eventObject;
		this.properties.pluggedIn = this.eventObject && this.eventObject.dataset.bxHtmlEditable === 'Y';
		if (!this.container)
		{
			return;
		}

		this.hasContainer = true;
	}

	isRelevant()
	{
		return this.hasContainer;
	}

	getEventObject()
	{
		return this.eventObject;
	}

	getContainer()
	{
		return this.container;
	}

	isPluggedIn(): boolean
	{
		return this.properties.pluggedIn;
	}

	show()
	{
		this.container.style.display = '';
		delete this.container.style.display;
	}

	hide()
	{
		this.container.style.display = 'none';
	}
}
