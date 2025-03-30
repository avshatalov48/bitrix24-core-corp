import { Cache, Dom, Loc, Tag, Text as TextFormat, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { FieldSelector } from 'sign.v2.b2e.field-selector';
import { DocumentInitiated } from 'sign.type';
import type { FieldSelectEvent, FieldSelectEventData } from '../../types/events/fieldSelectEvent';
import Dummy from '../dummy';

export default class MyB2eReference extends Dummy
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
					${Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1')}
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
								: Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1'),
						);
						const blockLayout = this.block.getLayout();
						const resizeNode = blockLayout.querySelector('.--myb2ereference');
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
			: Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1')
		;
	}

	#getFieldSelectorPanel(): FieldSelector
	{
		const blocksManager = this.block.blocksManager;
		const member = blocksManager.getMemberByPart(this.block.getMemberPart());

		const { presetId } = member;

		return this.#cache.remember('fieldSelector', () => {
			const categories = [
				'COMPANY',
				'PROFILE',
			];
			if (this.block.blocksManager.documentInitiatedByType !== DocumentInitiated.employee)
			{
				categories.push('SMART_B2E_DOC');
			}

			const selector = new FieldSelector({
				multiple: false,
				controllerOptions: {
					hideVirtual: 1,
					hideRequisites: 0,
					hideSmartB2eDocument: 1,
					presetId,
				},
				languages: blocksManager.getLanguages(),
				presetId,
				filter: {
					'+categories': categories,
					'+fields': [
						'list',
						'string',
						'date',
						'typed_string',
						'text',
						'enumeration',
						'address',
						'url',
						'double',
						'integer',
						'snils',
					],
					'-fields': [
						this.#getFieldNegativeFilter(),
						({ entity_field_name: fieldName }) => this.#getCrmFieldsNameBlackList().has(fieldName),
					],
				},
				categoryCaptions: {
					'PROFILE': Loc.getMessage('SIGN_EDITOR_BLOCKS_REPRESENTATIVE_B2E'),
				},
			});

			selector.subscribe('onSliderCloseComplete', (event) => this.#onFieldSelectorCloseComplete(event));

			return selector;
		});
	}

	#getFieldsNameBlackList(): Array<string>
	{
		return [
			'SMART_B2E_DOC_XML_ID',
			'SMART_B2E_DOC_STAGE_ID',

			'COMPANY_LINK',
			'COMPANY_REG_ADDRESS',
			'COMPANY_ORIGIN_VERSION',
		];
	}

	#getCrmFieldsNameBlackList(): Set<string>
	{
		return new Set([
			'ADDRESS',
			'REG_ADDRESS',
		]);
	}

	#getFieldNegativeFilter(): (Object) => boolean
	{
		const blackList = this.#getFieldsNameBlackList();

		return (field) => {
			return blackList.includes(field.name);
		};
	}

	#onActionClick(): void
	{
		const fieldSelector = this.#getFieldSelectorPanel();
		fieldSelector
			.show()
			.then((selectedNames: Array<string>) => {
				MyB2eReference.#updateFieldsList(fieldSelector.getFieldsList(false));
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

	getStyles(): { [p: string]: string }
	{
		return { ...super.getStyles(), ...MyB2eReference.defaultTextBlockPaddingStyles };
	}

	#loadFieldAndCategoryCaption(): Promise<{categoryCaption: string, fieldCaption: string}>
	{
		if (!Type.isStringFilled(this.#field))
		{
			return Promise.resolve({
				categoryCaption: '',
				fieldCaption: Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1'),
			});
		}

		const defaultCaption = Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1');
		if (MyB2eReference.#loadFieldsPromise === null)
		{
			const blocksManager = this.block.blocksManager;
			const member = blocksManager.getMemberByPart(this.block.getMemberPart());

			const { presetId } = member;

			MyB2eReference.#loadFieldsPromise = FieldSelector.loadFieldList({
				hideVirtual: 1,
				hideRequisites: 1,
				hideSmartB2eDocument: 1,
				presetId,
			});
		}

		return MyB2eReference.#loadFieldsPromise
			.then((fieldList) => {
				const categories = {
					profile: fieldList?.PROFILE,
					document: fieldList?.SMART_B2E_DOC,
					company: fieldList?.COMPANY
				};
				Object.entries(categories)
					.forEach(([key, category]) => {
						if (category === undefined)
						{
							return;
						}

						category.FIELDS = (category?.FIELDS ?? []).map((field) => {
							return {
								...field,
								category: key
							};
						})
					})
				;
				const fields = [
					...fieldList?.PROFILE?.FIELDS ?? [],
					...fieldList?.SMART_B2E_DOC?.FIELDS ?? [],
					...fieldList?.COMPANY?.FIELDS ?? [],
				];

				const field = fields.find((field) => field.name === this.#field);
				let categoryCaption = '';
				if (Type.isObject(field))
				{
					if (field?.category === 'profile')
					{
						categoryCaption = Loc.getMessage('SIGN_EDITOR_BLOCKS_REPRESENTATIVE_B2E');
					}
					else
					{
						categoryCaption = categories[field?.category]?.CAPTION ?? '';
					}
				}
				return {
					categoryCaption,
					fieldCaption: field ? field.caption : defaultCaption,
				};
			})
		;
	}

	#setContentText(value: ?string): void
	{
		const text =
			Type.isStringFilled(value)
				? value
				: Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1')
		;

		this.#content.textContent = text;
		this.#content.title = text;
	}

	static #updateFieldsList(fields): void
	{
		MyB2eReference.#loadFieldsPromise = Promise.resolve(fields);
	}
}
