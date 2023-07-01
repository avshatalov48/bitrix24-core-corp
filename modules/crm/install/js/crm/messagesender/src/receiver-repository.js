import { BaseEvent, EventEmitter } from 'main.core.events';
import { Reflection, Type } from 'main.core';
import { ItemIdentifier } from 'crm.data-structures';
import { Receiver } from './receiver';
import { extractReceivers } from './internal/extract-receivers';
import { ensureIsItemIdentifier, ensureIsReceiver } from './internal/validation';

const OBSERVED_EVENTS = new Set([
	'onCrmEntityCreate',
	'onCrmEntityUpdate',
	'onCrmEntityDelete',
]);

/**
 * @memberOf BX.Crm.MessageSender
 * @mixes EventEmitter
 *
 * @emits BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged
 * @emits BX.Crm.MessageSender.ReceiverRepository:OnItemDeleted
 *
 * Currently, this class is supposed to work only in the context of entity details tab.
 * In the future, it can be extended to work on any page.
 */
export class ReceiverRepository
{
	static #instance: ?ReceiverRepository;

	#onDetailsTabChangeEventHandler: (BaseEvent) => void;

	#storage: {[itemHash: string]: Receiver[]} = {};

	#observedItems: {[entityTypeId: number]: Set<number>} = {};


	static get Instance(): ReceiverRepository
	{
		if ((window.top !== window) && Reflection.getClass('top.BX.Crm.MessageSender.ReceiverRepository'))
		{
			return window.top.BX.Crm.MessageSender.ReceiverRepository;
		}

		if (!ReceiverRepository.#instance)
		{
			ReceiverRepository.#instance = new ReceiverRepository();
		}

		return ReceiverRepository.#instance;
	}

	/**
	 * @internal This class is a singleton. Use Instance getter instead of constructing a new instance
	 */
	constructor()
	{
		if (ReceiverRepository.#instance)
		{
			throw new Error('Attempt to make a new instance of a singleton');
		}

		this.#init();
	}

	#init(): void
	{
		EventEmitter.makeObservable(this, 'BX.Crm.MessageSender.ReceiverRepository');

		this.#onDetailsTabChangeEventHandler = (event: BaseEvent) => {
			if (!(event instanceof BaseEvent))
			{
				console.error('unexpected event type', event);
				return;
			}

			if (!Type.isArrayFilled(event.getData()) || !Type.isPlainObject(event.getData()[0]))
			{
				return;
			}

			this.#onCrmEntityChange(event.getType(), event.getData()[0]);
		};
		this.#onDetailsTabChangeEventHandler = this.#onDetailsTabChangeEventHandler.bind(this);

		for (const eventName of OBSERVED_EVENTS)
		{
			EventEmitter.subscribe(eventName, this.#onDetailsTabChangeEventHandler);
		}
		if (BX.SidePanel?.Instance?.isOpen())
		{
			// we are on entity details slider
			EventEmitter.subscribe('SidePanel.Slider:onDestroy', this.#destroy.bind(this));
		}
	}

	#destroy(): void
	{
		for (const eventName of OBSERVED_EVENTS)
		{
			EventEmitter.unsubscribe(eventName, this.#onDetailsTabChangeEventHandler);
		}
		ReceiverRepository.#instance = null;
	}

	#onCrmEntityChange(eventType: string, {entityTypeId, entityId, entityData}): void
	{
		if (!this.#observedItems[entityTypeId]?.has(entityId))
		{
			return;
		}

		const item = new ItemIdentifier(entityTypeId, entityId);

		if (
			eventType.toLowerCase() === 'onCrmEntityCreate'.toLowerCase()
			|| eventType.toLowerCase() === 'onCrmEntityUpdate'.toLowerCase()
		)
		{
			const oldReceivers = this.#storage[item.hash] ?? [];
			const newReceivers = extractReceivers(item, entityData);

			this.#storage[item.hash] = newReceivers;

			const added = newReceivers.filter(newReceiver => {
				return Type.isNil(oldReceivers.find(oldReceiver => oldReceiver.isEqualTo(newReceiver)));
			});
			const deleted = oldReceivers.filter(oldReceiver => {
				return Type.isNil(newReceivers.find(newReceiver => newReceiver.isEqualTo(oldReceiver)));
			});

			if (added.length > 0 || deleted.length > 0)
			{
				this.emit('OnReceiversChanged', {item, previous: oldReceivers, current: newReceivers, added, deleted});
			}
		}
		else if (eventType.toLowerCase() === 'onCrmEntityDelete'.toLowerCase())
		{
			delete this.#storage[item.hash];
			this.#observedItems[item.entityTypeId].delete(item.entityId);
			this.emit('OnItemDeleted', {item});
		}
		else
		{
			console.error('unknown event type', eventType);
		}
	}

	/**
	 * @internal
	 */
	static onDetailsLoad(entityTypeId: number, entityId: number, receiversJSONString: string): void
	{
		let item: ItemIdentifier;
		try
		{
			item = new ItemIdentifier(entityTypeId, entityId);
		}
		catch (e)
		{
			return;
		}

		const instance = ReceiverRepository.Instance;
		instance.#startObservingItem(item);

		const receiversJSON = JSON.parse(receiversJSONString);
		if (Type.isArrayFilled(receiversJSON))
		{
			const receivers = [];
			for (const singleReceiverJSON of receiversJSON)
			{
				const receiver = Receiver.fromJSON(singleReceiverJSON);
				if (!Type.isNil(receiver))
				{
					receivers.push(receiver);
				}
			}

			if (Type.isArrayFilled(receivers))
			{
				instance.#addReceivers(item, receivers);
			}
		}
	}

	#addReceivers(item: ItemIdentifier, receivers: Receiver[]): void
	{
		ensureIsItemIdentifier(item);

		this.#storage[item.hash] = [];
		for (const receiver of receivers)
		{
			ensureIsReceiver(receiver);

			this.#storage[item.hash].push(receiver);
		}

		this.#startObservingItem(item);
	}

	#startObservingItem(item: ItemIdentifier): void
	{
		ensureIsItemIdentifier(item);

		const observedItemsOfThisType = this.#observedItems[item.entityTypeId] ?? new Set();
		observedItemsOfThisType.add(item.entityId);
		this.#observedItems[item.entityTypeId] = observedItemsOfThisType;
	}

	getReceivers(entityTypeId: number, entityId: number): Receiver[]
	{
		try
		{
			return this.getReceiversByIdentifier(new ItemIdentifier(entityTypeId, entityId));
		}
		catch (e)
		{
			return [];
		}
	}

	getReceiversByIdentifier(item: ItemIdentifier): Receiver[]
	{
		ensureIsItemIdentifier(item);

		return this.#storage[item.hash] ?? [];
	}
}
