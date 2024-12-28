import { EventEmitter } from 'main.core.events';
import { Cache, ajax, Event, Runtime } from 'main.core';
import { PopupComponentsMaker } from 'ui.popupcomponentsmaker';
import type { ConfigContent } from './types/content';
import type { InvitationPopupOptions } from './types/options';
import { InvitationContent } from './contents/invitation';
import { StructureContent } from './contents/structure';
import { EmployeesContent } from './contents/employees';
import { ExtranetContent } from './contents/extranet';
import { CollabContent } from './contents/collab';
import { UserOnlineContent } from './contents/user-online';

export class InvitationPopup extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: InvitationPopupOptions)
	{
		super();
		this.setEventNamespace('BX.Intranet.InvitationWidget.Popup');
		this.setOptions(options)
		this.#setEventHandler();
	}

	setOptions(options: InvitationPopupOptions): void
	{
		this.#cache.set('options', options);
	}

	getOptions(): InvitationPopupOptions
	{
		return this.#cache.get('options', {});
	}

	show(): void
	{
		this.getPopup().show();
	}

	close(): void
	{
		this.getPopup().close();
	}

	#getAwaitData(): Promise
	{
		return this.#cache.remember('await-data', () => {
			return new Promise((resolve, reject) => {
				ajax.runAction("intranet.invitationwidget.getData", {
					data: {},
					analyticsLabel: {
						headerPopup: "Y"
					}
				}).then(resolve).catch(reject);
			});
		});
	}

	getPopup(): PopupComponentsMaker
	{
		return this.#cache.remember('popup', () => {
			return new PopupComponentsMaker({
				id: 'invitation-popup',
				target: this.getOptions().target,
				width: 350,
				content: this.#getContent(),
			});
		});
	}

	//This is the method for popup content configuration
	#getContent(): Array<ConfigContent>
	{
		return this.#cache.remember('content', () => {
			return [
				this.#getInvitationContent().getConfig(),
				{
					html: [
						this.#getStructureContent().getConfig(),
						this.#getEmployeesContent().getConfig(),
					],
					marginBottom: 24,
				},
				this.getOptions().isExtranetAvailable ? this.#getExtranetContent().getConfig() : null,
				this.getOptions().isCollabAvailable ? this.#getCollabContent().getConfig() : null,
				this.#getUserOnlineContent().getConfig(),
			];
		});
	}

	//region Get Content
	#getInvitationContent(): InvitationContent
	{
		return this.#cache.remember('invitation-content', () => {
			return new InvitationContent({
				isAdmin: this.getOptions().isAdmin,
				invitationLink: this.getOptions().params.invitationLink,
				isInvitationAvailable: this.getOptions().isInvitationAvailable,
			});
		});
	}

	#getStructureContent(): StructureContent
	{
		return this.#cache.remember('structure-content', () => {
			return new StructureContent({
				link: this.getOptions().params.structureLink,
				shouldShowStructureCounter: this.getOptions().params.shouldShowStructureCounter,
			});
		});
	}

	#getEmployeesContent(): EmployeesContent
	{
		return this.#cache.remember('employees-content', () => {
			return new EmployeesContent({
				isAdmin: this.getOptions().isAdmin,
				awaitData: this.#getAwaitData(),
				invitationCounter: this.getOptions().params.invitationCounter,
				counterId: this.getOptions().params.counterId,
			});
		});
	}

	#getExtranetContent(): ExtranetContent
	{
		return this.#cache.remember('extranet-content', () => {
			return new ExtranetContent({
				isAdmin: this.getOptions().isAdmin,
				awaitData: this.#getAwaitData(),
				invitationLink: this.getOptions().params.invitationLink,
				isInvitationAvailable: this.getOptions().isInvitationAvailable,
			});
		});
	}

	#getCollabContent(): CollabContent
	{
		return this.#cache.remember('collab-content', () => {
			return new CollabContent({
				isAdmin: this.getOptions().isAdmin,
				awaitData: Runtime.loadExtension('im.public', 'im.v2.component.content.chat-forms.forms'),
			});
		});
	}

	#getUserOnlineContent(): UserOnlineContent
	{
		return this.#cache.remember('user-online-content', () => {
			return new UserOnlineContent;
		});
	}
	//endregion

	#getPopupContainer(): HTMLElement
	{
		return this.#cache.remember('popup-container', () => {
			return this.getPopup().getPopup().getPopupContainer();
		});
	}

	#setEventHandler()
	{
		const autoHideHandler = (event) => {
			if (event.data.popup)
			{
				setTimeout(() => {
					Event.bind(this.#getPopupContainer(), 'click', () => {
						event.data.popup.close();
					});
				}, 100);
			}
		}

		const close = () => {
			this.close();
		}

		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.InvitationWidget.EmployeesContent:showRightMenu', autoHideHandler);
		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.InvitationWidget.HintPopup:show', autoHideHandler);
		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.UstatOnline:showPopup', autoHideHandler);
		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onOpenStart', close);
	}
}