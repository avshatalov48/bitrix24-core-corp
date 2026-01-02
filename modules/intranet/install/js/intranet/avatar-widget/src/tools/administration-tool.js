import { BaseSecondaryTool } from './base-secondary-tool';

export class AdministrationTool extends BaseSecondaryTool
{
	getIconClass(): string
	{
		return '--o-filter-2-lines';
	}

	onClick(): void
	{
		window.open(this.options.path, '_blank');
	}

	getId(): string
	{
		return 'administration';
	}
}
