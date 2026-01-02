import { Type, Loc } from 'main.core';
import { Options } from '../options';
import { NavigationPanel } from 'ui.navigationpanel';

type DocumentGridViewSwitcherOptions = {
	targetId: string,
	activeButtonId: string,
}

export class Switcher
{
	target: ?HTMLElement = null;
	activeButtonId: string;

	constructor(options: DocumentGridViewSwitcherOptions)
	{
		this.target = document.getElementById(options.targetId);
		this.activeButtonId = options.activeButtonId || '';
	}

	init()
	{
		if (Type.isDomNode(this.target))
		{
			new NavigationPanel({
				target: this.target,
				items: [
					{
						title: Loc.getMessage('DISK_DOCUMENTS_GRID_VIEW_LIST'),
						active: this.activeButtonId === 'list',
						events: {
							click: Options.setViewList,
						},
					},
					{
						title: Loc.getMessage('DISK_DOCUMENTS_GRID_VIEW_SMALL_TILE'),
						active: this.activeButtonId === 'smallTile',
						events: {
							click: Options.setViewSmallTile,
						},
					},
					{
						title: Loc.getMessage('DISK_DOCUMENTS_GRID_VIEW_TILE'),
						active: this.activeButtonId === 'tile',
						events: {
							click: Options.setViewBigTile,
						},
					},
				],
			}).init();
		}
	}
}
