import { EditableColumnManager } from 'biconnector.grid.editable-columns';
import { Reflection, Uri, Text, Loc, ajax as Ajax, Tag } from 'main.core';
import { AirButtonStyle, Button, ButtonSize } from 'ui.buttons';
import { Dialog } from 'ui.system.dialog';
import { EventEmitter } from 'main.core.events';
import 'ui.alerts';
import 'ui.forms';

type Props = {
	gridId: ?string,
	sourceTitleList: Object,
};

/**
 * @namespace BX.BIConnector
 */
class ExternalSourceManager
{
	#grid: BX.Main.grid;
	#filter: BX.Main.Filter;
	#sourceTitleList: Object;

	#datasetUrl: String = '/bi/dataset/';

	constructor(props: Props)
	{
		this.#grid = BX.Main.gridManager.getById(props.gridId)?.instance;
		this.#filter = BX.Main.filterManager.getById(props.gridId);
		this.#sourceTitleList = props.sourceTitleList ?? {};
		this.#initHints();
		this.#subscribeToEvents();

		EditableColumnManager.init(
			props.gridId,
			[
				{
					name: 'DESCRIPTION',
					saveEndpoint: 'biconnector.externalsource.source.updateComment',
					onValueCheck: () => true,
					onSave: () => {
						BX.UI.Notification.Center.notify({
							content: Text.encode(Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_COMMENT_UPDATED')),
						});
					},
				},
			],
		);
	}

	#subscribeToEvents()
	{
		EventEmitter.subscribe('Grid::updated', () => {
			this.#initHints();
		});

		EventEmitter.subscribe('SidePanel.Slider:onMessage', (event) => {
			const [messageEvent] = event.getData();
			const eventId = messageEvent.getEventId();
			if (
				eventId === 'BIConnector:ExternalConnectionGrid:reload'
				|| eventId === 'BIConnector:ExternalConnection:onConnectionSave'
			)
			{
				this.#grid.reload();
			}
		});
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

	openSourceDetail(id: number, moduleId: string): void
	{
		let sliderLink = '';
		let sliderWidth = 0;

		if (moduleId === 'BI')
		{
			sliderLink = new Uri(`/bitrix/components/bitrix/biconnector.externalconnection/slider.php?sourceId=${id}`);
			sliderWidth = 564;
		}
		else if (moduleId === 'CRM')
		{
			sliderLink = new Uri(`/crm/tracking/source/edit/${id}/`);
			sliderWidth = 900;
		}
		else
		{
			return;
		}

		BX.SidePanel.Instance.open(
			sliderLink.toString(),
			{
				width: sliderWidth,
				allowChangeHistory: false,
				cacheable: false,
				events: {
					onClose: BX.BIConnector.TrackingAnalyticsHandler.handleSliderClose,
				},
			},
		);
	}

	openCreateSourceSlider()
	{
		const sliderLink = new Uri('/bitrix/components/bitrix/biconnector.apachesuperset.source.connect.list/slider.php');

		BX.SidePanel.Instance.open(
			sliderLink.toString(),
			{
				width: 900,
				allowChangeHistory: false,
				cacheable: false,
			},
		);
	}

	changeActivitySource(id: number, moduleId: string)
	{
		Ajax.runAction('biconnector.externalsource.source.changeActivity', {
			data: {
				id,
				moduleId,
			},
		})
			.then(() => {
				this.getGrid().reload();
			})
			.catch((response) => {
				if (response.errors)
				{
					this.#notifyErrors(response.errors);
				}
			})
		;
	}

	deleteSource(id: number, moduleId: string)
	{
		Ajax.runAction('biconnector.externalsource.source.validateBeforeDelete', {
			data: {
				id,
				moduleId,
			},
		})
			.then(() => {
				this.#showDeleteSourcePopup(id, moduleId);
			})
			.catch((response) => {
				if (response.errors)
				{
					if (response.errors.some(({ code }) => code === 409))
					{
						this.#getDeleteSourceWithDatasetDialog(id).show();
					}
					else
					{
						this.#notifyErrors(response.errors);
					}
				}
			})
		;
	}

	#showDeleteSourcePopup(id: number, moduleId: string)
	{
		const messageBox = new Dialog({
			content: this.#getDeleteSourcePopupContent(),
			centerButtons: [
				new Button({
					color: AirButtonStyle.FILLED,
					useAirDesign: true,
					text: Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_DELETE_POPUP_CAPTION_YES'),
					onclick: (button) => {
						button.setWaiting();
						Ajax.runAction('biconnector.externalsource.source.delete', {
							data: {
								id,
								moduleId,
							},
						})
							.then(() => {
								this.getGrid().reload();
								messageBox.hide();
							})
							.catch((response) => {
								messageBox.hide();
								if (response.errors)
								{
									this.#notifyErrors(response.errors);
								}
							})
						;
					},
				}),
				new Button({
					text: Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_DELETE_POPUP_CAPTION_NO'),
					size: ButtonSize.LARGE,
					style: AirButtonStyle.PLAIN,
					useAirDesign: true,
					onclick: () => messageBox.hide(),
				}),
			],
			hasOverlay: true,
		});

		messageBox.show();
	}

	#getDeleteSourcePopupContent(): HTMLElement
	{
		return Tag.render`
			<div class="biconnector-delete-source-popup-content">
				${Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_DELETE_POPUP_TITLE')}
			</div>
		`;
	}

	#getDeleteSourceWithDatasetDialog(id: number): Dialog
	{
		const deleteSourcePopupInstance = new Dialog({
			title: Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_WITH_DATASET_DELETE_DIALOG_TITLE'),
			content: this.#getDeleteSourceWithDatasetDialogContent(),
			centerButtons: [
				new Button({
					text: Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_WITH_DATASET_DELETE_DIALOG_OK_CAPTION'),
					size: ButtonSize.LARGE,
					style: AirButtonStyle.FILLED,
					useAirDesign: true,
					onclick: () => {
						deleteSourcePopupInstance.hide();
						this.#openDatasetSlider(id);
					},
				}),
				new Button({
					text: Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_WITH_DATASET_DELETE_DIALOG_CANCEL_CAPTION'),
					size: ButtonSize.LARGE,
					style: AirButtonStyle.PLAIN,
					useAirDesign: true,
					onclick: () => deleteSourcePopupInstance.hide(),
				}),
			],
			hasOverlay: true,
			width: 446,
		});

		return deleteSourcePopupInstance;
	}

	#getDeleteSourceWithDatasetDialogContent(): HTMLElement
	{
		return Tag.render`
			<div class="biconnector-delete-source-with-dataset-dialog-content">
				${Loc.getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_WITH_DATASET_DELETE_DIALOG_DESCRIPTION')}
			</div>
		`;
	}

	#openDatasetSlider(id: number): void
	{
		BX.SidePanel.Instance.open(
			this.#datasetUrl,
			{
				allowChangeHistory: false,
				cacheable: false,
				events: {
					onOpenComplete: (event) => {
						const slider = event.getSlider ? event.getSlider() : BX.SidePanel.Instance.getTopSlider();

						if (!slider)
						{
							return;
						}

						const sliderWindow = slider.getWindow();
						const tryApplyFilter = () => {
							if (
								sliderWindow
								&& sliderWindow.BX
								&& sliderWindow.BX.Main
								&& sliderWindow.BX.Main.filterManager
							)
							{
								const filter = sliderWindow.BX.Main.filterManager.getById('biconnector_superset_external_dataset_grid');
								if (filter && (filter instanceof sliderWindow.BX.Main.Filter))
								{
									const filterApi = filter.getApi();
									filterApi.setFields({
										'SOURCE.ID': String(id),
										'SOURCE.ID_label': this.#sourceTitleList[id],
									});
									filterApi.apply();

									return;
								}
							}
							setTimeout(tryApplyFilter, 100);
						};
						tryApplyFilter();
					},
				},
			},
		);
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

Reflection.namespace('BX.BIConnector').ExternalSourceManager = ExternalSourceManager;
