import {Dom} from 'main.core';

import {View} from './view/view';
import {Plan} from './view/plan/plan';
import {ActiveSprint} from './view/active.sprint';
import {CompletedSprint} from './view/completed.sprint';

import {Culture, CultureData} from './utility/culture';

type Params = {
	viewName: string,
	culture: CultureData
}

export class Entry
{
	constructor(params: Params)
	{
		this.setParams(params);

		this.buildView(params);
	}

	setParams(params: Params)
	{
		this.setViewName(params.viewName);

		Culture.getInstance().setData(params.culture);
	}

	setViewName(viewName: string)
	{
		const availableViews = new Set([
			'plan',
			'activeSprint',
			'completedSprint'
		]);

		if (!availableViews.has(viewName))
		{
			throw Error('Invalid value to activeView parameter');
		}

		this.viewName = viewName;
	}

	getViewName(): string
	{
		return this.viewName;
	}

	setView(view: View)
	{
		if (view instanceof View)
		{
			this.view = view;
		}
		else
		{
			this.view = null;
		}
	}

	getView(): View
	{
		return this.view;
	}

	buildView(params: Params)
	{
		const viewName = this.getViewName();

		if (viewName === 'plan')
		{
			this.setView(new Plan(params));
		}
		else if (viewName === 'activeSprint')
		{
			this.setView(new ActiveSprint(params));
		}
		else if (viewName === 'completedSprint')
		{
			this.setView(new CompletedSprint(params));
		}
	}

	renderTo(container: HTMLElement)
	{
		const view = this.getView();
		if (view instanceof View)
		{
			this.getView().renderTo(container);
		}
	}

	renderTabsTo(container: HTMLElement)
	{
		const view = this.getView();
		if (view instanceof View)
		{
			this.getView().renderTabsTo(container);
		}
	}

	renderSprintStatsTo(container: HTMLElement)
	{
		const view = this.getView();
		if (view instanceof View)
		{
			this.getView().renderSprintStatsTo(container);
		}
	}

	renderRightElementsTo(container: HTMLElement)
	{
		const view = this.getView();
		if (view instanceof View)
		{
			this.getView().renderRightElementsTo(container);
		}
	}

	setDisplayPriority(menuItem: HTMLElement, value: string)
	{
		if (!Dom.hasClass(menuItem, 'menu-popup-item-accept'))
		{
			this.refreshIcons(menuItem);

			const view = this.getView();
			if (view instanceof View)
			{
				this.getView().setDisplayPriority(value);
			}
		}
	}

	refreshIcons(item: HTMLElement)
	{
		item.parentElement.childNodes.forEach((element) => {
			Dom.removeClass(element, 'menu-popup-item-accept');
		});

		Dom.addClass(item, 'menu-popup-item-accept');
	}
}
