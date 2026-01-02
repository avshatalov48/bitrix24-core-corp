import { ajax, Event, Dom, Tag, Reflection, Type, Text } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Api } from 'sign.v2.api';
import { UserSelector } from 'ui.form-elements.view';

const namespace = Reflection.namespace('BX.Sign');

const roles = {
	HEAD: '1',
	DEPUTY_HEAD: '3',
};

const responsibleSelector = '#id_responsible';
const assigneeSelector = '#id_representative';
const reviewerSelector = '#id_reviewer';
const editorSelector = '#id_editor';

type DocumentData = {
	title: string;
	document_uid: string;
	responsibleSelectorValue: number | string | null;
	assigneeSelectorValue: number | string | null;
	editorSelectorValue?: number | string | null;
	reviewerSelectorValue?: number | string | null;
}

export class SignB2EDocumentActivity extends EventEmitter
{
	#buttonNode: HTMLElement | null = null;
	#select: HTMLElement | null = null;
	#documentType: Array = [];
	#formName: string;
	#api: Api;
	#templateId: string;
	#previousData = {};

	constructor(options: Object)
	{
		super();
		this.setEventNamespace('BX.Sign.SignB2EDocumentActivity');
		this.#buttonNode = options.buttonNode;
		this.#documentType = options.documentType;
		this.#select = options.select;
		this.#api = new Api();
		this.#templateId = options.templateId;

		if (!Type.isStringFilled(options.formName))
		{
			throw new Error('formName must be filled string');
		}
		this.#formName = options.formName;
	}

	init(): void
	{
		this.#setTemplateList();

		if (!this.#buttonNode)
		{
			return;
		}

		Event.bind(this.#buttonNode, 'click', this.#openSlider.bind(this));
	}

	#openSlider(): void
	{
		BX.SidePanel.Instance.open('/sign/b2e/doc/0/?mode=template&IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER&FROM_ROBOT=1', {
			width: 1250,
			cacheable: false,
			events: {
				onClose: (event: BX.SidePanel.Event): void => {
					this.#onSliderClose(event);
				},
			},
		});
	}

	async #onSliderClose(event: BX.SidePanel.Event): void
	{
		const slider = event.getSlider();
		if (slider)
		{
			this.#setTemplateList(true, true);
		}
	}

	#setTemplateList(needUpdateTagSelectors: boolean = false, isSliderClose: boolean = false): void
	{
		this.#loadTemplatesList()
			.then(({ data }): void => {
				if (Type.isPlainObject(data))
				{
					this.#updateTemplateListSelect(data, isSliderClose);

					Object.entries(this.#getUserSelectorsMap()).forEach(([key, selector]) => {
						const dialog = this.#getDialog(selector);
						if (dialog === null)
						{
							return;
						}

						dialog.subscribe('onLoad', () => {
							const templateId = this.#templateId === '' ? 0 : this.#templateId;
							const defaultUserId = Object.values(data)[templateId]?.[`${key}SelectorValue`];
							if (defaultUserId)
							{
								this.#setSelectorValues([defaultUserId], selector);
							}
						});

						dialog.load();
					});

					if (needUpdateTagSelectors === true)
					{
						this.#updateTagSelectors(data);
					}

					this.#select.onchange = () => {
						this.#updateTagSelectors(data);
					};
				}
			})
			.catch((response): void => console.error(response.errors))
		;
	}

	#setSelectorValues(selectorValues: Array | null, selector: string): void
	{
		const dialog = this.#getDialog(selector);
		if (dialog === null)
		{
			return;
		}

		dialog.getSelectedItems().forEach(
			(item) => {
				item.deselect();
			},
		);

		selectorValues.forEach((value) => {
			let item = null;
			const roleId = roles[value];
			if (roleId !== undefined)
			{
				item = dialog.getItem([roleId, value]);
			}
			else if (Type.isInteger(Number(value)))
			{
				item = dialog.getItem(['user', value]);
			}

			if (item)
			{
				item.select();
			}
		});
	}

	#getUserSelectorsMap(): Record<string, string>
	{
		return {
			responsible: responsibleSelector,
			assignee: assigneeSelector,
			reviewer: reviewerSelector,
			editor: editorSelector,
		};
	}

	#getDialog(selector: string): ?Dialog
	{
		const userSelector = this.#getUserSelector(selector);
		if (!userSelector)
		{
			return null;
		}

		return userSelector.tagSelector.getDialog();
	}

	#getUserSelector(selector: string): ?UserSelector
	{
		return BX.Bizproc.UserSelector.getByNode(document.querySelector(selector));
	}

	#updateTemplateListSelect(data: Object, isSliderClose: boolean): void
	{
		if (this.#previousData && isSliderClose)
		{
			const previousFirstTemplateId = Object.keys(this.#previousData)[0];
			const currentFirstTemplateId = Object.keys(data)[0];

			if (previousFirstTemplateId !== currentFirstTemplateId)
			{
				this.#templateId = currentFirstTemplateId;
			}
		}

		if (this.#select)
		{
			this.#select.innerHTML = '';
		}

		Object.entries(data).forEach(([id, { title }]): void => {
			const selected = this.#templateId === id ? 'selected' : '';
			const idValue = Text.encode(id);
			const titleValue = Text.encode(title);
			const option = Tag.render`<option value="${idValue}" ${selected}>${titleValue}</option>`;
			Dom.append(option, this.#select);
		});

		this.#previousData = { ...data };
	}

	#updateTagSelectors(data: Record<string, DocumentData>): void
	{
		const selectedId = this.#select.value;
		if (!(selectedId in data))
		{
			return;
		}

		const selectedItem = data[selectedId];
		if (selectedItem)
		{
			this.#selectMembers(selectedItem);
		}
	}

	#selectMembers(selectedItem: Object): void
	{
		if (selectedItem)
		{
			this.#setSelectorValues([selectedItem.responsibleSelectorValue], responsibleSelector);
			this.#setSelectorValues([selectedItem.assigneeSelectorValue], assigneeSelector);
			this.#setSelectorValues(selectedItem.reviewerSelectorValue, reviewerSelector);
			this.#setSelectorValues([selectedItem.editorSelectorValue], editorSelector);
		}
	}

	#loadTemplatesList(): Promise
	{
		return ajax.runAction(
			'bizproc.activity.request',
			{
				data: {
					activity: 'SignB2EDocumentActivity',
					documentType: this.#documentType,
					params: {
						form_name: this.#formName,
					},
				},
			},
		);
	}
}

namespace.SignB2EDocumentActivity = SignB2EDocumentActivity;
