import { Event, Tag, Type, Text, Loc } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Popup } from 'main.popup';
import { UI } from 'ui.notification';
import { BitrixVue, VueCreateAppResult, reactive } from 'ui.vue3';
import { MiniCardComponent } from './components/mini-card-component';
import { MiniCardItem } from './lib/model/mini-card-item';

export type MiniCardOptions = {
	miniCardResolver: Promise<?MiniCard>,
	bindElement: HTMLElement,
};

export const EVENTS = {
	GLOBAL_ON_CLOSE_MAIN_POPUP: 'BX.Crm.MiniCard:OnMainPopupClose',
	GLOBAL_ON_MOUSE_ENTER_CHILD_POPUP: 'BX.Crm.MiniCard:onMouseEnterChildPopup',
	GLOBAL_ON_MOUSE_LEAVE_CHILD_POPUP: 'BX.Crm.MiniCard:onMouseLeaveChildPopup',
	INTERNAL_ON_CLOSE_MAIN_POPUP: 'crm:mini-card:on-close-main-popup',
	INTERNAL_ON_MOUSE_ENTER_CHILD_POPUP: 'crm:mini-card:on-mouse-enter-child-popup',
	INTERNAL_ON_MOUSE_LEAVE_CHILD_POPUP: 'crm:mini-card:on-mouse-leave-child-popup',
};

export class MiniCard
{
	#appId: string;
	#miniCardResolver: Promise<?MiniCard>;
	#bindElement: HTMLElement;

	#application: VueCreateAppResult = null;
	#popup: Popup = null;
	#container: HTMLElement = null;

	#hidePopupTimeout: number | undefined = undefined;
	#showPopupTimeout: number | undefined = undefined;

	#state: {
		appId: string,
		miniCardItem: ?MiniCardItem,
		isLoaded: boolean,
	};

	constructor(options: MiniCardOptions)
	{
		this.#appId = Text.getRandom(16);
		this.#setBindElement(options.bindElement);
		this.#setMiniCardResolver(options.miniCardResolver);

		this.#state = reactive({
			appId: this.#appId,
			miniCardItem: null,
			isLoaded: false,
			popup: null,
		});

		this.#initializeSipManager();
		this.#bindEvents();
	}

	#setBindElement(bindElement: HTMLElement): void
	{
		if (!Type.isElementNode(bindElement))
		{
			throw new RangeError('BX.Crm.MiniCard: bindElement must be an element node');
		}

		this.#bindElement = bindElement;
	}

	#setMiniCardResolver(miniCardResolver: Promise<?MiniCardItem>): void
	{
		this.#miniCardResolver = miniCardResolver;
	}

	popup(): Popup
	{
		if (this.#popup !== null)
		{
			return this.#popup;
		}

		this.#popup = new Popup({
			bindElement: this.#bindElement,
			content: this.container(),
			padding: 0,
			closeIcon: true,
			angle: {
				offset: 150,
			},
			bindOptions: {
				forceBindPosition: true,
				forceTop: true,
			},
			offsetLeft: (this.#bindElement.offsetWidth / 2) - 130,
			animation: 'fading',
			autoHide: true,
			events: {
				onShow: () => {
					if (this.#state.isLoaded)
					{
						return;
					}

					void this.#miniCardResolver()
						.then((miniCard: ?MiniCardItem) => {
							this.#state.miniCardItem = miniCard;
						})
						.catch((response) => {
							console.error('BX.Crm.MiniCard: Error while resolve mini card element', response.errors);

							UI.Notification.Center.notify({
								content: Text.encode(response.errors[0].message),
								autoHideDelay: 3000,
							});

							this.popup().destroy();
						})
						.finally(() => {
							this.#state.isLoaded = true;
						});
				},
				onClose: () => {
					clearTimeout(this.#showPopupTimeout);
					const event = new BaseEvent({
						data: {
							appId: this.#appId,
						},
					});

					Event.EventEmitter.emit(EVENTS.GLOBAL_ON_CLOSE_MAIN_POPUP, event);
				},
			},
		});

		this.#state.popup = this.#popup;

		Event.bind(this.#popup.getPopupContainer(), 'mouseenter', this.#startShowPopup.bind(this));
		Event.bind(this.#popup.getPopupContainer(), 'mouseleave', this.#startHidePopup.bind(this));

		return this.#popup;
	}

	#bindEvents(): void
	{
		Event.bind(this.#bindElement, 'mouseenter', this.#startShowPopup.bind(this));
		Event.bind(this.#bindElement, 'mouseleave', this.#startHidePopup.bind(this));
		Event.bind(this.#bindElement, 'click', this.#hidePopup.bind(this));
	}

	#startHidePopup(): void
	{
		clearTimeout(this.#showPopupTimeout);
		this.#hidePopupTimeout = setTimeout(() => {
			this.popup().close();
		}, 500);
	}

	#startShowPopup(): void
	{
		clearTimeout(this.#hidePopupTimeout);
		this.#showPopupTimeout = setTimeout(() => {
			if (Type.isDomNode(this.#bindElement) && document.body.contains(this.#bindElement))
			{
				this.popup().show();
			}
		}, 1000);
	}

	#hidePopup(): void
	{
		clearTimeout(this.#showPopupTimeout);
		if (this.popup().isShown())
		{
			this.popup().close();
		}
	}

	container(): HTMLElement
	{
		if (this.#container === null)
		{
			this.#container = Tag.render`
				<div class="crm-mini-card-container"></div>
			`;

			this.application().mount(this.#container);
		}

		return this.#container;
	}

	application(): VueCreateAppResult
	{
		if (this.#application === null)
		{
			this.#application = BitrixVue.createApp(MiniCardComponent, {
				state: this.#state,
			});

			Event.EventEmitter.subscribe(EVENTS.GLOBAL_ON_MOUSE_ENTER_CHILD_POPUP, (event: BaseEvent) => {
				const data = event.getData();
				if (data.appId !== this.#appId)
				{
					return;
				}

				this.#startShowPopup();
			});

			Event.EventEmitter.subscribe(EVENTS.GLOBAL_ON_MOUSE_LEAVE_CHILD_POPUP, (event: BaseEvent) => {
				const data = event.getData();
				if (data.appId !== this.#appId)
				{
					return;
				}

				this.#startHidePopup();
			});
		}

		return this.#application;
	}

	#initializeSipManager(): void
	{
		if (!window.BXIM || !BX.CrmSipManager)
		{
			return;
		}

		if (!BX.CrmSipManager.messages)
		{
			BX.CrmSipManager.messages = {
				unknownRecipient: Loc.getMessage('CRM_SIP_MGR_UNKNOWN_RECIPIENT'),
				makeCall: Loc.getMessage('CRM_SIP_MGR_MAKE_CALL'),
			};
		}

		const sipMgr = BX.CrmSipManager.getCurrent();

		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
		const sessid = BX.bitrix_sessid();

		sipMgr.setServiceUrl(
			'CRM_LEAD',
			`/bitrix/components/bitrix/crm.lead.show/ajax.php?sessid=${sessid}`,
		);

		sipMgr.setServiceUrl(
			'CRM_CONTACT',
			`/bitrix/components/bitrix/crm.contact.show/ajax.php?sessid=${sessid}`,
		);

		sipMgr.setServiceUrl(
			'CRM_COMPANY',
			`/bitrix/components/bitrix/crm.company.show/ajax.php?sessid=${sessid}`,
		);
	}
}
