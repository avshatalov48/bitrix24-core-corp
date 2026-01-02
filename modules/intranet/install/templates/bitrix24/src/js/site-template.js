import { Type, Reflection, Runtime, Dom, Browser } from 'main.core';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import { ChatMenu } from './chat-menu';
import { Composite } from './composite';
import { RightBar } from './right-bar';
import { Header } from './header/header';
import { Footer } from './footer';
import { GoTopButton } from './go-top-button';
import { CollaborationMenu } from './collaboration-menu';

export class SiteTemplate
{
	#rightBar: RightBar = null;
	#header: Header = null;
	#footer: Footer = null;
	#composite: Composite = null;
	#chatMenu: ChatMenu = null;
	#goTopButton: GoTopButton = null;
	#collaborationMenu: CollaborationMenu = null;

	constructor()
	{
		this.#preventFromIframe();

		this.#patchPopupMenu();
		this.#patchRestAPI();
		this.#patchJSClock();

		this.#goTopButton = new GoTopButton();
		this.#rightBar = new RightBar({
			goTopButton: this.#goTopButton,
		});
		this.#header = new Header();
		this.#footer = new Footer();
		this.#composite = new Composite();
		this.#chatMenu = new ChatMenu();
		this.#collaborationMenu = new CollaborationMenu();

		this.#applyUserAgentRules();
	}

	getRightBar(): RightBar
	{
		return this.#rightBar;
	}

	getHeader(): Header
	{
		return this.#header;
	}

	getFooter(): Footer
	{
		return this.#footer;
	}

	getComposite(): Composite
	{
		return this.#composite;
	}

	getChatMenu(): ChatMenu
	{
		return this.#chatMenu;
	}

	getCollaborationMenu(): CollaborationMenu
	{
		return this.#collaborationMenu;
	}

	#patchPopupMenu(): void
	{
		EventEmitter.subscribe('BX.Main.Menu:onInit', (event: BaseEvent) => {
			const { params } = event.getData();
			if (params && Type.isNumber(params.maxWidth))
			{
				// We increased menu-item's font-size that's why we increase max-width
				params.maxWidth += 10;
			}
		});
	}

	#patchJSClock(): void
	{
		EventEmitter.subscribe('onJCClockInit', (config) => {
			window.JCClock.setOptions({
				centerXInline: 83,
				centerX: 83,
				centerYInline: 67,
				centerY: 79,
				minuteLength: 31,
				hourLength: 26,
				popupHeight: 229,
				inaccuracy: 15,
				cancelCheckClick: true,
			});
		});
	}

	#preventFromIframe(): void
	{
		const iframeMode = window !== window.top;
		if (iframeMode)
		{
			window.top.location = window.location.href;
		}
	}

	#applyUserAgentRules(): void
	{
		if (!Browser.isMobile() && document.referrer !== '' && document.referrer.startsWith(location.origin) === false)
		{
			Runtime.loadExtension('intranet.recognize-links');
		}
	}

	#patchRestAPI(): void
	{
		const AppLayout = Reflection.getClass('BX.rest.AppLayout');
		if (!AppLayout)
		{
			return;
		}

		const placementInterface = AppLayout.initializePlacement('DEFAULT');
		placementInterface.prototype.showHelper = async function(params, cb)
		{
			let query = '';
			if (Type.isNumber(params))
			{
				query = `redirect=detail&code=${params}`;
			}
			else if (Type.isStringFilled(params))
			{
				query = params;
			}
			else if (Type.isPlainObject(params))
			{
				for (const param of Object.keys(params))
				{
					if (query.length > 0)
					{
						query += '&';
					}

					query += `${param}=${params[param]}`;
				}
			}

			if (query.length > 0)
			{
				await Runtime.loadExtension('helper');
				const Helper = Reflection.getClass('BX.Helper');
				Helper.show(query);
			}
		};
	}
}
