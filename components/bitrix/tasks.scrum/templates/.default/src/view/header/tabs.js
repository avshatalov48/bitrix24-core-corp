import {Event, Tag, Text} from 'main.core';

import {SidePanel} from '../../service/side.panel';

import type {Views} from '../view';

type Params = {
	sidePanel: SidePanel,
	views: Views
}

export class Tabs
{
	constructor(params: Params)
	{
		this.sidePanel = params.sidePanel;
		this.views = params.views;

		this.node = null;
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

		this.node = Tag.render`
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

		this.node.querySelectorAll('a').forEach((tab: HTMLElement) => {
			Event.bind(tab, 'click', () => {
				const topSidePanel = this.sidePanel.getTopSidePanel()
				if (topSidePanel !== null)
				{
					topSidePanel.showLoader();
				}
			});
		});

		return this.node;
	}
}