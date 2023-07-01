import PullOperation from "./pulloperation";
import {ViewMode} from "./viewmode";

/**
 * @class PullQueue
 */

const LOAD_ITEMS_DELAY = 5000;

export default class PullQueue
{
	#queue: Map<number>;
	#grid: BX.CRM.Kanban.Grid;
	#viewMode: string;
	#isProgress: boolean;
	#isFreeze: boolean;
	loadItemsTimer;

	constructor(grid: BX.CRM.Kanban.Grid): void
	{
		this.#grid = grid;
		this.#viewMode = ViewMode.normalize(this.#grid.getData().viewMode);
		this.#queue = new Map();
		this.#isProgress = false;
		this.#isFreeze = false;
		this.loadItemsTimer = null;
	}

	loadItem(ignoreProgressStatus: boolean = false, ignoreDelay: boolean = false): void
	{
		if (this.loadItemsTimer && !ignoreDelay)
		{
			return;
		}

		this.loadItemsTimer = setTimeout(
			() => {
				if (this.#isProgress && !ignoreProgressStatus)
				{
					this.loadItemsTimer = null;
					return;
				}

				if (document.hidden || this.isOverflow() || this.isFreezed())
				{
					this.loadItemsTimer = null;
					return;
				}

				const items = this.popAllAsArray();
				if (items.length)
				{
					const ids = [];
					items.map(item => {
						ids.push(item.id);
						const data = item.data;
						const operation = PullOperation.createInstance({
							grid: this.#grid,
							itemId: data.id,
							action: data.action,
							actionParams: data.actionParams,
						});
						operation.execute();
					});

					const loadNextOnSuccess = () => {
						this.loadItemsTimer = null;
						if (this.peek())
						{
							this.loadItem(true);
						}
						this.#isProgress = false;
					};
					const doNothingOnError = () => {
						this.loadItemsTimer = null;
					};

					this.#isProgress = true;
					this.
						#grid
						.loadNew(ids, false, true, true, true)
						.then(loadNextOnSuccess, doNothingOnError)
					;
				}
			},
			ignoreDelay ? 0 : LOAD_ITEMS_DELAY
		);
	}

	push(id: number, item: string): PullQueue
	{
		id = parseInt(id, 10);
		if (this.has(id))
		{
			this.delete(id);
		}

		this.#queue.set(id, item);
		return this;
	}

	popAllAsArray()
	{
		const items = Array.from(this.#queue, ([id, data]) => ({id, data}));
		this.#queue.clear();
		return items;
	}

	popBatch(count: number)
	{
		if (count <= 0)
		{
			return [];
		}

		const results = [];
		for (let i=0; i < count; i++)
		{
			const item = this.pop();

			if (!item)
			{
				break;
			}

			results.push(item);
		}

		return results;
	}

	pop(): number
	{
		const items = this.#queue.entries();
		const first = items.next();
		if (first.value)
		{
			this.#queue.delete(first.value[0]);
		}
		return first.value;
	}

	peek(): number|null
	{
		const items = this.#queue.entries();
		const first = items.next();
		return (first.value ?? null);
	}

	delete(id: number): void
	{
		this.#queue.delete(id);
	}

	has(id: number): boolean
	{
		return this.#queue.has(id);
	}

	clear(): void
	{
		this.#queue.clear();
	}

	isOverflow(): boolean
	{
		const MAX_PENDING_ITEMS = 30;
		return (this.#queue.size > MAX_PENDING_ITEMS);
	}

	freeze(): void
	{
		this.#isFreeze = true;
	}

	unfreeze(): void
	{
		this.#isFreeze = false;
	}

	isFreezed(): boolean
	{
		return this.#isFreeze;
	}
}
