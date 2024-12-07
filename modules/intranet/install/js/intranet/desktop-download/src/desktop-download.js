import { Extension, Browser } from 'main.core';

export type DesktopLinksType = {
	windows: string,
	macos: string,
	linuxDeb: string,
	linuxRpm: string,
	msi: string,
	macosArm: string,
}

export class DesktopDownload
{
	links: DesktopLinksType;

	static getLinks(): DesktopLinksType
	{
		return Extension.getSettings('intranet.desktop-download').downloadLinks;
	}

	static getLinkForCurrentUser(): string
	{
		const downloadLinks = this.getLinks();

		if (Browser.isMac())
		{
			return downloadLinks.macos;
		}

		if (Browser.isLinux())
		{
			const UA = navigator.userAgent.toLowerCase();

			if (
				UA.includes('Fedora')
				|| UA.includes('CentOS')
				|| UA.includes('Red Hat')
			)
			{
				return downloadLinks.linuxRpm;
			}

			return downloadLinks.linuxDeb;
		}

		return downloadLinks.windows;
	}
}
