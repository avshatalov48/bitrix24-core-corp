import { Tag, Loc, Event, bind } from 'main.core';
import { Popup } from 'main.popup';
import { RoleMaster, RoleMasterEvents } from './role-master';
import type { RoleMasterOptions } from './types';

import './css/role-master-popup.css';
import { Runtime } from 'main.core';
import type { AnalyticsOptions } from 'ui.analytics';
export type RoleMasterPopupOptions = {
	roleMaster: RoleMasterOptions;
}

export const RoleMasterPopupEvents = Object.freeze({
	OPEN: 'open',
	CANCEL: 'cancel',
	SAVE_SUCCESS: 'role-save-success',
	SAVE_FAILED: 'role-save-success',
});

export class RoleMasterPopup extends Event.EventEmitter
{
	#roleMasterOptions: RoleMasterOptions;
	#popup: Popup = null;
	#roleMaster: RoleMaster;
	#cancelledRoleSavingHandler: Function | null;

	constructor(options: RoleMasterPopupOptions = {})
	{
		super(options);
		this.setEventNamespace('AI.RoleMaster');

		this.#validateOptions(options);

		this.#roleMasterOptions = options.roleMaster || {};
	}

	async sendAnalytics(event: string): void
	{
		try
		{
			const { sendData } = await Runtime.loadExtension('ui.analytics');

			const sendDataOptions: AnalyticsOptions = {
				event,
				status: 'success',
				tool: 'ai',
				category: 'roles_saving',
			};

			sendData(sendDataOptions);
		}
		catch (e)
		{
			console.error('AI: RolesDialog: Can\'t send analytics', e);
		}
	}

	show(): void
	{
		if (!this.#popup)
		{
			this.#popup = this.#initPopup();
		}
		this.sendAnalytics(RoleMasterPopupEvents.OPEN);
		this.#cancelledRoleSavingHandler = this.#cancelRoleSavingAnalyticEvent.bind(this);
		bind(this.#popup.closeIcon, 'click', this.#cancelledRoleSavingHandler);
		this.#popup.show();
	}

	hide(): void
	{
		this.#popup?.close();
	}

	#initPopup(): Popup
	{
		return new Popup({
			id: 'role-master-popup',
			width: 360,
			minHeight: 592,
			content: this.#renderPopupContent(),
			cacheable: false,
			closeIcon: true,
			overlay: true,
			padding: 17,
			borderRadius: '12px',
			events: {
				onPopupClose: () => {
					this.#popup = null;
					this.#roleMaster.unsubscribeAll(RoleMasterEvents.CLOSE);
					this.#roleMaster.unsubscribeAll(RoleMasterEvents.SAVE_SUCCESS);
					this.#roleMaster.destroy();
				},
			},
		});
	}

	#cancelRoleSavingAnalyticEvent()
	{
		this.sendAnalytics(RoleMasterPopupEvents.CANCEL);
	}

	#renderPopupContent(): HTMLElement
	{
		this.#roleMaster = new RoleMaster({
			...this.#roleMasterOptions,
		});

		this.#roleMaster.subscribeOnce(RoleMasterEvents.CLOSE, () => {
			this.#popup.close();
		});
		this.#roleMaster.subscribeOnce(RoleMasterEvents.SAVE_SUCCESS, (event) => {
			this.emit(RoleMasterPopupEvents.SAVE_SUCCESS, event.getData());
		});

		const headerText = this.#roleMasterOptions.id === undefined
			? Loc.getMessage('ROLE_MASTER_POPUP_TITLE')
			: Loc.getMessage('ROLE_MASTER_POPUP_TITLE_EDIT_MODE')
		;

		return Tag.render`
			<div class="ai__role-master-popup-content">
				<header class="ai__role-master-popup-content-header">
					${headerText}
				</header>
				<div class="ai__role-master-popup-content-main">
					${this.#roleMaster.render()}
				</div>
			</div>
		`;
	}

	#validateOptions(options: RoleMasterPopupOptions): void
	{
		RoleMaster.validateOptions(options.roleMaster);
	}
}
