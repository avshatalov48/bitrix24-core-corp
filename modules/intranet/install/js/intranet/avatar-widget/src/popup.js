import { Cache, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { PopupComponentsMaker } from 'ui.popupcomponentsmaker';
import { MainContent } from './content/main-content';
import { FooterContent } from './content/footer-content';
import { SecondaryContent } from './content/secondary-content';
import { ApplicationContent } from './content/application-content';
import { MobileAuthContent } from './content/mobile-auth-content';
import { Analytics } from './analytics';
import type { ConfigOptions, PopupOptions } from './types';
import { ExtranetSecondaryContent } from './content/extranet-secondary-content';

export class Popup extends EventEmitter
{
	#cache = new Cache.MemoryCache();
	#popupsShowAfterBasePopup: Array<Popup> = [];

	constructor(options: PopupOptions)
	{
		super();
		this.setOptions(options);
		this.setEventNamespace('BX.Intranet.AvatarWidget.Popup');
		this.setEventHandlers();
	}

	setOptions(options: PopupOptions): void
	{
		this.#cache.set('options', options);
	}

	getOptions(): PopupOptions
	{
		return this.#cache.get('options', {});
	}

	show(): void
	{
		this.getBasePopup().show();
		this.emit('show');
		Analytics.send(Analytics.EVENT_OPEN_WIDGET);
	}

	close(): void
	{
		this.getBasePopup().close();
	}

	getBasePopup(): PopupComponentsMaker
	{
		return this.#cache.remember('popup', () => {
			this.emit('beforeInit');

			const popup = new PopupComponentsMaker({
				target: this.getOptions().target,
				width: 390,
				content: this.#getContent(),
				popupLoader: this.getOptions().loader,
				padding: 0,
				offsetTop: -50,
				offsetLeft: 0,
			});
			popup.getPopup().setFixed(true);
			const setOverlay = () => {
				if (BX.SidePanel.Instance.isOpen())
				{
					popup.getPopup().setOverlay({
						backgroundColor: 'transparent',
					});
					popup.getPopup().showOverlay();
				}
			};
			setOverlay();
			popup.getPopup().subscribe('onClose', () => {
				this.#popupsShowAfterBasePopup = [];
				popup.getPopup().removeOverlay();
			});
			popup.getPopup().subscribe('onBeforeShow', setOverlay);
			this.#cache.set('popup', popup);
			this.#cache.set('contentWrapper', popup.getContentWrapper());

			this.emit('afterInit');

			return popup;
		});
	}

	#getContent(): Array<ConfigOptions>
	{
		return this.#cache.remember('content', () => {
			const content = [
				this.#getMainContent().getConfig(),
			];

			content.push(this.#getApplicationContent().getConfig());

			if (this.getOptions().content.extranetSecondary)
			{
				content.push(this.#getExtranetSecondaryContent().getConfig());
			}

			if (this.getOptions().content.secondary)
			{
				content.push(this.#getSecondaryContent().getConfig());
			}

			content.push(this.#getFooterContent().getConfig());

			return content;
		});
	}

	#getMainContent(): MainContent
	{
		return this.#cache.remember('mainContent', () => {
			return new MainContent({
				...this.getOptions().content.main,
			});
		});
	}

	#getSecondaryContent(): SecondaryContent
	{
		return this.#cache.remember('secondaryContent', () => {
			return new SecondaryContent({
				...this.getOptions().content.secondary,
			});
		});
	}

	#getApplicationContent(): ApplicationContent
	{
		return this.#cache.remember('applicationContent', () => {
			return new ApplicationContent({
				...this.getOptions().content.application,
			});
		});
	}

	#getFooterContent(): FooterContent
	{
		return this.#cache.remember('footerContent', () => {
			return new FooterContent({
				...this.getOptions().content.footer,
			});
		});
	}

	#getMobileAuthContent(): MobileAuthContent
	{
		return this.#cache.remember('mobileAuthContent', () => {
			return new MobileAuthContent({
				...this.getOptions().content.mobileAuth.tools.fastMobileAuth,
			});
		});
	}

	#getFastMobileAuthSubsection(): HTMLElement
	{
		return this.#cache.remember('fastMobileAuthSubsection', () => {
			return (new PopupComponentsMaker(
				{ content: [this.#getMobileAuthContent().getConfig()] },
			)).getContentWrapper();
		});
	}

	#getExtranetSecondaryContent(): ExtranetSecondaryContent
	{
		return this.#cache.remember('extranetSecondaryContent', () => {
			return new ExtranetSecondaryContent({
				...this.getOptions().content.extranetSecondary,
			});
		});
	}

	setEventHandlers(): void
	{
		this.subscribe('beforeInit', () => {
			EventEmitter.subscribe('BX.Intranet.AvatarWidget.Popup:makeWithHint', () => {
				this.subscribe('afterInit', () => {
					BX.UI.Hint.init(this.getBasePopup().getPopup().getPopupContainer());
				});
			});
			this.subscribe('afterInit', () => {
				this.#setAutoHideEventHandlers();
				this.#setSubsectionEventHandlers();

				const close = () => {
					this.close();
				};

				EventEmitter.subscribe('SidePanel.Slider:onOpenStart', close);
				EventEmitter.subscribe('BX.Intranet.AvatarWidget.Popup:openChild', close);
			});
		});
	}

	#setAutoHideEventHandlers(): void
	{
		EventEmitter.subscribe('BX.Main.Popup:onShow', (event) => {
			if (!this.getBasePopup().getPopup().isShown())
			{
				return;
			}

			Dom.style(this.getBasePopup().getPopup().getPopupContainer(), 'overflow-y', 'hidden');

			const popup = event.getTarget();

			if (popup && popup.getId() === this.getBasePopup().getPopup().getId())
			{
				this.getBasePopup().getPopup().setAutoHide(true);
				EventEmitter.emit('BX.Intranet.AvatarWidget.Popup:enabledAutoHide');
			}
			else
			{
				this.getBasePopup().getPopup().setAutoHide(false);
				EventEmitter.emit('BX.Intranet.AvatarWidget.Popup:disabledAutoHide');

				if (!this.#popupsShowAfterBasePopup.includes(popup))
				{
					this.#popupsShowAfterBasePopup.push(popup);
				}

				const handler = () => {
					this.#popupsShowAfterBasePopup = this.#popupsShowAfterBasePopup.filter((item) => item !== popup);

					if (this.#popupsShowAfterBasePopup.length === 0)
					{
						this.getBasePopup().getPopup().setAutoHide(true);
						Dom.style(this.getBasePopup().getPopup().getPopupContainer(), 'overflow-y', 'auto');
						EventEmitter.emit('BX.Intranet.AvatarWidget.Popup:enabledAutoHide');
					}
				};

				popup.subscribeOnce('onClose', handler);
				popup.subscribeOnce('onDestroy', handler);
			}
		});
	}

	#setSubsectionEventHandlers(): void
	{
		const openFastMobileAuthSubsection = () => {
			const subsection = this.#getFastMobileAuthSubsection();
			this.getBasePopup().getPopup().setContent(subsection);
		};
		EventEmitter.subscribe('BX.Intranet.AvatarWidget.FastMobileAuthTool:onClick', openFastMobileAuthSubsection);
		EventEmitter.subscribe('BX.Intranet.AvatarWidget.ApplicationInstallerTool:onClick', openFastMobileAuthSubsection);
		EventEmitter.subscribe('BX.Intranet.AvatarWidget.Subsection:back', () => {
			this.getBasePopup().getPopup().setContent(this.getBasePopup().getContentWrapper());
		});
	}
}
