import {Type} from 'main.core';
import { DesktopApi } from 'im.v2.lib.desktop-api';
import type {MessengerFacade} from '../controller';

const desktopFeatureMap = {
	'iframe': 39
};

export class Desktop
{
	constructor(params)
	{
		this.parentPhoneCallView = params.parentPhoneCallView;
		this.closable = params.closable;
		this.title = params.title || '';
		this.window = null;
	}

	openCallWindow(content, js, params)
	{
		params = params || {};

		if (params.minSettingsWidth)
		{
			this.minSettingsWidth = params.minSettingsWidth;
		}

		if (params.minSettingsHeight)
		{
			this.minSettingsHeight = params.minSettingsHeight;
		}

		params.resizable = (params.resizable === true);

		DesktopApi.createWindow("callWindow", (callWindow) =>
		{
			callWindow.SetProperty("clientSize", {Width: params.width, Height: params.height});
			callWindow.SetProperty("resizable", params.resizable);
			if (params.resizable && params.hasOwnProperty('minWidth') && params.hasOwnProperty('minHeight'))
			{
				callWindow.SetProperty("minClientSize", {Width: params.minWidth, Height: params.minHeight});
			}
			callWindow.SetProperty("title", this.title);
			callWindow.SetProperty("closable", true);

			//callWindow.OpenDeveloperTools();
			let html = this.#getHtmlPage(content, js, {});
			callWindow.ExecuteCommand("html.load", html);
			this.window = callWindow;
		});
	};

	setClosable(closable: boolean)
	{
		this.closable = (closable === true);
		if (this.window)
		{
			this.window.SetProperty("closable", this.closable);
		}
	};

	setTitle(title)
	{
		this.title = title;
		if (this.window)
		{
			this.window.SetProperty("title", title)
		}
	};

	#getHtmlPage(content, jsContent, initImJs, bodyClass)
	{
		content = content || '';
		jsContent = jsContent || '';
		bodyClass = bodyClass || '';

		if (this.htmlWrapperHead == null)
		{
			this.htmlWrapperHead = document.head.outerHTML.replace(/BX\.PULL\.start\([^)]*\);/g, '');
		}

		if (Type.isDomNode(content))
		{
			content = content.outerHTML;
		}

		if (Type.isDomNode(jsContent))
		{
			jsContent = jsContent.outerHTML;
		}

		if (Type.isStringFilled(jsContent))
		{
			jsContent =
				`<script>
					BX.ready(function() {
						${jsContent}
					});
				</script>`;
		}

		let initJs = '';
		if (initImJs)
		{
			initJs = `
				<script>
					BX.ready(function() {
							const backgroundWorker = new BX.Voximplant.BackgroundWorker();
							
							window.PCW = new BX.Voximplant.PhoneCallView({
								isDesktop: true,
								slave: true, 
								skipOnResize: true, 
								callId: '${this.parentPhoneCallView.callId}',
								uiState: ${this.parentPhoneCallView._uiState},
								phoneNumber: '${this.parentPhoneCallView.phoneNumber}',
								companyPhoneNumber: '${this.parentPhoneCallView.companyPhoneNumber}',
								direction: '${this.parentPhoneCallView.direction}',
								fromUserId: '${this.parentPhoneCallView.fromUserId}',
								toUserId: '${this.parentPhoneCallView.toUserId}',
								crm: ${this.parentPhoneCallView.crm},
								hasSipPhone: ${this.parentPhoneCallView.hasSipPhone},
								deviceCall: ${this.parentPhoneCallView.deviceCall},
								transfer: ${this.parentPhoneCallView.transfer},
								crmEntityType: '${this.parentPhoneCallView.crmEntityType}',
								crmEntityId: '${this.parentPhoneCallView.crmEntityId}',
								crmActivityId: '${this.parentPhoneCallView.crmActivityId}',
								crmActivityEditUrl: '${this.parentPhoneCallView.crmActivityEditUrl}',
								callListId: ${this.parentPhoneCallView.callListId},
								callListStatusId: '${this.parentPhoneCallView.callListStatusId}',
								callListItemIndex: ${this.parentPhoneCallView.callListItemIndex},
								config: ${this.parentPhoneCallView.config ? JSON.stringify(this.parentPhoneCallView.config) : '{}'},
								portalCall: ${this.parentPhoneCallView.portalCall ? 'true' : 'false'},
								portalCallData: ${this.parentPhoneCallView.portalCallData ? JSON.stringify(this.parentPhoneCallView.portalCallData) : '{}'},
								portalCallUserId: ${this.parentPhoneCallView.portalCallUserId},
								webformId: ${this.parentPhoneCallView.webformId},
								webformSecCode: '${this.parentPhoneCallView.webformSecCode}',
								backgroundWorker: backgroundWorker,
								restApps: ${this.parentPhoneCallView.restApps ? JSON.stringify(this.parentPhoneCallView.restApps) : '[]'},
							});
					});
				</script>`;
		}
		return `
			<!DOCTYPE html>
			<html lang="${document.documentElement.lang}">
				${this.htmlWrapperHead}
				<body class="im-desktop im-desktop-popup ${bodyClass}">
					<div id="placeholder-messanger">${content}</div>
					${initJs}
					${jsContent}
				</body>
			</html>
		`;
	};

	addCustomEvent(eventName, eventHandler)
	{
		BX.desktop.addCustomEvent(eventName, eventHandler);
	};

	onCustomEvent(windowTarget, eventName, arEventParams)
	{
		BX.desktop.onCustomEvent(windowTarget, eventName, arEventParams);
	};

	resize(width, height)
	{
		BXDesktopWindow.SetProperty("clientSize", {Width: width, Height: height});
	};

	setResizable(resizable)
	{
		resizable = (resizable === true);
		BXDesktopWindow.SetProperty("resizable", resizable);
	};

	setMinSize(width, height)
	{
		BXDesktopWindow.SetProperty("minClientSize", {Width: width, Height: height});
	};

	setWindowPosition(params)
	{
		BXDesktopWindow.SetProperty("position", params);
	};

	center()
	{
		BXDesktopWindow.ExecuteCommand("center");
	};

	getVersion(full)
	{
		if (typeof (BXDesktopSystem) == 'undefined')
		{
			return 0;
		}

		if (!this.clientVersion)
		{
			this.clientVersion = BXDesktopSystem.GetProperty('versionParts');
		}

		return full ? this.clientVersion.join('.') : this.clientVersion[3];
	};

	isFeatureSupported(featureName)
	{
		if (!desktopFeatureMap.hasOwnProperty(featureName))
		{
			return false;
		}

		return this.getVersion() >= desktopFeatureMap[featureName];
	};
}