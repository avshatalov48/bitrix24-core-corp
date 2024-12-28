import { useChartStore } from 'humanresources.company-structure.chart-store';
import { getUserStoreItemByDialogItem } from 'humanresources.company-structure.utils';
import { ajax, Dom, Event, Loc, Tag, Text } from 'main.core';
import { memberRoles } from 'humanresources.company-structure.api';
import { refreshDepartments } from 'humanresources.company-structure.utils';
import { BaseFooter, type FooterOptions, type ItemOptions, type Tab } from 'ui.entity-selector';
import { UI } from 'ui.notification';

const disabledButtonClass = 'ui-btn-disabled';

export class MoveFromDialogFooter extends BaseFooter
{
	#nodeId: number;

	constructor(tab: Tab, options: FooterOptions)
	{
		super(tab, options);

		const selectedItems = this.getDialog().getSelectedItems();
		this.userCount = selectedItems.length;
		this.users = [];
		selectedItems.forEach((item) => {
			this.#onUserToggle(item);
		});

		this.#nodeId = this.getOption('nodeId');

		this.getDialog().subscribe('Item:onSelect', this.#handleOnTagAdd.bind(this));
		this.getDialog().subscribe('Item:onDeselect', this.#handleOnTagRemove.bind(this));
	}

	render(): HTMLElement
	{
		const { footer, footerAddButton, footerCloseButton } = Tag.render`
			<div ref="footer" class="hr-move-user-from-dialog__footer">
				<button ref="footerAddButton" class="ui-btn ui-btn ui-btn-sm ui-btn-primary ${this.users.length === 0 ? disabledButtonClass : ''} ui-btn-round hr-move-user-from-dialog-btn-width">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_ADD')}
				</button>
				<button ref="footerCloseButton" class="ui-btn ui-btn ui-btn-sm ui-btn-light-border ui-btn-round hr-move-user-from-dialog-btn-width">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_REMOVE')}
				</button>
			</div>
		`;

		this.footerAddButton = footerAddButton;
		Event.bind(footerCloseButton, 'click', (event) => {
			this.dialog.hide();
		});

		Event.bind(footerAddButton, 'click', (event) => {
			const users = this.dialog.getSelectedItems();
			const userIds = users.map((item: Item): void => item.getId());

			if (userIds.length > 0)
			{
				Dom.addClass(footerAddButton, 'ui-btn-wait');
				this.#moveEmployees(userIds);
			}
		});

		return footer;
	}

	async #moveEmployees(userIds: Array): void
	{
		if (!this.userCount)
		{
			return;
		}

		if (this.isMoving)
		{
			return;
		}

		this.isMoving = true;
		const departmentUserIds = { [memberRoles.employee]: userIds };

		const { data } = await ajax.runAction(
			'humanresources.api.Structure.Node.Member.moveUserListToDepartment',
			{
				data: {
					nodeId: this.#nodeId,
					userIds: departmentUserIds,
				},
			},
		);

		const store = useChartStore();
		const nodeStorage = store.departments.get(this.#nodeId);
		if (!nodeStorage)
		{
			this.#destroyDialog();

			return;
		}

		const newMemberUserIds = new Set(this.users.map((user) => user.id));

		const employees = (nodeStorage.employees ?? []).filter((user) => !newMemberUserIds.has(user.id));
		const headsUserIds = new Set(nodeStorage.heads.map((head) => head.id));
		this.users = this.users.filter((user) => !headsUserIds.has(user.id));
		employees.push(...this.users);
		nodeStorage.employees = employees;
		nodeStorage.userCount = data.userCount ?? 0;

		if (data.updatedDepartmentIds)
		{
			void refreshDepartments(data.updatedDepartmentIds);
		}

		if (this.users.length > 1)
		{
			UI.Notification.Center.notify({
				content: Text.encode(Loc.getMessage(
					'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_ADD_EMPLOYEES_MESSAGE',
					{
						'#DEPARTMENT#': nodeStorage.name,
					},
				)),
				autoHideDelay: 2000,
			});
			this.#destroyDialog();

			return;
		}

		if (this.users.length === 1)
		{
			UI.Notification.Center.notify({
				content: Text.encode(Loc.getMessage(
					'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_ADD_EMPLOYEE_MESSAGE',
					{
						'#DEPARTMENT#': nodeStorage.name,
					},
				)),
				autoHideDelay: 2000,
			});
		}

		this.#destroyDialog();
	}

	#destroyDialog(): void
	{
		this.isAdding = false;
		this.getDialog().destroy();
	}

	#handleOnTagAdd(event: BaseEvent): void
	{
		const { item } = event.getData();
		this.#onUserToggle(item);
	}

	#handleOnTagRemove(event: BaseEvent): void
	{
		const { item } = event.getData();
		this.#onUserToggle(item, false);
	}

	#onUserToggle(item: ItemOptions, isSelected: boolean = true): void
	{
		if (!isSelected)
		{
			this.users = this.users.filter((user) => user.id !== item.id);
			this.userCount -= 1;
			this.#toggleAddButton();

			return;
		}

		const userData = getUserStoreItemByDialogItem(item, this.role);
		this.users = [...this.users, userData];
		this.userCount += 1;
		this.#toggleAddButton();
	}

	#toggleAddButton(): void
	{
		if (this.userCount === 0)
		{
			Dom.addClass(this.footerAddButton, disabledButtonClass);

			return;
		}

		Dom.removeClass(this.footerAddButton, disabledButtonClass);
	}
}
