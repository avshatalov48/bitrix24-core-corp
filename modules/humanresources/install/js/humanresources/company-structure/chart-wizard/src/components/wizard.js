import { mapState } from 'ui.vue3.pinia';
import { Event } from 'main.core';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { memberRoles } from 'humanresources.company-structure.api';
import { TreePreview } from './tree-preview/tree-preview';
import { Department } from './steps/department';
import { Employees } from './steps/employees';
import { BindChat } from './steps/bind-chat';
import { Entities } from './steps/entities';
import { WizardAPI } from '../api';
import { chartWizardActions } from '../actions';
import { sendData as analyticsSendData } from 'ui.analytics';
import type { WizardData, Step, DepartmentData, DepartmentUserIds } from '../types';
import 'ui.buttons';
import 'ui.forms';
import '../style.css';

const SaveMode = Object.freeze({
	moveUsers: 'moveUsers',
	addUsers: 'addUsers',
});

export const ChartWizard = {
	name: 'chartWizard',

	emits: ['modifyTree', 'close'],

	components: { Department, Employees, BindChat, TreePreview, Entities },

	props: {
		nodeId: {
			type: Number,
			required: true,
		},
		isEditMode: {
			type: Boolean,
			required: true,
		},
		showEntitySelector: {
			type: Boolean,
			required: false,
		},
		entity: {
			type: String,
		},
		source: {
			type: String,
		},
	},

	data(): WizardData
	{
		return {
			stepIndex: 0,
			waiting: false,
			isValidStep: false,
			departmentData: {
				id: 0,
				parentId: 0,
				name: '',
				description: '',
				heads: [],
				employees: [],
				userCount: 0,
			},
			removedUsers: [],
			employeesIds: [],
			shouldErrorHighlight: false,
			visibleSteps: [],
			saveMode: SaveMode.moveUsers,
		};
	},

	created(): void
	{
		this.steps = [
			{
				id: 'entities',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_SELECT_ENTITY_TITLE'),
			},
			{
				id: 'department',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_TITLE'),
			},
			{
				id: 'employees',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEES_TITLE'),
			},
			{
				id: 'bindChat',
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TITLE'),
			},
		];
		this.init();
	},

	beforeUnmount(): void
	{
		Event.unbind(window, 'beforeunload', this.handleBeforeUnload);
	},

	computed: {
		stepTitle(): string
		{
			if (this.isFirstStep && !this.isEditMode)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE');
			}

			const currentStep = this.visibleSteps[0] === 'entities'
				? this.stepIndex
				: this.stepIndex + 1;

			return this.isEditMode ? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EDIT_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_STEP_PROGRESS', {
					'#CURRENT_STEP#': currentStep,
					'#MAX_STEP#': this.steps.length - 1,
				});
		},
		currentStep(): Step
		{
			const id = this.visibleSteps[this.stepIndex];

			return this.steps.find((step) => id === step.id);
		},
		componentInfo(): { name: string, params?: Object, hasData?: boolean }
		{
			const { parentId, name, description, heads } = this.departmentData;
			const components = {
				department: {
					name: 'Department',
					params: {
						parentId,
						name,
						description,
						shouldErrorHighlight: this.shouldErrorHighlight,
						isEditMode: this.isEditMode,
					},
					hasData: true,
				},
				employees: {
					name: 'Employees',
					params: {
						heads,
						employeesIds: this.employeesIds,
						isEditMode: this.isEditMode,
					},
					hasData: true,
				},
				bindChat: {
					name: 'BindChat',
				},
				entities: {
					name: 'Entities',
					hasData: true,
				},
			};

			const { id: stepId } = this.currentStep;

			return components[stepId];
		},
		isFirstStep(): boolean
		{
			return this.currentStep.id === 'entities';
		},
		filteredSteps(): string[]
		{
			return this.visibleSteps.filter((step) => step !== 'entities');
		},
		rootId(): number
		{
			const { id } = [...this.departments.values()].find((department) => {
				return department.parentId === 0;
			});

			return id;
		},
		...mapState(useChartStore, ['departments', 'userId', 'currentDepartments']),
	},

	methods: {
		handleBeforeUnload(event: Event): void
		{
			event.preventDefault();
		},
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		async init(): Promise<void>
		{
			Event.bind(window, 'beforeunload', this.handleBeforeUnload);
			this.createVisibleSteps();
			if (this.isEditMode)
			{
				const {
					id,
					name,
					description,
					parentId,
					heads,
					userCount,
					children,
					employees = [],
				} = this.departments.get(this.nodeId);
				this.departmentData = {
					...this.departmentData,
					id,
					parentId,
					name,
					description,
					heads,
					userCount,
					children,
					employees,
				};
				this.employeesIds = await WizardAPI.getEmployees(this.nodeId);

				return;
			}

			if (this.nodeId)
			{
				this.departmentData.parentId = this.nodeId;

				return;
			}

			this.departmentData.parentId = this.rootId;

			analyticsSendData({
				tool: 'structure',
				category: 'structure',
				event: 'create_wizard',
				c_element: this.source,
			});
		},
		createVisibleSteps(): void
		{
			switch (this.entity)
			{
				case 'department':
					this.visibleSteps = ['department'];
					break;
				case 'employees':
					this.visibleSteps = ['employees'];
					break;
				default:
					this.visibleSteps = this.showEntitySelector
						? this.steps.map((step) => step.id)
						: this.steps.filter((step) => step.id !== 'entities').map((step) => step.id);
					break;
			}
		},
		move(buttonId: string = 'next'): void
		{
			if (buttonId === 'next' && !this.isValidStep)
			{
				this.shouldErrorHighlight = true;

				return;
			}

			this.stepIndex = buttonId === 'back' ? this.stepIndex - 1 : this.stepIndex + 1;
			this.pickStepsAnalitics();
		},
		close(sendEvent: boolean = false): void
		{
			this.$emit('close');

			if (sendEvent)
			{
				analyticsSendData({
					tool: 'structure',
					category: 'structure',
					event: 'cancel_wizard',
					c_element: this.source,
				});
			}
		},
		onApplyData(data: { isValid?: boolean, removedUsers?: User, ...Partial<DepartmentData> }): void
		{
			const { isValid = true, removedUsers = [], ...departmentData } = data;
			this.isValidStep = isValid;
			if (departmentData)
			{
				this.departmentData = {
					...this.departmentData,
					...departmentData,
				};
			}

			this.removedUsers = removedUsers;
			if (isValid)
			{
				this.shouldErrorHighlight = false;
			}
		},
		getUsersPromise(departmentId: number): Promise<void>
		{
			const ids = this.calculateEmployeeIds();
			const { headsIds, deputiesIds, employeesIds } = ids;

			const departmentUserIds = {
				[memberRoles.head]: headsIds,
				[memberRoles.deputyHead]: deputiesIds,
				[memberRoles.employee]: employeesIds,
			};

			return this.getUserMemberPromise(departmentId, departmentUserIds);
		},
		calculateEmployeeIds(): Object
		{
			const { heads, employees = [] } = this.departmentData;

			return [...heads, ...employees].reduce((acc, user) => {
				const { headsIds, deputiesIds, employeesIds } = acc;
				if (user.role === memberRoles.head)
				{
					headsIds.push(user.id);
				}
				else if (user.role === memberRoles.deputyHead)
				{
					deputiesIds.push(user.id);
				}
				else
				{
					employeesIds.push(user.id);
				}

				return acc;
			}, {
				headsIds: [],
				deputiesIds: [],
				employeesIds: [],
			});
		},
		getUserMemberPromise(departmentId: number, ids: DepartmentUserIds, role: string): Promise<void>
		{
			if (this.isEditMode)
			{
				return WizardAPI.saveUsers(departmentId, ids);
			}

			const hasUsers = Object.values(ids).some((userIds) => userIds.length > 0);
			if (!hasUsers)
			{
				return Promise.resolve();
			}

			const parentId = this.departmentData.parentId ?? null;
			if (this.saveMode === SaveMode.moveUsers)
			{
				return WizardAPI.moveUsers(departmentId, ids, parentId);
			}

			return WizardAPI.saveUsers(departmentId, ids, parentId);
		},
		async create(): Promise<void>
		{
			const { name, parentId, description } = this.departmentData;
			let departmentId = 0;
			let accessCode = '';
			this.waiting = true;
			try
			{
				const [newDepartment] = await WizardAPI.addDepartment(name, parentId, description);
				departmentId = newDepartment.id;
				accessCode = newDepartment.accessCode;

				const data = await this.getUsersPromise(departmentId);
				if (data?.updatedDepartmentIds)
				{
					chartWizardActions.refreshDepartments(data.updatedDepartmentIds);
				}
				else
				{
					chartWizardActions.tryToAddCurrentDepartment(this.departmentData, departmentId);
				}
			}
			finally
			{
				this.waiting = false;
			}

			chartWizardActions.createDepartment({ ...this.departmentData, id: departmentId, accessCode });
			this.$emit('modifyTree', { id: departmentId, parentId, showConfetti: true });

			const { headsIds, deputiesIds, employeesIds } = this.calculateEmployeeIds();

			analyticsSendData(
				{
					tool: 'structure',
					category: 'structure',
					event: 'create_dept',
					c_element: this.source,
					p2: `headAmount_${headsIds.length}`,
					p3: `secondHeadAmount_${deputiesIds.length}`,
					p4: `employeeAmount_${employeesIds.length}`,
				},
			);
			this.close();
		},
		async save(): Promise<void>
		{
			if (!this.isValidStep)
			{
				this.shouldErrorHighlight = true;

				return;
			}

			const { id, parentId, name, description } = this.departmentData;
			const currentNode = this.departments.get(id);
			const targetNodeId = currentNode?.parentId === parentId ? null : parentId;
			this.waiting = true;
			const usersPromise = this.entity === 'employees'
				? this.getUsersPromise(id)
				: Promise.resolve();
			const departmentPromise = this.entity === 'department'
				? WizardAPI.updateDepartment(id, targetNodeId, name, description)
				: Promise.resolve();

			this.pickEditAnalitics(id, parentId);
			try
			{
				const [usersResponse] = await Promise.all([usersPromise, departmentPromise]);
				let userMovedToRootIds = [];
				if (this.removedUsers.length > 0)
				{
					userMovedToRootIds = usersResponse?.userMovedToRootIds ?? [];
					if (userMovedToRootIds.length > 0)
					{
						chartWizardActions.moveUsersToRootDepartment(this.removedUsers, userMovedToRootIds);
					}
				}

				const store = useChartStore();
				if (userMovedToRootIds.includes(this.userId))
				{
					store.changeCurrentDepartment(id, this.rootId);
				}
				else if (this.removedUsers.some((user) => user.id === this.userId))
				{
					store.changeCurrentDepartment(id);
				}
				else
				{
					chartWizardActions.tryToAddCurrentDepartment(this.departmentData, id);
				}

				chartWizardActions.editDepartment(this.departmentData);
			}
			catch (e)
			{
				console.error(e);

				return;
			}
			finally
			{
				this.waiting = false;
			}

			this.$emit('modifyTree', { id, parentId });
			this.close();
		},
		handleSaveModeChanged(actionId: string): void
		{
			this.saveMode = actionId;
		},
		pickEditAnalitics(departmentId: number, parentId: number): void
		{
			const currentNode = this.departments.get(departmentId);
			switch (this.entity)
			{
				case 'department':
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: 'edit_dept_name',
						c_element: this.source,
						p1: currentNode?.parentId === parentId ? 'editHead_N' : 'editHeadDept_Y',
						p2: currentNode?.name === name ? 'editName_N' : 'editName_Y',
					});
					break;
				case 'employees':
				{
					const { headsIds, deputiesIds, employeesIds } = this.calculateEmployeeIds();
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: 'edit_dept_employee',
						c_element: this.source,
						p2: `headAmount_${headsIds.length}`,
						p3: `secondHeadAmount_${deputiesIds.length}`,
						p4: `employeeAmount_${employeesIds.length}`,
					});
					break;
				}
				default:
					break;
			}
		},
		pickStepsAnalitics(): void
		{
			switch (this.currentStep.id)
			{
				case 'department':
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: 'create_dept_step1',
						c_element: this.source,
					});
					break;
				case 'employees':
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: 'create_dept_step2',
						c_element: this.source,
					});
					break;
				case 'bindChat':
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: 'create_dept_step3',
						c_element: this.source,
					});
					break;
				default:
					break;
			}
		},
	},

	template: `
		<div class="chart-wizard">
			<div class="chart-wizard__dialog" :style="{ 'max-width': !isEditMode && isFirstStep ? '643px' : '883px' }">
				<div class="chart-wizard__head">
					<div class="chart-wizard__head_close" @click="close(true)"></div>
					<p class="chart-wizard__head_title">{{ stepTitle }}</p>
					<p class="chart-wizard__head_descr">{{ currentStep.title }}</p>
					<div class="chart-wizard__head_stages" v-if="!isFirstStep && !isEditMode">
						<div
							v-for="n in filteredSteps.length"
							class="chart-wizard__head_stage"
							:class="{ '--completed': stepIndex >= (this.showEntitySelector ? n : n - 1) }"
						></div>
					</div>
				</div>
				<div class="chart-wizard__content" :style="{ display: !isEditMode && isFirstStep ? 'block' : 'flex' }">
					<KeepAlive>
						<component
							:is="componentInfo.name"
							v-bind="componentInfo.params"
							v-on="{
								applyData: componentInfo.hasData ? onApplyData : undefined,
								saveModeChanged: componentInfo.name === 'Employees' ? handleSaveModeChanged : undefined
							}"
						>
						</component>
					</KeepAlive>
					<TreePreview
						v-if="isEditMode || !isFirstStep"
						:parentId="departmentData.parentId"
						:name="departmentData.name"
						:heads="departmentData.heads"
						:userCount="departmentData.userCount"
					/>
				</div>
				<div class="chart-wizard__footer">
					<button
						v-if="stepIndex > 0"
						class="ui-btn ui-btn-light --back"
						@click="move('back')"
					>
						<div class="ui-icon-set --chevron-left"></div>
						<span>{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BACK_BTN') }}</span>
					</button>
					<button
						v-show="stepIndex < visibleSteps.length - 1 && !isEditMode"
						class="ui-btn ui-btn-primary ui-btn-round --next"
						:class="{ 'ui-btn-disabled': !isValidStep, 'ui-btn-light-border': isEditMode }"
						@click="move()"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_NEXT_BTN') }}
					</button>
					<button
						v-show="isEditMode"
						class="ui-btn ui-btn-primary ui-btn-round --next"
						:class="{ 'ui-btn-light-border': isEditMode }"
						@click="close(true)"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DISCARD_BTN') }}
					</button>
					<button
						v-show="!isEditMode && stepIndex === visibleSteps.length - 1"
						class="ui-btn ui-btn-primary ui-btn-round"
						:class="{ 'ui-btn-wait': waiting }"
						@click="create"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE_BTN') }}
					</button>
					<button
						v-show="isEditMode"
						class="ui-btn ui-btn-primary ui-btn-round --save"
						:class="{ 'ui-btn-wait': waiting, 'ui-btn-disabled': !isValidStep, }"
						@click="save"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_SAVE_BTN') }}
					</button>
				</div>
			</div>
			<div class="chart-wizard__overlay"></div>
		</div>
	`,
};
