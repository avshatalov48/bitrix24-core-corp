import { Api } from 'humanresources.hcmlink.api';
import { Tag, Loc, Dom, Text } from 'main.core';
import { Layout } from 'ui.sidepanel.layout';

import './style.css';

type Options = {
	companyId: Number,
	title: String,
}

export class HcmlinkCompanyConfig
{
	#api: Api;
	#options: Options;
	#companyConfig: Array = [];
	#loadConfigPromise: Promise;

	constructor(options: Options)
	{
		this.#api = new Api();
		this.#options = options;

		this.#load();
	}

	async #load(): Promise
	{
		if (this.#loadConfigPromise)
		{
			return this.#loadConfigPromise;
		}

		this.#loadConfigPromise = this.#api.loadCompanyConfig({ companyId: this.#options.companyId });

		return this.#loadConfigPromise;
	}

	static openSlider(
		options: Options,
		sliderOptions: { onCloseHandler: () => void },
	): void
	{
		const companyId = options.companyId;
		BX.SidePanel.Instance.open(`humanresources:hcmlink-company-config-${companyId}`, {
			width: 800,
			loader: 'default-loader',
			cacheable: false,
			contentCallback: () => {
				return top.BX.Runtime.loadExtension('humanresources.hcmlink.company-config').then((exports) => {
					return (new exports.HcmlinkCompanyConfig(options)).getLayout();
				});
			},
			events: {
				onClose: sliderOptions?.onCloseHandler ?? (() => {}),
			},
		});
	}

	async getLayout(): Promise
	{
		const data = await this.#load();
		this.#companyConfig = data.config;

		return Layout.createContent({
			title: this.#options.title,
			content: () => {
				return this.#getContent();
			},
			buttons(): Array<Button> {
				return [];
			},
		});
	}

	#getContent(): HTMLDivElement
	{
		const body = this.#getBody();

		return Tag.render`
			<div>
				<h2 class="hr-hcmlink-company-config-header">${Loc.getMessage('HUMANRESOURCES_HCMLINK_COMPANY_CONFIG_SLIDER_TITLE')}</h2>
				${body}
			</div>
		`;
	}

	#getBody(): HTMLDivElement
	{
		const bodyContainer = Tag.render`<div class="hr-hcmlink-company-config-container"></div>`;
		this.#companyConfig.forEach((item) => Dom.append(this.#getElementNode(item), bodyContainer));

		return bodyContainer;
	}

	#getElementNode(item: Object): HTMLDivElement
	{
		return Tag.render`
			<div class="hr-hcmlink-company-config-container__element">
				<div class="hr-hcmlink-company-config-container__label">${Text.encode(item.title)}</div>
				<div class="hr-hcmlink-company-config-container__value">${Text.encode(item.value)}</div>
			</div>
		`;
	}
}
