import { TestSuite } from "./test-suite";
import { Type, Sorter } from "../../src";
import { KanbanItemMock } from "./kanban-item-mock";

export class ById extends TestSuite
{
	getSortType(): string
	{
		return Type.BY_ID;
	}

	createItems(): KanbanItemMock[]
	{
		const items = [];

		for (let i = 0; i < TestSuite.ITEMS_COUNT; i++)
		{
			items.push(
				KanbanItemMock.create(this.id, this.timestamp),
			);

			this.id += 100;
			this.timestamp -= 100;
		}

		return items.reverse();
	}

	createNextItem(): KanbanItemMock
	{
		// noinspection IncrementDecrementResultUsedJS
		return KanbanItemMock.create(++this.id, --this.timestamp);
	}

	createItemThatShouldBePlacedAfter(beforeItem)
	{
		return KanbanItemMock.create(beforeItem.getId() + 1, beforeItem.getLastActivityTimestamp());
	}

	createItemThatShouldBePlacedBefore(afterItem)
	{
		return KanbanItemMock.create(afterItem.getId() - 1, afterItem.getLastActivityTimestamp());
	}

	createItemWithSamePosition(item)
	{
		return KanbanItemMock.create(item.getId(), item.getLastActivityTimestamp() + 1);
	}

	runLogic()
	{
		super.runLogic();

		it('Should return previous item sibling if item exists on a top of a column', () => {
			const topItem = this.getTopItem();

			const result = this.createDefaultSorter().calcBeforeItem(topItem);

			assert.strictEqual(result, this.getPreviousItem(topItem));
		});

		it('Should return previous item sibling if item exists in a middle of a column', () => {
			const middleItem = this.getRandomMiddleItem();

			const result = this.createDefaultSorter().calcBeforeItem(middleItem);

			assert.strictEqual(result, this.getPreviousItem(middleItem));
		});

		it('Should return null if item is not in a column, so that it will be placed on top of a column', () => {
			const newItem = this.createNextItem();

			const result = this.createDefaultSorter().calcBeforeItem(newItem);

			assert.strictEqual(result, null)
		});

		it('Should not change items position', () => {
			// these items are not sorted by id, they have random order. imagine, that it was sorted by user
			const shuffledItems = this.getShuffledItems();

			const randomMiddleIndex = this.getRandomInt(1, shuffledItems.length - 1);
			const middleItem = shuffledItems[randomMiddleIndex];
			const previousItem = shuffledItems[randomMiddleIndex + 1];

			const result = (new Sorter(this.getSortType(), shuffledItems)).calcBeforeItem(middleItem);

			// it should not resort items by id, items order should remain the same.
			// and middle item should not change its position.
			assert.strictEqual(result, previousItem);
		});
	}
}
