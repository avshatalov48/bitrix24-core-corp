import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { Loc } from 'main.core';

export type BaseActionType = {
	grid: ?BX.Main.grid,
	filter: Array,
	selectedUsers: ?Array,
	showPopups: ?boolean,
	isCloud: ?boolean,
};

/**
 * @abstract
 */
export class BaseAction
{
	/**
	 * @abstract
	 */
	static getActionId(): string
	{
		throw new Error('not implemented');
	}

	/**
	 * @abstract
	 */
	getAjaxMethod(): string
	{
		throw new Error('not implemented');
	}

	constructor(params: BaseActionType)
	{
		this.grid = params.grid;
		this.userFilter = params.filter;
		this.selectedUsers = params.selectedUsers;
		this.showPopups = params.showPopups ?? true;
		this.isCloud = params.isCloud;
	}

	execute(): void
	{
		const confirmationPopup = this.showPopups
			? this.getConfirmationPopup()
			: null;

		if (confirmationPopup)
		{
			confirmationPopup.setOkCallback(() => {
				this.sendActionRequest();
				confirmationPopup.close();
			});
			confirmationPopup.show();
		}
		else
		{
			this.sendActionRequest();
		}
	}

	getConfirmationPopup(): ?MessageBox
	{
		return null;
	}

	sendActionRequest(): void
	{
		this.grid.tableFade();
		const selectedRows = this.selectedUsers ?? this.grid.getRows().getSelectedIds();
		const isSelectedAllRows = this.grid.getRows().isAllSelected() ? 'Y' : 'N';

		BX.ajax.runAction(this.getAjaxMethod(), {
			data: {
				fields: {
					userIds: selectedRows,
					isSelectedAllRows,
					filter: this.userFilter,
				},
			},
		})
			.then((result) => this.handleSuccess(result))
			.catch((result) => this.handleError(result));
	}

	handleSuccess(result: Result): void
	{
		this.grid.reload();

		if (this.showPopups)
		{
			const { skippedActiveUsers, skippedFiredUsers } = result.data;

			if (skippedActiveUsers && Object.keys(skippedActiveUsers).length > 0)
			{
				this.showActiveUsersPopup(skippedActiveUsers);
			}
			else if (skippedFiredUsers && Object.keys(skippedFiredUsers).length > 0)
			{
				this.showFiredUsersPopup(skippedFiredUsers);
			}
		}
	}

	handleError(result: Result): void
	{
		this.grid.tableUnfade();
		this.unselectRows(this.grid);
		console.error(result);

		if (this.showPopups && result.errors && result.errors.length > 0)
		{
			const errorMessage = result.errors.map((item) => {
				return item.message;
			}).join(', ');
			MessageBox.show({
				message: errorMessage,
				buttons: MessageBoxButtons.YES,
				yesCaption: Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
				onYes: (messageBox) => {
					messageBox.close();
				},
			});
		}
	}

	showActiveUsersPopup(activeUsers: Array): void
	{
		MessageBox.show({
			title: Loc.getMessage(this.getSkippedUsersTitleCode()),
			message: this.getMessageWithProfileNames(this.getSkippedUsersMessageCode(), activeUsers),
			buttons: MessageBoxButtons.YES,
			yesCaption: Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
			onYes: (messageBox) => {
				messageBox.close();
			},
		});
	}

	showFiredUsersPopup(firedUsers: Array): void
	{
		MessageBox.show({
			title: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_TITLE'),
			message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_MESSAGE', firedUsers),
			buttons: MessageBoxButtons.YES,
			yesCaption: Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
			onYes: (messageBox) => {
				messageBox.close();
			},
		});
	}

	getMessageWithProfileNames(messageCode: string, users: Array): string
	{
		const maxDisplayCount = 5;
		const userValues = Object.values(users);
		const displayedNames = userValues.slice(0, maxDisplayCount).map((user) => user.fullName);
		const remainingCount = userValues.length - maxDisplayCount;
		const namesString = displayedNames.join(', ');

		if (displayedNames.length < 2 && remainingCount < 1)
		{
			return Loc.getMessage(`${messageCode}_SINGLE`, {
				'#USER#': namesString,
			});
		}

		if (remainingCount > 0)
		{
			return Loc.getMessage(`${messageCode}_REMAINING`, {
				'#USER_LIST#': namesString,
				'#USER_REMAINING#': remainingCount,
			});
		}

		return Loc.getMessage(messageCode, {
			'#USER_LIST#': namesString,
		});
	}

	getSkippedUsersTitleCode(): string
	{
		return '';
	}

	getSkippedUsersMessageCode(): string
	{
		return '';
	}

	unselectRows(grid: BX.Main.grid): void
	{
		grid.getRows().unselectAll();
		grid.updateCounterDisplayed();
		grid.updateCounterSelected();
		grid.disableActionsPanel();
		BX.onCustomEvent(window, 'Grid::allRowsUnselected', []);
	}
}
