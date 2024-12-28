import { QueueAnimationManager } from './queue-animation-manager';
import { User } from './queue-animation-manager';

type Data = {
	FLOW_ID: number,
	newStatus: Status,
	oldStatus?: Status,
}

type Status = {
	id: string,
	queue: Array<User>,
	queueSubsequence: Array<number>,
	total: number,
	date: string,
}

export type State = {
	total: number,
	subsequence: Array<number>,
	label: string,
}

export type AnimationInfo = {
	linkToNodes: Map<string, HTMLElement>,
	linkToQueueData: Map<string, Array<User>>,
	currentStateOfStatuses: Map<string, State>,
	newStateOfStatuses: Map<string, State>,
}

export class QueueAnimation
{
	#grid: BX.Main.grid;
	#data: Data;
	#rowId: number;
	#animationManager: QueueAnimationManager;

	constructor(grid, data: Data)
	{
		this.#grid = grid;
		this.#data = data;
		this.#rowId = parseInt(data.FLOW_ID, 10);

		this.#animationManager = new QueueAnimationManager(this.#rowId);
	}

	start(): Promise
	{
		return new Promise((resolve, reject) => {
			const animationInfo: AnimationInfo = this.#prepareData();

			this.#animationManager.animate(animationInfo)
				.then(() => {
					resolve();
				})
				.catch(() => {
					reject();
				})
			;
		});
	}

	#prepareData(): AnimationInfo
	{
		const linkToNodes: Map<string, HTMLElement> = new Map();
		const linkToQueueData: Map<string, Array<User>> = new Map();

		const currentStateOfStatuses: Map<string, State> = new Map();
		const newStateOfStatuses: Map<string, State> = new Map();

		this.#getNodes().forEach((node: HTMLElement, statusId: string) => {
			linkToNodes.set(statusId, node);
			currentStateOfStatuses.set(statusId, this.#getCurrentState(node));
		});

		this.#getNewStatuses().forEach((status: Status, statusId: string) => {
			linkToQueueData.set(statusId, status.queue);
			newStateOfStatuses.set(statusId, this.#getNewState(status));
		});

		this.#compareAndFilterStates(currentStateOfStatuses, newStateOfStatuses);
		this.#removeDeletedKeys(newStateOfStatuses, linkToNodes, linkToQueueData);

		return {
			linkToNodes,
			linkToQueueData,
			currentStateOfStatuses,
			newStateOfStatuses,
		};
	}

	#getNodes(): Map<string, HTMLElement>
	{
		const list = new Map();

		const addToList = (statusId: string) => {
			list.set(
				statusId,
				this.#getCell(statusId),
			);
		};

		if ('newStatus' in this.#data)
		{
			addToList(this.#data.newStatus.id);
		}

		if ('oldStatus' in this.#data)
		{
			addToList(this.#data.oldStatus.id);
		}

		return list;
	}

	#getNewStatuses(): Map<string, State>
	{
		const list = new Map();

		const addToList = (status: Status) => {
			list.set(status.id, status);
		};

		if ('newStatus' in this.#data)
		{
			addToList(this.#data.newStatus);
		}

		if ('oldStatus' in this.#data)
		{
			addToList(this.#data.oldStatus);
		}

		return list;
	}

	#getCurrentState(node: HTMLElement): State
	{
		const listNode = node.querySelector('.tasks-flow__list-members');
		const labelNode = node.querySelector('.tasks-flow__list-members_info');

		const subsequence: string = (
			this.#isValidSequence(listNode.dataset.subsequence)
				? listNode.dataset.subsequence.split(',').map(Number)
				: []
		);

		return {
			total: parseInt(listNode.dataset.total, 10),
			subsequence,
			label: labelNode.textContent.trim(),
		};
	}

	#getNewState(status: Status): State
	{
		return {
			total: parseInt(status.total, 10),
			subsequence: status.queueSubsequence,
			label: status.date,
		};
	}

	#getRow(): BX.Grid.Row
	{
		return this.#grid.getRows().getById(this.#rowId);
	}

	#getCell(columnId: string): ?HTMLElement
	{
		return this.#getRow(this.#rowId).getCellById(columnId);
	}

	#isValidSequence(sequence: string): boolean
	{
		const regex = /^\d+(,\d+)*$/;

		return regex.test(sequence);
	}

	#statesAreEqual(first: State, second: State): boolean
	{
		if (first.total !== second.total)
		{
			return false;
		}

		if (first.subsequence.length !== second.subsequence.length)
		{
			return false;
		}

		for (let i = 0; i < first.subsequence.length; i++)
		{
			if (first.subsequence[i] !== second.subsequence[i])
			{
				return false;
			}
		}

		return true;
	}

	#compareAndFilterStates(first: Map<string, State>, second: Map<string, State>)
	{
		for (const [key, value] of first)
		{
			if (second.has(key) && this.#statesAreEqual(value, second.get(key)))
			{
				first.delete(key);
				second.delete(key);
			}
		}
	}

	#removeDeletedKeys(
		newStateOfStatuses: Map<string, State>,
		linkToNodes: Map<string, HTMLElement>,
		linkToQueueData: Map<string, Array<User>>,
	) {
		for (const key of linkToNodes.keys())
		{
			if (!newStateOfStatuses.has(key))
			{
				linkToNodes.delete(key);
			}
		}

		for (const key of linkToQueueData.keys())
		{
			if (!newStateOfStatuses.has(key))
			{
				linkToQueueData.delete(key);
			}
		}
	}
}
