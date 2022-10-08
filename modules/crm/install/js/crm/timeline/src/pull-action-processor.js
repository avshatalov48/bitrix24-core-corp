import {Type} from 'main.core';
import Stream from './stream';
import Fasten from "./animations/fasten";
import CompatibleItem from "./items/compatible-item";

declare type PullActionProcessorParams = {
	action: string,
	item: Object,
	stream: string,
	id: string,
	params: Object | null,
};

export default class PullActionProcessor
{
	#scheduleStream: Stream = null;
	#fixedHistoryStream: Stream = null;
	#historyStream: Stream = null;

	#itemsQueue = [];
	#itemsQueueProcessing = false;

	constructor(params: {scheduleStream: Stream, fixedHistoryStream: Stream, historyStream: Stream})
	{
		if (Type.isObject(params.scheduleStream))
		{
			this.#scheduleStream = params.scheduleStream;
		}
		else
		{
			throw new Error(`scheduleStream must be set`);
		}
		if (Type.isObject(params.fixedHistoryStream))
		{
			this.#fixedHistoryStream = params.fixedHistoryStream;
		}
		else
		{
			throw new Error(`fixedHistoryStream must be set`);
		}
		if (Type.isObject(params.historyStream))
		{
			this.#historyStream = params.historyStream;
		}
		else
		{
			throw new Error(`historyStream must be set`);
		}
	}

	processAction(actionParams: PullActionProcessorParams): void
	{
		this.#addToQueue(actionParams);
	}

