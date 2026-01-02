import { Dom, Tag } from 'main.core';
import { Content } from './content';
import { BaseFooterTool } from '../tools/base-footer-tool';
import { ThemeTool } from '../tools/theme-tool';
import { PulseTool } from '../tools/pulse-tool';
import { LogoutTool } from '../tools/logout-tool';

export class FooterContent extends Content
{
	getConfig(): Object
	{
		return {
			html: this.getLayout(),
			withoutBackground: true,
			margin: '0 0 16px 0',
		};
	}

	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			const container = Tag.render`
				<div data-testid="bx-avatar-widget-content-footer" class="intranet-avatar-widget-footer__wrapper"/>
			`;
			const tools = this.#getTools();

			tools.forEach((tool) => {
				Dom.append(tool.getLayout(), container);
			});

			return container;
		});
	}

	#getTools(): Array<BaseFooterTool>
	{
		return this.cache.remember('tools', () => {
			const tools = this.getOptions().tools;

			return [
				tools.theme ? new ThemeTool(tools.theme) : null,
				tools.pulse ? new PulseTool(tools.pulse) : null,
				tools.logout ? new LogoutTool(tools.logout) : null,
			].filter(Boolean);
		});
	}
}
