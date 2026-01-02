import { Dom, Event, Loc, Type, Uri } from 'main.core';
import { TemplateEntity, type TemplateEntityType } from 'sign.type';
import { Analytics } from 'sign.v2.analytics';
import { Api } from 'sign.v2.api';
import { MessageBox } from 'ui.dialogs.messagebox';
import { Switcher, SwitcherSize } from 'ui.switcher';
import { toggleActionButton } from './action-panel';
import { CreateFolderPopup } from './popup/create-folder';
import { DeleteConfirmationPopup } from './popup/delete-confirmation';
import './style.css';
import { FolderSelectionPopup } from './popup/folder-selection';
import type { GridRow } from './rows';
import { FeatureStorage } from 'sign.feature-storage';
import { extractMetadataFromRow } from './rows';
import { sendBlockedParams } from './type';

type TemplateSelectedEntity = {
	id: number;
	entityType: TemplateEntity.template | TemplateEntity.folder;
};

type Grid = BX.Main.grid;

export class Templates
{
	#gridId: string;
	#addNewTemplateLink: string;
	#urlsForReload: string[];

	constructor(gridId: string, addNewTemplateLink: string, urlsForReload: string[])
	{
		this.#gridId = gridId;
		this.#addNewTemplateLink = addNewTemplateLink;
		this.#urlsForReload = urlsForReload;
	}

	#analytics = new Analytics();
	#api = new Api();

	#changeVisibilityForTemplate(templateId: number, visibility: string): Promise<any>
	{
		const api = this.#api.template;

		return api.changeVisibility(templateId, visibility);
	}

	#changeVisibilityForFolder(folderId: number, visibility: string): Promise<any>
	{
		const api = this.#api.templateFolder;

		return api.changeVisibility(folderId, visibility);
	}