	#addToQueue(actionParams: PullActionProcessorParams)
	{
		this.#itemsQueue.push(actionParams);
		if (!this.#itemsQueueProcessing)
		{
			this.#processQueueItem();
		}
	}

	#processQueueItem(): void
	{
		if (!this.#itemsQueue.length)
		{
			this.#itemsQueueProcessing = false;

			return;
		}
		this.#itemsQueueProcessing = true;
		const actionParams = this.#itemsQueue.shift();

		const stream = this.#getStreamByName(actionParams.stream);

		const promises = [];
		switch (actionParams.action)
		{
			case 'add':
				promises.push(this.#addItem(actionParams.id, actionParams.item, stream));
				break;
			case 'update':
				promises.push(this.#updateItem(actionParams.id, actionParams.item, stream, false));
				if (stream.isHistoryStream())
				{
					// fixed history stream can contain the same item as a history stream, so both should be updated:
					promises.push(this.#updateItem(actionParams.id, actionParams.item, this.#fixedHistoryStream, false));
				}
				break;
			case 'delete':
				promises.push(this.#deleteItem(actionParams.id, stream));
				if (stream.isHistoryStream())
				{
					// fixed history stream can contain the same item as a history stream, so both should be updated:
					promises.push(this.#deleteItem(actionParams.id, this.#fixedHistoryStream));
				}
				break;
			case 'move':
				// move item from one stream to another one:
				const sourceStream = this.#getStreamByName(actionParams.params.fromStream);
				promises.push(this.#moveItem(actionParams.params.fromId, sourceStream, actionParams.id, stream, actionParams.item));
				break;
			case 'changePinned':
				// pin or unpin item
				if (this.#getStreamByName(actionParams.params.fromStream).isHistoryStream())
				{
					promises.push(this.#pinItem(actionParams.id, actionParams.item));
				}
				else {
					promises.push(this.#unpinItem(actionParams.id, actionParams.item));
				}
		}

		Promise.all(promises).then(() => {
			this.#processQueueItem();
		})
	}

	#addItem(id: number, itemData: Object, stream: Stream): Promise
	{
		const existedStreamItem = stream.findItemById(id);
		if (existedStreamItem)
		{
			return Promise.resolve();
		}
		const streamItem = stream.createItem(itemData);
		if (!streamItem)
		{
			return Promise.resolve();
		}

		const index = stream.calculateItemIndex(streamItem);
		const anchor = stream.createAnchor(index);
		stream.addItem(streamItem, index);
		streamItem.layout({anchor: anchor});
		return stream.animateItemAdding(streamItem);
	}

	#updateItem(id: number, itemData: Object, stream: Stream, animated: boolean = true): Promise
	{
		const isDone = BX.prop.getString(itemData['ASSOCIATED_ENTITY'], 'COMPLETED') === 'Y';
		const existedStreamItem = stream.findItemById(id);
		if (!existedStreamItem)
		{
			return Promise.resolve();
		}

		if (existedStreamItem instanceof CompatibleItem && isDone)
		{
			existedStreamItem._existedStreamItemDeadLine = existedStreamItem.getDeadline();
		}

		existedStreamItem.setData(itemData);
		return stream.refreshItem(existedStreamItem, animated);
	}

	#deleteItem(id: number, stream: Stream): Promise
	{
		const item = stream.findItemById(id);
		if (item)
		{
			return stream.deleteItemAnimated(item);
		}

		return Promise.resolve();
	}

	#moveItem(sourceId: string, sourceStream: Stream, destinationId: string, destinationStream: Stream, destinationItemData: Object): Promise
	{
		const sourceItem = sourceStream.findItemById(sourceId);
		if (!sourceItem)
		{
			return this.#addItem(destinationId, destinationItemData, destinationStream);
		}

		const existedDestinationItem = destinationStream.findItemById(destinationId);
		if (sourceItem && existedDestinationItem)
		{
			return this.#deleteItem(sourceId, sourceStream);
		}

		const destinationItem = destinationStream.createItem(destinationItemData);
		destinationStream.addItem(destinationItem, destinationStream.calculateItemIndex(destinationItem));
		destinationItem.layout({ add: false });

		return sourceStream.moveItemToStream(sourceItem, destinationStream, destinationItem);
	}

	#pinItem(id: number, itemData: Object): Promise
	{
		if (this.#fixedHistoryStream.findItemById(id))
		{
			return Promise.resolve();
		}

		const historyItem = this.#historyStream.findItemById(id);
		if (!historyItem) // fixed history item does not exist into history items stream, so just add to fixed history stream
		{
			return this.#addItem(id, itemData, this.#fixedHistoryStream);
		}
		if (historyItem instanceof CompatibleItem)
		{
			historyItem.onSuccessFasten();

			return Promise.resolve();
		}
		else
		{
			return this.#updateItem(id, itemData, this.#historyStream, false).then(() => {
				const fixedHistoryItem = this.#fixedHistoryStream.createItem(itemData);
				this.#fixedHistoryStream.addItem(fixedHistoryItem, 0);
				fixedHistoryItem.layout({ add: false });

				return new Promise((resolve) => {
					const animation = Fasten.create(
						'',
						{
							initialItem: historyItem,
							finalItem: fixedHistoryItem,
							anchor: this.#fixedHistoryStream.getAnchor(),
							events: {
								complete: resolve,
							}
						}
					);
					animation.run();
				})
			});
		}
	}

	#unpinItem(id: number, itemData: Object): Promise
	{
		const fixedHistoryItem = this.#fixedHistoryStream.findItemById(id);
		if (fixedHistoryItem instanceof CompatibleItem)
		{
			fixedHistoryItem.onSuccessUnfasten();

			return Promise.resolve();
		}
		else
		{
			return this.#updateItem(id, itemData, this.#historyStream, false).then(() => {
				return this.#deleteItem(id, this.#fixedHistoryStream);
			});
		}
	}

	#getStreamByName(streamName): Stream
	{
		switch (streamName)
		{
			case 'scheduled':
				return this.#scheduleStream;
			case 'fixedHistory':
				return this.#fixedHistoryStream;
			case 'history':
				return this.#historyStream;
		}

		throw new Error(`Stream "${streamName}" not found`);
	}
}
