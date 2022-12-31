import { TestSuite } from "./test-suite";
import { Type } from "../../src";
import { KanbanItemMock } from "./kanban-item-mock";

export class ByLastActivityTime extends TestSuite
{
	getSortType(): string
	{
		return Type.BY_LAST_ACTIVITY_TIME;
	}

	createItems(): KanbanItemMock[]
	{
		const items = [];

		for (let i = 0; i < TestSuite.ITEMS_COUNT; i++)
		{
			items.push(
				KanbanItemMock.create(this.id, this.timestamp),
			);

			this.id++;
			this.timestamp += 100;
		}

		return items.reverse();
	}

	createNextItem(): KanbanItemMock
	{
		// noinspection IncrementDecrementResultUsedJS
		return KanbanItemMock.create(++this.id, ++this.timestamp);
	}

	createItemThatShouldBePlacedAfter(beforeItem: KanbanItemMock): KanbanItemMock
	{
		// noinspection IncrementDecrementResultUsedJS
		return KanbanItemMock.create(++this.id, beforeItem.getLastActivityTimestamp() + 1);
	}

	createItemThatShouldBePlacedBefore(afterItem: KanbanItemMock): KanbanItemMock
	{
		// noinspection IncrementDecrementResultUsedJS
		return KanbanItemMock.create(++this.id, afterItem.getLastActivityTimestamp() - 1);
	}

	createItemWithSamePosition(item: KanbanItemMock): KanbanItemMock
	{
		// noinspection IncrementDecrementResultUsedJS
		return KanbanItemMock.create(++this.id, item.getLastActivityTimestamp());
	}

	runLogic()
	{
		super.runLogic();

		it('Should return top item if new item should be placed at top', () => {
			const newItem = this.createNextItem();

			const result = this.createDefaultSorter().calcBeforeItem(newItem);

			assert.strictEqual(result, this.getTopItem());
		});

		// its kanban api limitation. there is no way to place item at the bottom. since it's not a common case, leave it like this
		it('Should return the lowest item in column if the provided item should be placed in column bottom', () => {
			const bottomItem = this.getBottomItem();
			const newItem = this.createItemThatShouldBePlacedBefore(bottomItem);

			const result = this.createDefaultSorter().calcBeforeItem(newItem);

			assert.strictEqual(result, bottomItem)
		});

		it('Should return some relevant middle item if new item should be placed in the middle', () => {
			const beforeItem = this.getRandomMiddleItem();
			const newItem = this.createItemThatShouldBePlacedAfter(beforeItem);

			const result = this.createDefaultSorter().calcBeforeItem(newItem);

			assert.strictEqual(result, beforeItem);
		});

		it('If there are items with same sort as new item, new item should be placed on top of them', () => {
			const middleItem = this.getRandomMiddleItem();
			const newItem = this.createItemWithSamePosition(middleItem);

			const result = this.createDefaultSorter().calcBeforeItem(newItem);

			assert.strictEqual(result, middleItem)
		});

	}
}
