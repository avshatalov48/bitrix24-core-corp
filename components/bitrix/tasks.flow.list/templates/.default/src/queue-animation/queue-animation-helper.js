import { Dom, Event, Loc, Tag, Text } from 'main.core';

import { State } from './queue-animation';
import { User } from './queue-animation-manager';

export class QueueAnimationHelper
{
	#rowId: number;

	constructor(rowId: number)
	{
		this.#rowId = rowId;
	}

	changeAvatarsToEmpty(nodes: Array<HTMLElement>): Promise
	{
		const promises = nodes.map((node) => {
			return new Promise((resolve) => {
				Dom.addClass(node, '--icon');

				Event.bindOnce(node, 'transitionend', () => {
					Dom.style(node, null);
					Dom.append(this.#renderPersonNode(), node);
					requestAnimationFrame(() => {
						resolve();
					});
				});
			});
		});

		return Promise.all(promises);
	}

	async showAvatars(nodes: Array<HTMLElement>): Promise
	{
		const promises = nodes.map((node) => {
			return new Promise((resolve) => {
				Dom.removeClass(node, '--hidden');

				Event.bindOnce(node, 'transitionend', () => {
					resolve();
				});
			});
		});

		return Promise.all(promises);
	}

	async hideAvatars(nodes: Array<HTMLElement>): Promise
	{
		const promises = nodes.map((node) => {
			return new Promise((resolve) => {
				Dom.addClass(node, '--hidden');

				Event.bindOnce(node, 'transitionend', () => {
					resolve(node);
				});
			});
		});

		return Promise.all(promises);
	}

	renderHiddenEmptyAvatar(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__list-members-icon_element --icon --hidden --center">
				${this.#renderPersonNode()}
			</div>
		`;
	}

	renderHiddenAvatar(user: User, extraClass: string = ''): HTMLElement
	{
		if ('src' in user.photo)
		{
			return Tag.render`
				<div
					class="tasks-flow__list-members-icon_element --hidden ${extraClass}"
					style="background-image: url('${encodeURI(user.photo.src)}')"
				></div>
			`;
		}

		const uiClasses = 'ui-icon ui-icon-common-user ui-icon-xs';

		return Tag.render`
			<div
				class="tasks-flow__list-members-icon_element ${uiClasses} --hidden ${extraClass}"
			>
				<i></i>
			</div>
		`;
	}

	async changeAvatarsToUser(nodes: Array<HTMLElement>, users: Array<User>): Promise
	{
		return new Promise((resolve, reject) => {
			nodes.forEach((node: HTMLElement, index) => {
				const user = users[index];

				Dom.removeClass(node, ['--icon', '--count', '--at-work']);
				Dom.clean(node);

				if ('src' in user.photo)
				{
					Dom.style(node, 'background-image', `url('${encodeURI(user.photo.src)}')`);
				}
				else
				{
					Dom.addClass(node, ['ui-icon', 'ui-icon-common-user', 'ui-icon-xs']);
					Dom.append(Tag.render`<i></i>`, node);
				}
			});

			requestAnimationFrame(() => {
				resolve();
			});
		});
	}

	changeAvatarToCounter(node: HTMLElement, count: number, memberClass: string = ''): Promise
	{
		return new Promise((resolve, reject) => {
			Dom.removeClass(node, ['ui-icon', 'ui-icon-common-user', 'ui-icon-xs']);
			Dom.addClass(node, ['--count', memberClass]);

			Dom.clean(node);

			Dom.style(node, null);

			Dom.append(Tag.render`
				<span class="tasks-flow__warning-icon_element-plus">+</span>
			`, node);
			Dom.append(Tag.render`
				<span class="tasks-flow__warning-icon_element-number">${parseInt(count, 10)}</span>
			`, node);

			requestAnimationFrame(() => {
				resolve();
			});
		});
	}

	changeNodeAttributes(statusId: string, node: HTMLElement, state: State, isEmpty: boolean): void
	{
		const wrapper = node.querySelector('.tasks-flow__list-members_wrapper');
		const label = node.querySelector('.tasks-flow__list-members_info');
		const list = node.querySelector('.tasks-flow__list-members');

		Dom.attr(list, 'data-total', state.total);
		Dom.attr(list, 'data-subsequence', state.subsequence.join(','));

		if (isEmpty)
		{
			Dom.removeClass(wrapper, '--link');
			Dom.attr(wrapper, 'onclick', null);

			this.#changeLabel(label, Loc.getMessage('TASKS_FLOW_LIST_NO_TASKS'), true);
		}
		else
		{
			Dom.addClass(wrapper, '--link');
			wrapper.setAttribute(
				'onclick',
				`BX.Tasks.Flow.Grid.showTaskQueue('${this.#rowId}', '${statusId}', this)`,
			);

			this.#changeLabel(label, state.label);
		}
	}

	blinkAvatar(node: HTMLElement): Promise
	{
		return new Promise((resolve) => {
			Event.bindOnce(node, 'animationend', () => {
				Dom.removeClass(node, ['--blink']);

				resolve();
			});

			Dom.addClass(node, ['--blink']);
		});
	}

	removeClassesWithoutAnimation(node: HTMLElement, classes: Array<string>): void
	{
		Dom.style(node, 'transition', 'none');
		Dom.removeClass(node, classes);
		// eslint-disable-next-line no-unused-expressions
		node.offsetHeight;
		Dom.style(node, 'transition', '');
	}

	defineSubsequenceIndices(
		currentSubsequence: Array<number>,
		newSubsequence: Array<number>,
	): { presentIndices: [], absentIndices: [] }
	{
		const iteratedSequence = (
			currentSubsequence.length >= newSubsequence.length
				? currentSubsequence
				: newSubsequence
		);
		const comparedSequence = (
			currentSubsequence.length >= newSubsequence.length
				? newSubsequence
				: currentSubsequence
		);

		return iteratedSequence.reduce((result, value, index) => {
			if (comparedSequence.includes(value))
			{
				result.presentIndices.push(index);
			}
			else
			{
				result.absentIndices.push(index);
			}

			return result;
		}, { presentIndices: [], absentIndices: [] });
	}

	#changeLabel(node: HTMLElement, text: string, isEmpty: boolean = false): void
	{
		// eslint-disable-next-line no-param-reassign
		node.textContent = Text.encode(text);

		if (isEmpty)
		{
			Dom.removeClass(node, '--link');
		}
		else
		{
			Dom.addClass(node, '--link');
		}
	}

	#renderPersonNode(): HTMLElement
	{
		return Tag.render`
			<div
				class="ui-icon-set --person"
				style="--ui-icon-set__icon-color: var(--ui-color-base-50);"
			></div>
		`;
	}
}
