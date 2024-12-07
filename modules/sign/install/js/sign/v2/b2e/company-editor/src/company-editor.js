import { ajax, Dom, Loc, Runtime, Tag, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Layout } from 'ui.sidepanel.layout';
import { CompanyEditorMode } from './types';
import type { CompanyEditorOptions } from './types';

const crmEditorSettings = Object.freeze({
	entityTypeId: 39,
	guid: 'sign_b2e_entity_editor',
	params: {
		ENABLE_PAGE_TITLE_CONTROLS: false,
		ENABLE_MODE_TOGGLE: false,
		IS_EMBEDDED: 'N',
		forceDefaultConfig: 'Y',
		enableSingleSectionCombining: 'N'
	}
});
const entityEditorEvents = [
	'onCrmEntityUpdate',
	'BX.Crm.EntityEditor:onFailedValidation',
	'onCrmEntityUpdateError',
	'BX.Crm.EntityEditor:onEntitySaveFailure',
];

export class CompanyEditor
{
	#mode: string = CompanyEditorMode.Create;

	#documentEntityId: number;
	#companyId: ?number = null;

	#options: CompanyEditorOptions;

	constructor(options: CompanyEditorOptions)
	{
		this.#options = options;
		if (options?.mode === CompanyEditorMode.Edit && options?.companyId > 0)
		{
			this.#mode = CompanyEditorMode.Edit;
			this.#companyId = options.companyId;
		}

		this.#documentEntityId = options.documentEntityId;
	}

	static openSlider(
		options: CompanyEditorOptions,
		sliderOptions: { onCloseHandler: () => void }
	): void
	{
		BX.SidePanel.Instance.open('company-editor', {
			width: 800,
			cacheable: false,
			contentCallback: () => {
				return top.BX.Runtime.loadExtension('sign.v2.b2e.company-editor').then(() => {
					return (new top.BX.Sign.V2.B2e.CompanyEditor(options))
						.getLayout()
					;
				});
			},
			events: {
				onClose: sliderOptions?.onCloseHandler ?? (() => {}),
			}
		});
	}

	render(): HTMLElement
	{
		const container = Tag.render`<div style="position: relative; z-index: 1;"></div>`;

		ajax.runAction('crm.api.item.getEditor', {
			data: { ...crmEditorSettings, id: this.#documentEntityId }
		}).then(async (response) => {
			const html = response?.data?.html || '';
			await Runtime.html(container, html);

			this.#loadedHandler();
		});

		return container;
	}

	getLayout(): Layout
	{
		const companyEditor = this;

		return Layout.createContent({
			title: companyEditor.#options?.layoutTitle ?? '',
			content(): HTMLElement
			{
				return companyEditor.render();
			},
			buttons({ cancelButton, SaveButton })
			{
				const saveButton = new SaveButton({
					onclick: async () => {
						saveButton.setClocking(true);
						await companyEditor.#save();
						saveButton.setClocking(false);
					}
				});
				return [
					saveButton,
					cancelButton
				];
			},
		});
	}

	async #save(): Promise<void>
	{
		const promise = new Promise((resolve) => {
			const [successFullEvent, ...errorEvents] = entityEditorEvents;
			EventEmitter.subscribeOnce(successFullEvent, (event) => {
				const [{ entityData }] = event.data;
				resolve(entityData);
			});
			errorEvents.forEach((event) => {
				EventEmitter.subscribeOnce(event, () => resolve(null));
			});
		});
		BX.Crm.EntityEditor.defaultInstance.save();
		const entityData = await promise;
		entityEditorEvents.forEach((event) => EventEmitter.unsubscribeAll(event));
		if (entityData)
		{
			const {
				MYCOMPANY_ID_INFO: { COMPANY_DATA: [companyData] }
			} = entityData;

			if (Type.isFunction(this.#options?.events?.onCompanySavedHandler))
			{
				this.#options.events.onCompanySavedHandler(companyData.id);
			}

			BX.SidePanel.Instance.close();
		}
	}

	#loadedHandler(): void
	{
		const crmEditor = BX.Crm.EntityEditor.defaultInstance;
		const companyField = crmEditor.getControlById('MYCOMPANY_ID');
		const [searchBox] = companyField._companySearchBoxes;

		if (this.#mode === CompanyEditorMode.Create)
		{
			crmEditor.switchControlMode(companyField, BX.UI.EntityEditorMode.edit);
			searchBox.setEntity(BX.CrmEntityInfo.create({
				typeName: 'COMPANY',
				title: ''
			}), true);
			searchBox._searchInput.value = '';
			Dom.remove(searchBox._changeButton);

			return;
		}


		if (this.#mode === CompanyEditorMode.Edit)
		{
			const deployEvent = 'BX.Crm.EntityEditor:onUserFieldsDeployed';

			searchBox._searchInput.value = '';
			searchBox.setEntityTypeName('COMPANY');
			searchBox.setMode(BX.Crm.EntityEditorClientMode.loading);
			searchBox.adjust();
			EventEmitter.unsubscribeAll(deployEvent);
			EventEmitter.subscribe(deployEvent, () => {
				Dom.remove(searchBox._changeButton);
			});
			searchBox.loadEntityInfo(`${this.#companyId}`);
		}
	}
}