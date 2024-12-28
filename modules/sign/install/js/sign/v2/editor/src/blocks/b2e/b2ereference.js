import { Cache, Dom, Loc, Tag, Text as TextFormat, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { FieldSelector } from 'sign.v2.b2e.field-selector';
import type { FieldSelectEvent, FieldSelectEventData } from '../../types/events/fieldSelectEvent';
import Dummy from '../dummy';

export default class B2eReference extends Dummy
{
	#cache = new Cache.MemoryCache();
	#field: string;
	#actionButton: HTMLElement;
	#content: HTMLElement;

	static #loadFieldsPromise: ?Promise<any> = null;

	/**
	 * Sets new data.
	 * @param {any} data
	 */
	setData(data: any): void
	{
		this.data = data ? data : {};
		this.#field = this.data.field ?? '';
	}

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		this.#setActionButtonLabel();
		if (Type.isStringFilled(this.data?.text))
		{
			this.#content = Tag.render`
				<div class="sign-document__block-b2e-reference">
					${TextFormat.encode(this.data.text || '').toString()}
				</div>
			`;
		}
		else
		{
			this.#content = Tag.render`
				<div class="sign-document__block-content_member-nodata">
					${Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E')}
				</div>
			`;
		}

		if (Type.isStringFilled(this.#field))
		{
			this.#loadFieldAndCategoryCaption()
				.then(({ categoryCaption, fieldCaption }) => {
					this.#setActionButtonLabel(fieldCaption);
					if (!Type.isStringFilled(this.data?.text))
					{
						this.#setContentText(
							Type.isStringFilled(categoryCaption) && Type.isStringFilled(fieldCaption)
								? Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_NO_DATA_CONTENT_TEMPLATE', {
									'#CATEGORY#': categoryCaption,
									'#FIELD#': fieldCaption,
								})
								: Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'),
						);
						const blockLayout = this.block.getLayout();
						const resizeNode = blockLayout.querySelector('.--b2ereference');
						this.block.resizeText({ element: resizeNode, step: 0.5 });
					}
				})
				.catch((err) => {
					console.error(err);
				})
			;
		}

		return this.#content;
	}

	/**
	 * Calls when block has placed on document.
	 */
	onPlaced()
	{
		this.#onActionClick();
	}

	#setContentText(value: ?string): void
	{
		const text =
			Type.isStringFilled(value)
				? value
				: Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1')
		;
		this.#content.textContent = text;
		this.#content.title = text;
	}

	#loadFieldAndCategoryCaption(): Promise<{categoryCaption: string, fieldCaption: string}>
	{
		if (!Type.isStringFilled(this.#field))
		{
			return Promise.resolve({
				categoryCaption: '',
				fieldCaption: Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1'),
			});
		}

		const defaultCaption = Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1');
		if (B2eReference.#loadFieldsPromise === null)
		{
			B2eReference.#loadFieldsPromise = FieldSelector.loadFieldList({});
		}

		return B2eReference.#loadFieldsPromise
			.then((fieldList) => {
				const fields = [
					...fieldList?.PROFILE?.FIELDS ?? [],
				];
				const field = fields.find((field) => field.name === this.#field);

				return {
					categoryCaption: Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'),
					fieldCaption: field ? field.caption : defaultCaption,
				};
			})
		;
	}

	/**
	 * Returns action button for edit content.
	 * @return {HTMLElement | null}
	 */
	getActionButton(): ?HTMLElement
	{
		this.#actionButton = Tag.render`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${this.#onActionClick.bind(this)}" data-role="action" data-id="action-${this.block.getCode()}">
				</button>
			</div>
		`;

		this.#setActionButtonLabel();

		return this.#actionButton;
	}

	/**
	 * Sets label to action button.
	 */
	#setActionButtonLabel(label: ?string)
	{
		if (!this.#actionButton)
		{
			return;
		}

		const actionButton = this.#actionButton.querySelector('button');
		actionButton.textContent = Type.isStringFilled(label)
			? label
			: Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1')
		;
	}

	#getFieldSelectorPanel(): FieldSelector
	{
		const blocksManager = this.block.blocksManager;

		return this.#cache.remember('fieldSelector', () => {
			const selector = new FieldSelector({
				multiple: false,
				controllerOptions: {
					hideVirtual: 1,
					hideRequisites: 1,
					hideSmartB2eDocument: 1,
				},
				languages: blocksManager.getLanguages(),
				filter: {
					'+categories': [
						'PROFILE',
					],
					'+fields': this.#getSelectorFieldTypes(),
					'-fields': [
						({ entity_field_name: fieldName }) => this.#getFieldsNameBlackList().has(fieldName),
					],
				},
				title: Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1'),
				categoryCaptions: {
					'PROFILE': Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E')
				},
				fieldsFactory: this.#getSelectorFieldsFactory(),
			});
			selector.subscribe('onSliderCloseComplete', (event) => this.#onFieldSelectorCloseComplete(event));

			return selector;
		});
	}

	#getFieldsNameBlackList(): Set<string>
	{
		return new Set([
			'ADDRESS',
			'REG_ADDRESS',
		]);
	}

	#onActionClick(): void
	{
		const fieldSelector = this.#getFieldSelectorPanel();
		fieldSelector
			.show()
			.then((selectedNames: Array<string>) => {
				B2eReference.#updateFieldsList(fieldSelector.getFieldsList(false));
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
			})
		;
	}

	#emitOnFieldSelectEvent(selectedNames: Array<string>): void
	{
		const onFieldSelectEventData: FieldSelectEventData = { selectedFieldNames: selectedNames };
		const onFieldSelectEvent: FieldSelectEvent = new BaseEvent({ data: onFieldSelectEventData });
		this.emit('onFieldSelect', onFieldSelectEvent);
	}

	#onFieldSelectorCloseComplete(event): void
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

	static #updateFieldsList(fields): void
	{
		B2eReference.#loadFieldsPromise = Promise.resolve(fields);
	}

	getStyles(): { [p: string]: string }
	{
		return { ...super.getStyles(), ...B2eReference.defaultTextBlockPaddingStyles };
	}

	#getSelectorFieldTypes(): string[]
	{
		const types = [
			'list',
			'string',
			'date',
			'typed_string',
			'text',
			'datetime',
			'enumeration',
			'url',
			'double',
			'integer',
			'snils',
		];

		if (!this.block.blocksManager.isTemplateMode)
		{
			types.push('address');
		}

		return types;
	}

	#getSelectorFieldsFactory(): Object | null
	{
		if (this.block.blocksManager.isTemplateMode)
		{
			return {
				filter: (fields) => fields.filter((field) => Type.isObject(field)
					&& Type.isStringFilled(field.name)
					&& ['list', 'string', 'date', 'enumeration'].includes(field.name)),
			};
		}

		return null;
	}
}
