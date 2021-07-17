import {Reflection} from "main.core";

const namespace = Reflection.namespace('BX.Crm.Kanban');

export default class PullQueue
{
	#queue;
	#grid;
	#isProgress;

	constructor(grid)
	{
		this.#grid = grid;
		this.#queue = [];
		this.#isProgress = false;
	}

	loadItem(isForce)
	{
		isForce = (isForce || false);
		if (this.#isProgress && !isForce)
		{
			return;
		}

		const id = this.pop();

		if (id)
		{
			this.#isProgress = true;
			this.#grid.loadNew(id, false, true).then(
				function (response)
				{
					if (this.peek())
					{
						this.loadItem(true);
					}
					else
					{
						this.#isProgress = false;
					}
				}.bind(this)
			);
		}
	}

	push(id)
	{
		id = parseInt(id, 10);
		const index = this.getAll().indexOf(id);

		if (index !== -1)
		{
			this.splice(index);
		}

		this.#queue.push(id);
		return this;
	}

	pop()
	{
		return this.#queue.shift();
	}

	peek()
	{
		return (this.#queue.length ? this.#queue[0] : null)
	}

	getAll()
	{
		return this.#queue;
	}

	splice(index)
	{
		this.#queue.splice(index, 1);
	}
}
