import { ajax as Ajax, Loc, Reflection, Text } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { MessageBox } from 'ui.dialogs.messagebox';

type ElementToDelete = {
	elementId: number,
	elementType: 'chart' | 'dataset',
};

type GridRowEditableData = {
	TITLE: string,
	EXTERNAL_ID: number,
	TYPE: string,
}

/**
 * @namespace BX.BIConnector
 */
class UnusedElementsGridManager
{
	#grid: BX.Main.grid;

	constructor(props)
	{
		this.#grid = BX.Main.gridManager.getById(props.gridId)?.instance;
		this.#initHints();
		this.#subscribeToEvents();
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

	#subscribeToEvents(): void
	{
		EventEmitter.subscribe('Grid::updated', () => {
			this.#initHints();
		});
	}

	deleteElement(elements: ElementToDelete[])
	{
		let popupTitle = Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_TITLE_SINGLE');
		let popupText = Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_TEXT_SINGLE');
		if (elements.length > 1)
		{
			popupTitle = Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_TITLE');
			popupText = Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_TEXT');
		}
		const messageBox = new MessageBox({
			title: popupTitle,
			message: popupText,
			maxWidth: 300,
			minWidth: 300,
			buttons: [
				new BX.UI.Button({
					color: BX.UI.Button.Color.DANGER,
					text: Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_BUTTON_YES'),
					onclick: (button) => {
						button.setWaiting();
						Ajax.runComponentAction('bitrix:biconnector.unusedelements', 'delete', {
							mode: 'class',
							data: {
								elements,
							},
						})
							.then(() => {
								this.#grid.reload();
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
					text: Loc.getMessage('BI_UNUSED_ELEMENTS_POPUP_DELETE_BUTTON_NO'),
					onclick: () => messageBox.close(),
				}),
			],
		});

		messageBox.show();
	}

	// noinspection JSUnusedGlobalSymbols
	openElement(openUrl: string)
	{
		this.#grid.tableFade();
		Ajax.runComponentAction('bitrix:biconnector.unusedelements', 'getOpenUrl', {
			mode: 'class',
			data: {
				openUrl,
			},
		})
			.then((response) => {
				const link = response.data;
				if (link)
				{
					window.open(link, '_blank').focus();
				}
				this.#grid.tableUnfade();
			})
			.catch((response) => {
				if (response.errors)
				{
					this.#notifyErrors(response.errors);
				}
				this.#grid.tableUnfade();
			});
	}

	// noinspection JSUnusedGlobalSymbols
	deleteSelectedElements()
	{
		const selectedElements = this.#grid.getRows().getSelected();
		const elementsToDelete: ElementToDelete[] = [];

		for (const row: BX.Grid.Row of selectedElements)
		{
			const rowData: GridRowEditableData = row.getEditData();
			elementsToDelete.push({
				elementId: rowData.EXTERNAL_ID,
				elementType: rowData.TYPE,
			});
		}
		this.deleteElement(elementsToDelete);
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

Reflection.namespace('BX.BIConnector').UnusedElementsGridManager = UnusedElementsGridManager;
