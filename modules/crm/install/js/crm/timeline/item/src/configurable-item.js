import { ajax, Dom, Text, Type } from 'main.core';
import TimelineItem from './item';
import { Item } from './components/item';
import Layout from './layout';
import { BitrixVue } from 'ui.vue3';
import { Base } from "./controllers/base";
import ControllerManager from './controller-manager';
import { DatetimeConverter } from 'crm.timeline.tools';
import { StreamType } from 'crm.timeline.item';

declare type ConfigurableItemParams = {
	timelineId: string,
	container: HTMLElement,
	itemClassName: string,
	useShortTimeFormat: boolean,
	isReadOnly: boolean,
	currentUser: ?Object,
	ownerTypeId: number,
	ownerId: number,
	data: ConfigurableItemData,
	streamType: number;
};

declare type ConfigurableItemData = {
	type: string,
	timestamp: ?Number,
	sort: ?Array,
	layout: ?Object,
	payload: ?Object,
}

export default class ConfigurableItem extends TimelineItem
{
	#container: HTMLElement = null;
	#itemClassName: string = null;
	#type: string = null;
	#dataPayload: ?Object = null;
	#timelineId: string = null;
	#timestamp: number = null;
	#sort: Array = null;
	#useShortTimeFormat: boolean = false;
	#isReadOnly: boolean = false;
	#currentUser: ?Object = null;
	#ownerTypeId: number = null;
	#ownerId: number = null;
	#controllers: Base[] = null;
	#layoutComponent: ?Object = null;
	#layoutApp: ?Object = null;
	#layout: ?Layout = null;
	#streamType: number = null;

	initialize(id, settings: ConfigurableItemParams): void
	{
		this._setId(id);
		settings = settings || {};
		this.#timelineId = settings.timelineId || '';
		this.setContainer(settings.container || null);
		this.#itemClassName = settings.itemClassName || '';

		if (Type.isPlainObject(settings.data))
		{
			this.setData(settings.data);

			this.#useShortTimeFormat = settings.useShortTimeFormat || false;
			this.#isReadOnly = settings.isReadOnly || false;
			this.#currentUser = settings.currentUser || null;
			this.#ownerTypeId = settings.ownerTypeId;
			this.#ownerId = settings.ownerId;
			this.#streamType = settings.streamType || StreamType.history;
		}

		this.#controllers = ControllerManager.getInstance(this.#timelineId).getItemControllers(this);
	}

	setData(data: ConfigurableItemData): void
	{
		this.#type = data.type || null;
		this.#timestamp = data.timestamp || null;
		this.#sort = data.sort || [];
		this.#layout = new Layout(data.layout || {});
		this.#dataPayload = data.payload || {};
	}

	getLayout(): Layout
	{
		return this.#layout;
	}

	getType(): string
	{
		return this.#type;
	}

	getDataPayload(): ?Object
	{
		return this.#dataPayload;
	}

