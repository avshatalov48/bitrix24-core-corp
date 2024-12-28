import { Tag, Type, Loc, Dom, Event } from 'main.core';
import { Loader } from 'main.loader';
import { EventEmitter } from 'main.core.events';
import { Mapper } from 'humanresources.hcmlink.data-mapper';
import type { Api } from 'signproxy.signing.api';
import type { HrmLinkOptions } from './type';

import './style.css';

export class HcmLinkMapping extends EventEmitter
{
	#api: Api;

	#documentUid: string | null = null;
	#integrationId: number | null = null;
	#employeeIds: Array<number> = [];

	#container: HTMLElement | null = null;
	#linkText: HTMLElement | null = null;
	#loaderContainer: HTMLElement | null = null;
	#loader: Loader | null = null;

	#isValid: boolean = false;

	#enabled: boolean = false;

	constructor(options: HrmLinkOptions)
	{
		super();
		this.#api = options.api;

		this.setEventNamespace('BX.Sign.V2.B2e.HcmLinkMapping');

		this.#container = this.render();
	}

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		const { root, loaderContainer } = Tag.render`
			<div class="sign-b2e-hcm-link-mapping-container">
				<div 
					class="sign-b2e-hcm-link-mapping-loader"
					ref="loaderContainer"
				></div>
				${this.#getText()}
			</div>
		`;
		this.#container = root;
		this.#loaderContainer = loaderContainer;
		this.#hide();

		return this.#container;
	}

	setEnabled(value: boolean): void
	{
		this.#enabled = value;
	}

	setDocumentUid(uid: string): void
	{
		this.#documentUid = uid;
		this.#hide();

		if (this.#enabled)
		{
			this.#refresh();
		}
		else
		{
			this.#setValid(true);
		}
	}

	async #checkNotMapped(): Promise<boolean>
	{
		if (!Type.isStringFilled(this.#documentUid))
		{
			return;
		}

		this.#showLoader();
		const { integrationId, userIds } = await this.#api.checkNotMappedMembersHrIntegration(this.#documentUid);

		this.#employeeIds = userIds;
		this.#integrationId = integrationId;

		const isAllMapped = !Type.isArrayFilled(this.#employeeIds);
		this.#setValid(isAllMapped);

		if (isAllMapped)
		{
			this.#hide();
		}
		else
		{
			this.#show();
		}
	}

	#refresh(): void
	{
		this.#hide();
		this.#setValid(false);

		void this.#checkNotMapped();
	}

	#getText(): HTMLElement
	{
		const syncButton = Tag.render`
			<a class="sign-b2e-hcm-link-mapping-sync-button">
				${Loc.getMessage('SIGN_V2_B2E_HCM_LINK_MAPPING_SYNC_BUTTON')}
			</a>
		`;
		Event.bind(syncButton, 'click', () => { this.#openMapper(); });

		this.#linkText = Tag.render`
			<div class="sign-b2e-hcm-link-mapping-text">
				${Loc.getMessage('SIGN_V2_B2E_HCM_LINK_MAPPING_TEXT')}
				${syncButton}
			</div>
		`;

		return this.#linkText;
	}

	#setValid(value: boolean): void
	{
		this.#isValid = value;
		this.emit('validUpdate', { value: this.#isValid });
	}

	#openMapper(): void
	{
		Mapper.openSlider({
			companyId: this.#integrationId,
			userIds: new Set(this.#employeeIds),
			mode: Mapper.MODE_DIRECT,
		}, {
			onCloseHandler: () => void this.#checkNotMapped(),
		});
	}

	#showLoader(): Loader
	{
		Dom.hide(this.#linkText);
		void this.#getLoader().show(this.#loaderContainer);
	}

	#getLoader(): Loader
	{
		if (!this.#loader)
		{
			this.#loader = new Loader({
				size: 30,
				mode: 'inline',
			});
		}

		return this.#loader;
	}

	#hide(): void
	{
		void this.#getLoader().destroy();
		Dom.hide(this.#container);
	}

	#show(): void
	{
		void this.#getLoader().destroy();
		Dom.show(this.#linkText);
		Dom.show(this.#container);
	}
}
