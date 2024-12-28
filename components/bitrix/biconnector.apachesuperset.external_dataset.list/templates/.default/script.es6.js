import { MessageBox } from 'ui.dialogs.messagebox';
import { Reflection, Loc, Text, ajax as Ajax } from 'main.core';
import { EventEmitter } from 'main.core.events';
import 'spotlight';
import 'ui.alerts';
import 'ui.forms';

type Props = {
	gridId: ?string,
	isNeedShowTopMenuGuide: boolean,
	isNeedShowDraftGuide: boolean,
};

/**
 * @namespace BX.BIConnector
 */
class ExternalDatasetManager
{
	#grid: BX.Main.grid;
	#filter: BX.Main.Filter;

	constructor(props: Props)
	{
		this.#grid = BX.Main.gridManager.getById(props.gridId)?.instance;
		this.#filter = BX.Main.filterManager.getById(props.gridId);
		this.#subscribeToEvents();
		this.#initHints();
	}

	#initHints(): void
	{
		const manager = BX.UI.Hint.createInstance({
			popupParameters: {
				autoHide: true,
			},
		});
		manager.init(this.#grid.getContainer());
	}

	#subscribeToEvents()
	{
		EventEmitter.subscribe('SidePanel.Slider:onMessage', (event) => {
			const [messageEvent] = event.getData();
			if (messageEvent.getEventId() === 'BIConnector.dataset-import:onDatasetCreated')
			{
				this.#grid.reload();
			}
		});

		EventEmitter.subscribe('Grid::updated', () => {
			this.#initHints();
		});
	}

	getGrid(): BX.Main.grid
	{
		return this.#grid;
	}

	getFilter(): BX.Main.Filter
	{
		return this.#filter;
	}

	handleCreatedByClick(ownerData: Object)
	{
		this.handleDatasetFilterChange({
			fieldId: 'CREATED_BY_ID',
			...ownerData,
		});
	}

	handleUpdatedByClick(ownerData: Object)
	{
		this.handleDatasetFilterChange({
			fieldId: 'UPDATED_BY_ID',
			...ownerData,
		});
	}

	handleSourceClick(sourceData: Object)
	{
		this.handleDatasetFilterChange({
			fieldId: 'SOURCE.ID',
			...sourceData,
		});
	}

	handleDatasetFilterChange(fieldData: Object)
	{
		const filterFieldsValues = this.getFilter().getFilterFieldsValues();
		let currentFilteredField = filterFieldsValues[fieldData.fieldId] ?? [];
		let currentFilteredFieldLabel = filterFieldsValues[`${fieldData.fieldId}_label`] ?? [];

		if (fieldData.IS_FILTERED)
		{
			currentFilteredField = currentFilteredField.filter((value) => parseInt(value, 10) !== fieldData.ID);
			currentFilteredFieldLabel = currentFilteredFieldLabel.filter((value) => value !== fieldData.TITLE);
		}
		else if (!currentFilteredField.includes(fieldData.ID))
		{
			currentFilteredField.push(fieldData.ID);
			currentFilteredFieldLabel.push(fieldData.TITLE);
		}

		const filterApi = this.getFilter().getApi();
		const filterToExtend = {};
		filterToExtend[fieldData.fieldId] = currentFilteredField;
		filterToExtend[`${fieldData.fieldId}_label`] = currentFilteredFieldLabel;

		filterApi.extendFilter(filterToExtend);
		filterApi.apply();
	}

	deleteDataset(id) {
		const messageBox = new BX.UI.Dialogs.MessageBox({
			message: Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_DELETE_POPUP_DESCRIPTION'),
			title: Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_DELETE_POPUP_TITLE'),
			buttons: [
				new BX.UI.Button({
					color: BX.UI.Button.Color.DANGER,
					text: Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_DELETE_POPUP_CAPTION_YES'),
					onclick: (button) => {
						button.setWaiting();
						this.deleteDatasetAjaxAction(id)
							.then(() => {
								this.getGrid().reload();
								messageBox.close();
							})
							.catch((response) => {
								messageBox.close();
								if (response.errors)
								{
									this.#notifyErrors(response.errors);
								}
							});
					},
				}),
				new BX.UI.CancelButton({
					text: Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_DELETE_POPUP_CAPTION_NO'),
					onclick: (button) => messageBox.close(),
				}),
			],
		});

		messageBox.show();
	}

	deleteDatasetAjaxAction(datasetId: int): Promise
	{
		return Ajax.runAction('biconnector.externalsource.dataset.delete', {
			data: {
				id: datasetId,
			},
		});
	}

	showSupersetError(): void
	{
		BX.UI.Notification.Center.notify({
			content: Text.encode(Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_ERROR_SUPERSET')),
		});
	}

	#notifyErrors(errors: Array): void
	{
		if (errors[0] && errors[0].message)
		{
			BX.UI.Notification.Center.notify({
				content: Text.encode(errors[0].message),
			});
		}
	}
}

Reflection.namespace('BX.BIConnector').ExternalDatasetManager = ExternalDatasetManager;
