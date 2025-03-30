import { Dom, Tag, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import { HcmLinkMapping } from 'sign.v2.b2e.hcm-link-mapping';
import { HcmLinkEmployeeSelector } from 'sign.v2.b2e.hcm-link-employee-selector';
import type { Api } from 'sign.v2.api';

import "./style.css";

export class HcmLinkPartyChecker extends EventEmitter
{
	#container: HTMLElement = null;
	#loaderContainer: HTMLElement = null;

	#documentGroupUids: Array<string> = [];
	#enabled: boolean = false;

	#hcmLinkMapping: HcmLinkMapping;
	#hcmLinkEmployeeSelector: HcmLinkEmployeeSelector;

	#loader: Loader | null;

	#api: Api;

	constructor(options: Object)
	{
		super();
		this.#api = options.api;

		this.#hcmLinkMapping = new HcmLinkMapping({ api: this.#api });
		this.#hcmLinkEmployeeSelector = new HcmLinkEmployeeSelector({ api: this.#api });
		this.#container = this.#getContainer();

		this.#subscribeToEvents();

		this.setEventNamespace('BX.Sign.V2.B2e.HcmLinkPartyChecker');
	}

	render(): HTMLElement | null
	{
		if (!this.#enabled)
		{
			return null;
		}

		return this.#getContainer();
	}

	setDocumentGroupUids(uids: Array<string>): void
	{
		this.#documentGroupUids = uids;

		const lastUid = this.#documentGroupUids.at(-1);
		if (!Type.isStringFilled(lastUid))
		{
			return;
		}

		this.#hcmLinkMapping.setDocumentUid(lastUid);
		this.#hcmLinkEmployeeSelector.setDocumentGroupUids(this.#documentGroupUids);
	}

	setEnabled(value: boolean): void
	{
		this.#enabled = value;

		this.#hcmLinkMapping.setEnabled(this.#enabled);
		this.#hcmLinkEmployeeSelector.setEnabled(this.#enabled);

		this.#getLoader().destroy();
		Dom.hide(this.#getContainer());
		if (this.#enabled)
		{
			Dom.show(this.#getContainer());
		}
	}

	async check(): Promise<void>
	{
		if (!this.#enabled)
		{
			this.emit('updateValidation', true);
			return;
		}

		this.emit('updateValidation', false);

		const checkers = [
			this.#hcmLinkMapping,
			this.#hcmLinkEmployeeSelector,
		];

		for (const checker of checkers)
		{
			checker.hide();
		}

		this.#showLoader();

		for (const checker of checkers)
		{
			const result = await checker.check();
			if (result)
			{
				continue;
			}

			this.#getLoader().destroy();
			checker.show();
			this.emit('updateValidation', false);

			return;
		}

		this.#getLoader().destroy();
		this.emit('updateValidation', true);
	}

	#subscribeToEvents(): void
	{
		this.#hcmLinkMapping.subscribe('update', (): void => void this.check() );
		this.#hcmLinkEmployeeSelector.subscribe('update', (): void => void this.check() );
	}

	#showLoader(): void
	{
		void this.#getLoader().show(this.#getLoaderContainer());
	}

	#getLoaderContainer(): HTMLElement
	{
		if (!this.#loaderContainer)
		{
			this.#loaderContainer = Tag.render`
				<div 
					class="sign-b2e-hcmlink-party-check__loader"
				></div>
			`;
		}

		return this.#loaderContainer;
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

	#getContainer(): HTMLElement
	{
		if (!this.#container)
		{
			this.#container = Tag.render`
				<div class="sign-b2e-hcmlink-party-check">
					${this.#hcmLinkMapping.render()}
					${this.#hcmLinkEmployeeSelector.render()}
					${this.#getLoaderContainer()}
				</div>
			`;
		}

		return this.#container;
	}
}