	renderSendButton(
		entityId: number,
		entityType: string,
		templateIds: number[],
		blockParams: sendBlockedParams,
	): HTMLElement
	{
		if (entityId <= 0)
		{
			throw new Error('Invalid entityId must be greater than 0');
		}

		const validEntityTypes = ['template', 'folder'];
		if (!validEntityTypes.includes(entityType))
		{
			throw new Error(`Invalid entityType must be one of ${validEntityTypes.join(', ')}`);
		}

		const button = new BX.UI.Button({
			text: Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_LAUNCH_SIGNING'),
			color: BX.UI.Button.Color.SUCCESS,
			size: BX.UI.Button.Size.SMALL,
			round: true,
			disabled: blockParams.isBlocked,
		});

		const buttonElement = button.render();

		if (blockParams.isNoCompaniesInFolder)
		{
			buttonElement.setAttribute('title', Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_EMPTY_FOLDER'));
		}
		else if (blockParams.isTemplateDisabled)
		{
			buttonElement.setAttribute('title', Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_TEMPLATE_DISABLED'));
		}
		else if (blockParams.isMultipleCompaniesInFolder)
		{
			buttonElement.setAttribute('title', Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_MULTIPLE_COMPANIES_IN_FOLDER'));
		}
		else if (blockParams.isInvisible && entityType === 'template')
		{
			buttonElement.setAttribute('title', Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_INVISIBLE_TEMPLATE'));
		}
		else if (blockParams.isInvisible && entityType === 'folder')
		{
			buttonElement.setAttribute('title', Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_INVISIBLE_FOLDER'));
		}
		else if (blockParams.hasAnyInvisibleTemplates)
		{
			buttonElement.setAttribute('title', Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_INVISIBLE_TEMPLATES_IN_FOLDER'));
		}
		else if (blockParams.hasNoReadAccess)
		{
			buttonElement.setAttribute('title', Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_NO_READ_ACCESS'));
		}
		else
		{
			BX.Event.bind(buttonElement, 'click', (event: MouseEvent): void => {
				event.preventDefault();
				event.stopPropagation();

				const sliderUrl = `sign-b2e-templates-settings-${entityId}-${entityType}`;
				BX.SidePanel.Instance.open(sliderUrl, {
					width: 900,
					cacheable: false,
					contentCallback: (): Promise<HTMLElement> => {
						return top.BX.Runtime.loadExtension(['sign.v2.b2e.sign-settings-templates']).then((exports) => {
							const { B2ETemplatesSignSettings } = exports;
							const container = BX.Tag.render`<div id="sign-b2e-templates-settings-container-${entityId}-${entityType}"></div>`;
							const templatesSignSettings = new B2ETemplatesSignSettings(templateIds, sliderUrl);
							templatesSignSettings.renderToContainer(container);

							return container;
						});
					},
				});
			});
		}

		return buttonElement;
	}

	async renderSwitcher(
		entityId: number,
		entityType: TemplateEntityType,
		isChecked: boolean,
		isDisabled: boolean,
		hasEditTemplateAccess?: boolean,
	): Promise<void>
	{
		const switcherNode = document.getElementById(`switcher_b2e_template_grid_${entityId}_${entityType}`);
		const switcher = new Switcher({
			node: switcherNode,
			checked: isChecked,
			size: SwitcherSize.medium,
			disabled: isDisabled,
			handlers: {
				toggled: async (event) => {
					event.stopPropagation();
					switcher.setLoading(true);
					const checked = switcher.isChecked();
					const visibility = checked ? 'visible' : 'invisible';
					try
					{
						switch (entityType)
						{
							case TemplateEntity.template:
								await this.#changeVisibilityForTemplate(entityId, visibility);
								break;
							case TemplateEntity.folder:
								await this.#changeVisibilityForFolder(entityId, visibility);
								break;
							default:
								await console.error(`Unknown entity type: ${entityType}`);
						}
						await this.reload();
					}
					catch
					{
						switcher.setLoading(false);
						switcher.check(!checked, false);
					}
					finally
					{
						this.#sendActionStateAnalytics(checked, entityId);
						switcher.setLoading(false);
					}
				},
			},
		});

		if (!isDisabled)
		{
			return;
		}

		let title = '';
		switch (entityType)
		{
			case TemplateEntity.template:
				title = hasEditTemplateAccess
					? Loc.getMessage('SIGN_TEMPLATE_BLOCKED_SWITCHER_HINT')
					: Loc.getMessage('SIGN_TEMPLATE_BLOCKED_SWITCHER_HINT_NO_ACCESS');
				break;
			case TemplateEntity.folder:
				title = hasEditTemplateAccess
					? Loc.getMessage('SIGN_TEMPLATE_FOLDER_BLOCKED_SWITCHER_HINT')
					: Loc.getMessage('SIGN_TEMPLATE_FOLDER_BLOCKED_SWITCHER_HINT_NO_ACCESS');
				break;
			default:
				title = '';
				break;
		}

		switcherNode.setAttribute('title', title);
	}

	async createFolder(): Promise<void>
	{
		try
		{
			const createFolderPopup = new CreateFolderPopup();
			const title = await createFolderPopup.show();
			const api = this.#api.templateFolder;
			await api.create(title);
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_HINT_SUCCESS'),
			});
		}
		catch
		{
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_HINT_FAIL'),
			});
		}

		await this.reload();
	}

	async renameFolder(entityId: number, oldTitle: string): Promise<void>
	{
		try
		{
			const createFolderPopup = new CreateFolderPopup();
			const newTitle = await createFolderPopup.show(oldTitle);
			const api = this.#api.templateFolder;
			await api.rename(entityId, newTitle);
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_HINT_RENAME_SUCCESS'),
			});
		}
		catch
		{
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_HINT_RENAME_FAIL'),
			});
		}

