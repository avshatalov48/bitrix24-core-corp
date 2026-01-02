import { BaseFooterTool } from './base-footer-tool';
import { Type, Uri } from 'main.core';
import { DesktopApi } from 'im.v2.lib.desktop-api';
import { Analytics } from '../analytics';

export class LogoutTool extends BaseFooterTool
{
	onClick(): void
	{
		Analytics.send(Analytics.EVENT_CLICK_LOGOUT);
		if (!Type.isNil(DesktopApi) && DesktopApi.isDesktop())
		{
			DesktopApi.logout();
		}
		else
		{
			const backUrl = new Uri(window.location.pathname);
			backUrl.removeQueryParam(this.options.removeQueryParam);
			const newUrl = new Uri(this.options.path);
			newUrl.setQueryParam('sessid', BX.bitrix_sessid());
			newUrl.setQueryParam('backurl', encodeURIComponent(backUrl.toString()));
			document.location.href = newUrl;
		}
	}

	getId(): string
	{
		return 'logout';
	}
}
