import { Cache, Loc, Tag, Text as TextFormat, Type } from 'main.core';
import Dummy from './dummy';
import { Selector } from "crm.form.fields.selector";

export default class Reference extends Dummy
{
	#cache = new Cache.MemoryCache();
	#field: string;
	#actionButton: HTMLElement;

	/**
	 * Sets new data.
	 * @param {any} data
	 */
	setData(data: any)
	{
		this.data = data ? data : {};
		this.#field = this.data.field;
	}

	/**
	 * Returns current data.
	 * @return {any}
	 */
	getData(): any
	{
		const data = this.data;

		if (data.text && data.field)
		{
			delete data.text;
		}

		return data;
	}

	/**
	 * Calls when block has placed on document.
	 */
	onPlaced()
	{
		this.#onActionClick();
	}

	/**
	 * Calls when action button was clicked.
	 */
	#onActionClick()
	{
		this.#getCrmFieldSelectorPanel()
			.show()
			.then((selectedName: Array<string>) => {
				if (selectedName.length === 0)
				{
					return;
				}

				this.#field = selectedName[0];
				this.setData({
					field: this.#field,
				});
				setTimeout(() => {
					this.block.assign();
				}, 0);
			});
	}

	/**
	 * Returns action button for edit content.
	 * @return {HTMLElement | null}
	 */
	getActionButton(): ?HTMLElement
	{
		if (Type.isUndefined(Selector))
		{
			return null;
		}

		this.#actionButton = Tag.render`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${this.#onActionClick.bind(this)}" data-role="action">
				</button>
			</div>
		`;

		this.#setActionButtonLabel();

		return this.#actionButton;
	}

	/**
	 * Sets label to action button.
	 */
	#setActionButtonLabel()
	{
		if (!this.#actionButton)
		{
			return;
		}

		const config = this.block.getDocument().getConfig();
		let fieldLabel = config.crmEntityFields[this.#field] || null;

		if (!fieldLabel)
		{
			fieldLabel = Loc.getMessage('SIGN_JS_DOCUMENT_REFERENCE_ACTION_BUTTON');
		}

		this.#actionButton.querySelector('button').innerText = fieldLabel;
	}

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		this.#setActionButtonLabel();

		const { width, height } = this.block.getPosition();

		if (this.data.src)
		{
			return Tag.render`
				<div style="width: ${width - 14}px; height: ${height - 14}px; background: url(${this.data.src}) no-repeat top; background-size: cover;">
				</div>
			`;
		}
		else
		{
			const className = !this.data.text ? 'sign-document__block-content_member-nodata' : '';

			return Tag.render`
				<div class="${className}">
					${TextFormat.encode(this.data.text || Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA'))}
				</div>
			`;
		}
	}

	#getCrmFieldSelectorPanel(): Selector
	{
		return this.#cache.remember('getFieldSelector', () => new Selector({
			multiple: false,
			controllerOptions: {
				hideVirtual: 1,
				hideRequisites: 1,
				hideSmartDocument: 1,
			},
			filter: {
				'+categories': [
					'CONTACT',
					'SMART_DOCUMENT',
				],
				'+fields': [
					'list',
					'string',
					'date',
					'typed_string',
					'text',
					'datetime',
					'enumeration',
					'address',
					'url',
					'money',
					'boolean',
					'double',
				],
			},
		}));
	}


	getStyles(): { [p: string]: string }
	{
		return { ...super.getStyles(), ...Reference.defaultTextBlockPaddingStyles };
	}
}
