import {Item} from './item';

import {Entity} from '../entity/entity';
import {EntityStorage} from '../entity/entity.storage';

import {RequestSender} from '../utility/request.sender';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage
}

export class ItemDesigner
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;

		this.listAllUsedColors = new Set();

		this.randomColorCount = 0;
		this.defaultColor = '#2ECEFF';
	}

	getRandomColorForItemBorder(): Promise
	{
		this.randomColorCount = 0;

		return this.getAllUsedColors().then(() => {
			return this.getRandomColor(this.getAllColors());
		});
	}

	getAllColors(): Array
	{
		return [
			'#2ECEFF', '#10E5FC', '#A5DE00', '#EEC202',
			'#AD8F47', '#FF5B55', '#EF3001', '#F968B6',
			'#6B52CC', '#07BAB1', '#5CD1DF', '#A1A6AC',
			'#949DA9', '#01A64C', '#B02FB0', '#EF008B',
			'#0202FF', '#555555', '#C4C4C4', '#AAAAAA',
			'#F89675', '#C5E099', '#7ECB9C', '#78CDCA',
			'#887FC0', '#BD8AC0', '#F6989C', '#F26A47',
			'#ABD46B', '#00BBB4', '#3FB2CD', '#5471B9',
			'#3E8BCD', '#A861AB', '#F26A7B', '#9E0402',
			'#A46200', '#578520', '#01736A', '#0175A6',
			'#033172', '#460763', '#630260', '#9F0137',
			'#B7EB81', '#FFA900', '#F7A700', '#333333',
			'#EDEEF0', '#E1F3F9'
		];
	}

	getRandomColor(allColors: Array): string
	{
		this.randomColorCount++;

		if (this.randomColorCount >= allColors.length)
		{
			return this.defaultColor;
		}

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

		this.listAllUsedColors
			.forEach((usedColor: string) => {
				if (usedColor === color)
				{
					isAlreadyUse = true;
				}
			})
		;

		return isAlreadyUse;
	}

	getAllUsedColors(): Promise
	{
		const entityIds = new Set();
		this.entityStorage.getAllEntities()
			.forEach((entity: Entity) => {
				if (!entity.isCompleted())
				{
					entityIds.add(entity.getId());
				}
			})
		;

		return this.requestSender.getAllUsedItemBorderColors({
			entityIds: [...entityIds.values()]
		})
			.then((response) => {
				this.listAllUsedColors = new Set(response.data);
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	updateBorderColor(items: Set<Item>)
	{
		const itemIdsToUpdateColor = new Set();

		items.forEach((item: Item) => {
			if (item.isLinkedTask() && !item.getBorderColor())
			{
				itemIdsToUpdateColor.add(item.getId());
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
				})
					.then((response) => {
						const updatedItems = response.data;
						Object.keys(updatedItems).forEach((itemId: number) => {
							const borderColor = updatedItems[itemId];
							const item = this.entityStorage.findItemByItemId(itemId);
							item.setBorderColor(borderColor);
						});
					})
					.catch((response) => {
						this.requestSender.showErrorAlert(response);
					})
				;
			});
		}
	}
}