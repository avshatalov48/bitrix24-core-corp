import { BaseAction } from './base-action';
import { Dialog } from 'ui.entity-selector';
import { Tag } from 'main.core';

export class ChangeDepartmentAction extends BaseAction
{
	static getActionId(): string
	{
		return 'changeDepartment';
	}

	getAjaxMethod(): string
	{
		return 'intranet.controller.user.userlist.groupChangeDepartment';
	}

	execute(): void
	{
		const saveButton = new BX.UI.SaveButton({
			onclick: () => {
				const selectedIds = dialog.getSelectedItems().map((item) => item.id);
				dialog.hide();

				if (selectedIds.length > 0)
				{
					this.sendChangeDepartmentRequest(selectedIds);
				}
				else
				{
					this.unselectRows(this.grid);
				}
			},
			size: BX.UI.Button.Size.SMALL,
		});
		const cancelButton = new BX.UI.CancelButton({
			onclick: () => {
				dialog.hide();
			},
			size: BX.UI.Button.Size.SMALL,
		});
		const footer = Tag.render`<span></span>`;
		saveButton.renderTo(footer);
		cancelButton.renderTo(footer);

		const dialog = new Dialog({
			dropdownMode: true,
			enableSearch: true,
			compactView: true,
			multiple: true,
			footer,
			entities: [
				{
					id: 'department',
					options: {
						selectMode: 'departmentsOnly',
						allowSelectRootDepartment: true,
					},
				},
			],
		});

		dialog.show();
	}

	sendChangeDepartmentRequest(departmentIds: Array): void
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
					departmentIds,
				},
			},
		})
			.then((result) => this.handleSuccess(result))
			.catch((result) => this.handleError(result));
	}

	getSkippedUsersTitleCode(): string
	{
		return 'INTRANET_USER_LIST_GROUP_ACTION_EXTRANET_CHANGE_DEPARTMENT_TITLE';
	}

	getSkippedUsersMessageCode(): string
	{
		return this.isCloud
			? 'INTRANET_USER_LIST_GROUP_ACTION_EXTRANET_CHANGE_DEPARTMENT_MESSAGE_CLOUD'
			: 'INTRANET_USER_LIST_GROUP_ACTION_EXTRANET_CHANGE_DEPARTMENT_MESSAGE';
	}
}
