import {Sprint} from '../entity/sprint/sprint';
import {Item} from '../item/item';

import {RequestSender} from './request.sender';
import {DomBuilder} from './dom.builder';

import type {ItemParams} from '../item/item';

type Params = {
	requestSender: RequestSender,
	domBuilder: DomBuilder
}

export class SubTasksManager
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.domBuilder = params.domBuilder;

		this.listSubTasks = new Map();
		this.visibilityList = new Set();
	}

	toggleSubTasks(sprint: Sprint, item: Item): Promise
	{
		if (this.listSubTasks.has(item.getItemId()))
		{
			if (this.isShown(item))
			{
				this.hideSubTaskItems(sprint, item);
			}
			else
			{
				this.showSubTaskItems(sprint, item);
			}

			item.toggleSubTasksTick();

			return Promise.resolve();
		}
		else
		{
			return this.requestSender.getSubTaskItems({
				entityId: sprint.getId(),
				taskId: item.getSourceId()
			}).then((response) => {
				const listItemParams = response.data;
				const listSubTaskItems = new Map();
				listItemParams.forEach((itemParams: ItemParams) => {
					const subTaskItem = this.buildSubTaskItem(itemParams);
					listSubTaskItems.set(subTaskItem.getItemId(), subTaskItem);
				});
				this.listSubTasks.set(item.getItemId(), listSubTaskItems);
				if (this.isShown(item))
				{
					this.hideSubTaskItems(sprint, item);
				}
				else
				{
					this.showSubTaskItems(sprint, item);
				}
				item.toggleSubTasksTick();
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		}
	}

	buildSubTaskItem(itemParams: ItemParams): Item
	{
		return new Item(itemParams);
	}

	showSubTaskItems(sprint: Sprint, parentItem: Item)
	{
		const parentItemNode = parentItem.getItemNode();
		const listSubTasks = this.listSubTasks.get(parentItem.getItemId());
		if (!listSubTasks)
		{
			return;
		}

		listSubTasks.forEach((subTaskItem: Item) => {
			sprint.setItem(subTaskItem);
			this.domBuilder.appendItemAfterItem(subTaskItem.render(), parentItemNode);
			subTaskItem.onAfterAppend(sprint.getListItemsNode());

		});

		this.setVisibility(parentItem);
	}

	hideSubTaskItems(sprint: Sprint, parentItem: Item)
	{
		const listSubTasks = this.listSubTasks.get(parentItem.getItemId());
		if (!listSubTasks)
		{
			return;
		}

		listSubTasks.forEach((subTaskItem: Item) => {
			if (subTaskItem.isParentTask())
			{
				this.hideSubTaskItems(sprint, subTaskItem);
			}
			sprint.removeItem(subTaskItem);
			subTaskItem.removeYourself();
		});

		this.cleanVisibility(parentItem);
	}

	addSubTask(parentItem: Item, item: Item)
	{
		if (this.listSubTasks.has(parentItem.getItemId()))
		{
			const listSubTasks = this.listSubTasks.get(parentItem.getItemId());
			listSubTasks.set(item.getItemId(), item);
			this.listSubTasks.set(parentItem.getItemId(), listSubTasks);
		}
		else
		{
			const listSubTasks = new Map();
			listSubTasks.set(item.getItemId(), item);
			return this.listSubTasks.set(parentItem.getItemId(), listSubTasks);
		}
	}

	getSubTasks(parentItem: Item): Map<number, Item>
	{
		return this.listSubTasks.get(parentItem.getItemId());
	}

	isShown(item: Item)
	{
		return this.visibilityList.has(item.getItemId());
	}

	setVisibility(item: Item)
	{
		this.visibilityList.add(item.getItemId());
	}

	cleanVisibility(item: Item)
	{
		this.visibilityList.delete(item.getItemId());
	}

	cleanSubTasks(item: Item)
	{
		this.listSubTasks.delete(item.getItemId());
	}
}