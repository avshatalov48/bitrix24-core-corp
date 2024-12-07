import { Main } from 'ui.icon-set.api.core';
import 'ui.icon-set.main';

import type { BaseMenuItemGetOptions, BaseMenuItemOptions } from '../../../src/copilot-menu/copilot-menu-item';
import { BaseMenuItem } from '../../../src/copilot-menu/copilot-menu-item';

export type ProviderMenuItemOptions = BaseMenuItemOptions & {
	selected: boolean;
}

type ProviderMenuItemGetOptions = BaseMenuItemGetOptions & {
	selected: boolean;
}

export class ProviderMenuItem extends BaseMenuItem
{
	selected: boolean;
	constructor(options: ProviderMenuItemOptions)
	{
		super({
			icon: Main.ROBOT,
			...options,
		});

		this.selected = options.selected === true;
	}

	getOptions(): ProviderMenuItemGetOptions
	{
		return {
			...super.getOptions(),
			selected: this.selected,
		};
	}
}
