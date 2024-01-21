import { ajax } from 'main.core';
import { Button } from 'ui.buttons';
import type { LandingOptions } from './landing-button';
import { CreateState, EditState, LandingButton, LandingButtonState, ShowState } from './landing-button';
import { LandingCard } from './landing-card';

export class LandingButtonFactory
{
	#options: LandingOptions;
	landingData: Object;
	#menuRenderer: function;

	constructor(options: LandingOptions, landingData)
	{
		this.#options = options;
		this.landingData = landingData;
		this.#menuRenderer = this.#defaultMenuRenderer;
	}

	setMenuRenderer(renderer: function): void
	{
		this.#menuRenderer = renderer;
	}

	create(): Button
	{
		const btn = new LandingButton();
		let state: LandingButtonState;
		if (this.#options.is_connected && !this.#options.is_public)
		{
			state = new EditState(this.#options);
		}
		else if (this.#options.is_connected && this.#options.is_public)
		{
			state = new ShowState(this.#options, this.#menuRenderer.bind(this));
		}
		else
		{
			state = new CreateState(() => {
				return this.#getRequestCreateLanding()
			}, this.#menuRenderer.bind(this));
		}
		btn.setState(state);

		return btn.getButton();
	}

	#defaultMenuRenderer(landingData: LandingOptions): Object
	{
		return {
			angle: true,
			maxWidth: 396,
			closeByEsc: true,
			className: 'intranet-settings__qr_popup',
			items: [
				{
					html: (new LandingCard(landingData)).render(),
					className: 'intranet-settings__qr_popup_item',
				},
			],
		}
	}

	#getRequestCreateLanding(): Promise
	{
		return ajax.runComponentAction(
			'bitrix:intranet.settings',
			'getLanding',
			{
				mode: 'class',
				data: {
					companyId: this.landingData.company_id,
					requisiteId: this.landingData.requisite_id,
					bankRequisiteId: this.landingData.bank_requisite_id,
				}
			},
		)
	}
}