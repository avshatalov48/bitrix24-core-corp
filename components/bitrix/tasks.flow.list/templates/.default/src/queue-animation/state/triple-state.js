import { Dom, Event } from 'main.core';

import { State } from '../queue-animation';
import { QueueAnimationHelper } from '../queue-animation-helper';
import { User } from '../queue-animation-manager';
import { QueueState } from './queue-state';

export class TripleState extends QueueState
{
	#animationHelper: QueueAnimationHelper;

	constructor(animationHelper: QueueAnimationHelper)
	{
		super(animationHelper);

		this.#animationHelper = animationHelper;
	}

	async animateFromStateWithTwoElement(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		return new Promise((resolve) => {
			const firstAvatar = children[0];
			const lastAvatar = children[1];

			const newFirstAvatar = this.#animationHelper.renderHiddenAvatar(users[0], '--start-invisible');

			Dom.prepend(newFirstAvatar, list);

			requestAnimationFrame(() => {
				Event.bindOnce(firstAvatar, 'transitionend', () => {
					// eslint-disable-next-line promise/catch-or-return
					this.#animationHelper.blinkAvatar(newFirstAvatar)
						.then(() => {
							this.#animationHelper.removeClassesWithoutAnimation(
								newFirstAvatar,
								['--start-invisible', '--tiny-left'],
							);
							this.#animationHelper.removeClassesWithoutAnimation(
								firstAvatar,
								['--tiny-right'],
							);
							this.#animationHelper.removeClassesWithoutAnimation(
								lastAvatar,
								['--tiny-right'],
							);

							resolve();
						})
					;
				});

				Dom.removeClass(newFirstAvatar, ['--hidden']);

				Dom.addClass(newFirstAvatar, ['--tiny-left']);
				Dom.addClass(firstAvatar, ['--tiny-right']);
				Dom.addClass(lastAvatar, ['--tiny-right']);
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
		return this.#animationHelper.changeAvatarsToUser([...children], users);
	}

	async animateFromStateWithCounter(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		await this.#animationHelper.hideAvatars([...children]);

		await this.#animationHelper.changeAvatarsToUser([...children], users)
			.then(() => {
				return this.#animationHelper.showAvatars([...children]);
			})
		;

		return Promise.resolve();
	}
}
