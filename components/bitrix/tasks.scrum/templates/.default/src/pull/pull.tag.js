import { Filter } from '../service/filter';

import { EntityStorage } from '../entity/entity.storage';

import { RequestSender } from '../utility/request.sender';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	groupId: number
}

type PushParams = {
	oldTagName: string,
	newTagName: string,
	groupId: number,
	oldTagsNames: Array,
}

export class PullTag
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.groupId = params.groupId;
	}

	getModuleId()
	{
		return 'tasks';
	}

	getMap()
	{
		return {
			tag_changed: this.onTagChanged.bind(this),
		};
	}

	onTagChanged(params: PushParams)
	{
		let tagDialogs = BX.UI.EntitySelector.Dialog.getInstances();
		tagDialogs.forEach(dialog => {
			dialog.hide();
		});
		if (parseInt(this.groupId, 10) !== params.groupId)
		{
			return;
		}
		this.entityStorage.getBacklog().fadeOut();

		const updatedTags = params.oldTagsNames;
		let updatedTag = params.oldTagName;
		const newTag = params.newTagName;

		const items = this.entityStorage.getAllItems();
		const itemsToUpdate = new Set();
		items.forEach((item: Item) => {
			const tags = item.getTags().getValue();
			if (tags.find((tag: string) => tag === updatedTag))
			{
				itemsToUpdate.add(item);
			}
			if (updatedTags)
			{
				updatedTags.forEach(tag => {
					let updatedTagFromArray = tag;
					if (tags.find((tag: string) => tag === updatedTagFromArray))
					{
						itemsToUpdate.add(item);
					}
				});
			}

		});
		itemsToUpdate.forEach((item: Item) => {
			const currentTags = item.getTags().getValue();
			currentTags.forEach((tag, index, array) => {
				if (updatedTags &&updatedTags.length !== 0 && updatedTags.find((tagInArr: string) => tag === tagInArr))
				{
					array[index] = newTag;
				}
				else if (tag === updatedTag)
				{
					array[index] = newTag;
				}
			});
			const newTags = [];
			currentTags.forEach(tag => {
				if (tag !== '')
				{
					newTags.push(tag);
				}
			});
			item.setTags(newTags);
		});
		setTimeout(() => {
			this.entityStorage.getBacklog().fadeIn();
		}, 1000);

	}

}