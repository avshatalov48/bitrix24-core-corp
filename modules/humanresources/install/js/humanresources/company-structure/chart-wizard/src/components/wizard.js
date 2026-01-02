import { UI } from 'ui.notification';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { mapState } from 'ui.vue3.pinia';
import { Event } from 'main.core';
import { useChartStore, UserService } from 'humanresources.company-structure.chart-store';
import { getMemberRoles, reportedErrorTypes } from 'humanresources.company-structure.api';
import { DepartmentContentActions } from 'humanresources.company-structure.department-content';
import { StepIds, AuthorityTypes } from '../consts';
import { TreePreview } from './tree-preview/tree-preview';
import { Department } from './steps/department';
import { Employees } from './steps/employees';
import { BindChat } from './steps/bind-chat';
import { Entities } from './steps/entities';
import { Settings } from './steps/settings';
import { AccessDenied } from './steps/access-denied';
import { WizardAPI } from '../api';
import { chartWizardActions } from '../actions';
import { sendData as analyticsSendData } from 'ui.analytics';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import type { WizardData, Step, DepartmentUserIds } from '../types';
import { EntityTypes, NodeColorsSettingsDict, WizardApiEntityChangedDict, NodeSettingsTypes } from 'humanresources.company-structure.utils';
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

	components: { Department, Employees, BindChat, TreePreview, Entities, Settings, AccessDenied },

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
		/** @type StepIdType */
		entity: {
			type: String,
		},
		entityType: {
			type: String,
			default: EntityTypes.department,
		},
		source: {
			type: String,
		},
		refToFocus: {
			type: String,
			default: null,
		},
	},

	data(): WizardData
	{
		return {
			stepIndex: 0,
			waiting: false,
			isValidStep: false,
			isDepartmentDataChanged: false,
			departmentData: {
				id: 0,
				parentId: 0,
				name: '',
				description: '',
				heads: [],
				employees: [],
				chats: [],
				channels: [],
				collabs: [],
				userCount: 0,
				createDefaultChat: false,
				createDefaultChannel: false,
				teamColor: NodeColorsSettingsDict.blue,
				entityType: EntityTypes.department,
				settings: {
					[NodeSettingsTypes.businessProcAuthority]: new Set([AuthorityTypes.departmentHead]),
					[NodeSettingsTypes.reportsAuthority]: new Set([AuthorityTypes.departmentHead]),
				},
			},
			removedUsers: [],
			moveUsersMap: [],
			employeesIds: [],
			departmentSettings: {
				[NodeSettingsTypes.businessProcAuthority]: new Set([AuthorityTypes.departmentHead]),
				[NodeSettingsTypes.reportsAuthority]: new Set([AuthorityTypes.departmentHead]),
			},
			initChats: [],
			initChannels: [],
			initCollabs: [],
			shouldErrorHighlight: false,
			steps: [],
			saveMode: SaveMode.moveUsers,
			permissionChecker: null,
		};
	},

	created(): void
	{
		this.permissionChecker = PermissionChecker.getInstance();
		this.init();
	},

	beforeUnmount(): void
	{
		Event.unbind(window, 'beforeunload', this.handleBeforeUnload);
	},

	computed: {
		breadcrumbsTitle(): string
		{
			if (this.isEditMode)
			{
				return this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EDIT_TEAM_TITLE')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EDIT_TITLE')
				;
			}

			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE_TEAM_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE_TITLE')
			;
		},
		closeConfirmTitle(): string
		{
			if (this.isEditMode)
			{
				return this.departmentData?.entityType === EntityTypes.team
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_EDIT_WIZARD_TEAM_TITLE')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_EDIT_WIZARD_DEPARTMENT_TITLE')
				;
			}

			return this.departmentData?.entityType === EntityTypes.team
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_CREATE_WIZARD_TEAM_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_CREATE_WIZARD_DEPARTMENT_TITLE')
			;
		},
		currentStep(): Step
		{
			return this.steps[this.stepIndex];
		},
		isDeputyApprovesBPAvailable(): boolean
		{
			if (!this.permissionChecker)
			{
				return false;
			}

			return this.permissionChecker.checkDeputyApprovalBPAvailable();
		},
		isDepartmentSettingsAvailable(): boolean
		{
			if (!this.permissionChecker)
			{
				return false;
			}

			return this.permissionChecker.checkDepartmentSettingsAvailable();
		},
		componentInfo(): { name: string, params?: Object }
		{
			if (!this.currentStep.isPermitted)
			{
				return {
					name: 'AccessDenied',
				};
			}

			const {
				parentId,
				name,
				description,
				heads,
				entityType,
				teamColor,
				employees,
			} = this.departmentData;

			const components = {
				[StepIds.department]: {
					name: 'Department',
					params: {
						parentId,
						name,
						description,
						entityType,
						teamColor,
						refToFocus: this.refToFocus,
						shouldErrorHighlight: this.shouldErrorHighlight,
						isEditMode: this.isEditMode,
					},
				},
				[StepIds.employees]: {
					name: 'Employees',
					params: {
						entityId: this.nodeId,
						heads,
						entityType,
						employeesIds: this.employeesIds,
						isEditMode: this.isEditMode,
					},
				},
				[StepIds.bindChat]: {
					name: 'BindChat',
					params: {
						entityId: this.nodeId,
						heads,
						employees,
						employeesIds: this.employeesIds,
						name,
						entityType,
						isEditMode: this.isEditMode,
						initChats: this.initChats,
						initChannels: this.initChannels,
						initCollabs: this.initCollabs,
					},
				},
				[StepIds.settings]: {
					name: 'Settings',
					params: {
						name,
						heads,
						settings: this.departmentSettings,
						entityType,
						features: {
							isDeputyApprovesBPAvailable: this.isDeputyApprovesBPAvailable,
						},
						shouldErrorHighlight: this.shouldErrorHighlight,
					},
				},
				[StepIds.entities]: {
					name: 'Entities',
					params: {
						parentId,
					},
				},
			};

			return components[this.currentStep.id];
		},
		isFirstStep(): boolean
		{
			return this.currentStep.id === StepIds.entities;
		},
		breadcrumbsSteps(): Step[]
		{
			return this.steps.filter((step) => step.hasBreadcrumbs);
		},
		rootId(): number
		{
			const { id } = [...this.departments.values()].find((department) => {
				return department.parentId === 0;
			});

			return id;
		},
		isTeamEntity(): boolean
		{
			return this.departmentData?.entityType === EntityTypes.team;
		},
		entityAnalyticsCategory(): string
		{
			return this.isTeamEntity ? 'team' : 'dept';
		},
		createButtonText(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE_TEAM_BTN')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE_BTN')
			;
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
		mapRawSettings(rawSettings: number[]): Object<string, Set<(string)>>
		{
			return rawSettings.reduce((acc, { settingsType, settingsValue }) => {
				if (!Object.hasOwn(acc, settingsType))
				{
					acc[settingsType] = new Set();
				}

				acc[settingsType].add(settingsValue);

				return acc;
			}, {});
		},
		async init(): Promise<void>
		{
			this.apiEntityChanged = new Set();

			// Important: we need to call createSteps as soon as we get entityType or determine a lack of it

			Event.bind(window, 'beforeunload', this.handleBeforeUnload);
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
					entityType,
					teamColor,
					employees = [],
				} = this.departments.get(this.nodeId);

				this.getMemberRoles(entityType);
				this.createSteps(entityType, id);
				this.stepIndex = this.steps.indexOf(this.getStepById(this.entity));
				this.createDefaultSaveMode(entityType);

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
					entityType,
					teamColor,
					createDefaultChat: false,
					createDefaultChannel: false,
				};
				const rawSettings = await WizardAPI.getSettings(this.nodeId);
				const newSettings = this.mapRawSettings(rawSettings);

				this.departmentSettings[NodeSettingsTypes.businessProcAuthority] = new Set();
				this.departmentSettings = {
					...this.departmentSettings,
					...newSettings,
				};

				// store directly bound chats and channels
				const rawChatsAndChannels = await WizardAPI.getChatsAndChannels(this.nodeId);
				this.initChats = rawChatsAndChannels.chats.filter((item) => !item.originalNodeId);
				this.initChannels = rawChatsAndChannels.channels.filter((item) => !item.originalNodeId);
				this.initCollabs = rawChatsAndChannels.collabs.filter((item) => !item.originalNodeId);

				this.employeesIds = await WizardAPI.getEmployees(this.nodeId);

				return;
			}

			if (this.entityType)
			{
				this.departmentData.entityType = this.entityType;
			}

			this.getMemberRoles(this.entityType);
			this.createSteps(this.entityType, this.departmentData.id);
			this.createDefaultSaveMode(this.entityType);

			if (this.nodeId)
			{
				this.departmentData.parentId = this.nodeId;

				return;
			}

			this.departmentData.parentId = 0;

			analyticsSendData({
				tool: 'structure',
				category: 'structure',
				event: 'create_wizard',
				c_element: this.source,
			});
		},
		getMemberRoles(entityType): void
		{
			this.memberRoles = getMemberRoles(entityType);
		},
		move(buttonId: string = 'next'): void
		{
			if (!this.isValidStep)
			{
				this.shouldErrorHighlight = true;

				return;
			}

			this.stepIndex = buttonId === 'back' ? this.stepIndex - 1 : this.stepIndex + 1;
			this.pickStepsAnalytics();
		},
		moveToStep(stepId: string): void
		{
			if (!this.isValidStep)
			{
				this.shouldErrorHighlight = true;

				return;
			}

			this.stepIndex = this.steps.indexOf(this.getStepById(stepId, true));
			this.pickStepsAnalytics();
		},
		getStepById(stepId: string, force: false): Step
		{
			const stepIndex = this.steps.findIndex((step) => stepId === step.id);

			if (stepIndex > -1 && (this.steps[stepIndex].isEditPermitted || force))
			{
				return this.steps[stepIndex];
			}

			return this.steps.find((step, index) => step.isEditPermitted && index > stepIndex)
				|| this.steps.find((step) => StepIds.department === step.id)
			;
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
					type: this.entityAnalyticsCategory,
					c_element: this.source,
				});
			}
		},
		closeWithConfirm(): void
		{
			if (!this.isDepartmentDataChanged)
			{
				this.close(true);

				return;
			}

			const confirmPopup = new MessageBox({
				title: this.closeConfirmTitle,
				message: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_WIZARD_MESSAGE'),
				yesCaption: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_WIZARD_OK_CAPTION'),
				noCaption: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_CLOSE_WIZARD_NO_CAPTION'),
				onYes: () => {
					this.close(true);

					return true;
				},
				minHeight: 155,
				buttons: MessageBoxButtons.YES_NO,
				popupOptions: {
					closeIcon: true,
					className: 'humanresources-chart-wizard__close-confirm-popup',
				},
			});
			confirmPopup.getYesButton().setRound(true);
			confirmPopup.getNoButton().setRound(true);
			confirmPopup.show();
		},
		onApplyData(
			data: {
				isValid?: boolean,
				isDepartmentDataChanged?: boolean,
				removedUsers?: User, ...Partial<WizardData['departmentData']>
			},
		): void
		{
			const prevEntityType = this.departmentData.entityType;
			const {
				isValid = true,
				removedUsers = [],
				moveUsersMap = {},
				isDepartmentDataChanged = true,
				apiEntityChanged = null,
				...departmentData
			} = data;
			this.isValidStep = isValid;
			this.isDepartmentDataChanged = this.isDepartmentDataChanged || isDepartmentDataChanged;

			if (apiEntityChanged)
			{
				this.apiEntityChanged.add(apiEntityChanged);
			}

			if (departmentData)
			{
				this.departmentData = {
					...this.departmentData,
					...departmentData,
				};
			}

			this.removedUsers = removedUsers;
			this.moveUsersMap = moveUsersMap;
			if (isValid)
			{
				this.shouldErrorHighlight = false;
			}

			// change steps and roles according to entityType
			if (prevEntityType !== this.departmentData.entityType)
			{
				this.getMemberRoles(this.departmentData.entityType);
				this.createSteps(this.departmentData.entityType, this.departmentData.id);
				this.createDefaultSaveMode(this.departmentData.entityType);

				const prevMemberRoles = getMemberRoles(prevEntityType);
				const rolesKeys = Object.keys(prevMemberRoles);
				this.departmentData.heads = this.departmentData.heads.map((item) => {
					const roleKey = rolesKeys.find((key) => prevMemberRoles[key] === item.role);

					return { ...item, role: this.memberRoles[roleKey] };
				});

				this.departmentData.employees = this.departmentData.employees
					.map((item) => ({ ...item, role: this.memberRoles.employee }))
				;
			}

			// change NodeColorsSettingsDict according to entityType
			this.departmentData.teamColor = this.isTeamEntity
				? this.departmentData.teamColor ?? NodeColorsSettingsDict.blue
				: null
			;
		},
		getAllSteps(entityType: string, departmentId: number = null): Step[]
		{
			const isTeamEntity = entityType === EntityTypes.team;

			return {
				entity: {
					id: StepIds.entities,
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_SELECT_ENTITY_TITLE'),
					hasBreadcrumbs: false,
					hasTreePreview: false,
					dataTestIdPart: 'entities',
				},
				department: {
					id: StepIds.department,
					breadcrumbsTitleDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_BREADCRUMBS',
					breadcrumbsTitleTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_TITLE_BREADCRUMBS',
					hasBreadcrumbs: true,
					hasTreePreview: true,
					isEditPermitted: isTeamEntity
						? this.permissionChecker.hasPermission(PermissionActions.teamEdit, departmentId)
						: this.permissionChecker.hasPermission(PermissionActions.departmentEdit, departmentId),
					dataTestIdPart: 'department',
				},
				employees: {
					id: StepIds.employees,
					breadcrumbsTitleDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_EMPLOYEES_BREADCRUMBS',
					breadcrumbsTitleTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_EMPLOYEES_BREADCRUMBS',
					hasBreadcrumbs: true,
					hasTreePreview: true,
					isEditPermitted: isTeamEntity
						? this.permissionChecker.hasPermission(PermissionActions.teamAddMember, departmentId)
						: this.permissionChecker.hasPermission(PermissionActions.employeeAddToDepartment, departmentId),
					dataTestIdPart: 'employees',
				},
				bindChat: {
					id: StepIds.bindChat,
					breadcrumbsTitleDepartment: this.permissionChecker.isCollabsAvailable
						? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TITLE_BREADCRUMBS_W_COLLABS'
						: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TITLE_BREADCRUMBS',
					breadcrumbsTitleTeam: this.permissionChecker.isCollabsAvailable
						? 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_TITLE_BREADCRUMBS_W_COLLABS'
						: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_TEAM_TITLE_BREADCRUMBS',
					hasBreadcrumbs: true,
					hasTreePreview: false,
					isEditPermitted: this.checkCommunicationEditPermission(departmentId, isTeamEntity),
					dataTestIdPart: 'bind-chat',
				},
				settings: {
					id: StepIds.settings,
					breadcrumbsTitleDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_DEPARTMENT_SETTINGS_BREADCRUMBS',
					breadcrumbsTitleTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_TEAM_RIGHTS_BREADCRUMBS',
					hasBreadcrumbs: true,
					hasTreePreview: false,
					isEditPermitted: isTeamEntity
						? this.permissionChecker.hasPermission(PermissionActions.teamSettingsEdit, departmentId)
						: this.permissionChecker.hasPermission(PermissionActions.departmentSettingsEdit, departmentId),
					dataTestIdPart: 'team-rights',
				},
			};
		},
		createSteps(entityType: string = 'DEPARTMENT', departmentId: number = null): void
		{
			const { entity, department, employees, bindChat, settings } = this.getAllSteps(entityType, departmentId);

			const steps: Step[] = this.showEntitySelector ? [entity] : [];
			steps.push(department, employees, bindChat);

			if (entityType === EntityTypes.team)
			{
				steps.push(settings);
				steps.forEach((step: Step): void => {
					Object.assign(step, {
						breadcrumbsTitle: step.breadcrumbsTitleTeam ? this.loc(step.breadcrumbsTitleTeam) : '',
					});
				});
			}
			else
			{
				if (this.isDepartmentSettingsAvailable)
				{
					steps.push(settings);
				}

				steps.forEach((step: Step): void => {
					Object.assign(step, {
						breadcrumbsTitle: step.breadcrumbsTitleDepartment ? this.loc(step.breadcrumbsTitleDepartment) : '',
					});
				});
			}

			if (this.isEditMode)
			{
				steps.forEach((step: Step): void => {
					Object.assign(step, {
						isPermitted: step.isEditPermitted,
						hasTreePreview: step.hasTreePreview && step.isEditPermitted,
					});
				});
			}
			else
			{
				steps.forEach((step: Step): void => {
					Object.assign(step, {
						isPermitted: true,
					});
				});
			}

			this.steps = steps;
		},
		getUsersPromise(departmentId: number): Promise<void>
		{
			const ids = this.calculateEmployeeIds();
			const { headsIds, deputiesIds, employeesIds } = ids;

			const departmentUserIds = {
				[this.memberRoles.head]: headsIds,
				[this.memberRoles.deputyHead]: deputiesIds,
				[this.memberRoles.employee]: employeesIds,
			};

			return this.getUserMemberPromise(departmentId, departmentUserIds);
		},
		calculateEmployeeIds(): Object
		{
			const { heads, employees = [] } = this.departmentData;

			return [...heads, ...employees].reduce((acc, user) => {
				const { headsIds, deputiesIds, employeesIds } = acc;
				if (user.role === this.memberRoles.head)
				{
					headsIds.push(user.id);
				}
				else if (user.role === this.memberRoles.deputyHead)
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
			const {
				name,
				parentId,
				description,
				chats,
				channels,
				collabs,
				createDefaultChat,
				createDefaultChannel,
				createDefaultCollab,
				entityType,
				teamColor,
				settings,
			} = this.departmentData;

			let departmentId = 0;
			let accessCode = '';
			this.waiting = true;
			try
			{
				const userIds = this.calculateEmployeeIds();
				const { headsIds, deputiesIds, employeesIds } = userIds;

				const departmentUserIds = {
					[this.memberRoles.head]: headsIds,
					[this.memberRoles.deputyHead]: deputiesIds,
					[this.memberRoles.employee]: employeesIds,
				};

				const nodeSettings = {
					[NodeSettingsTypes.businessProcAuthority]:
						{
							values: [...settings[NodeSettingsTypes.businessProcAuthority]],
							replace: true,
						},
				};

				let newDepartment = {};
				let updatedDepartmentIds = false;
				let userMovedToRootIds = false;

				if (entityType === EntityTypes.team)
				{

					({
						node: newDepartment,
						updatedDepartmentIds = false,
						userMovedToRootIds = false,
					} = await WizardAPI.createTeam(
						name,
						parentId,
						description,
						teamColor.name,
						departmentUserIds,
						Number(createDefaultChat),
						chats,
						Number(createDefaultChannel),
						channels,
						Number(createDefaultCollab),
						collabs,
						nodeSettings,
					));
				}
				else if (entityType === EntityTypes.department)
				{
					({
						node: newDepartment,
						updatedDepartmentIds = false,
						userMovedToRootIds = false,
					} = await WizardAPI.createDepartment(
						name,
						parentId,
						description,
						departmentUserIds,
						this.saveMode === SaveMode.moveUsers ? 1 : 0,
						Number(createDefaultChat),
						chats,
						Number(createDefaultChannel),
						channels,
						Number(createDefaultCollab),
						collabs,
						nodeSettings,
					));
				}

				departmentId = newDepartment.id;
				accessCode = newDepartment.accessCode;

				if (updatedDepartmentIds)
				{
					chartWizardActions.refreshDepartments(updatedDepartmentIds);
				}
				else if (userMovedToRootIds)
				{
					chartWizardActions.tryToAddCurrentDepartment(this.departmentData, departmentId);
				}
			}
			catch (error)
			{
				this.reportError(error);

				return;
			}
			finally
			{
				this.waiting = false;
			}

			await chartWizardActions.createDepartment({ ...this.departmentData, id: departmentId, accessCode });
			this.$emit('modifyTree', { id: departmentId, parentId, showConfetti: true });

			const { headsIds, deputiesIds, employeesIds } = this.calculateEmployeeIds();

			analyticsSendData(
				{
					tool: 'structure',
					category: 'structure',
					event: `create_${this.entityAnalyticsCategory}`,
					c_element: this.source,
					...(!this.isTeamEntity && {
						p1: `deptChat_${createDefaultChat ? 'Y' : 'N'}`,
						p2: `headAmount_${headsIds.length}`,
						p3: `secondHeadAmount_${deputiesIds.length}`,
						p4: `employeeAmount_${employeesIds.length}`,
						p5: `deptChannel_${createDefaultChannel ? 'Y' : 'N'}`,
					}),
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

			const {
				id,
				parentId,
				name,
				description,
				teamColor,
				settings,
				chats,
				createDefaultChat,
				channels,
				createDefaultChannel,
				collabs,
				createDefaultCollab,
			} = this.departmentData;

			const currentNode = this.departments.get(id);
			const targetNodeId = currentNode?.parentId === parentId ? null : parentId;
			this.waiting = true;

			const moveUsersResult = await this.moveUsers();

			let allUpdatedDepartmentIds = [];
			// Process move users results to update UserService
			if (moveUsersResult.results?.length > 0)
			{
				for (const result of moveUsersResult.results)
				{
					// Collect all updatedDepartmentIds
					if (result.response?.updatedDepartmentIds && Array.isArray(result.response.updatedDepartmentIds))
					{
						allUpdatedDepartmentIds = [...allUpdatedDepartmentIds, ...result.response.updatedDepartmentIds];
					}

					if (result.users?.length > 0)
					{
						// Call UserService.moveUsersToEntity for this batch
						UserService.moveUsersToEntity(
							Number(result.departmentId),
							result.users,
							result.response?.userCount,
							[],
						);
					}
				}

				allUpdatedDepartmentIds = [...new Set(allUpdatedDepartmentIds)].filter((nodeId) => nodeId !== id);
			}

			const usersPromise = this.apiEntityChanged.has(WizardApiEntityChangedDict.employees)
				? this.getUsersPromise(id)
				: Promise.resolve()
			;

			const departmentPromise = this.apiEntityChanged.has(WizardApiEntityChangedDict.department)
				? WizardAPI.updateDepartment(id, targetNodeId, name, description, teamColor?.name)
				: Promise.resolve()
			;

			const settingsPromise = this.apiEntityChanged.has(WizardApiEntityChangedDict.settings)
				? WizardAPI.updateSettings(
					id,
					{
						[NodeSettingsTypes.businessProcAuthority]: {
							values: [...settings[NodeSettingsTypes.businessProcAuthority]],
							replace: true,
						},
					},
					parentId,
				)
				: Promise.resolve()
			;

			this.pickEditAnalytics(id, parentId);

			let usersResponse = null;
			try
			{
				[usersResponse] = await Promise.all([
					usersPromise,
					departmentPromise,
					settingsPromise,
				]);
			}
			catch (e)
			{
				this.reportError(e);
				this.waiting = false;

				return;
			}

			if (this.apiEntityChanged.has(WizardApiEntityChangedDict.bindChats))
			{
				const chatsToAdd = chats.filter((chatId) => !this.initChats.some((chat) => chat.id === chatId));
				const chatsToRemove = this.initChats.filter((chat) => !chats.includes(chat.id)).map((chat) => chat.id);

				if (chatsToAdd.length > 0 || chatsToRemove.length > 0 || Number(createDefaultChat) !== 0)
				{
					try
					{
						await WizardAPI.saveChats(id, chatsToAdd, Number(createDefaultChat), chatsToRemove);
					}
					catch (e)
					{
						this.reportError(e);
					}
				}

				const channelsToAdd = channels.filter(
					(channelId) => !this.initChannels.some((channel) => channel.id === channelId),
				);
				const channelsToRemove = this.initChannels.filter(
					(channel) => !channels.includes(channel.id),
				).map((channel) => channel.id);

				if (channelsToAdd.length > 0 || channelsToRemove.length > 0 || Number(createDefaultChannel) !== 0)
				{
					try
					{
						await WizardAPI.saveChannels(id, channelsToAdd, Number(createDefaultChannel), channelsToRemove);
					}
					catch (e)
					{
						this.reportError(e);
					}
				}

				const collabsToAdd = collabs.filter(
					(collabId) => !this.initCollabs.some((collab) => Number(collab.id) === collabId),
				);
				const collabsToRemove = this.initCollabs.filter(
					(collab) => !collabs.includes(Number(collab.id)),
				).map((collab) => collab.id);

				if (collabsToAdd.length > 0 || collabsToRemove.length > 0 || Number(createDefaultCollab) !== 0)
				{
					try
					{
						await WizardAPI.saveCollabs(id, collabsToAdd, Number(createDefaultCollab), collabsToRemove);
					}
					catch (e)
					{
						this.reportError(e);
					}
				}

				this.departmentData.chatsDetailed = null;
				this.departmentData.channelsDetailed = null;
				this.departmentData.collabssDetailed = null;

				if (this.isEditMode && id && DepartmentContentActions?.updateChatsInChildrenNodes)
				{
					DepartmentContentActions.updateChatsInChildrenNodes(this.nodeId);
				}
			}

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

			store.updateDepartment(this.departmentData);
			store.refreshDepartments(allUpdatedDepartmentIds);

			this.waiting = false;
			this.$emit('modifyTree', { id, parentId });
			this.close();
		},
		reportError(error: Error, delay: number = 4000): void
		{
			if (!reportedErrorTypes.has(error.code))
			{
				UI.Notification.Center.notify({
					content: error.message,
					autoHideDelay: delay,
				});
			}
		},
		handleSaveModeChanged(actionId: string): void
		{
			this.saveMode = actionId;
		},
		createDefaultSaveMode(entityType: string = 'DEPARTMENT'): void
		{
			if (entityType === EntityTypes.team)
			{
				this.saveMode = SaveMode.addUsers;
			}
			else
			{
				this.saveMode = SaveMode.moveUsers;
			}
		},
		pickEditAnalytics(departmentId: number, parentId: number): void
		{
			const currentNode = this.departments.get(departmentId);
			switch (this.entity)
			{
				case StepIds.department:
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: 'edit_dept_name',
						c_element: this.source,
						p1: currentNode?.parentId === parentId ? 'editHead_N' : 'editHeadDept_Y',
						p2: currentNode?.name === name ? 'editName_N' : 'editName_Y',
					});
					break;
				case StepIds.employees:
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
		pickStepsAnalytics(): void
		{
			let event = null;
			switch (this.currentStep.id)
			{
				case StepIds.department:
					event = `create_${this.entityAnalyticsCategory}_step1`;
					break;
				case StepIds.employees:
					event = `create_${this.entityAnalyticsCategory}_step2`;
					break;
				case StepIds.bindChat:
					event = `create_${this.entityAnalyticsCategory}_step3`;
					break;
				case StepIds.settings:
					event = `create_${this.entityAnalyticsCategory}_step4`;
					break;
				default:
					break;
			}

			if (event)
			{
				analyticsSendData({
					tool: 'structure',
					category: 'structure',
					event,
					type: this.entityAnalyticsCategory,
					c_element: this.source,
				});
			}
		},
		checkCommunicationEditPermission(entityId: number, isTeamEntity: boolean): boolean
		{
			return isTeamEntity
				? (
					this.permissionChecker.hasPermission(PermissionActions.teamChatEdit, entityId)
					|| this.permissionChecker.hasPermission(PermissionActions.teamChannelEdit, entityId)
					|| this.permissionChecker.hasPermission(PermissionActions.teamCollabEdit, entityId)
				)
				: (
					this.permissionChecker.hasPermission(PermissionActions.departmentChatEdit, entityId)
					|| this.permissionChecker.hasPermission(PermissionActions.departmentChannelEdit, entityId)
					|| this.permissionChecker.hasPermission(PermissionActions.departmentCollabEdit, entityId)
				)
			;
		},
		async moveUsers(): Promise<Object>
		{
			if (!this.isEditMode || !this.moveUsersMap || Object.keys(this.moveUsersMap).length === 0)
			{
				return { success: true, results: [] };
			}

			const results = [];
			const errors = [];

			// Execute promises for each department
			for (const [departmentId, users] of Object.entries(this.moveUsersMap))
			{
				const userIds = users.map((user) => user.id);
				try
				{
					// Create the department user IDs structure expected by the API
					const departmentUserIds = {
						[this.memberRoles.employee]: userIds,
						[this.memberRoles.head]: [],
						[this.memberRoles.deputyHead]: [],
					};

					// Call the API to move users to this department and await the result
					// eslint-disable-next-line no-await-in-loop
					const response = await WizardAPI.moveUsers(Number(departmentId), departmentUserIds);
					results.push({ departmentId: Number(departmentId), users, response });
				}
				catch (error)
				{
					errors.push({
						departmentId: Number(departmentId),
						users,
						error: error.message || 'Unknown error moving users',
					});
					this.reportError(error);
				}
			}

			return {
				success: errors.length === 0,
				results,
				errors: errors.length > 0 ? errors : null,
			};
		},
	},

	template: `
		<div class="chart-wizard">
			<div class="chart-wizard__dialog" :class="{ '--first-step': isFirstStep }">
				<div v-if="currentStep.hasBreadcrumbs" class="chart-wizard__breadcrumbs-head">
					<div class="chart-wizard__head_close --breadcrumbs" @click="closeWithConfirm(true)"></div>
					<div class="chart-wizard__breadcrumbs-head_descr">
						{{ breadcrumbsTitle }}
					</div>
					<div class="chart-wizard__breadcrumbs-head_breadcrumbs">
						<span 
							v-for="(step, index) in breadcrumbsSteps" 
							:key="index"
							class="chart-wizard__breadcrumbs-head_breadcrumbs-item"
						>
							<span v-if="index > 0" class="ui-icon-set --chevron-right"></span>
							<span class="chart-wizard__breadcrumbs-head_breadcrumbs-item-text"
								  :class="{ '--active': step.id === currentStep.id }"
								  :data-test-id="'hr-company-structure_chart-wizard__breadcrumbs_' + step.dataTestIdPart"
								  @click="moveToStep(step.id)"
							>
								{{ step.breadcrumbsTitle }}
							</span>
						</span>
					</div>
				</div>
				<div v-else class="chart-wizard__head">
					<div class="chart-wizard__head_close" @click="close(true)"></div>
					<p class="chart-wizard__head_title">{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_CREATE') }}</p>
					<p class="chart-wizard__head_descr" :class="{ '--first-step': isFirstStep }">
						{{ currentStep.title }}
					</p>
				</div>
				<div class="chart-wizard__content" :style="{ display: !isEditMode && isFirstStep ? 'block' : 'flex' }">
					<KeepAlive>
						<component
							:is="componentInfo.name"
							v-bind="componentInfo.params"
							v-on="{
								applyData: onApplyData,
								saveModeChanged: componentInfo.name === 'Employees' ? handleSaveModeChanged : undefined
							}"
						>
						</component>
					</KeepAlive>
					<div v-if="currentStep.hasTreePreview" class="chart-wizard__tree_container">
						<TreePreview
							:parentId="departmentData.parentId"
							:name="departmentData.name"
							:heads="departmentData.heads"
							:userCount="departmentData.userCount"
							:entityType="departmentData.entityType"
							:teamColor="departmentData.teamColor"
						/>
					</div>
				</div>
				<div class="chart-wizard__footer">
					<button
						v-if="stepIndex > 0"
						class="ui-btn ui-btn-light chart-wizard__button --back"
						@click="move('back')"
						data-test-id="hr-company-structure_chart-wizard__back-button"
					>
						<span class="chart-wizard__back-button-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BACK_BTN') }}
						</span>
					</button>
					<button
						v-show="stepIndex < steps.length - 1"
						class="ui-btn ui-btn-primary ui-btn-round chart-wizard__button --next"
						:class="{ 
							'ui-btn-disabled': !isValidStep,
							'ui-btn-light-border': isEditMode,
						 }"
						@click="move()"
						data-test-id="hr-company-structure_chart-wizard__next-button"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_NEXT_BTN') }}
					</button>
					<button
						v-show="!isEditMode && stepIndex === steps.length - 1"
						class="ui-btn ui-btn-primary ui-btn-round chart-wizard__button"
						:class="{ 'ui-btn-wait': waiting }"
						@click="create"
						data-test-id="hr-company-structure_chart-wizard__create-button"
					>
						{{ createButtonText }}
					</button>
					<button
						v-show="isEditMode"
						class="ui-btn ui-btn-primary ui-btn-round chart-wizard__button --save"
						:class="{ 'ui-btn-wait': waiting, 'ui-btn-disabled': !isValidStep, }"
						@click="save"
						data-test-id="hr-company-structure_chart-wizard__save-button"
					>
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_SAVE_BTN') }}
					</button>
				</div>
			</div>
			<div class="chart-wizard__overlay"></div>
		</div>
	`,
};
