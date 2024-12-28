import { ajax, Dom, Runtime, Tag, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import { Layout } from 'ui.sidepanel.layout';
import type { CompanyEditorOptions } from './types';
import { CompanyEditorMode } from './types';

const crmEditorSettings = Object.freeze({
	params: {
		ENABLE_CONFIGURATION_UPDATE: 'N',
		ENABLE_PAGE_TITLE_CONTROLS: false,
		ENABLE_MODE_TOGGLE: false,
		IS_EMBEDDED: 'N',
		forceDefaultConfig: 'Y',
		enableSingleSectionCombining: 'N',
	},
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
	#guid: string;
	#entityTypeId: number;
	#companyId: ?number = null;
	#showOnlyCompany: boolean;

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
		this.#entityTypeId = options.entityTypeId;
		this.#guid = options.guid;
		this.#showOnlyCompany = options.showOnlyCompany ?? false;
	}

	static openSlider(
		options: CompanyEditorOptions,
		sliderOptions: { onCloseHandler: () => void },
	): void
	{
		BX.SidePanel.Instance.open('company-editor', {
			width: 800,
			cacheable: false,
			contentCallback: () => {
				return top.BX.Runtime.loadExtension('sign.v2.company-editor')
					.then(({ CompanyEditor }) => new CompanyEditor(options).getLayout())
				;
			},
			events: {
				onClose: sliderOptions?.onCloseHandler ?? (() => {}),
			},
		});
	}

	async #loadEntityEditor(): Promise<string>
	{
		const response = await ajax.runAction('crm.api.item.getEditor', {
			data: {
				...crmEditorSettings,
				id: this.#documentEntityId,
				entityTypeId: this.#entityTypeId,
				guid: this.#guid,
			},
		});

		return response?.data?.html || '';
	}

	async #showEntityEditor(container: HTMLElement): Promise<void>
	{
		const loader = new Loader({
			target: container,
			size: 80,
		});

		try
		{
			loader.show();
			const editorHtml = await this.#loadEntityEditor();
			await Runtime.html(container, editorHtml);
		}
		finally
		{
			loader.destroy();
		}

		if (this.#showOnlyCompany)
		{
			const columnContent = container.querySelector('.ui-entity-editor-column-content');
			if (!Type.isDomNode(columnContent))
			{
				this.#loadedHandler();

				return;
			}

			const companyNode = columnContent.querySelector('[data-cid="myCompany"]');
			if (!Type.isDomNode(companyNode))
			{
				this.#loadedHandler();

				return;
			}

			Dom.clean(columnContent);
			Dom.append(companyNode, columnContent);

			const sectionHeader = columnContent.querySelector('.ui-entity-editor-section-header');
			if (Type.isDomNode(sectionHeader))
			{
				Dom.remove(sectionHeader);
			}
		}

		this.#loadedHandler();
	}

	render(): HTMLElement
	{
		const container = Tag.render`<div style="position: relative; z-index: 1; height: 100%"></div>`;
		this.#showEntityEditor(container);

		return container;
	}

	getLayout(): Promise<HTMLElement>
	{
		// eslint-disable-next-line unicorn/no-this-assignment
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
					},
				});

				return [
					saveButton,
					cancelButton,
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
		BX.Crm.EntityEditor.getDefault().save();
		const entityData = await promise;
		entityEditorEvents.forEach((event) => EventEmitter.unsubscribeAll(event));
		if (entityData)
		{
			const {
				MYCOMPANY_ID_INFO: { COMPANY_DATA: [companyData] },
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
		const crmEditor = BX.Crm.EntityEditor.getDefault();
		if (Type.isNil(crmEditor))
		{
			return;
		}
		const companyField = crmEditor.getControlById('MYCOMPANY_ID');
		// eslint-disable-next-line no-underscore-dangle
		const [searchBox] = companyField._companySearchBoxes;

		if (this.#mode === CompanyEditorMode.Create)
		{
			crmEditor.switchControlMode(companyField, BX.UI.EntityEditorMode.edit);
			searchBox.setEntity(BX.CrmEntityInfo.create({
				typeName: 'COMPANY',
				title: '',
			}), true);
			// eslint-disable-next-line no-underscore-dangle
			searchBox._searchInput.value = '';
			// eslint-disable-next-line no-underscore-dangle
			Dom.remove(searchBox._changeButton);

			return;
		}

		if (this.#mode === CompanyEditorMode.Edit)
		{
			const deployEvent = 'BX.Crm.EntityEditor:onUserFieldsDeployed';

			// eslint-disable-next-line no-underscore-dangle
			searchBox._searchInput.value = '';
			searchBox.setEntityTypeName('COMPANY');
			searchBox.setMode(BX.Crm.EntityEditorClientMode.loading);
			searchBox.adjust();
			EventEmitter.unsubscribeAll(deployEvent);
			EventEmitter.subscribe(deployEvent, () => {
				// eslint-disable-next-line no-underscore-dangle
				Dom.remove(searchBox._changeButton);
			});
			searchBox.loadEntityInfo(String(this.#companyId));
		}
	}
}
