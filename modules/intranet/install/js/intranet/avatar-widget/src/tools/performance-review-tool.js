import { BaseSecondaryTool } from './base-secondary-tool';

export class PerformanceReviewTool extends BaseSecondaryTool
{
	getIconClass(): string
	{
		return '--o-feedback';
	}

	onClick(): void
	{
		document.location.href = this.options.path;
	}

	getId(): string
	{
		return 'performance-review';
	}
}
