import { Content } from './content';
import { Tag, Dom } from 'main.core';
import type { BaseSecondaryTool } from '../tools/base-secondary-tool';
import type { BaseTool } from '../tools/base-tool';
import { InstallMobileTool } from '../tools/install-mobile-tool';
import { FastMobileAuthTool } from '../tools/fast-mobile-auth-tool';
import { ApplicationsInstallerTool } from '../tools/applications-installer-tool';

export class ApplicationContent extends Content
{
	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			const container = Tag.render`
				<div data-testid="bx-avatar-widget-content-application" class="intranet-avatar-widget-item__wrapper"></div>
			`;
			this.#getTools().forEach((tool) => {
				Dom.append(tool.getLayout(), container);
			});

			return container;
		});
	}

	#getTools(): Array<BaseSecondaryTool | BaseTool>
	{
		return this.cache.remember('tools', () => {
			const tools = this.getOptions().tools;

			return [
				tools.installMobile ? new InstallMobileTool(tools.installMobile) : null,
				tools.fastMobileAuth ? new FastMobileAuthTool(tools.fastMobileAuth) : null,
				tools.applicationsInstaller ? new ApplicationsInstallerTool(tools.applicationsInstaller) : null,
			].filter(Boolean);
		});
	}
}
