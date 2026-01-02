import { DesktopApi } from 'im.v2.lib.desktop-api';

export class UnsupportedBrowserFeatures
{
	static listOfDevicesBeforeStream: boolean[] = [BX.browser.IsFirefox()];
	static listOfPiPAvailable: boolean[] = [BX.browser.IsChrome(), DesktopApi.isDesktop()];

	static get isNotSupportDevicesListBeforeStream(): boolean
	{
		return this.listOfDevicesBeforeStream.some((browser) => browser);
	}

	static get isPiPAvailable(): boolean
	{
		return this.listOfPiPAvailable.some(device => device);
	}
}