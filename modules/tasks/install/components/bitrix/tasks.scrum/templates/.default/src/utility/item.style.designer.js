import {Entity} from '../entity/entity';
import {Item} from '../item/item';

import {RequestSender} from './request.sender';
import {EntityStorage} from './entity.storage';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage
}

export class ItemStyleDesigner
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;

		this.listAllUsedColors = new Set();

		this.updateBorderColorForLinkedItems();
	}

	updateBorderColorForLinkedItems()
	{
		const itemIdsToUpdateColor = new Set();
		this.entityStorage.getAllItems().forEach((item: Item) => {
			if (item.isLinkedTask() && !item.getBorderColor())
			{
				itemIdsToUpdateColor.add(item.getItemId());
			}
		});

		if (itemIdsToUpdateColor.size)
		{
			this.getAllUsedColors().then(() => {
				const items = new Map();
				itemIdsToUpdateColor.forEach((itemId: number) => {
					items.set(itemId, this.getRandomColor(this.getAllColors()));
				});
				this.requestSender.updateBorderColorToLinkedItems({
					items: Object.fromEntries(items)
				}).then((response) => {
					const updatedItems = response.data;
					Object.keys(updatedItems).forEach((itemId: number) => {
						const borderColor = updatedItems[itemId];
						const item = this.entityStorage.findItemByItemId(itemId);
						item.setBorderColor(borderColor);
					});
				}).catch((response) => {
					this.requestSender.showErrorAlert(response);
				});
			});
		}
	}

	getRandomColorForItemBorder(): Promise
	{
		return this.getAllUsedColors().then(() => {
			return this.getRandomColor(this.getAllColors());
		});
	}

	getAllColors(): Array
	{
		const colorPicker = this.getColorPicker();
		let allColors = [];
		colorPicker.getDefaultColors().forEach((defaultColors) => {
			allColors = [...allColors, ...defaultColors];
		});

		return allColors;
	}

	getRandomColor(allColors: Array): string
	{
		const randomColor = allColors[Math.floor(Math.random() * allColors.length)];
		if (this.isThisBorderColorAlreadyUse(randomColor))
		{
			return this.getRandomColor(allColors);
		}
		else
		{
			return randomColor;
		}
	}

	isThisBorderColorAlreadyUse(color: string): boolean
	{
		let isAlreadyUse = false;

		this.listAllUsedColors.forEach((usedColor: string) => {
			if (usedColor === color)
			{
				isAlreadyUse = true;
			}
		});

		return isAlreadyUse;
	}

	getAllUsedColors(): Promise
	{
		const entityIds = new Set();
		this.entityStorage.getAllEntities().forEach((entity: Entity) => {
			if (!entity.isCompleted())
			{
				entityIds.add(entity.getId());
			}
		});

		return this.requestSender.getAllUsedItemBorderColors({
			entityIds: [...entityIds.values()]
		}).then((response) => {
			this.listAllUsedColors = new Set([response.data]);
		})
		.catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	getColorPicker()
	{
		/* eslint-disable */
		return new BX.ColorPicker();
		/* eslint-enable */
	}
}