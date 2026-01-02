import { Type, Loc, ajax } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { ErrorCollection } from 'ui.form-elements.field';

export type SetSortType = {
	menuId: ?string,
	gridId: string,
	sortBy: string,
	order: 'ASC' | 'DESC',
}

export type SetFilterType = {
	gridId: string,
	filter: Object,
}

export class GridManager
{
	static instances: Array<GridManager> = [];
	#grid: BX.Main.grid;

	constructor(gridId: string)
	{
		this.#grid = BX.Main.gridManager.getById(gridId)?.instance;
	}

	static getInstance(gridId: string): GridManager
	{
		if (!this.instances[gridId])
		{
			this.instances[gridId] = new GridManager(gridId);
		}

		return this.instances[gridId];
	}

	static setSort(options: SetSortType): void
	{
		const grid = BX.Main.gridManager.getById(options.gridId)?.instance;

		if (Type.isObject(grid))
		{
			grid.tableFade();
			grid.getUserOptions().setSort(options.sortBy, options.order, () => {
				grid.reload();
			});
		}
	}

	static setFilter(options: SetFilterType): void
	{
		const grid = BX.Main.gridManager.getById(options.gridId)?.instance;
		const filter = BX.Main.filterManager.getById(options.gridId);

		if (Type.isObject(grid) && Type.isObject(filter))
		{
			filter.getApi().extendFilter(options.filter);
		}
	}

	static reinviteCloudAction(data): Promise
	{
		return ajax.runAction('intranet.invite.reinviteWithChangeContact', {
			data,
		}).then((response) => {
			if (response.data.result)
			{
				const InviteAccessPopup = new BX.PopupWindow({
					content: `<p>${Loc.getMessage('INTRANET_USER_LIST_ACTION_REINVITE_SUCCESS')}</p>`,
					autoHide: true,
				});

				InviteAccessPopup.show();
			}

			return response;
		}, (response) => {
			const errors = response.errors.map((error) => error.message);
			ErrorCollection.showSystemError(errors.join('<br>'));

			return response;
		});
	}

	static reinviteAction(userId, isExtranetUser): Promise
	{
		return ajax.runAction('intranet.controller.invite.reinvite', {
			data: {
				params: {
					userId,
					extranet: (isExtranetUser ? 'Y' : 'N'),
				},
			},
		}).then((response) => {
			if (response.data.result)
			{
				const InviteAccessPopup = new BX.PopupWindow({
					content: `<p>${Loc.getMessage('INTRANET_USER_LIST_ACTION_REINVITE_SUCCESS')}</p>`,
					autoHide: true,
				});

				InviteAccessPopup.show();
			}

			return response;
		});
	}

	getGrid(): BX.Main.grid
	{
		return this.#grid;
	}

	confirmAction(params: {
		userId: number,
		isAccept: boolean,
	}): void
	{
		if (params.userId)
		{
			this.confirmUser(params.isAccept ? 'confirm' : 'decline', () => {
				const row = this.#grid.getRows().getById(params.userId);
				row?.stateLoad();
				ajax.runAction('intranet.controller.invite.confirmUserRequest', {
					data: {
						userId: params.userId,
						isAccept: params.isAccept ? 'Y' : 'N',
					},
				}).then((response) => {
					if (response.data === true)
					{
						row?.update();
					}
					else if (params.isAccept)
					{
						row?.stateUnload();
					}
					else
					{
						this.activityAction({
							userId: params.userId,
							action: 'deleteOrFire',
						});
					}
				}).catch(() => {
					if (params.isAccept)
					{
						row?.stateUnload();
					}
					else
					{
						this.activityAction({
							userId: params.userId,
							action: 'deleteOrFire',
						});
					}
				});
			});
		}
	}

	activityAction(params: {
		userId: number,
		action: string,
	}): void
	{
		const userId = params.userId ?? null;
		const action = params.action ?? null;

		if (userId)
		{
			this.confirmUser(action, () => {
				const row = this.#grid.getRows().getById(params.userId);
				row?.stateLoad();

				if (['fire', 'restore', 'deleteOrFire'].includes(action))
				{
					ajax.runAction(`intranet.v2.User.${action}`, {
						data: {
							userId,
						},
					}).then(() => {
						row?.update();
					}).catch((response) => {
						row?.stateUnload();
						const errors = response.errors.map((error) => error.message);
						ErrorCollection.showSystemError(errors.join('<br>'));
					});
				}
			});
		}
	}

	confirmUser(action: ?string, callBack: function)
	{
		MessageBox.show({
			title: this.getConfirmTitle(action) ?? '',
			message: this.getConfirmMessage(action) ?? '',
			buttons: MessageBoxButtons.YES_CANCEL,
			yesCaption: this.getConfirmButtonText(action),
			onYes: (messageBox) => {
				callBack();
				messageBox.close();
			},
		});
	}

	getConfirmTitle(action: ?string): ?string
	{
		switch (action)
		{
			case 'restore':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_RESTORE_TITLE');
			case 'confirm':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_CONFIRM_TITLE');
			case 'delete':
			case 'deleteOrFire':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DELETE_TITLE');
			case 'fire':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_TITLE');
			case 'deactivateInvited':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_TITLE');
			case 'decline':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DECLINE_TITLE');
			default:
				return '';
		}
	}

	getConfirmMessage(action: ?string): ?string
	{
		switch (action)
		{
			case 'restore':
			case 'confirm':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_CONFIRM_MESSAGE');
			case 'delete':
			case 'deleteOrFire':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DELETE_MESSAGE');
			case 'fire':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_MESSAGE');
			case 'deactivateInvited':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_MESSAGE');
			case 'decline':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DECLINE_MESSAGE');
			default:
				return '';
		}
	}

	getConfirmButtonText(action: ?string): ?string
	{
		switch (action)
		{
			case 'restore':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_RESTORE_BUTTON');
			case 'confirm':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_CONFIRM_BUTTON');
			case 'delete':
			case 'deleteOrFire':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DELETE_BUTTON');
			case 'fire':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_BUTTON');
			case 'deactivateInvited':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_BUTTON');
			case 'decline':
				return Loc.getMessage('INTRANET_USER_LIST_ACTION_DECLINE_BUTTON');
			default:
				return null;
		}
	}
}
