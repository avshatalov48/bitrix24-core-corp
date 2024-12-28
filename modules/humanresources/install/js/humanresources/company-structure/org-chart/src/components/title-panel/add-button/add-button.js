import { RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { Main, CRM } from 'ui.icon-set.api.core';
import { getColorCode } from 'humanresources.company-structure.utils';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import 'ui.icon-set.main';
import 'ui.icon-set.crm';

const MenuOption = Object.freeze({
	addDepartment: 'add-department',
	addEmployee: 'add-employee',
});

export const AddButton = {
	name: 'AddButton',

	emits: ['addDepartment'],

	data(): Object
	{
		return {
			actionMenu: {
				visible: false,
			},
		};
	},
	components: {
		RouteActionMenu,
	},

	mounted(): void
	{
		const permissionChecker = PermissionChecker.getInstance();

		if (!permissionChecker)
		{
			return;
		}
		this.dropdownItems = [
			{
				id: MenuOption.addDepartment,
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON_MENU_ADD_DEPARTMENT_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON_MENU_ADD_DEPARTMENT_DESCR'),
				bIcon: {
					name: Main.CUBE_PLUS,
					size: 20,
					color: getColorCode('paletteBlue50'),
				},
				permission: {
					action: PermissionActions.departmentCreate,
				},
			},
			{
				id: MenuOption.addEmployee,
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON_MENU_ADD_EMPLOYEE_TITLE'),
				description: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON_MENU_ADD_EMPLOYEE_DESCR'),
				bIcon: {
					name: CRM.PERSON_PLUS_2,
					size: 20,
					color: getColorCode('paletteBlue50'),
				},
				permission: {
					action: PermissionActions.employeeAddToDepartment,
				},
			},
		];

		this.dropdownItems = this.dropdownItems.filter((item) => {
			if (!item.permission)
			{
				return false;
			}

			return permissionChecker.hasPermissionOfAction(item.permission.action);
		});
	},

	computed: {
		MenuOption(): typeof MenuOption
		{
			return MenuOption;
		},
	},
	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		addDepartment(): void
		{
			this.$emit('addDepartment');
		},
		actionMenuItemClickHandler(actionId: string): void
		{
			if (actionId === MenuOption.addDepartment)
			{
				this.$emit('addDepartment');
			}
		},
	},
	template: `
		<div class="ui-btn ui-btn-success ui-btn-round ui-btn-sm" @click="addDepartment">
			{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_ADD_BUTTON') }}
		</div>
	`,
};
