import {Tag, Text} from 'main.core';

import type {Views} from '../view';

type Params = {
	views: Views
}

export class Tabs
{
	constructor(params: Params)
	{
		this.views = params.views;
	}

	render(): HTMLElement
	{
		const planTabActiveClass = (
			this.views['plan'].active
			? 'tasks-view-switcher--item --active'
			: ''
		);
		const activeTabActiveClass = (
			this.views['activeSprint'].active
			? 'tasks-view-switcher--item --active'
			: ''
		);
		const completedTabActiveClass = (
			this.views['completedSprint'].active
			? 'tasks-view-switcher--item --active'
			: ''
		);

		return Tag.render`
			<div class="tasks-view-switcher">
				<a
					href="${this.views['plan'].url}"
					class="tasks-view-switcher--item ${planTabActiveClass}"
				>${Text.encode(this.views['plan'].name)}</a>
				<a
					href="${this.views['activeSprint'].url}"
					class="tasks-view-switcher--item ${activeTabActiveClass}"
				>${Text.encode(this.views['activeSprint'].name)}</a>
				<a
					href="${this.views['completedSprint'].url}"
					class="tasks-view-switcher--item ${completedTabActiveClass}"
				>${Text.encode(this.views['completedSprint'].name)}</a>
			</div>
		`;
	}
}