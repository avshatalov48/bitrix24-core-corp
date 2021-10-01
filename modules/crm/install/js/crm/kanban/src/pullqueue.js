export default class PullQueue
{
	#queue: Set<number>;
	#grid: BX.CRM.Kanban.Grid;
	#isProgress: boolean;
	#isFreeze: boolean;

	constructor(grid: BX.CRM.Kanban.Grid): void
	{
		this.#grid = grid;
		this.#queue = new Set();
		this.#isProgress = false;
		this.#isFreeze = false;
	}

	loadItem(isForce: boolean): void
	{
		setTimeout(
			() => {
				isForce = (isForce || false);
				if (this.#isProgress && !isForce)
				{
					return;
				}

				if (document.hidden || this.isOverflow() || this.isFreezed())
				{
					return;
				}

				const id = this.pop();
				if (id)
				{
					const loadNextOnSuccess = (response) => {
						if (this.peek())
						{
							this.loadItem(true);
						}
						this.#isProgress = false;
					};
					const doNothingOnError = (err) => {};

					this.#isProgress = true;
					this.#grid.loadNew(id, false, true, true).then(loadNextOnSuccess, doNothingOnError);
				}
			},
			1000
		);
	}

	push(id: number): PullQueue
	{
		id = parseInt(id, 10);
		if (this.#queue.has(id))
		{
			this.#queue.delete(id);
		}

		this.#queue.add(id);
		return this;
	}

	pop(): number
	{
		const values = this.#queue.values();
		const first = values.next();
		if (first.value !== undefined)
		{
			this.#queue.delete(first.value);
		}
		return first.value;
	}

	peek(): number|null
	{
		const values = this.#queue.values();
		const first = values.next();
		return (first.value !== undefined ? first.value : null);
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
		const MAX_PENDING_ITEMS = 10;
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
