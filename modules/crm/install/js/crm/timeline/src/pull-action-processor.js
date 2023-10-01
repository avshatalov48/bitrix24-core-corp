import { ajax, clone, Loc, Type } from 'main.core';
import Stream from './stream';
import Fasten from "./animations/fasten";
import CompatibleItem from "./items/compatible-item";

declare type PullActionProcessorMessage = {
	action: string,
	item: Object,
	stream: string,
	id: string,
	params: Object | null,
};

declare type PullActionProcessorProps = {
	scheduleStream: Stream,
	fixedHistoryStream: Stream,
	historyStream: Stream,
	ownerTypeId: number,
	ownerId: number,
};

export default class PullActionProcessor
{
	#scheduleStream: Stream = null;
	#fixedHistoryStream: Stream = null;
	#historyStream: Stream = null;

	#itemsQueue = [];
	#itemsQueueProcessing = false;

	#reloadingMessagesQueue: PullActionProcessorMessage[] = [];

	#ownerTypeId: number;
	#ownerId: number;

	constructor(params: PullActionProcessorProps)
	{
		if (
			!Type.isObject(params.scheduleStream)
			|| !Type.isObject(params.fixedHistoryStream)
			|| !Type.isObject(params.historyStream)
		)
		{
			throw new Error(`params scheduleStream, fixedHistoryStream and historyStream are required`);
		}

		if (!Type.isNumber(params.ownerTypeId) || !Type.isNumber(params.ownerId))
		{
			throw new Error('params ownerTypeId and ownerId are required');
		}

		this.#scheduleStream = params.scheduleStream;
		this.#fixedHistoryStream = params.fixedHistoryStream;
		this.#historyStream = params.historyStream;
		this.#ownerTypeId = params.ownerTypeId;
		this.#ownerId = params.ownerId;
	}

	processAction(actionParams: PullActionProcessorMessage): void
	{
		if (this.#itemDataShouldBeReloaded(actionParams))
		{
			this.#reloadingMessagesQueue.push(actionParams);
			this.#fetchItems();
		}
		else
		{
			this.#addToQueue(actionParams);
		}
	}

	#itemDataShouldBeReloaded(actionParams: PullActionProcessorMessage)
	{
		const { item } = actionParams;

		if (!item)
		{
			return false;
		}

		const appLanguage = Loc.getMessage('LANGUAGE_ID').toLowerCase();
		const languageId = BX.prop.getString(item, 'languageId', appLanguage).toLowerCase();
		const canBeReloaded = BX.prop.getBoolean(item, 'canBeReloaded', true);

		return (languageId !== appLanguage) && canBeReloaded;
	}

	#addToQueue(actionParams: PullActionProcessorMessage)
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
				promises.push(this.#updateItem(actionParams.id, actionParams.item, stream, false, true));
				if (stream.isHistoryStream())
				{
					// fixed history stream can contain the same item as a history stream, so both should be updated:
					promises.push(this.#updateItem(actionParams.id, actionParams.item, this.#fixedHistoryStream, false, true));
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

	#updateItem(id: number, itemData: Object, stream: Stream, animateUpdate: boolean = true, animateMove: true): Promise
	{
		const isDone = BX.prop.getString(itemData['ASSOCIATED_ENTITY'], 'COMPLETED') === 'Y';
		const existedStreamItem = stream.findItemById(id);
		if (!existedStreamItem)
		{
			return Promise.resolve();
		}

		if (existedStreamItem instanceof CompatibleItem && isDone)
		{
			existedStreamItem._existedStreamItemDeadLine = existedStreamItem.getLightTime();
		}

		existedStreamItem.setData(itemData);
		return stream.refreshItem(existedStreamItem, animateUpdate, animateMove);
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

		if (destinationItem instanceof CompatibleItem)
		{
			destinationItem.layout({ add: false });
		}

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
			// hide files block in comment content before pin
			const historyCommentBlock = historyItem.getLayoutContentBlockById('commentContentWeb');
			if (historyCommentBlock)
			{
				historyCommentBlock.setIsFilesBlockDisplayed(false);
				historyCommentBlock.setIsMoving();
			}

			return this.#updateItem(id, itemData, this.#historyStream, false, false).then(() => {
				const fixedHistoryItem = this.#fixedHistoryStream.createItem(itemData);
				fixedHistoryItem.initWrapper();
				this.#fixedHistoryStream.addItem(fixedHistoryItem, 0);

				return new Promise((resolve) => {
					const animation = Fasten.create(
						'',
						{
							initialItem: historyItem,
							finalItem: fixedHistoryItem,
							anchor: this.#fixedHistoryStream.getAnchor(),
							events: {
								complete: () => {
									fixedHistoryItem.initLayoutApp({ add: false });

									// show files block in comment content after pin record
									if (historyCommentBlock)
									{
										historyCommentBlock.setIsFilesBlockDisplayed();
										historyCommentBlock.setIsMoving(false);

										const fixedHistoryCommentBlock = fixedHistoryItem.getLayoutContentBlockById('commentContentWeb');
										if (fixedHistoryCommentBlock)
										{
											fixedHistoryCommentBlock.setIsFilesBlockDisplayed();
											fixedHistoryCommentBlock.setIsMoving(false);
										}
									}

									resolve();
								},
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
			return this.#updateItem(id, itemData, this.#historyStream, false, false).then(() => {
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

	#fetchItems()
	{
		setTimeout(() => {

			const messages = clone(this.#reloadingMessagesQueue);
			this.#reloadingMessagesQueue = [];

			const activityIds = [];
			const historyIds = [];

			messages.forEach(message => {
				const container = message.stream === 'scheduled' ? activityIds : historyIds;
				container.push(message.id);
			});

			if (messages.length)
			{
				const data = {
					activityIds,
					historyIds,
					ownerTypeId: this.#ownerTypeId,
					ownerId: this.#ownerId,
				};

				ajax.runAction('crm.timeline.item.load', { data })
					.then(response => {
						messages.forEach(message => {
							if (response.data[message.id])
							{
								message.item = response.data[message.id];
							}
							this.#addToQueue(message);
						})
					})
					.catch(err => {
						console.error(err);
						messages.forEach(message => this.#addToQueue(message));
					});
			}

		}, 1500);
	}
}
