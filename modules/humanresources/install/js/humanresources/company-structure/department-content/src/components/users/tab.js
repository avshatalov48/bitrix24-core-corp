import { EntityTypes } from 'humanresources.company-structure.utils';
import { Dom, Event, Text } from 'main.core';
import { UI } from 'ui.notification';
import { EventEmitter } from 'main.core.events';
import { mapState, mapWritableState } from 'ui.vue3.pinia';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { getMemberRoles, MemberRolesType, AnalyticsSourceType } from 'humanresources.company-structure.api';
import { UserManagementDialogAPI } from 'humanresources.company-structure.user-management-dialog';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { UsersTabActionMenu, events } from 'humanresources.company-structure.org-chart';
import { MoveEmployeeConfirmationPopup } from 'humanresources.company-structure.structure-components';
import { HeadListDataTestIds, EmployeeListDataTestIds } from './consts';
import { EmptyTabAddButton } from './empty-tab-add-button';
import { UserListItem } from './item/item';
import { EmptyState } from '../base-components/empty-state/empty-state';
import { TabList } from '../base-components/list/list';
import { SearchInput } from '../base-components/search/search-input';
import { DepartmentAPI } from '../../api';
import { DepartmentContentActions } from '../../actions';

import type { TabListDataTestIds } from '../base-components/list/types';

import 'ui.buttons';
import './styles/tab.css';

type DepartmentUsersStatus = {
	departmentId: number;
	loaded: boolean;
};

const AUTOSCROLL_AREA_SIZE = 70;

