import { State } from '../queue-animation';
import { User } from '../queue-animation-manager';

export class QueueState
{
	animate(
		node: HTMLElement,
		users: Array<User>,
		state: State,
		statusId: string,
	): Promise
	{
		const list = node.querySelector('.tasks-flow__list-members');
		const children = list.children;
		const number = list.childElementCount;

		const variantHandlers = {
			1: this.animateFromStateWithOneElement.bind(this, list, children, users, state),
			2: this.animateFromStateWithTwoElement.bind(this, list, children, users, state),
			3: this.animateFromStateWithThreeElement.bind(this, list, children, users, state),
			4: this.animateFromStateWithCounter.bind(this, list, children, users, state),
			default: this.skipAnimation.bind(this),
		};
		const animateFrom = (variantHandlers[number] || variantHandlers.default);

		return animateFrom();
	}

	async animateFromStateWithOneElement(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		return Promise.resolve();
	}

	async animateFromStateWithTwoElement(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		return Promise.resolve();
	}

	async animateFromStateWithThreeElement(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		return Promise.resolve();
	}

	async animateFromStateWithCounter(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		return Promise.resolve();
	}

	skipAnimation(): Promise
	{
		return Promise.resolve();
	}
}
