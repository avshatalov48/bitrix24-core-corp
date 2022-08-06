import {Dom} from 'main.core';
import {Form} from './form';
import {DataProvider} from "../../data_provider";
import {HELP_CENTER_ID, HELP_CENTER_URL} from "../../tab";

export class Publink extends Form
{
	#container: HTMLElement;
	#rendered: boolean = false;

	constructor(formId: number, dataProvider: DataProvider): undefined
	{
		super(formId, dataProvider);
		this.#container = super.render();
	}

	load(force: boolean = false): Promise
	{
		return super.load(force).then(() => {
			if (!this.#rendered)
			{
				this.render();
			}
		}).catch((error) => {
			console.error(error);
			if (!this.#rendered)
			{
				this.#renderError();
			}
		});
	}

	render(): HTMLElement
	{
		this.#container.innerHTML = '';

		const headerSection = this.renderHeaderSection(
			'linked-link',
			BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK_TITLE'),
			BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK_DESCRIPTION'),
			HELP_CENTER_ID,
			HELP_CENTER_URL
		);
		Dom.append(headerSection, this.#container);

		if (!super.loaded)
		{
			this.loader.show(this.#container);
		}
		else
		{
			// update with actual help-center link
			Dom.replace(headerSection, this.renderHeaderSection(
				'linked-link',
				BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK_TITLE'),
				BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK_DESCRIPTION'),
				this.dataProvider.data.embed.helpCenterId,
				this.dataProvider.data.embed.helpCenterUrl
			));

			Dom.append(this.renderPreviewSection(
				BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK_QR_TITLE'),
				BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK_QR_DESC'),
				BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK_OPEN_IN_NEW_TAB'),
				this.dataProvider.data.embed.pubLink
			), this.#container);
			Dom.append(this.renderCopySection(
				null, // BX.Loc.getMessage('EMBED_SLIDER_COPY_LINK'),
				BX.Loc.getMessage('EMBED_SLIDER_COPY_LINK2'),
				this.dataProvider.data.embed.pubLink,
				null,
				'ui-btn-icon-follow crm-form-embed__btn-icon--link'
			), this.#container);

			this.#rendered = true;
		}

		return this.#container;
	}

	#renderError(): HTMLElement
	{
		this.#container.innerHTML = '';
		Dom.append(super.renderError(this.dataProvider.data), this.#container);
		this.#rendered = true;
		return this.#container;
	}
}