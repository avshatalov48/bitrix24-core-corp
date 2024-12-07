import { Cache, Loc, Tag, Text as TextFormat, Type, Dom } from 'main.core';
import { BaseEvent } from 'main.core.events';
import type { FieldSelectEvent, FieldSelectEventData } from '../types/events/fieldSelectEvent';
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
		this.#field = this.data.field ?? '';
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
			.then((selectedNames: Array<string>) => {
				const eventSelectedNames = [...selectedNames];
				if (selectedNames.length === 0 && Type.isStringFilled(this.#field))
				{
					eventSelectedNames.push(this.#field);
				}
				this.#emitOnFieldSelectEvent(eventSelectedNames);

				if (selectedNames.length === 0)
				{
					return;
				}

				Dom.removeClass(this.block.getLayout(), '--invalid');
				this.#field = selectedNames[0];
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

		const actionButton = this.#actionButton.querySelector('button');
		const defaultCaption = Loc.getMessage('SIGN_JS_DOCUMENT_REFERENCE_ACTION_BUTTON');
		if (!this.#field)
		{
			actionButton.textContent = defaultCaption;
			return;
		}

		const fieldSelector = this.#cache.get('fieldSelector') ?? this.#getCrmFieldSelectorPanel();
		const fieldsList = fieldSelector.getFieldsList();
		const fields = [
			...fieldsList?.CONTACT?.FIELDS ?? [],
			...fieldsList?.SMART_DOCUMENT?.FIELDS ?? [],
		];
		const field = fields.find((field) => field.name === this.#field);
		const caption = field ? field.caption : defaultCaption;
		actionButton.textContent = caption;
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
		const blocksManager = this.block.blocksManager;
		const member = blocksManager.getMemberByPart(this.block.getMemberPart());

		const { presetId } = member;

		return this.#cache.remember('fieldSelector', () => {
			const selector = new Selector({
				multiple: false,
				controllerOptions: {
					hideVirtual: 1,
					hideRequisites: 0,
					hideSmartDocument: 1,
					presetId,
				},
				presetId,
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
						'double',
						'integer',
					],
					'-fields': [
						({ entity_field_name: fieldName }) => this.#getCrmFieldsNameBlackList().has(fieldName),
					],
				},
				fieldsFactory: {
					filter: this.#filterRequisiteCreateFields.bind(this),
				},
			});

			selector.subscribe('onSliderCloseComplete', this.#onCrmFieldSelectorSliderCloseComplete.bind(this));

			return selector;
		});
	}

	#onCrmFieldSelectorSliderCloseComplete(): void
	{
		setTimeout(() => {
			// event already sended
			if (Type.isStringFilled(this.#field))
			{
				return;
			}

			this.#emitOnFieldSelectEvent([]);
		});
	}

	#emitOnFieldSelectEvent(selectedNames: Array<string>): void
	{
		const onFieldSelectEventData: FieldSelectEventData = { selectedFieldNames: selectedNames };
		const onFieldSelectEvent: FieldSelectEvent = new BaseEvent({ data: onFieldSelectEventData });
		this.emit('onFieldSelect', onFieldSelectEvent);
	}

	getStyles(): { [p: string]: string }
	{
		return { ...super.getStyles(), ...Reference.defaultTextBlockPaddingStyles };
	}

	#getCrmFieldsNameBlackList(): Set<string>
	{
		return new Set([
			'ADDRESS',
			'REG_ADDRESS',
		]);
	}

	#getCreateFieldTypeNamesBlackList(): Set<string>
	{
		return new Set([
			'file',
			'employee',
			'boolean',
			'money',
		]);
	}

	#filterRequisiteCreateFields(fields: Array<{ name: string }>): Array<Object>
	{
		return fields.filter(
			(createFieldType) => !this.#getCreateFieldTypeNamesBlackList().has(createFieldType.name),
		);
	}
}
