import {Dom} from 'main.core';
import {Form} from './form';
import {DataProvider} from "../../data_provider";
import {HELP_CENTER_ID, HELP_CENTER_URL} from "../../tab";

export class Inline extends Form
{
	#container: HTMLElement;
	#rendered: boolean = false;

	constructor(formId: number, dataProvider: DataProvider)
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
			'code',
			BX.Loc.getMessage('EMBED_SLIDER_INLINE_TITLE'),
			BX.Loc.getMessage('EMBED_SLIDER_INLINE_DESC'),
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
				'code',
				BX.Loc.getMessage('EMBED_SLIDER_INLINE_TITLE'),
				BX.Loc.getMessage('EMBED_SLIDER_INLINE_DESC'),
				this.dataProvider.data.embed.helpCenterId,
				this.dataProvider.data.embed.helpCenterUrl
			));

			Dom.append(this.renderPreviewSection(
				BX.Loc.getMessage('EMBED_SLIDER_QR_TITLE'),
				BX.Loc.getMessage('EMBED_SLIDER_QR_DESC'),
				BX.Loc.getMessage('EMBED_SLIDER_OPEN_IN_NEW_TAB'),
				this.dataProvider.data.embed.previewLink.replace('#preview#', 'inline') // this.dataProvider.data.embed.pubLink
			), this.#container);

			const code = this.dataProvider.data.embed.scripts['inline'].text;
			Dom.append(this.renderCodeBlock(code), this.#container);
			Dom.append(this.renderCopySection(
				BX.Loc.getMessage('EMBED_SLIDER_COPY_CODE'),
				BX.Loc.getMessage('EMBED_SLIDER_COPY_CODE2'),
				code,
				this.renderBubble(BX.Loc.getMessage('EMBED_SLIDER_COPY_CODE_BUBBLE_INLINE'), true)
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