import { Type } from 'main.core';

import { SidePanel } from '../../service/side.panel';
import type { Views } from '../view';
import { NavigationPanel } from 'ui.navigationpanel';

type Params = {
	sidePanel: SidePanel,
	views: Views,
	target: HTMLElement,
}

export class Tabs extends NavigationPanel
{
	constructor(params: Params): void
	{
		params.views.forEach((view) => {
			if (Type.isStringFilled(view.url))
			{
				view.events = {
					click: () => this.openUrl(),
				};
			}
		});

		super({
			target: params.target,
			items: params.views,
		});

		this.sidePanel = params.sidePanel;
	}

	openUrl(): void
	{
		const topSidePanel = this.sidePanel.getTopSidePanel();

		if (topSidePanel !== null)
		{
			topSidePanel.showLoader();
		}
	}
}
