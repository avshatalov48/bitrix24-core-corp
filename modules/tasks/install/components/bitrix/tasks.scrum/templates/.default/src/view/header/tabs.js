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
		const planTabActiveClass = (this.views['plan'].active ? 'tasks-scrum-switcher-tab-active' : '');
		const activeTabActiveClass = (this.views['activeSprint'].active ? 'tasks-scrum-switcher-tab-active' : '');
		const completedTabActiveClass = (this.views['completedSprint'].active ? 'tasks-scrum-switcher-tab-active' : '');

		return Tag.render`
			<div class="tasks-scrum-switcher-views">
				<a
					href="${this.views['plan'].url}"
					class="tasks-scrum-switcher-tab ${planTabActiveClass}"
				>${Text.encode(this.views['plan'].name)}</a>
				<a
					href="${this.views['activeSprint'].url}"
					class="tasks-scrum-switcher-tab ${activeTabActiveClass}"
				>${Text.encode(this.views['activeSprint'].name)}</a>
				<a
					href="${this.views['completedSprint'].url}"
					class="tasks-scrum-switcher-tab ${completedTabActiveClass}"
				>${Text.encode(this.views['completedSprint'].name)}</a>
			</div>
		`;
	}
}