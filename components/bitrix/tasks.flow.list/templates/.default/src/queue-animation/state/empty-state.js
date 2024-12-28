import { Dom } from 'main.core';

import { State } from '../queue-animation';
import { QueueAnimationHelper } from '../queue-animation-helper';
import { User } from '../queue-animation-manager';
import { QueueState } from './queue-state';

export class EmptyState extends QueueState
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
		console.log('EmptyState');

		const currentAvatar = list.lastElementChild;

		Dom.prepend(this.#animationHelper.renderHiddenEmptyAvatar(), list);
		Dom.append(this.#animationHelper.renderHiddenEmptyAvatar(), list);

		return new Promise((resolve) => {
			requestAnimationFrame(() => {
				this.#moveAvatarsToCenter([...children]);

				// eslint-disable-next-line promise/catch-or-return
				this.#animationHelper.changeAvatarsToEmpty([currentAvatar]).then(() => {
					this.#moveAvatarsToDefaultPosition([...children]);

					resolve();
				});
			});
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
		const lastAvatar = children[2];

		const newEmptyAvatar = this.#animationHelper.renderHiddenEmptyAvatar();
		Dom.prepend(newEmptyAvatar, list);

		return requestAnimationFrame(() => {
			this.#moveAvatarsToCenter([...children]);

			return this.#animationHelper.changeAvatarsToEmpty([firstAvatar, lastAvatar]).then(() => {
				this.#moveAvatarsToDefaultPosition([...children]);
			});
		});
	}

	async animateFromStateWithThreeElement(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		this.#moveAvatarsToCenter([...children]);

		return this.#animationHelper.changeAvatarsToEmpty([...children]).then(() => {
			this.#moveAvatarsToDefaultPosition([...children]);
		});
	}

	#moveAvatarsToCenter(avatars: Array<HTMLElement>)
	{
		avatars.forEach((avatar: HTMLElement) => {
			Dom.addClass(avatar, '--center');
		});
	}

	#moveAvatarsToDefaultPosition(avatars: Array<HTMLElement>)
	{
		avatars.forEach((avatar: HTMLElement) => {
			Dom.removeClass(avatar, ['--center', '--hidden']);
		});
	}
}
