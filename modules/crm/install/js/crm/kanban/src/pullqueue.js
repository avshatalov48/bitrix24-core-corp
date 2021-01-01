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
		const id = this.pop();

		if (id && (!this.#isProgress || isForce === true))
		{
			this.#isProgress = true;
			this.#grid.loadNew(id, false).then(
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
		if (this.getAll().indexOf(id) === -1)
		{
			this.#queue.push(id);
		}
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
}