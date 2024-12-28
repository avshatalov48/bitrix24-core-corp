import { ajax, Dom, Event, Tag, Loc, Text } from 'main.core';
import { BaseFooter } from 'ui.entity-selector';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { memberRoles } from 'humanresources.company-structure.api';
import { type Tab, type ItemOptions, type FooterOptions } from 'ui.entity-selector';
import { getUserStoreItemByDialogItem } from 'humanresources.company-structure.utils';
import { UI } from 'ui.notification';

const employeeType = memberRoles.employee;
const disabledButtonClass = 'ui-btn-disabled';

export class AddDialogFooter extends BaseFooter
{
	constructor(tab: Tab, options: FooterOptions)
	{
		super(tab, options);

		this.role = this.getOption('role');

		const selectedItems = this.getDialog().getSelectedItems();
		this.userCount = selectedItems.length;
		this.users = [];
		selectedItems.forEach((item) => {
			this.#onUserToggle(item);
		});
		this.getDialog().subscribe('Item:onSelect', this.#handleOnTagAdd.bind(this));
		this.getDialog().subscribe('Item:onDeselect', this.#handleOnTagRemove.bind(this));
	}

	render(): HTMLElement
	{
		const { footer, footerAddButton, footerCloseButton } = Tag.render`
			<div ref="footer" class="hr-add-employee-to-department-dialog__footer">
				<button ref="footerAddButton" class="ui-btn ui-btn ui-btn-sm ui-btn-primary ${this.users.length === 0 ? disabledButtonClass : ''} ui-btn-round hr-add-employee-dialog-btn-width">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_ADD_BUTTON')}
				</button>
				<button ref="footerCloseButton" class="ui-btn ui-btn ui-btn-sm ui-btn-light-border ui-btn-round hr-add-employee-dialog-btn-width">
					${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_CANCEL_BUTTON')}
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
				this.#addEmployees(userIds);
			}
		});

		return footer;
	}

	async #addEmployees(userIds: Array): void
	{
		if (!this.userCount)
		{
			return;
		}

		if (this.isAdding)
		{
			return;
		}

		this.isAdding = true;
		const store = useChartStore();
		const nodeId = this.getOption('nodeId') ?? store.focusedNode;
		const { data, errors } = await ajax.runAction(
			'humanresources.api.Structure.Node.Member.addUserMember',
			{
				data: {
					nodeId,
					userIds,
					roleXmlId: this.role,
				},
			},
		);

		const nodeStorage = store.departments.get(nodeId);
		if (!nodeStorage || errors.length > 0)
		{
			this.#destroyDialog();

			return;
		}

		const newMemberUserIds = new Set(this.users.map((user) => user.id));

		let heads = nodeStorage.heads ?? [];
		heads = heads.filter((user) => !newMemberUserIds.has(user.id));

		const employees = (nodeStorage.employees ?? []).filter((user) => !newMemberUserIds.has(user.id));
		(this.role === employeeType ? employees : heads).push(...this.users);

		nodeStorage.heads = heads;
		nodeStorage.employees = employees;
		const newUserCount = data.userCount ?? 0;
		const countDiff = newUserCount - nodeStorage.userCount;
		nodeStorage.userCount = newUserCount;

		if (
			countDiff > 1
			|| this.users.length > 1
		)
		{
			UI.Notification.Center.notify({
				content: Text.encode(Loc.getMessage(
					'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_EMPLOYEES_ADD',
					{
						'#DEPARTMENT#': nodeStorage.name,
					},
				)),
				autoHideDelay: 2000,
			});
		}

		if (
			countDiff === 1
			&& this.users.length === 1
		)
		{
			UI.Notification.Center.notify({
				content: Text.encode(Loc.getMessage(
					'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_ADD_DIALOG_EMPLOYEE_ADD',
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