		await this.reload();
	}

	#sendActionStateAnalytics(checked: boolean, templateId: number): void
	{
		this.#analytics.send({
			category: 'templates',
			event: 'turn_on_off_template',
			type: 'manual',
			c_section: 'sign',
			c_sub_section: 'templates',
			c_element: checked ? 'on' : 'off',
			p5: `templateid_${templateId}`,
		});
	}

	reload(): Promise<void>
	{
		Event.ready(() => {
			const grid = this.#getFolderGrid() ?? this.#getTemplateListGrid();
			if (Type.isObject(grid))
			{
				grid.reload();
			}
		});
	}

	#getFolderGrid(): ?Grid
	{
		return BX.SidePanel.Instance.getTopSlider()?.getFrameWindow().BX.Main.gridManager.getById(this.#gridId)?.instance;
	}

	#getTemplateListGrid(): ?Grid
	{
		return BX.Main.gridManager.getById(this.#gridId)?.instance;
	}

	reloadAfterSliderClose(): void
	{
		const context = window === top ? window : top;

		context.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', async (event) => {
			const closedSliderUrl = event.getData()[0].getSlider().getUrl();

			if (this.#shouldReloadAfterClose(closedSliderUrl))
			{
				await this.reload();
			}
		});
	}

	#shouldReloadAfterClose(closedSliderUrl: string): boolean
	{
		const uri = new Uri(closedSliderUrl);
		const path = uri.getPath();

		return closedSliderUrl === this.#addNewTemplateLink
			|| closedSliderUrl === 'sign-settings-template-created'
			|| this.#urlsForReload.some((url) => path.startsWith(new Uri(url).getPath()));
	}

	async exportBlank(templateId: number): Promise<void>
	{
		try
		{
			const { json, filename } = await this.#api.template.exportBlank(templateId);
			const mimeType = 'application/json';
			this.#downloadStringLikeFile(json, filename, mimeType);

			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_EXPORT_BLANK_SUCCESS'),
			});
		}
		catch (e)
		{
			console.error(e);
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_EXPORT_BLANK_FAILURE'),
			});
		}
	}

	#downloadStringLikeFile(data: string, filename: string, mimeType: string): void
	{
		const blob = new Blob([data], { type: mimeType });
		const url = window.URL.createObjectURL(blob);
		const a = document.createElement('a');
		Dom.style(a, 'display', 'none');
		a.href = url;
		a.download = filename;
		Dom.append(a, document.body);
		a.click();
		window.URL.revokeObjectURL(url);
		Dom.remove(a);
	}

	async copyTemplate(templateId: number, folderId: number): Promise<void>
	{
		try
		{
			const response = await this.#api.template.copy(templateId, folderId);
			const copyTemplateId = response.template.id;

			await this.reload();

			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_COPY_HINT_SUCCESS'),
			});

			if (window.top.BX.SidePanel && this.#addNewTemplateLink && copyTemplateId)
			{
				window.top.BX.SidePanel.Instance.open(
					`${this.#addNewTemplateLink}&templateId=${copyTemplateId}&stepId=changePartner&noRedirect=Y`,
				);
			}
		}
		catch (error)
		{
			console.error('Error copying template:', error);
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_TEMPLATE_GRID_COPY_HINT_FAIL'),
			});
		}
	}

	#getSelectedItems(): TemplateSelectedEntity
	{
		const items = [];
		const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
		checkboxes.forEach((checkbox) => {
			const id = checkbox.value;

			if (Number.isInteger(Number(id)))
			{
				const rowElement = checkbox.closest('tr');
				const entityElement = rowElement
					? rowElement.querySelector('.sign-template-title, .sign-template-title-without-link')
					: null;
				const entityType = entityElement
					? entityElement.getAttribute('data-entity-type')
					: null;

				items.push({
					id,
					entityType,
				});
			}
		});

		return items;
	}

	async delete(entityId: number, entityType: TemplateEntityType)
	{
		const deleteConfirmationPopup = new DeleteConfirmationPopup();
		await deleteConfirmationPopup.show(entityType, async () => {
			const api = this.#api;
			switch (entityType)
			{
				case TemplateEntity.template:
					await api.template.delete(entityId);
					break;
				case TemplateEntity.folder:
					await api.templateFolder.delete(entityId);
					break;
				default:
					await console.error(`Unknown entity type: ${entityType}`);
			}
			await this.reload();
		});
	}

	async deleteSelectedItems(): Promise<void>
	{
		const selectedItems = this.#getSelectedItems();
		if (selectedItems.length > 0)
		{
			const deleteConfirmationPopup = new DeleteConfirmationPopup();
			await deleteConfirmationPopup.show(TemplateEntity.multiple, async () => {
				try
				{
					await this.#api.template.deleteEntities(selectedItems);
					await this.reload();
				}
				catch (error)
				{
					await console.error('Error deleting template entities:', error);
				}
			});
		}
	}

	async moveTemplatesToFolder(templateId: number | null = null): void
	{
		const selectedItems = this.#getSelectedItems();
		if (selectedItems.length > 0 || templateId !== null)
		{
			const folderSelectionPopup = new FolderSelectionPopup();
			folderSelectionPopup.subscribe('folderSelected', (event) => {
				this.selectedFolder = event.getData();
			});
			const folderList = await folderSelectionPopup.show();

			MessageBox.show({
				title: Loc.getMessage('SIGN_TEMPLATE_GRID_MOVE_TO_FOLDER_POPUP_TITLE'),
				message: folderList,
				modal: true,
				minWidth: 500,
				minHeight: 370,
				buttons: [
					new BX.UI.Button({
						text: Loc.getMessage('SIGN_TEMPLATE_GRID_MOVE_TO_FOLDER_POPUP_OK_BUTTON_TEXT'),
						color: BX.UI.Button.Color.SUCCESS,
						onclick: async (button) => {
							if (this.selectedFolder)
							{
								const selectedItem = { id: templateId, entityType: TemplateEntity.template };
								const selectedTemplates = templateId === null ? selectedItems : [selectedItem];
								await this.#api.template.moveToFolder(selectedTemplates, Number(this.selectedFolder.id));
								button.getContext().close();
								await this.reload();
							}
						},
						className: 'sign-b2e-grid-templates-popup__move-to-button',
					}),
					new BX.UI.Button({
						text: Loc.getMessage('SIGN_TEMPLATE_GRID_MOVE_TO_FOLDER_POPUP_CANCEL_BUTTON_TEXT'),
						color: BX.UI.Button.Color.LINK,
						onclick: (button) => {
							button.getContext().close();
						},
					}),
				],
			});
		}
	}

	openSliderTemplateFolderContent(folderId: number = 0): boolean | any
	{
		window.event.stopPropagation();
		if (BX.SidePanel && BX.SidePanel.Instance)
		{
			const basePath = folderId === 0 ? 'employee/templates/folder/' : 'templates/folder/';

			const queryParams = new URLSearchParams(window.location.search);
			const url = (folderId !== 0 && queryParams.has('folderId'))
				? `?folderId=${encodeURIComponent(folderId)}`
				: `${basePath}?folderId=${encodeURIComponent(folderId)}`;

			BX.SidePanel.Instance.open(url, {
				width: 1650,
				cacheable: false,
				allowChangeHistory: true,
			});
		}
	}

	subscribeOnGridEvents(): void
	{
		Event.ready(() => {
			const grid: ?Grid = this.#getFolderGrid() ?? this.#getTemplateListGrid();
			if (!Type.isObject(grid))
			{
				return;
			}

			const gridTreeAttributesChangesMutationObserver = new MutationObserver(
				() => this.#onGridTreeAttributesMutate(grid),
			);
			gridTreeAttributesChangesMutationObserver.observe(grid.getContainer(), { subtree: true, attributes: true });
		});
	}

	#onGridTreeAttributesMutate(grid: Grid): void
	{
		const rows: GridRow[] = grid.getRows().getRows();
		const isRowUnmovable = (row: GridRow): boolean => {
			const metadata = extractMetadataFromRow(row);
			if (Type.isNull(metadata))
			{
				return true;
			}

			const isChecked = row.getCheckbox()?.checked;
			const isRestricted = metadata.isFolderEntityType()
				|| metadata.isInitiatedByTypeisEmployee()
				|| !metadata.canEditAccess()
			;

			return isRestricted && isChecked;
		};

		const allSelectedCellsAmount = parseInt(
			document.querySelector('[data-role="action-panel-total-param"]')?.textContent ?? '0',
			10,
		);

		const moveActionButton = {
			id: 'sign-template-list-move-to-folder-button',
			disabled: false,
			title: Loc.getMessage('SIGN_TEMPLATE_GRID_MOVE_TO_FOLDER_GROUP_ACTION_BUTTON_DISABLED_HINT'),
		};

		const rowsWithoutHeaderAndHiddenRow = rows.slice(2);

		moveActionButton.disabled = rowsWithoutHeaderAndHiddenRow
			.some((row) => isRowUnmovable(row));

		toggleActionButton(moveActionButton);

		const isRowNonDeletable = (row: GridRow): boolean => {
			const metadata = extractMetadataFromRow(row);
			if (Type.isNull(metadata))
			{
				return true;
			}

			const isChecked = row.getCheckbox()?.checked;

			return !metadata.canDeleteAccess() && isChecked;
		};

		const deleteActionButton = {
			id: 'sign-template-list-delete-button',
			disabled: false,
			title: FeatureStorage.isTemplateFolderGroupingAllowed()
				? Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_GROUP_ACTION_BUTTON_DISABLED_HINT')
				: Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_TEMPLATES_GROUP_ACTION_BUTTON_DISABLED_HINT'),
		};

		if (allSelectedCellsAmount <= 1)
		{
			toggleActionButton(moveActionButton);

			return;
		}

		deleteActionButton.disabled = rowsWithoutHeaderAndHiddenRow
			.some((row) => isRowNonDeletable(row));

		toggleActionButton(deleteActionButton);
	}
}
