import { SecondaryContent } from './secondary-content';
import type { BaseSecondaryTool } from '../tools/base-secondary-tool';
import { SecuritySecondaryTool } from '../tools/security-secondary-tool';
import { ThemeSecondaryTool } from '../tools/theme-secondary-tool';

export class ExtranetSecondaryContent extends SecondaryContent
{
	getTools(): Array<BaseSecondaryTool>
	{
		return this.cache.remember('tools', () => {
			const tools = this.getOptions().tools;

			return [
				tools.security ? new SecuritySecondaryTool(tools.security) : null,
				tools.theme ? new ThemeSecondaryTool(tools.theme) : null,
			].filter(Boolean);
		});
	}

	getId(): string
	{
		return 'extranet-secondary';
	}
}