// @vue/component
export const UsersTab = {
	name: 'usersTab',

	components: {
		SearchInput,
		EmptyState,
		TabList,
		EmptyTabAddButton,
		UserListItem,
		MoveEmployeeConfirmationPopup,
	},

	emits: ['showDetailLoader', 'hideDetailLoader'],

	data(): Object
	{
		return {
			searchQuery: '',
			selectedUserId: null,
			needToScroll: false,
			dragState: this.getInitialDragState(),
			showDndConfirmationPopup: false,
			dndPreviousState: null,
			dndPopupDescription: '',
			boundHandleDragMove: null,
			boundHandleDragEnd: null,
		};
	},

	computed: {
		memberRoles(): MemberRolesType
		{
			return getMemberRoles(this.entityType);
		},
		heads(): Array
		{
			return this.departments.get(this.focusedNode).heads ?? [];
		},
		headCount(): number
		{
			return this.heads.length ?? 0;
		},
		departmentId(): number
		{
			return this.focusedNode;
		},
		formattedHeads(): Array
		{
			return this.heads.map((head) => ({
				...head,
				subtitle: head.workPosition,
				badgeText: this.getBadgeText(head.role),
			})).sort((a, b) => {
				const roleOrder = {
					[this.memberRoles.head]: 1,
					[this.memberRoles.deputyHead]: 2,
				};

				const roleA = roleOrder[a.role] || 3;
				const roleB = roleOrder[b.role] || 3;

				return roleA - roleB;
			});
		},
		filteredHeads(): Array
		{
			return this.formattedHeads.filter(
				(head) => head.name.toLowerCase().includes(this.searchQuery.toLowerCase())
					|| head.workPosition?.toLowerCase().includes(this.searchQuery.toLowerCase()),
			);
		},
		employeeCount(): number
		{
			const memberCount = this.departments.get(this.focusedNode).userCount ?? 0;

			return memberCount - (this.headCount ?? 0);
		},
		formattedEmployees(): Array
		{
			return this.employees.map((employee) => ({
				...employee,
				subtitle: employee.workPosition,
			})).reverse();
		},
		filteredEmployees(): Array
		{
			return this.formattedEmployees.filter(
				(employee) => employee.name.toLowerCase().includes(this.searchQuery.toLowerCase())
					|| employee.workPosition?.toLowerCase().includes(this.searchQuery.toLowerCase()),
			);
		},
		memberCount(): number
		{
			return this.departments.get(this.focusedNode).userCount ?? 0;
		},
		...mapState(useChartStore, ['focusedNode', 'departments', 'searchedUserId']),
		...mapWritableState(useChartStore, ['searchedUserId']),
		employees(): Array
		{
			return this.departments.get(this.focusedNode)?.employees ?? [];
		},
		showSearchBar(): boolean
		{
			return this.memberCount > 0;
		},
		showSearchEmptyState(): boolean
		{
			return this.filteredHeads.length === 0 && this.filteredEmployees.length === 0;
		},
		canAddUsers(): boolean
		{
			const permissionChecker = PermissionChecker.getInstance();
			if (!permissionChecker)
			{
				return false;
			}

			const nodeId = this.focusedNode;
			const permission = this.isTeamEntity
				? PermissionActions.teamAddMember
				: PermissionActions.employeeAddToDepartment
			;

			return permissionChecker.hasPermission(permission, nodeId);
		},
		headListEmptyStateTitle(): string
		{
			if (this.canAddUsers)
			{
				return this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_HEAD_EMPTY_LIST_ITEM_TITLE')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_EMPTY_LIST_ITEM_TITLE');
			}

			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_HEAD_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION');
		},
		employeesListEmptyStateTitle(): string
		{
			if (this.canAddUsers)
			{
				return this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_EMPLOYEE_EMPTY_LIST_ITEM_TITLE')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_EMPTY_LIST_ITEM_TITLE');
			}

			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_EMPLOYEE_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION');
		},
		shouldUpdateList(): boolean
		{
			return this.departments.get(this.focusedNode).employeeListOptions?.shouldUpdateList ?? true;
		},
		departmentUsersStatus(): DepartmentUsersStatus
		{
			const department = this.departments.get(this.focusedNode);
			if (department?.heads && department.employees)
			{
				return { departmentId: this.focusedNode, loaded: true };
			}

			return { departmentId: this.focusedNode, loaded: false };
		},
		headMenu(): UsersTabActionMenu
		{
			return new UsersTabActionMenu(
				this.focusedNode,
				AnalyticsSourceType.DETAIL,
				'head',
				this.entityType,
			);
		},
		employeeMenu(): UsersTabActionMenu
		{
			return new UsersTabActionMenu(
				this.focusedNode,
				AnalyticsSourceType.DETAIL,
				'employee',
				this.entityType,
			);
		},
		isEmployeeListOptionsSet(): boolean
		{
			const department = this.departments.get(this.focusedNode) || {};

			return department.employeeListOptions && Object.keys(department.employeeListOptions).length > 0;
		},
		employeeTitle(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_EMPLOYEE_LIST_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_LIST_TITLE');
		},
		entityType(): string
		{
			return this.departments.get(this.focusedNode)?.entityType;
		},
		isTeamEntity(): boolean
		{
			return this.entityType === EntityTypes.team;
		},
		emptyStateTitle(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_TEAM_ADD_USER_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_TITLE');
		},
		emptyStateDescription(): string
		{
			if (this.canAddUsers)
			{
				return this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_TEAM_ADD_USER_SUBTITLE')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_SUBTITLE');
			}

			// text is in progress
			return this.isTeamEntity
				? ''
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_EMPLOYEES_ADD_USER_SUBTITLE');
		},
		isHeadListPotentialTarget(): boolean
		{
			return this.dragState.isDragging
				&& this.dragState.initialList === 'employees'
			;
		},
		isEmployeeListPotentialTarget(): boolean
		{
			return this.dragState.isDragging
				&& (this.dragState.initialList === 'head' || this.dragState.initialList === 'deputyHead')
			;
		},
		dndConfirmationPopupTitle(): ?string
		{
			const toHead = this.dragState.targetList === 'head';

			return toHead
				? undefined
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_TITLE');
		},
		dndConfirmationPopupBtn(): string
		{
			const toHead = this.dragState.targetList === 'head';

			return toHead
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_BTN_TO_HEAD')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_BTN');
		},
		showDndRoleSelect(): boolean
		{
			return this.dragState.targetList === 'head';
		},
		headListTitle(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_TEAM_HEAD_LIST_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_LIST_TITLE')
			;
		},
	},

	watch: {
		focusedNode(newId: number): void
		{
			const department = this.departments.get(newId) || {};
			if (!this.isEmployeeListOptionsSet)
			{
				const employeeListOptions = {
					page: 0,
					shouldUpdateList: true,
					isListUpdated: false,
				};
				DepartmentContentActions.updateEmployeeListOptions(newId, employeeListOptions);
				this.departments.set(newId, department);
			}

			if (
				department.employeeListOptions.page === 0
				&& department.employeeListOptions.isListUpdated === false
				&& department.employeeListOptions.shouldUpdateList === true
			)
			{
				this.loadEmployeesAction();
			}

			this.isDescriptionExpanded = false;
			this.searchQuery = '';

			if (this.searchedUserId)
			{
				this.needToFocusUserId = this.searchedUserId;
				this.$nextTick(() => {
					this.scrollToUser();
				});
			}
		},
		searchedUserId: {
			handler(userId: number): void
			{
				if (!userId)
				{
					return;
				}

				this.needToFocusUserId = userId;
				if (this.isListUpdated)
				{
					this.needToScroll = true;
				}
				else
				{
					this.$nextTick(() => {
						this.scrollToUser();
					});
				}
			},
			immediate: true,
		},
		async searchQuery(newQuery: string): Promise<void>
		{
			await this.searchMembers(newQuery);
		},
		departmentUsersStatus(usersStatus: DepartmentUsersStatus, prevUsersStatus: DepartmentUsersStatus): void
		{
			const { departmentId, loaded } = usersStatus;
			const { departmentId: prevDepartmentId, loaded: prevLoaded } = prevUsersStatus;
			if (departmentId === prevDepartmentId && loaded === prevLoaded)
			{
				return;
			}

			if (loaded)
			{
				this.$emit('hideDetailLoader');
			}
			else
			{
				this.$emit('showDetailLoader');
				this.loadEmployeesAction();
			}
		},
	},

	created(): void
	{
		this.loadEmployeesAction();
		this.clearSearchTimeout = null;
		this.autoscrollIntervalId = null;

		this.boundHandleDragMove = this.handleDragMove.bind(this);
		this.boundHandleDragEnd = this.handleDragEnd.bind(this);
	},

	mounted(): void
	{
		this.tabContainer = this.$refs['tab-container'];
	},

	methods: {
		loc(phraseCode, replacements = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		getBadgeText(role): ?string
		{
			if (role === this.memberRoles.head)
			{
				return this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_TEAM_HEAD_BADGE')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_HEAD_BADGE')
				;
			}

			if (role === this.memberRoles.deputyHead)
			{
				return this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_TEAM_DEPUTY_HEAD_BADGE')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_DEPUTY_HEAD_BADGE')
				;
			}

			return null;
		},
		updateList(event): void
		{
			const employeesList = event.target;
			const scrollPosition = employeesList.scrollTop + employeesList.clientHeight;

			if (employeesList.scrollHeight - scrollPosition < 40)
			{
				this.loadEmployeesAction();
			}
		},
		async loadEmployeesAction(): void
		{
			const nodeId = this.focusedNode;

			if (!this.departments.get(nodeId))
			{
				return;
			}

			const employeeListOptions = this.departments.get(nodeId).employeeListOptions ?? {};

			employeeListOptions.page = employeeListOptions.page ?? 0;
			employeeListOptions.shouldUpdateList = employeeListOptions.shouldUpdateList ?? true;
			employeeListOptions.isListUpdated = employeeListOptions.isListUpdated ?? false;
			DepartmentContentActions.updateEmployeeListOptions(nodeId, employeeListOptions);
			if (employeeListOptions.isListUpdated || !employeeListOptions.shouldUpdateList)
			{
				return;
			}

			if (
				!employeeListOptions.isListUpdated
				&& employeeListOptions.page === 0
				&& employeeListOptions.shouldUpdateList === true
			)
			{
				this.$emit('showDetailLoader');
			}

			employeeListOptions.isListUpdated = true;
			employeeListOptions.page += 1;
			DepartmentContentActions.updateEmployeeListOptions(nodeId, employeeListOptions);

			let loadedEmployees = await DepartmentAPI.getPagedEmployees(nodeId, employeeListOptions.page, 25);

			if (!loadedEmployees)
			{
				employeeListOptions.shouldUpdateList = false;
				employeeListOptions.isListUpdated = false;
				DepartmentContentActions.updateEmployeeListOptions(nodeId, employeeListOptions);

				return;
			}

			loadedEmployees = loadedEmployees.map((user) => {
				return { ...user, role: this.memberRoles.employee };
			});

			const employees = this.departments.get(nodeId)?.employees ?? [];
			const employeeIds = new Set(employees.map((employee) => employee.id));

			const newEmployees = loadedEmployees.reverse().filter((employee) => !employeeIds.has(employee.id));
			employees.unshift(...newEmployees);
			employeeListOptions.shouldUpdateList = newEmployees.length === 25;
			employeeListOptions.isListUpdated = false;

			DepartmentContentActions.updateEmployeeListOptions(nodeId, employeeListOptions);
			DepartmentContentActions.updateEmployees(nodeId, employees);
			if (this.departmentUsersStatus.loaded)
			{
				this.$emit('hideDetailLoader');
			}

			if (this.needToScroll)
			{
				this.scrollToUser();
			}
		},
		async scrollToUser(): void
		{
			const userId = this.needToFocusUserId;
			this.needToFocusUserId = null;
			this.needToScroll = false;
			const selectors = `.hr-department-detail-content__user-container[data-id="hr-department-detail-content__user-${userId}-item"]`;
			let element = this.tabContainer.querySelector(selectors);

			if (!element)
			{
				let user = null;
				try
				{
					user = await DepartmentAPI.getUserInfo(this.focusedNode, userId);
				}
				catch
				{ /* empty */ }

				const department = this.departments.get(this.focusedNode);
				if (!user || !department)
				{
					return;
				}

				if (user.role === this.memberRoles.head || user.role === this.memberRoles.deputyHead)
				{
					department.heads = department.heads ?? [];
					if (!department.heads.some((head) => head.id === user.id))
					{
						return;
					}
				}
				else
				{
					department.employees = department.employees ?? [];
					if (!department.employees.some((employee) => employee.id === user.id))
					{
						department.employees.push(user);
					}
				}

				// eslint-disable-next-line vue/valid-next-tick
				await this.$nextTick(() => {
					element = this.tabContainer.querySelector(selectors);
				});
			}

			if (!element)
			{
				return;
			}

			element.scrollIntoView({ behavior: 'smooth', block: 'center' });
			setTimeout(() => {
				this.selectedUserId = userId;
			}, 750);

			if (this.clearSearchTimeout)
			{
				clearTimeout(this.clearSearchTimeout);
			}

			this.clearSearchTimeout = setTimeout(() => {
				if (this.searchedUserId === userId)
				{
					this.selectedUserId = null;
					this.searchedUserId = null;
				}
			}, 4000);
		},
		async searchMembers(query)
		{
			if (query.length === 0)
			{
				return;
			}

			this.findQueryResult = this.findQueryResult || {};
			this.findQueryResult[this.focusedNode] = this.findQueryResult[this.focusedNode] || {
				success: [],
				failure: [],
			};

			const nodeResults = this.findQueryResult[this.focusedNode];

			if (nodeResults.failure.some((failedQuery) => query.startsWith(failedQuery)))
			{
				return;
			}

			if (nodeResults.success.includes(query) || nodeResults.failure.includes(query))
			{
				return;
			}

			const founded = await DepartmentAPI.findMemberByQuery(this.focusedNode, query);

			if (founded.length === 0)
			{
				nodeResults.failure.push(query);

				return;
			}

			const department = this.departments.get(this.focusedNode);
			const newMembers = founded.filter((found) => !department.heads.some((head) => head.id === found.id)
				&& !department.employees.some((employee) => employee.id === found.id));

			department.employees.push(...newMembers);
			nodeResults.success.push(query);
		},
		searchUser(searchQuery: string): void
		{
			this.searchQuery = searchQuery;
		},
		onHeadListActionMenuItemClick(actionId: string): void
		{
			this.headMenu.onActionMenuItemClick(actionId);
		},
		onEmployeeListActionMenuItemClick(actionId: string): void
		{
			this.employeeMenu.onActionMenuItemClick(actionId);
		},
		getHeadListDataTestIds(): TabListDataTestIds
		{
			return HeadListDataTestIds;
		},
		getEmployeeListDataTestIds(): TabListDataTestIds
		{
			return EmployeeListDataTestIds;
		},
		handleItemDragStart(payload): void
		{
			if (this.dragState.isDragging)
			{
				return;
			}

			const { event, element, userId, initialListType } = payload;

			event.preventDefault();
			this.dragState.isDragging = true;
			this.dragState.userId = userId;
			this.dragState.initialList = initialListType;
			this.dragState.draggedElement = element;

			const rect = this.dragState.draggedElement.getBoundingClientRect();
			this.dragState.offsetX = event.clientX - rect.left;
			this.dragState.offsetY = event.clientY - rect.top;

			const ghost = this.dragState.draggedElement.cloneNode(true);
			Dom.addClass(ghost, '--ghost');
			Dom.style(ghost, 'left', `${event.clientX - this.dragState.offsetX}px`);
			Dom.style(ghost, 'top', `${event.clientY - this.dragState.offsetY}px`);

			Dom.append(ghost, document.body);
			this.dragState.ghostElement = ghost;

			Dom.addClass(this.dragState.draggedElement, '--dragging');
			Dom.addClass(document.body, '--user-dragging');

			Event.bind(document, 'mousemove', this.boundHandleDragMove);
			Event.bind(document, 'mouseup', this.boundHandleDragEnd);

			// user to computed?
			const user = this.formattedHeads.find((u) => u.id === userId)
				|| this.formattedEmployees.find((u) => u.id === userId);
			EventEmitter.emit(events.HR_USER_DRAG_START, { user });
		},
		handleDragMove(event: MouseEvent): void
		{
			if (!this.dragState.isDragging)
			{
				return;
			}

			Dom.addClass(this.dragState.draggedElement, '--hidden');

			EventEmitter.emit(events.HR_USER_DRAG_MOVE, {
				pageX: event.pageX,
				pageY: event.pageY,
			});

			if (this.dragState.ghostElement)
			{
				Dom.style(this.dragState.ghostElement, 'left', `${event.clientX - this.dragState.offsetX}px`);
				Dom.style(this.dragState.ghostElement, 'top', `${event.clientY - this.dragState.offsetY}px`);
			}

			let potentialTarget = null;
			if (this.isHeadListPotentialTarget && this.isPointerOverElement(event, this.$refs.headList?.$el))
			{
				potentialTarget = 'head';
			}
			else if (this.isEmployeeListPotentialTarget && this.isPointerOverElement(event, this.$refs.employeeList?.$el))
			{
				potentialTarget = 'employee';
			}
			this.dragState.targetList = potentialTarget;

			this.handleAutoscrollOnDrag(event);
		},
		handleAutoscrollOnDrag(event: MouseEvent): void
		{
			if (!this.isHeadListPotentialTarget)
			{
				return;
			}

			const scrollableContainer = this.$refs.scrollableContainer;
			if (!scrollableContainer)
			{
				return;
			}

			const rect = scrollableContainer.getBoundingClientRect();
			const mouseY = event.clientY;

			const topTriggerZone = rect.top + AUTOSCROLL_AREA_SIZE;

			if (mouseY < topTriggerZone)
			{
				if (this.autoscrollIntervalId !== null)
				{
					return;
				}

				const scrollSpeed = 10;
				this.autoscrollIntervalId = setInterval(() => {
					scrollableContainer.scrollTop -= scrollSpeed;
				}, 16);
			}
			else
			{
				this.stopAutoscroll();
			}
		},
		stopAutoscroll(): void
		{
			if (this.autoscrollIntervalId !== null)
			{
				clearInterval(this.autoscrollIntervalId);
				this.autoscrollIntervalId = null;
			}
		},
		isPointerOverElement(event: MouseEvent, element: HTMLElement): boolean
		{
			if (!element)
			{
				return false;
			}
			const rect = element.getBoundingClientRect();

			return (
				event.clientX >= rect.left && event.clientX <= rect.right
				&& event.clientY >= rect.top && event.clientY <= rect.bottom
			);
		},
		async handleDragEnd(): void
		{
			if (!this.dragState.isDragging)
			{
				return;
			}

			EventEmitter.emit(events.HR_USER_DRAG_DROP);
			this.stopAutoscroll();

			const { userId, initialList, targetList, ghostElement, draggedElement } = this.dragState;

			const isValidDrop = userId && targetList && targetList !== initialList;
			let targetIndex = null;
			if (isValidDrop)
			{
				const listRef = targetList === 'head' ? this.$refs.headList : this.$refs.employeeList;
				targetIndex = listRef?.placeholderIndex;
			}

			Event.unbind(document, 'mousemove', this.boundHandleDragMove);
			Event.unbind(document, 'mouseup', this.boundHandleDragEnd);
			if (ghostElement)
			{
				Dom.remove(ghostElement);
			}

			if (draggedElement)
			{
				Dom.removeClass(draggedElement, '--dragging');
				Dom.removeClass(draggedElement, '--hidden');
			}
			Dom.removeClass(document.body, '--user-dragging');

			EventEmitter.emit(events.HR_USER_DRAG_END);
			if (!isValidDrop || targetIndex === null)
			{
				this.dragState = this.getInitialDragState();

				return;
			}

			this.dragState.isDragging = false;
			this.dragState.ghostElement = null;
			this.dragState.draggedElement = null;
			this.dragState.context = { targetIndex };

			this.dndPreviousState = this.moveDndUser();
			if (!this.dndPreviousState)
			{
				this.dragState = this.getInitialDragState();

				return;
			}

			const { context } = this.dragState;
			context.selectedRole = null;
			context.selectedRoleLabel = '';

			if (targetList === 'head')
			{
				context.selectedRole = this.memberRoles.head;
				context.selectedRoleLabel = this.loc(
					'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_HEAD_BADGE',
				);
			}

			await this.prepareDndPopupDescription();
			this.showDndConfirmationPopup = true;
		},
		cancelDndUserMove(): void
		{
			const { heads, employees } = this.dndPreviousState;
			DepartmentContentActions.updateHeads(this.focusedNode, heads);
			DepartmentContentActions.updateEmployees(this.focusedNode, employees);

			this.dndPreviousState = null;
			this.dragState = this.getInitialDragState();
			this.showDndConfirmationPopup = false;
		},
		async confirmDndUserMove(payload): Promise<void>
		{
			const { userId } = this.dragState;
			const targetRole = payload.role || this.memberRoles.employee;

			const department = this.departments.get(this.focusedNode);
			const user = department.heads.find((u) => u.id === userId) || department.employees.find((u) => u.id === userId);

			if (!user)
			{
				this.dragState = this.getInitialDragState();

				return;
			}

			const previousRole = user.role;
			user.role = targetRole;

			try
			{
				await UserManagementDialogAPI.addUsersToDepartment(this.focusedNode, [userId], targetRole);

				UI.Notification.Center.notify({
					content: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_SUCCEED'),
					autoHideDelay: 3000,
				});
			}
			catch (error)
			{
				console.error(error);
				user.role = previousRole;
				this.cancelDndUserMove();
			}

			this.showDndConfirmationPopup = false;
			this.dragState = this.getInitialDragState();
		},
		async prepareDndPopupDescription(): Promise<string>
		{
			const { userId, targetList } = this.dragState;

			const toHead = targetList === 'head';
			const isTeam = this.isTeamEntity;
			const department = this.departments.get(this.departmentId);
			const departmentName = Text.encode(department?.name ?? '');

			let user = null;
			try
			{
				user = await DepartmentAPI.getUserInfo(this.departmentId, userId);
			}
			catch
			{ /* empty */ }

			const userName = Text.encode(user?.name ?? '');
			const userUrl = user?.url ?? '#';
			let phraseCode = '';

			if (toHead)
			{
				phraseCode = isTeam
					? 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_DESC_TEAM_HEAD'
					: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_DESC_HEAD';
			}
			else
			{
				const basePhrase = isTeam
					? 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_DESC_TEAM'
					: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_DND_POPUP_CONFIRM_DESC';

				const genderSuffix = user?.gender === 'F' ? '_F' : '_M';
				phraseCode = basePhrase + genderSuffix;
			}

			this.dndPopupDescription = this.loc(phraseCode, {
				'#USER_NAME#': userName,
				'#DEPARTMENT_NAME#': departmentName,
			})
				.replace(
					'[link]',
					`<a class="hr-department-detail-content__move-user-department-user-link" href="${userUrl}">`,
				)
				.replace('[/link]', '</a>');
		},
		moveDndUser(): Object | null
		{
			const { userId, targetList, context } = this.dragState;
			const { targetIndex } = context;
			const department = this.departments.get(this.focusedNode);
			if (!department)
			{
				return null;
			}

			const previousState = { heads: [...department.heads], employees: [...department.employees] };
			const isMovingToHeads = targetList === 'head';
			const sourceList = isMovingToHeads ? [...previousState.employees] : [...previousState.heads];
			const targetListForMove = isMovingToHeads ? [...previousState.heads] : [...previousState.employees];

			const userIndex = sourceList.findIndex((user) => user.id === userId);
			if (userIndex === -1)
			{
				return null;
			}

			const [userToMove] = sourceList.splice(userIndex, 1);
			targetListForMove.splice(targetIndex, 0, userToMove);

			if (isMovingToHeads)
			{
				DepartmentContentActions.updateHeads(this.focusedNode, targetListForMove);
				DepartmentContentActions.updateEmployees(this.focusedNode, sourceList);
			}
			else
			{
				DepartmentContentActions.updateHeads(this.focusedNode, sourceList);
				DepartmentContentActions.updateEmployees(this.focusedNode, targetListForMove);
			}

			return previousState;
		},
		getInitialDragState(): Object
		{
			return {
				isDragging: false,
				userId: null,
				initialList: null,
				targetList: null,
				ghostElement: null,
				draggedElement: null,
				offsetX: 0,
				offsetY: 0,
				context: null,
			};
		},
	},

	template: `
		<div class="hr-department-detail-content__tab-container --users" ref="tab-container">
			<template v-if="memberCount > 0">
				<SearchInput
					v-if="showSearchBar"
					:placeholder="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_SEARCH_PLACEHOLDER')"
					:value="searchQuery"
					@inputChange="searchUser"
					dataTestId="hr-department-detail-content_users-tab__users-search-input"
				/>
				<div
					v-if="!showSearchEmptyState"
					v-on="shouldUpdateList ? { scroll: updateList } : {}"
					class="hr-department-detail-content__lists-container"
					ref="scrollableContainer"
				>
					<TabList
						ref="headList"
						id='hr-department-detail-content_chats-tab__chat-list'
						:title="headListTitle"
						:count="headCount"
						:menuItems="headMenu.items"
						:listItems="filteredHeads"
						listType="head"
						:emptyItemTitle="headListEmptyStateTitle"
						emptyItemImageClass="hr-department-detail-content__empty-user-list-item-image"
						:hideEmptyItem="searchQuery.length > 0"
						:withAddPermission="canAddUsers"
						@tabListAction="onHeadListActionMenuItemClick"
						:dataTestIds="getHeadListDataTestIds()"
						:isDropTarget="isHeadListPotentialTarget && dragState.targetList === 'head'"
					>
						<template v-slot="{ item }">
							<UserListItem
								:user="item"
								listType="head"
								:selectedUserId="selectedUserId"
								:entityType="entityType"
								:canDrag="canAddUsers"
								@itemDragStart="handleItemDragStart"
							/>
						</template>
					</TabList>
					<TabList
						ref="employeeList"
						id='hr-department-detail-content_chats-tab__channel-list'
						:title="employeeTitle"
						:count="employeeCount"
						listType="employees"
						:menuItems="employeeMenu.items"
						:listItems="filteredEmployees"
						:emptyItemTitle="employeesListEmptyStateTitle"
						emptyItemImageClass="hr-department-detail-content__empty-user-list-item-image"
						:hideEmptyItem="searchQuery.length > 0"
						:withAddPermission="canAddUsers"
						@tabListAction="onEmployeeListActionMenuItemClick"
						:dataTestIds="getEmployeeListDataTestIds()"
						:isDropTarget="isEmployeeListPotentialTarget && dragState.targetList === 'employee'"
					>
						<template v-slot="{ item }">
							<UserListItem
								:user="item"
								listType="employees"
								:canDrag="canAddUsers"
								@itemDragStart="handleItemDragStart"
								:selectedUserId="selectedUserId"
								:entityType="entityType"
							/>
						</template>
					</TabList>
				</div>
				<EmptyState v-else
					imageClass="hr-department-detail-content__empty-tab-user-not-found-icon"
					:title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_TITLE')"
					:description ="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_SUBTITLE')"
				/>
			</template>
			<EmptyState v-else
				:imageClass="canAddUsers 
					? 'hr-department-detail-content__empty-tab-add-user-icon' 
					: 'hr-department-detail-content__empty-tab-cant-add-user-icon'"
				:title="emptyStateTitle"
				:description ="emptyStateDescription"
			>
				<template v-slot:content>
					<EmptyTabAddButton v-if="canAddUsers" :departmentId="departmentId" :entityType="entityType"/>
				</template>
			</EmptyState>
			<MoveEmployeeConfirmationPopup 
				v-if="showDndConfirmationPopup" 
				:title="dndConfirmationPopupTitle"
				:description="dndPopupDescription"
				:confirmButtonText="dndConfirmationPopupBtn"
				:showRoleSelect="showDndRoleSelect"
				:excludeEmployeeRole="true"
				:targetType="entityType"
				@confirm="confirmDndUserMove"
				@close="cancelDndUserMove"
			/>
		</div>
	`,
};
