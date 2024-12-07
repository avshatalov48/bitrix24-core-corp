import { Loc, Tag } from 'main.core';
import { Button } from 'ui.buttons';
import { Api } from 'sign.v2.api';
import './style.css';

export class LangSelector
{
	#api: Api;
	#langs: ?Object;
	#langButton: Button;
	#region: string;
	#documentUid: string;

	constructor(region, langs)
	{
		this.#region = region;
		this.#langs = langs;
		this.#documentUid = '';
		this.#langButton = this.#getLanguageButton();
		this.#api = new Api();
	}

	getLayout(): HTMLElement
	{
		return Tag.render`
			<div class="sign-lang-selector">
				<span class="sign-lang-selector__label">
					${Loc.getMessage('SIGN_BLANK_LANGUAGE_SELECTOR_LABEL')}
				</span>
				${this.#langButton.getContainer()}
			</div>
		`;
	}

	#getLanguageItems(): Array<{ text: string; onclick: Function; dataset: { langId: string; }}>
	{
		const onItemClick = function(event) {
			const id = event.currentTarget.getAttribute('data-lang-id');
			this.#langButton.menuWindow.close();
			this.#changeLang(id);
			this.#langButton.setText(this.#langs[id].NAME);
		}.bind(this);

		return Object.entries(this.#langs).map((lang) => {
			return {
				text: lang[1].NAME,
				onclick: onItemClick,
				dataset: { langId: lang[0] },
			};
		});
	}

	#getLanguageButton(): Button
	{
		return new Button({
			text: (this.#langs[this.#region]?.NAME || Loc.getMessage('SIGN_BLANK_LANGUAGE_SELECTOR_BUTTON_TITLE')),
			dropdown: true,
			closeByEsc: true,
			autoHide: true,
			autoClose: true,
			color: BX.UI.Button.Color.LIGHT,
			size: BX.UI.Button.Size.SMALL,
			menu: {
				items: this.#getLanguageItems(),
			},
			className: 'sign-lang-selector__language-button',
		});
	}

	async #changeLang(langId)
	{
		await this.#api.modifyLanguageId(this.#documentUid, langId);
	}

	setDocumentUid(uid)
	{
		this.#documentUid = uid;
	}
}
