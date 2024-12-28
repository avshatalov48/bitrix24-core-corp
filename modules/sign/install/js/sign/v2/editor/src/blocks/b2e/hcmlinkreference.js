import { Cache, Dom, Loc, Tag, Text as TextFormat, Type } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { FieldSelector } from 'sign.v2.b2e.field-selector';
import type { FieldSelectEvent, FieldSelectEventData } from '../../types/events/fieldSelectEvent';
import Dummy from '../dummy';

export default class HcmLinkReference extends Dummy
{
	#cache = new Cache.MemoryCache();
	#field: string;
	#actionButton: HTMLElement;
	#content: HTMLElement;

	static #loadFieldsPromise: ?Promise<any> = null;
	static #lastLoadFieldsDocumentUid: string | null = null;

	/**
	 * Sets new data.
	 * @param {any} data
	 */
	setData(data: any): void
	{
		this.data = data ? data : {};
		this.#field = this.data.field ?? '';

		const party = this.#extractPartyFromSelectedField(this.#field);
		if (party > 0)
		{
			this.block.setMemberParty(party);
		}
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
				<div class="sign-document__block-b2e-hcmlinkreference">
					${TextFormat.encode(this.data.text || '').toString()}
				</div>
			`;
		}
		else
		{
			this.#content = Tag.render`
				<div class="sign-document__block-content_member-nodata">
					${Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE')}
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
								: Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE'),
						);
						const blockLayout = this.block.getLayout();
						const resizeNode = blockLayout.querySelector('.--hcmlinkreference');
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
				: Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE')
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
				fieldCaption: Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE'),
			});
		}

		const defaultCaption = Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE');

		const documentUid = this.block.blocksManager.getDocumentUid();
		if (HcmLinkReference.#loadFieldsPromise === null || documentUid !== HcmLinkReference.#lastLoadFieldsDocumentUid)
		{
			HcmLinkReference.#lastLoadFieldsDocumentUid = documentUid;
			HcmLinkReference.#loadFieldsPromise = FieldSelector.loadFieldList({}, this.#getCustomBackendSettings());
		}

		return HcmLinkReference.#loadFieldsPromise
			.then((fieldList) => {
				const categories = {
					representative: fieldList?.REPRESENTATIVE,
					employee: fieldList?.EMPLOYEE,
					company: fieldList?.COMPANY,
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
					...fieldList?.REPRESENTATIVE?.FIELDS ?? [],
					...fieldList?.EMPLOYEE?.FIELDS ?? [],
					...fieldList?.COMPANY?.FIELDS ?? [],
				];

				const field = fields.find((field) => field.name === this.#field);
				let categoryCaption = '';
				if (Type.isObject(field))
				{
					if (field?.category === 'representative')
					{
						categoryCaption = Loc.getMessage('SIGN_EDITOR_BLOCKS_REPRESENTATIVE_B2E');
					}
					else if (field?.category === 'employee')
					{
						categoryCaption = Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E');
					}
					else if (field?.category === 'company')
					{
						categoryCaption = Loc.getMessage('SIGN_EDITOR_BLOCKS_COMPANY');
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
			: Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE')
		;
	}

	#getFieldSelectorPanel(): FieldSelector
	{
		const blocksManager = this.block.blocksManager;

		const fieldSelector = this.#cache.remember('fieldSelector', () => {
			const selector = new FieldSelector({
				multiple: false,
				languages: blocksManager.getLanguages(),
				filter: {
					'+categories': [
						'REPRESENTATIVE',
						'EMPLOYEE',
					],
					'+fields': [

					],
					'-fields': [
						({ entity_field_name: fieldName }) => this.#getFieldsNameBlackList().has(fieldName),
					],
				},
				title: Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE'),
				categoryCaptions: {
					'REPRESENTATIVE': Loc.getMessage('SIGN_EDITOR_BLOCKS_REPRESENTATIVE_B2E'),
					'EMPLOYEE': Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'),
					'COMPANY': Loc.getMessage('SIGN_EDITOR_BLOCKS_COMPANY'),
				},
				alwaysHideCreateFieldButton: true,
			});
			selector.subscribe('onSliderCloseComplete', (event) => this.#onFieldSelectorCloseComplete(event));

			return selector;
		});

		fieldSelector.setCustomBackendSettings(this.#getCustomBackendSettings());

		return fieldSelector;
	}

	#getFieldsNameBlackList(): Set<string>
	{
		return new Set([

		]);
	}

	#onActionClick(): void
	{
		const fieldSelector = this.#getFieldSelectorPanel();
		fieldSelector
			.show()
			.then((selectedNames: Array<string>) => {
				let list = fieldSelector.getFieldsList(false)
				HcmLinkReference.#updateFieldsList(list);
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

	#extractPartyFromSelectedField(fieldName: string): number | null
	{
		if (!Type.isStringFilled(fieldName))
		{
			return null;
		}

		const splitedName = fieldName.split('_');

		return splitedName[3]
			? Number(splitedName[3])
			: null
		;
	}

	static #updateFieldsList(fields): void
	{
		HcmLinkReference.#loadFieldsPromise = Promise.resolve(fields);
	}

	#getCustomBackendSettings(): Object
	{
		return {
			uri: 'sign.api_v1.integration.humanresources.hcmLink.loadFields',
			requestOptions: {
				documentUid: this.block.blocksManager.getDocumentUid(),
			},
		};
	}

	getStyles(): { [p: string]: string }
	{
		return { ...super.getStyles(), ...this.defaultTextBlockPaddingStyles };
	}
}
