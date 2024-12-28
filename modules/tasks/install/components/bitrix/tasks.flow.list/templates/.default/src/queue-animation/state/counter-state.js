import { Dom, Event } from 'main.core';

import { State } from '../queue-animation';
import { QueueAnimationHelper } from '../queue-animation-helper';
import { User } from '../queue-animation-manager';
import { QueueState } from './queue-state';

export class CounterState extends QueueState
{
	#animationHelper: QueueAnimationHelper;

	#counter: number;
	#statusId: string;

	constructor(animationHelper: QueueAnimationHelper)
	{
		super(animationHelper);

		this.#animationHelper = animationHelper;
	}

	animate(node: HTMLElement, users: Array<User>, state: State, statusId: string): Promise
	{
		const maxVisibleNumberAvatars = 99;
		const visibleAmount = 2;
		const invisibleAmount = state.total - visibleAmount;

		this.#counter = Math.min(invisibleAmount, maxVisibleNumberAvatars);
		this.#statusId = statusId;

		return super.animate(node, users, state, statusId);
	}

	async animateFromStateWithThreeElement(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		const firstAvatar = children[0];
		const middleAvatar = children[1];
		const lastAvatar = children[2];

		const newFirstAvatar = this.#animationHelper.renderHiddenAvatar(users[0], '--invisible');
		const newMiddleAvatar = this.#animationHelper.renderHiddenAvatar(users[1], '--invisible');

		Dom.prepend(newMiddleAvatar, list);
		Dom.prepend(newFirstAvatar, list);

		Event.bindOnce(firstAvatar, 'transitionend', () => {
			Dom.remove(firstAvatar);
			Dom.removeClass(newFirstAvatar, ['--invisible']);
		});
		Event.bindOnce(middleAvatar, 'transitionend', () => {
			Dom.remove(middleAvatar);
			Dom.removeClass(newMiddleAvatar, ['--invisible']);
		});

		Dom.removeClass(newFirstAvatar, ['--hidden']);
		Dom.removeClass(newMiddleAvatar, ['--hidden']);

		Dom.addClass(firstAvatar, ['--right']);
		Dom.addClass(middleAvatar, ['--right']);

		const memberClass = this.#statusId === 'AT_WORK' ? '--at-work' : '';

		return this.#animationHelper.changeAvatarToCounter(lastAvatar, this.#counter, memberClass)
			.then(() => {
				this.#animationHelper.blinkAvatar(lastAvatar);
			})
		;
	}
}