	layout(options): void
	{
		this.setWrapper(Dom.create({tag: 'div', attrs: {className: this.#itemClassName}}));
		this.initLayoutApp(options);
	}

	initWrapper(): HTMLElement
	{
		this.setWrapper(Dom.create({tag: 'div', attrs: {className: this.#itemClassName}}));

		return this._wrapper;
	}

	initLayoutApp(options): void
	{
		this.#initLayoutApp();

		if (this.needBindToContainer(options))
		{
			const bindTo = this.getBindToNode(options);
			if (bindTo && !this.#useAnchorNextSibling(options))
			{
				Dom.insertBefore(this.getWrapper(), bindTo);
			}
			else if (bindTo && bindTo.nextSibling)
			{
				Dom.insertBefore(this.getWrapper(), bindTo.nextSibling);
			}
			else
			{
				Dom.append(this.getWrapper(), this.#container);
			}
		}

		for (const controller of this.#controllers)
		{
			controller.onAfterItemLayout(this, options);
		}
	}

	needBindToContainer(options): Boolean
	{
		if (Type.isPlainObject(options))
		{
			return BX.prop.getBoolean(options, 'add', true);
		}

		return true;
	}

	getBindToNode(options): ?HTMLElement
	{
		if (Type.isPlainObject(options))
		{
			return Type.isElementNode(options['anchor']) ? options['anchor'] : null;
		}

		return null;
	}

	#useAnchorNextSibling(options): ?HTMLElement
	{
		if (Type.isPlainObject(options))
		{
			return Type.isBoolean(options['useAnchorNextSibling']) ? options['useAnchorNextSibling'] : true;
		}

		return true;
	}

	refreshLayout(): void
	{
		// try to refresh layout via vue reactivity, if possible:
		if (this.#layoutComponent)
		{
			this.#layoutComponent.setLayout(this.getLayout().asPlainObject());
			for (const controller of this.#controllers)
			{
				controller.onAfterItemRefreshLayout(this);
			}
			this.#layoutComponent.showLoader(false);
		}
		else
		{
			super.refreshLayout();
		}
	}

	forceRefreshLayout(): void
	{
		const bindTo = this.getWrapper()?.nextSibling;
		this.clearLayout();
		this.layout({anchor: bindTo, useAnchorNextSibling: false});
	}

	getLayoutContentBlockById(id: string): ?Object
	{
		return this.#layoutComponent.getContentBlockById(id);
	}

	getLogo(): ?Object
	{
		return this.#layoutComponent.getLogo();
	}

	getLayoutFooterButtonById(id: string): ?Object
	{
		return this.#layoutComponent.getFooterButtonById(id);
	}

	getLayoutFooterMenu(): ?Object
	{
		return this.#layoutComponent.getFooterMenu();
	}

	getLayoutHeaderChangeStreamButton(): ?Object
	{
		return this.#layoutComponent.getHeaderChangeStreamButton();
	}

	highlightContentBlockById(blockId: string, isHighlighted: boolean): void
	{
		this.#layoutComponent.highlightContentBlockById(blockId, isHighlighted);
	}

	clearLayout(): void
	{
		for (const controller of this.#controllers)
		{
			controller.onBeforeItemClearLayout(this);
		}
		this.#layoutApp.unmount();
		this.#layoutApp = null;
		this.#layoutComponent = null;
		super.clearLayout();
	}

	getCreatedDate(): Date
	{
		const timestamp = this.#timestamp ? this.#timestamp : Date.now() / 1000;

		return BX.prop.extractDate(DatetimeConverter.createFromServerTimestamp(timestamp).toUserTime().getValue());
	}

	getSourceId(): number
	{
		let id = this.getId();
		if (!Type.isInteger(id))
		{
			// id is like ACTIVITY_12
			id = Text.toInteger(id.replace(/^\D+/g, ''));
		}

		return id;
	}

	#initLayoutApp(): void
	{
		if (!this.#layoutApp)
		{
			this.#layoutApp = BitrixVue.createApp(Item, this.#getLayoutAppProps());

			const contentBlockComponents = this.#getContentBlockComponents();
			for (const componentName in contentBlockComponents)
			{
				this.#layoutApp.component(componentName, contentBlockComponents[componentName]);
			}

			this.#layoutComponent = this.#layoutApp.mount(this.getWrapper());
		}
	}

	#getLayoutAppProps(): Object
	{
		return {
			initialLayout: this.getLayout().asPlainObject(),
			id: String(this.getId()),
			useShortTimeFormat: this.#useShortTimeFormat,
			isReadOnly: this.isReadOnly(),
			currentUser: this.getCurrentUser(),
			streamType: this.#streamType,
			onAction: this.#onLayoutAppAction.bind(this),
		};
	}

	#onLayoutAppAction(eventData: ?Object)
	{
		for (const controller of this.#controllers)
		{
			controller.onItemAction(this, eventData);
		}
	}

	#getContentBlockComponents(): Object
	{
		let components = {};
		for (const controller of this.#controllers)
		{
			components = Object.assign(components, controller.getContentBlockComponents(this));
		}

		return components;
	}

	setContainer(container): void
	{
		this.#container = container;
	}

	getContainer(): HTMLElement
	{
		return this.#container;
	}

	getDeadline(): ?Date
	{
		if (!this.#timestamp)
		{
			return null;
		}

		return DatetimeConverter.createFromServerTimestamp(this.#timestamp).toUserTime().getValue();
	}

	getSort(): Array
	{
		return this.#sort;
	}

	isReadOnly(): boolean
	{
		return this.#isReadOnly;
	}

	getCurrentUser(): ?Object
	{
		return this.#currentUser;
	}

	clone(): ConfigurableItem
	{
		return ConfigurableItem.create(this.getId(), {
			timelineId: this.#timelineId,
			container: this.getContainer(),
			itemClassName: this.#itemClassName,
			useShortTimeFormat: this.#useShortTimeFormat,
			isReadOnly: this.#isReadOnly,
			currentUser: this.#currentUser,
			streamType: this.#streamType,
			data: {
				type: this.#type,
				timestamp: this.#timestamp,
				sort: this.#sort,
				layout: this.getLayout().asPlainObject(),
			},
		});
	}

	reloadFromServer(forceRefreshLayout: boolean = false): Promise
	{
		const data = {
			ownerTypeId: this.#ownerTypeId,
			ownerId: this.#ownerId,
		};
		if (this.#streamType === StreamType.history || this.#streamType === StreamType.pinned)
		{
			data.historyIds = [ this.getId() ];
		}
		else if (this.#streamType === StreamType.scheduled)
		{
			data.activityIds = [ this.getId() ];
		}
		else
		{
			throw new Error('Wrong stream type');
		}

		return ajax.runAction('crm.timeline.item.load', {data})
			.then(response => {
				Object.values(response.data).forEach(item => {
					if (item.id === this.getId())
					{
						this.setData(item);
						if (forceRefreshLayout)
						{
							this.forceRefreshLayout();
						}
						else
						{
							this.refreshLayout();
						}
					}
				});
				return true;
			})
			.catch(err => {
				console.error(err);
				return true;
			});
	}

	static create(id, settings): ConfigurableItem
	{
		const self = new ConfigurableItem();
		self.initialize(id, settings);
		return self;
	}
}
