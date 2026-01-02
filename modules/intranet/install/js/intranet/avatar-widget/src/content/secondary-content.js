import { Content } from './content';
import { Tag, Dom } from 'main.core';
import { AccountChangerTool } from '../tools/account-changer-tool';
import type { BaseSecondaryTool } from '../tools/base-secondary-tool';
import { AdministrationTool } from '../tools/administration-tool';
import { PerformanceReviewTool } from '../tools/performance-review-tool';

export class SecondaryContent extends Content
{
	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			const container = Tag.render`
				<div data-testid="bx-avatar-widget-content-${this.getId()}" class="intranet-avatar-widget-item__wrapper"></div>
			`;
			this.getTools().forEach((tool) => {
				Dom.append(tool.getLayout(), container);
			});

			return container;
		});
	}

	getTools(): Array<BaseSecondaryTool>
	{
		return this.cache.remember('tools', () => {
			const tools = this.getOptions().tools;

			return [
				tools.accountChanger ? new AccountChangerTool(tools.accountChanger) : null,
				tools.admin ? new AdministrationTool(tools.admin) : null,
				tools.perfReview ? new PerformanceReviewTool(tools.perfReview) : null,
			].filter(Boolean);
		});
	}

	getId(): string
	{
		return 'secondary';
	}
}
