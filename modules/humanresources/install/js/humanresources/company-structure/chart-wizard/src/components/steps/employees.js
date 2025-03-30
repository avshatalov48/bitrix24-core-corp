import { memberRoles } from 'humanresources.company-structure.api';
import { ChangeSaveModeControl } from '../change-save-mode-control/change-save-mode-control';
import { TagSelector, type ItemOptions } from 'ui.entity-selector';
import { getUserDataBySelectorItem } from 'humanresources.company-structure.utils';

export const Employees = {
	name: 'employees',

	components: { ChangeSaveModeControl },

	emits: ['applyData', 'saveModeChanged'],

	props: {
		heads: {
			type: Array,
			required: true,
		},
		employeesIds: {
			type: Array,
			required: true,
		},
		isEditMode: {
			type: Boolean,
			required: true,
		},
	},

	created(): void
	{
		this.selectedUsers = new Set();
		this.departmentHeads = [];
		this.departmentEmployees = [];
		this.removedUsers = [];
		this.headSelector = this.getUserSelector(memberRoles.head);
		this.deputySelector = this.getUserSelector(memberRoles.deputyHead);
		this.employeesSelector = this.getUserSelector(memberRoles.employee);
		this.userCount = 0;
	},

	mounted(): void
	{
		this.headSelector.renderTo(this.$refs['head-selector']);
		this.deputySelector.renderTo(this.$refs['deputy-selector']);
		this.employeesSelector.renderTo(this.$refs['employees-selector']);
	},
	watch:
	{
		employeesIds:
		{
			handler(payload: number[]): void
			{
				const preselectedEmployees = payload.map((employeeId) => ['user', employeeId]);
				const { dialog } = this.employeesSelector;
				dialog.setPreselectedItems(preselectedEmployees);
				dialog.load();
			},
		},
	},

	methods:
	{
		getPreselectedItems(role: string): Array<number>
		{
			if (memberRoles.employee === role)
			{
				return this.employeesIds.map((employeeId) => ['user', employeeId]);
			}

			return this.heads.filter((head) => head.role === role).map((head) => {
				return ['user', head.id];
			});
		},
		getUserSelector(role: String): TagSelector
		{
			const selector = new TagSelector({
				events: {
					onTagAdd: (event: BaseEvent) => {
						const { tag } = event.getData();
						this.selectedUsers.add(tag.id);
						this.onSelectorToggle(tag, role);
						this.applyData();
					},
					onTagRemove: (event: BaseEvent) => {
						const { tag } = event.getData();
						this.selectedUsers.delete(tag.id);
						this.onSelectorToggle(tag, role);
						this.applyData();
					},
				},
				multiple: true,
				dialogOptions: {
					preselectedItems: this.getPreselectedItems(role),
					popupOptions: {
						events: {
							onBeforeShow: () => {
								dialog.setHeight(250);
								if (dialog.isLoaded())
								{
									this.toggleUsers(dialog);
								}
							},
						},
					},
					events: {
						onShow: () => {
							const { dialog } = selector;
							const container = dialog.getContainer();
							const { top } = container.getBoundingClientRect();
							const offset = top + container.offsetHeight - document.body.offsetHeight;
							if (offset > 0)
							{
								const margin = 5;
								dialog.setHeight(container.offsetHeight - offset - margin);
							}
						},
						onLoad: (event) => {
							this.toggleUsers(dialog);
							const users = event.target.items.get('user');

							users.forEach((user) => {
								user.setLink('');
							});
						},
						'SearchTab:onLoad': () => {
							this.toggleUsers(dialog);
						},
					},
					height: 250,
					width: 380,
					entities: [
						{
							id: 'user',
							options: {
								intranetUsersOnly: true,
								inviteEmployeeLink: true,
							},
						},
					],
					dropdownMode: true,
					hideOnDeselect: false,
				},
			});
			const dialog = selector.getDialog();

			return selector;
		},
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		toggleUsers(dialog): void
		{
			const items = dialog.getItems();
			items.forEach((item) => {
				const hidden = this.selectedUsers.has(item.id)
					&& !dialog.selectedItems.has(item);
				item.setHidden(hidden);
			});
		},
		onSelectorToggle(tag: ItemOptions, role: string): void
		{
			const item = tag.selector.dialog.getItem(['user', tag.id]);
			const userData = getUserDataBySelectorItem(item, role);

			const isEmployee = role === memberRoles.employee;
			if (!tag.rendered)
			{
				this.removedUsers = this.removedUsers.filter((user) => user.id !== userData.id);
				if (isEmployee)
				{
					this.departmentEmployees = [...this.departmentEmployees, { ...userData }];
				}
				else
				{
					this.departmentHeads = [...this.departmentHeads, { ...userData }];
				}

				this.userCount += 1;

				return;
			}

			const { preselectedItems = [] } = tag.selector.dialog;
			const parsedPreselected = preselectedItems.flat().filter((item) => item !== 'user');
			if (parsedPreselected.includes(userData.id))
			{
				this.removedUsers = [...this.removedUsers, { ...userData, role }];
			}

			if (isEmployee)
			{
				this.departmentEmployees = this.departmentEmployees.filter((employee) => employee.id !== tag.id);
			}
			else
			{
				this.departmentHeads = this.departmentHeads.filter((head) => head.id !== tag.id);
			}

			this.userCount -= 1;
		},
		applyData(): void
		{
			this.$emit('applyData', {
				heads: this.departmentHeads,
				employees: this.departmentEmployees,
				removedUsers: this.removedUsers,
				userCount: this.userCount,
			});
		},
		handleSaveModeChangedChanged(actionId: string): void
		{
			this.$emit('saveModeChanged', actionId);
		},
	},

	template: `
		<div class="chart-wizard__employee">
			<div class="chart-wizard__form">
				<div class="chart-wizard__employee_item">
					<span class="chart-wizard__employee_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_TITLE')}}
					</span>
					<div
						class="chart-wizard__employee_selector"
						ref="head-selector"
						data-test-id="hr-company-structure_chart-wizard__employees-head-selector"
					/>
					<span class="chart-wizard__employee_item-description">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_HEAD_DESCR')}}
					</span>
				</div>
				<div class="chart-wizard__employee_item">
					<span class="chart-wizard__employee_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_TITLE')}}
					</span>
					<div
						class="chart-wizard__employee_selector"
						ref="deputy-selector"
						data-test-id="hr-company-structure_chart-wizard__employees-deputy-selector"
					/>
					<span class="chart-wizard__employee_item-description">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_DEPUTY_DESCR')}}
					</span>
				</div>
				<div class="chart-wizard__employee_item">
					<span class="chart-wizard__employee_item-label">
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEE_EMPLOYEES_TITLE')}}
					</span>
					<div
						class="chart-wizard__employee_selector"
						ref="employees-selector"
						data-test-id="hr-company-structure_chart-wizard__employees-employee-selector"
					/>
				</div>
				<div class="chart-wizard__employee_item --change-save-mode-control">
					<ChangeSaveModeControl
						v-if="!isEditMode"
						@saveModeChanged="handleSaveModeChangedChanged"
					></ChangeSaveModeControl>
					<div class="chart-wizard__change-save-mode-control-container" v-else>
						{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_EDIT_WIZARD_EMPLOYEE_SAVE_MODE_TEXT')}}
					</div>
				</div>
			</div>
		</div>
	`,
};
