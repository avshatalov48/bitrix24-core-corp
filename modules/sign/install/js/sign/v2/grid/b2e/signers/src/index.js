import { Dom, Event, Loc, Type, Tag } from 'main.core';
import { Api } from 'sign.v2.api';
import { MessageBox } from 'ui.dialogs.messagebox';
import { CreateListPopup } from './popup/create-list';
import { type UserParty } from 'sign.v2.b2e.user-party';

const GRID_SIGNERS_LISTS = 'SIGN_B2E_SIGNERS_LIST_GRID';
const GRID_SIGNERS = 'SIGN_B2E_SIGNERS_LIST_GRID_EDIT';

export class Signers
{
	#api = new Api();

	reloadSigners(): void
	{
		const gridManager = this.#getGridManager();

		Event.ready(() => {
			const grid = gridManager?.getById(GRID_SIGNERS)?.instance;
			if (Type.isObject(grid))
			{
				grid.reload();
			}
		});
	}

	reloadLists(): void
	{
		const gridManager = this.#getGridManager();

		Event.ready(() => {
			const grid = gridManager?.getById(GRID_SIGNERS_LISTS)?.instance;
			if (Type.isObject(grid))
			{
				grid.reload();
			}
		});
	}

	async deleteList(listId: number): Promise<void>
	{
		const messageContent = Tag.render`
			<div>
				${Loc.getMessage('SIGN_SIGNERS_DELETE_CONFIRMATION_MESSAGE')}
			</div>
		`;
		Dom.style(messageContent, 'margin-top', '5%');
		Dom.style(messageContent, 'color', '#535c69');

		MessageBox.show({
			title: Loc.getMessage('SIGN_SIGNERS_DELETE_CONFIRMATION_TITLE'),
			message: messageContent.outerHTML,
			modal: true,
			buttons: [
				new BX.UI.Button({
					text: Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_POPUP_YES'),
					color: BX.UI.Button.Color.PRIMARY,
					onclick: async (button) => {
						button.setDisabled(true);
						button.setState(BX.UI.Button.State.WAITING);

						try
						{
							const api = this.#api;
							await api.signersList.deleteSignersList(listId);

							window.top.BX.UI.Notification.Center.notify({
								content: Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_HINT_SUCCESS'),
							});
						}
						catch
						{
							window.top.BX.UI.Notification.Center.notify({
								content: Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_HINT_FAIL'),
							});
						}

						await this.reloadLists();
						button.getContext().close();
					},
				}),
				new BX.UI.Button({
					text: Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_POPUP_NO'),
					color: BX.UI.Button.Color.LINK,
					onclick: (button) => {
						button.getContext().close();
					},
				}),
			],
		});
	}

	async copyList(listId: number): Promise<void>
	{
		try
		{
			await this.#api.signersList.copySignersList(listId);
			await this.reloadLists();
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_SIGNERS_GRID_COPY_HINT_SUCCESS'),
			});
		}
		catch (error)
		{
			console.error('Error copying list:', error);
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_SIGNERS_GRID_COPY_HINT_FAIL'),
			});
		}
	}

	async deleteSelectedSigners(listId: number): Promise<void>
	{
		const gridManager = this.#getGridManager();
		const grid = gridManager?.getById(GRID_SIGNERS)?.instance;
		if (!grid)
		{
			return;
		}

		const selectedIds = grid.getRows().getSelectedIds();

		if (selectedIds.length === 0)
		{
			return;
		}

		await this.deleteSigners(listId, selectedIds);
	}

	async deleteSigners(listId: number, userIds: number[]): Promise<void>
	{
		BX.UI.Dialogs.MessageBox.show({
			message: Loc.getMessage('SIGN_SIGNERS_SIGNER_DELETE_CONFIRMATION_TITLE'),
			modal: true,
			buttons: [
				new BX.UI.Button({
					text: Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_POPUP_YES'),
					color: BX.UI.Button.Color.PRIMARY,
					onclick: async (button) => {
						button.setDisabled(true);
						button.setState(BX.UI.Button.State.WAITING);

						const isSingle = userIds.length === 1;
						const successMsg = isSingle
							? Loc.getMessage('SIGN_SIGNERS_SIGNER_GRID_DELETE_HINT_SUCCESS')
							: Loc.getMessage('SIGN_SIGNERS_SIGNERS_GRID_DELETE_HINT_SUCCESS');
						const failMsg = isSingle
							? Loc.getMessage('SIGN_SIGNERS_SIGNER_GRID_DELETE_HINT_FAIL')
							: Loc.getMessage('SIGN_SIGNERS_SIGNERS_GRID_DELETE_HINT_FAIL');

						try
						{
							await this.#api.signersList.deleteSignersFromList(listId, userIds);
							window.top.BX.UI.Notification.Center.notify({
								content: successMsg,
							});
						}
						catch
						{
							window.top.BX.UI.Notification.Center.notify({
								content: failMsg,
							});
						}
						await this.reloadSigners();
						button.getContext().close();
					},
				}),
				new BX.UI.Button({
					text: Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_POPUP_NO'),
					color: BX.UI.Button.Color.LINK,
					onclick: (button) => button.getContext().close(),
				}),
			],
		});
	}

	async createList(): void
	{
		try
		{
			const createListPopup = new CreateListPopup();
			const title = await createListPopup.show();
			await this.#api.signersList.createList(title);
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_SIGNERS_GRID_LIST_CREATE_SUCCESS'),
			});
		}
		catch
		{
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_SIGNERS_GRID_LIST_CREATE_FAIL'),
			});
		}

		await this.reloadLists();
	}

	async renameList(listId: number, title: string): void
	{
		try
		{
			const createListPopup = new CreateListPopup();
			const newTitle = await createListPopup.show(title);
			await this.#api.signersList.renameList(listId, newTitle);
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_SIGNERS_GRID_LIST_CREATE_SUCCESS'),
			});
		}
		catch
		{
			window.top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('SIGN_SIGNERS_GRID_LIST_CREATE_FAIL'),
			});
		}

		await this.reloadLists();
	}

	async addSigners(listId: number, entities: Array, excludeRejected: boolean = true): void
	{
		const members = entities.map((entity) => ({ ...entity, party: 2 }));
		await this.#api.signersList.addSignersToList(listId, members, excludeRejected);
		BX.SidePanel.Instance.close();
		await this.reloadSigners();
	}

	async handleAddSignersButtonClick(listId: number, userParty: UserParty): Promise<void>
	{
		if (userParty.validate())
		{
			const listGrid = new BX.Sign.V2.Grid.B2e.Signers();
			await listGrid.addSigners(listId, userParty.getEntities(), userParty.isRejectExcludedEnabled());
		}
	}

	#getGridManager(): ?Object
	{
		if (BX.Main.gridManager)
		{
			return BX.Main.gridManager;
		}

		const previousSlider = BX.SidePanel.Instance.getPreviousSlider(BX.SidePanel.Instance.getSliderByWindow(window));
		const gridWindow = previousSlider ? previousSlider.getWindow() : window.top;

		return gridWindow?.BX.Main.gridManager;
	}
}
