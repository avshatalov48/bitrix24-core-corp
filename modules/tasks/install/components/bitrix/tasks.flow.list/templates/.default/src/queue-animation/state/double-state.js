import { Dom, Event } from 'main.core';

import { State } from '../queue-animation';
import type { QueueAnimationHelper } from '../queue-animation-helper';
import { User } from '../queue-animation-manager';
import { QueueState } from './queue-state';

export class DoubleState extends QueueState
{
	#animationHelper: QueueAnimationHelper;

	constructor(animationHelper: QueueAnimationHelper)
	{
		super(animationHelper);

		this.#animationHelper = animationHelper;
	}

	animateFromStateWithOneElement(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		return new Promise((resolve) => {
			const firstAvatar = children[0];

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

							resolve();
						})
					;
				});

				Dom.removeClass(newFirstAvatar, ['--hidden']);

				Dom.addClass(newFirstAvatar, ['--tiny-left']);
				Dom.addClass(firstAvatar, ['--tiny-right']);
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
		return this.#animationHelper.changeAvatarsToUser([...children], users);
	}

	async animateFromStateWithThreeElement(
		list: HTMLElement,
		children: HTMLCollection,
		users: Array<User>,
		state: State,
	): Promise
	{
		const currentSubsequence = list.dataset.subsequence.split(',').map(Number);
		const newSubsequence = state.subsequence;

		const { presentIndices, absentIndices } = this.#animationHelper.defineSubsequenceIndices(
			currentSubsequence,
			newSubsequence,
		);

		const removeAbsentAvatars = (absentAvatars, resolve) => {
			// eslint-disable-next-line promise/catch-or-return
			return this.#animationHelper.hideAvatars(absentAvatars).then(() => {
				absentAvatars.forEach((absentAvatar) => {
					Dom.remove(absentAvatar);
				});

				resolve();
			});
		};

		const variantMovements = {
			'1,2': (absentAvatars: Array<HTMLElement>) => {
				return new Promise((resolve) => {
					const middleAvatar = children[1];
					const lastAvatar = children[2];
					Event.bindOnce(lastAvatar, 'transitionend', () => {
						this.#animationHelper.removeClassesWithoutAnimation(middleAvatar, ['--tiny-left']);
						this.#animationHelper.removeClassesWithoutAnimation(lastAvatar, ['--tiny-left']);
					});
					Dom.addClass(middleAvatar, '--tiny-left');
					Dom.addClass(lastAvatar, '--tiny-left');

					removeAbsentAvatars(absentAvatars, resolve);
				});
			},
			'0,1': (absentAvatars: Array<HTMLElement>) => {
				return new Promise((resolve) => {
					const firstAvatar = children[0];
					const middleAvatar = children[1];
					Event.bindOnce(firstAvatar, 'transitionend', () => {
						this.#animationHelper.removeClassesWithoutAnimation(middleAvatar, ['--tiny-right']);
						this.#animationHelper.removeClassesWithoutAnimation(firstAvatar, ['--tiny-right']);
					});
					Dom.addClass(middleAvatar, '--tiny-right');
					Dom.addClass(firstAvatar, '--tiny-right');

					removeAbsentAvatars(absentAvatars, resolve);
				});
			},
			'0,2': (absentAvatars: Array<HTMLElement>) => {
				return new Promise((resolve) => {
					const firstAvatar = children[0];
					const lastAvatar = children[2];
					Event.bindOnce(lastAvatar, 'transitionend', () => {
						this.#animationHelper.removeClassesWithoutAnimation(firstAvatar, ['--tiny-right']);
						this.#animationHelper.removeClassesWithoutAnimation(lastAvatar, ['--tiny-left']);
					});
					Dom.addClass(firstAvatar, '--tiny-right');
					Dom.addClass(lastAvatar, '--tiny-left');

					removeAbsentAvatars(absentAvatars, resolve);
				});
			},
			default: () => {
				return Promise.resolve();
			},
		};

		const presentSubsequence = presentIndices.join(',');
		const variantMovement = (
			variantMovements[presentSubsequence]
			|| variantMovements.default
		);

		const absentAvatars = [];
		absentIndices.forEach((absentIndex) => {
			absentAvatars.push(children[absentIndex]);
		});

		return variantMovement(absentAvatars);
	}
}
