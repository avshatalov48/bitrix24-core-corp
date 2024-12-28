import { AnimationInfo, State } from './queue-animation';
import { QueueAnimationHelper } from './queue-animation-helper';

import { EmptyState } from './state/empty-state';
import type { QueueState } from './state/queue-state';
import { SingleState } from './state/single-state';
import { DoubleState } from './state/double-state';
import { TripleState } from './state/triple-state';
import { CounterState } from './state/counter-state';

export type User = {
	id: number,
	name: string,
	pathToProfile: string,
	photo: Photo,
}

type Photo = {
	src: string,
}

export class QueueAnimationManager
{
	#animationHelper: QueueAnimationHelper;

	constructor(rowId: number)
	{
		this.#animationHelper = new QueueAnimationHelper(rowId);
	}

	async animate(animationInfo: AnimationInfo): Promise
	{
		const animationStates = {
			0: (new EmptyState(this.#animationHelper)),
			1: (new SingleState(this.#animationHelper)),
			2: (new DoubleState(this.#animationHelper)),
			3: (new TripleState(this.#animationHelper)),
			default: (new CounterState(this.#animationHelper)),
		};

		const processStatusAnimation = async (newStateOfStatuses) => {
			for (const [statusId: string, state: State] of newStateOfStatuses)
			{
				const animationState: QueueState = (
					animationStates[state.total]
					|| animationStates.default
				);
				const node = animationInfo.linkToNodes.get(statusId);
				const users = animationInfo.linkToQueueData.get(statusId);

				// eslint-disable-next-line no-await-in-loop
				await animationState.animate(node, users, state, statusId).then(() => {
					this.#animationHelper.changeNodeAttributes(statusId, node, state, state.total === 0);
				});
			}
		};

		await processStatusAnimation(animationInfo.newStateOfStatuses);

		// eslint-disable-next-line no-promise-executor-return
		return new Promise((resolve) => setTimeout(resolve, 500));
	}
}
