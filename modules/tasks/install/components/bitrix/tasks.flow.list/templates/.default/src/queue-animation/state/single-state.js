import { Dom, Event } from 'main.core';

import { State } from '../queue-animation';
import { QueueAnimationHelper } from '../queue-animation-helper';
import { User } from '../queue-animation-manager';
import { QueueState } from './queue-state';

export class SingleState extends QueueState
{
	#animationHelper: QueueAnimationHelper;

	constructor(animationHelper: QueueAnimationHelper)
	{
		super(animationHelper);

		this.#animationHelper = animationHelper;
	}

	async animateFromStateWithOneElement(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		const firstAvatar = children[0];

		return this.#animationHelper.changeAvatarsToUser([firstAvatar], users).then(() => {
			this.#animationHelper.blinkAvatar(firstAvatar);
		});
	}

	async animateFromStateWithTwoElement(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		const firstAvatar = children[0];
		const lastAvatar = children[1];

		const newFirstAvatar = this.#animationHelper.renderHiddenAvatar(users[0], '--invisible');

		Dom.prepend(newFirstAvatar, list);

		return new Promise((resolve, reject) => {
			Event.bindOnce(lastAvatar, 'transitionend', () => {
				Dom.remove(firstAvatar);
				Dom.remove(lastAvatar);

				Event.bindOnce(newFirstAvatar, 'transitionend', () => {
					this.#animationHelper.blinkAvatar(newFirstAvatar);

					resolve();
				});
				Dom.removeClass(newFirstAvatar, ['--hidden', '--invisible']);
			});
			Dom.addClass(firstAvatar, '--tiny-right');
			Dom.addClass(firstAvatar, '--hidden');
			Dom.addClass(lastAvatar, '--tiny-left');
			Dom.addClass(lastAvatar, '--hidden');
		});
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

		Dom.addClass(firstAvatar, ['--center']);
		Dom.addClass(lastAvatar, ['--center']);

		return this.#animationHelper.changeAvatarsToUser([middleAvatar], users).then(() => {
			Event.bindOnce(lastAvatar, 'transitionend', () => {
				this.#animationHelper.blinkAvatar(middleAvatar);
				Dom.remove(firstAvatar);
				Dom.remove(lastAvatar);
			});
			Dom.addClass(lastAvatar, '--hidden');
		});
	}
}
