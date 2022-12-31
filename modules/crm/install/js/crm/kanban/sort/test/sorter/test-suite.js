import { KanbanItemMock } from "./kanban-item-mock";
import { Runtime, Text, Type } from "main.core";
import { Sorter, Type as SortType } from "../../src";

const abstractMethodBody = () => {
	throw new Error('Should be overwritten');
};

export class TestSuite
{
	static ITEMS_COUNT = 10;

	id: number = 100;
	timestamp: number = 1657787953;

	#items: KanbanItemMock[] = [];

	constructor()
	{
		this.#items = this.createItems();
	}

	/**
	 * @abstract
	 * @protected
	 */
	createItems(): KanbanItemMock[]
	{
		abstractMethodBody();
	}

	/**
	 * @protected
	 */
	getShuffledItems(): KanbanItemMock[]
	{
		const shuffledItems = [];

		const pickedIndexes = {};
		while (shuffledItems.length < this.#items.length)
		{
			let index;
			do
			{
				index = this.getRandomInt(0, this.#items.length);
			} while (pickedIndexes.hasOwnProperty(index));

			pickedIndexes[index] = index;

			shuffledItems.push(this.#items[index]);
		}

		//check that new array has different order
		assert.notDeepEqual(shuffledItems, this.#items);

		return shuffledItems;
	}

	/**
	 * Returns random positive integer within the range.
	 * Min border is included (result can be equal to min).
	 * Max border is not included (result can not be equal to max).
	 */
	getRandomInt(min: number = 0, max: number = Number.MAX_SAFE_INTEGER): number
	{
		const range = max - min;

		const float = Math.random() * range + min;

		return Text.toInteger(float);
	}

	#getRandomMiddleItemIndex(): number
	{
		return this.getRandomInt(1, this.#items.length - 1);
	}

	getRandomMiddleItem(): KanbanItemMock
	{
		return this.#items[this.#getRandomMiddleItemIndex()];
	}

	getTopItem(): KanbanItemMock
	{
		return this.#items[0];
	}

	getBottomItem(): KanbanItemMock
	{
		return this.#items[this.#items.length - 1];
	}

	getPreviousItem(item: KanbanItemMock): ?KanbanItemMock
	{
		const index = this.#items.indexOf(item);
		if (index < 0)
		{
			return null;
		}

		return this.#items[index + 1];
	}

	/**
	 * @abstract
	 * @protected
	 */
	createNextItem(): KanbanItemMock
	{
		abstractMethodBody();
	}

	/**
	 * @abstract
	 * @protected
	 */
	createItemThatShouldBePlacedAfter(beforeItem: KanbanItemMock): KanbanItemMock
	{
		abstractMethodBody();
	}

	//todo extract class with these methods? and return test in main file, run some tests in cicle for each object
	/**
	 * @abstract
	 * @protected
	 */
	createItemThatShouldBePlacedBefore(afterItem: KanbanItemMock): KanbanItemMock
	{
		abstractMethodBody();
	}

	/**
	 * @abstract
	 * @protected
	 */
	createItemWithSamePosition(item: KanbanItemMock): KanbanItemMock
	{
		abstractMethodBody();
	}

	createItemClone(item: KanbanItemMock): KanbanItemMock
	{
		return KanbanItemMock.create(item.getId(), item.getLastActivityTimestamp());
	}

	/**
	 * @protected
	 */
	createDefaultSorter(): Sorter
	{
		return new Sorter(this.getSortType(), this.#items);
	}

	/**
	 * @abstract
	 * @protected
	 */
	getSortType(): string
	{
		abstractMethodBody();
	}

	run(): void
	{
		describe('Sorter ' + this.getSortType(), () => {
			it('Should be a function', () => {
				assert(Type.isFunction(Sorter));
			});

			describe('Items sorting', () => {
				const unsortedItems = this.getShuffledItems();

				it('Should not mutate source array', () => {
					const unsortedItemsCopy = Runtime.clone(unsortedItems);
					//sanity check
					assert.deepEqual(unsortedItemsCopy, unsortedItems);

					(new Sorter(SortType.BY_ID, unsortedItemsCopy)).getSortedItems();

					assert.deepEqual(unsortedItemsCopy, unsortedItems);
				});

				it(`Should sort items in descending order`, () => {
					const result = (new Sorter(this.getSortType(), unsortedItems)).getSortedItems();

					assert.deepEqual(result, this.#items);
				});
			});

			describe('BeforeItem calculation', () => {
				describe('Logic', () => {
					this.runLogic();
				});

				describe('Invalid arguments', () => {
					it('Should return null if item with no data is provided', () => {
						const result = this.createDefaultSorter().calcBeforeItem(new KanbanItemMock({}));

						assert.strictEqual(result, null);
					});

					it('Should return null if empty sort is provided', () => {
						const result = this.createDefaultSorter().calcBeforeItemByParams({});

						assert.strictEqual(result, null);
					});

					it('Should return null if invalid timestamp is provided', () => {
						const result = this.createDefaultSorter().calcBeforeItemByParams({lastActivityTimestamp: 0});

						assert.strictEqual(result, null);
					});

					it('Should return null if invalid id is provided', () => {
						const result = this.createDefaultSorter().calcBeforeItemByParams({
							lastActivityTimestamp: this.timestamp,
							id: 0,
						});

						assert.strictEqual(result, null);
					});
				});
			});
		});
	}

	/**
	 * @protected
	 */
	runLogic(): void
	{
		it("Should return an item's previous sibling if the new item exists in items in a middle of a column and its position should not change", () => {
			const middleItem = this.getRandomMiddleItem();

			// it's identical to middle item
			const newItem = this.createItemClone(middleItem);

			const result = this.createDefaultSorter().calcBeforeItem(newItem);

			assert.strictEqual(result, this.getPreviousItem(middleItem));
		});

		it("Should return an item's previous sibling if the new item exists in items on top of a column and its position should not change", () => {
			const topItem = this.getTopItem();

			// it's identical to top item
			const newItem = this.createItemClone(topItem);

			const result = this.createDefaultSorter().calcBeforeItem(newItem);

			assert.strictEqual(result, this.getPreviousItem(topItem));
		});

		it("Should return null if no items, so that's new item is placed on top", () => {
			const newItem = this.createNextItem();

			const result = (new Sorter(this.getSortType(), [])).calcBeforeItem(newItem);

			assert.strictEqual(result, null);
		});
	}
}
