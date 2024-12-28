import { BaseEvent, EventEmitter } from 'main.core.events';
import { ajax, Cache, Text } from 'main.core';
import { ConfigContent } from '../types/content';
import { Popup } from 'main.popup';
import { Analytics } from '../analytics';

export class Content extends EventEmitter
{
	cache = new Cache.MemoryCache();

	constructor(options: Object)
	{
		super();
		this.setOptions(options);
		this.analytics = new Analytics();
	}

	setOptions(options: Object): void
	{
		this.cache.set('options', { ...options });
	}

	getOptions(): Object
	{
		return this.cache.get('options', {});
	}

	getLayout(): HTMLElement
	{
		throw new Error('Must be implemented in a child class');
	}

	showInfoHelper(articleCode: string): void
	{
		BX.UI.InfoHelper.show(articleCode);
		this.sendAnalytics(articleCode);
	}

	sendAnalytics(code: string): void
	{
		ajax.runAction('intranet.invitationwidget.analyticsLabel', {
			data: {},
			analyticsLabel: {
				helperCode: code,
				headerPopup: 'Y'
			}
		});
	}

	getHintPopup(text: string, element: HTMLElement, type: string): Popup
	{
		return this.cache.remember(type, () => {
			return new Popup(`bx-hint-${Text.getRandom()}`, element, {
				content: text,
				className: 'bx-invitation-warning',
				zIndex: 15000,
				angle: true,
				offsetTop: 0,
				offsetLeft: 40,
				closeIcon: false,
				autoHide: true,
				darkMode: true,
				overlay: false,
				maxWidth: 300,
				events: {
					onShow: (event) => {
						EventEmitter.emit(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.InvitationWidget.HintPopup:show', new BaseEvent({
							data: {
								popup: event.target,
							}
						}));
						const timeout = setTimeout(() => {
							event.target.close();
						}, 4000)
						EventEmitter.subscribeOnce(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.InvitationWidget.HintPopup:close', () => {
							clearTimeout(timeout);
						});
					},
					onClose: () => {
						EventEmitter.emit(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.InvitationWidget.HintPopup:close');
					},
				}
			});
		});
	}

	showHintPopup(text: string, element: HTMLElement, type: string): void
	{
		this.getHintPopup(text, element, type).toggle();
	}

	showInvitationPlace(text: string, element: HTMLElement, type: string): void
	{
		if (this.getOptions().isAdmin)
		{
			this.showInvitationSlider(type);
		}
		else
		{
			if (this.getOptions().isInvitationAvailable)
			{
				this.showInvitationSlider(type);
			}
			else
			{
				this.showHintPopup(text, element, 'hint-' + type);
			}
		}
	}

	showInvitationSlider(type: string): void
	{
		let link = this.getOptions().invitationLink;

		if (type === 'extranet')
		{
			link = `${link}&firstInvitationBlock=extranet`;
			Analytics.send(Analytics.EVENT_OPEN_SLIDER_EXTRANET_INVITATION);
		}
		else
		{
			Analytics.send(Analytics.EVENT_OPEN_SLIDER_INVITATION);
		}

		BX.SidePanel.Instance.open(
			link,
			{ cacheable: false, allowChangeHistory: false, width: 1100 }
		);
	}

	getConfig(): ConfigContent
	{
		return {
			html: this.getLayout(),
		}
	}
}
