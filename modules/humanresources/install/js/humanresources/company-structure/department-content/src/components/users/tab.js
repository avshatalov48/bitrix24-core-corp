import { AddUserDialog } from 'humanresources.company-structure.add-user-dialog';
import { MoveUserFromDialog } from 'humanresources.company-structure.move-user-from-dialog';
import { DepartmentAPI } from '../../api';
import { UserList } from './list/list';
import { UserListActionButton } from './list/list-action-button';
import { EmptyStateContainer } from './empty-state-container';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState, mapWritableState } from 'ui.vue3.pinia';
import { memberRoles } from 'humanresources.company-structure.api';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import { emptyStateTypes } from './empty-state-types';
import 'ui.buttons';
import './../../style.css';

export const EmployeeTab = {
	name: 'employeeTab',

	emits: ['editDepartmentUsers', 'showDetailLoader', 'hideDetailLoader'],

	components: { UserList, UserListActionButton, EmptyStateContainer },

	data(): Object
	{
		return {
			searchQuery: '',
			shouldUpdateList: true,
			selectedUserId: null,
			hasFocus: false,
		};
	},

	created(): void
	{
		this.loadEmployeesAction();
	},

	mounted(): void
	{
		this.tabContainer = this.$refs['tab-container'];
	},

	computed:
		{
			heads(): Array
			{
				return this.departments.get(this.focusedNode).heads ?? [];
			},
			headCount(): number
			{
				return this.heads.length ?? 0;
			},
			formattedHeads(): Array
			{
				return this.heads.map((head) => ({
					...head,
					subtitle: head.workPosition,
					badgeText: this.getBadgeText(head.role),
				})).sort((a, b) => {
					const roleOrder = {
						[memberRoles.head]: 1,
						[memberRoles.deputyHead]: 2,
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
						|| head.workPosition.toLowerCase().includes(this.searchQuery.toLowerCase()),
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
						|| employee.workPosition.toLowerCase().includes(this.searchQuery.toLowerCase()),
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
			showEmptyState(): boolean
			{
				if (!this.memberCount)
				{
					return true;
				}

				return this.filteredHeads.length === 0 && this.filteredEmployees.length === 0;
			},
			emptyStateType(): ?string
			{
				if (!this.memberCount && this.canAddUsers)
				{
					return emptyStateTypes.NO_MEMBERS_WITH_ADD_PERMISSION;
				}

				if (!this.memberCount)
				{
					return emptyStateTypes.NO_MEMBERS_WITHOUT_ADD_PERMISSION;
				}

				if (this.filteredHeads.length === 0 && this.filteredEmployees.length === 0)
				{
					return emptyStateTypes.NO_SEARCHED_USERS_RESULTS;
				}

				return null;
			},
			showSearchBar(): boolean
			{
				return this.memberCount > 0;
			},
			canAddUsers(): boolean
			{
				const permissionChecker = PermissionChecker.getInstance();
				if (!permissionChecker)
				{
					return false;
				}

				const nodeId = this.focusedNode;

				return permissionChecker.hasPermission(PermissionActions.employeeAddToDepartment, nodeId);
			},
			headListEmptyStateTitle(): string
			{
				if (this.canAddUsers)
				{
					return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_EMPTY_LIST_ITEM_TITLE');
				}

				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION');
			},
			employeesListEmptyStateTitle(): string
			{
				if (this.canAddUsers)
				{
					return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_EMPTY_LIST_ITEM_TITLE');
				}

				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_EMPTY_LIST_ITEM_TITLE_WITHOUT_ADD_PERMISSION');
			},
		},

	methods: {
		onDragStart(targetElement)
		{
			if (!targetElement.id)
			{
				return;
			}

			this.draggedEmployee = targetElement;
		},
		onDropToEmployee(targetIndex)
		{
			// @todo send order or new member to backend
			if (this.draggedEmployee)
			{
				if (this.draggedEmployee.role)
				{
					const movedEmployee = { ...this.draggedEmployee };
					delete movedEmployee.role;
					delete movedEmployee.badgeText;

					const index = this.heads.findIndex((head) => head.id === this.draggedEmployee.id);
					this.heads.splice(index, 1);
					this.employees.splice(targetIndex.id, 0, movedEmployee);
				}
				else
				{
					const index = this.employees.findIndex((employee) => employee && employee.id === this.draggedEmployee.id);
					const movedEmployee = this.employees.splice(index, 1)[0];
					this.employees.splice(targetIndex.id, 0, movedEmployee);
				}

				this.draggedEmployee = null;
				this.draggedIndex = null;
			}
		},
		onDropToHead(targetElement)
		{
			if (this.draggedEmployee)
			{
				if (this.draggedEmployee.role === 'MEMBER_HEAD')
				{
					const index = this.heads.findIndex((head) => head.id === this.draggedEmployee.id);
					const movedHead = this.heads.splice(index, 1)[0];
					this.heads.splice(targetElement.id, 0, movedHead);
				}
				else
				{
					const movedHead = { ...this.draggedEmployee, role: 'MEMBER_HEAD' };

					const index = this.employees.findIndex((employee) => employee && employee.id === this.draggedEmployee.id);
					this.employees.splice(index, 1);

					this.heads.splice(targetElement.id, 0, movedHead);
				}

				this.draggedEmployee = null;
				this.draggedIndex = null;
			}
		},
		loc(phraseCode, replacements = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		getBadgeText(role): ?string
		{
			if (role === memberRoles.head)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_HEAD_BADGE');
			}

			if (role === memberRoles.deputyHead)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPLOYEES_DEPUTY_HEAD_BADGE');
			}

			return null;
		},
		updateList(event): void
		{
			const employeesList = event.target;
			const scrollPosition = employeesList.scrollTop + employeesList.clientHeight;

			if (
				employeesList.scrollHeight - scrollPosition < 40
				&& !this.isLoading
			)
			{
				this.loadEmployeesAction();
			}
		},
		addToDepartment(options): void
		{
			AddUserDialog.openDialog(options);
		},
		userInvite(): void
		{
			const departmentToInvite = this.departments.get(this.focusedNode).accessCode.slice(1);

			BX.SidePanel.Instance.open(
				'/bitrix/services/main/ajax.php?action=getSliderContent'
				+ '&c=bitrix%3Aintranet.invitation&mode=ajax'
				+ `&departments[]=${departmentToInvite}&firstInvitationBlock=invite-with-group-dp`,
				{ cacheable: false, allowChangeHistory: false, width: 1100 },
			);
		},
		moveUser(): void
		{
			const nodeId = this.focusedNode;
			MoveUserFromDialog.openDialog(nodeId);
		},
		editDepartmentUsers(): void
		{
			this.$emit('editDepartmentUsers');
		},
		async loadEmployeesAction(): void
		{
			const nodeId = this.focusedNode;
			const department = this.departments.get(nodeId);

			if (!department)
			{
				return;
			}

			department.page = department.page ?? 0;
			department.shouldUpdateList = department.shouldUpdateList ?? true;

			if (!this.isListUpdated && department.page === 0 && department.shouldUpdateList === true)
			{
				this.$emit('showDetailLoader');
			}

			if (this.isListUpdated || !department.shouldUpdateList)
			{
				return;
			}

			this.isListUpdated = true;
			const page = department.page + 1;

			const loadedEmployees = await DepartmentAPI.getPagedEmployees(nodeId, page, 25);

			if (!loadedEmployees)
			{
				department.shouldUpdateList = false;
				this.isListUpdated = false;

				return;
			}

			department.employees = department.employees || [];
			const employeeIds = new Set(department.employees.map((employee) => employee.id));

			const newEmployees = loadedEmployees.reverse().filter((employee) => !employeeIds.has(employee.id));
			department.employees.unshift(...newEmployees);

			if (newEmployees.length > 0)
			{
				department.page = page;
				department.shouldUpdateList = true;
			}
			else
			{
				department.shouldUpdateList = false;
			}

			this.departments.set(nodeId, department);
			this.isListUpdated = false;
			this.$emit('hideDetailLoader');
		},
		async scrollToUser(): void
		{
			const userId = this.needToFocusUserId;
			this.needToFocusUserId = null;
			const selectors = `.hr-department-detail-content__user-container[data-id="${userId}"]`;
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

				if (user.role === memberRoles.head || user.role === memberRoles.deputyHead)
				{
					department.heads = department.heads ?? [];
					if (!department.heads.some((head) => head.id === user.id))
					{
						department.heads.push(user);
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

			setTimeout(() => {
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
		onBlur(): void
		{
			if (this.searchQuery.length === 0)
			{
				this.hasFocus = false;
			}
		},
		clearInput(): void
		{
			this.searchQuery = '';
			this.hasFocus = false;
		},
	},

	watch: {
		focusedNode(newId): void
		{
			const department = this.departments.get(newId) || {};
			if (!department.page)
			{
				department.page = department.page ?? 0;
				department.shouldUpdateList = department.shouldUpdateList ?? true;
				this.departments.set(newId, department);
				this.loadEmployeesAction();
			}
			this.isDescriptionExpanded = false;
			this.searchQuery = '';
		},
		searchedUserId: {
			handler(userId): void
			{
				if (!userId)
				{
					return;
				}

				this.needToFocusUserId = userId;
				if (!this.isListUpdated)
				{
					this.$nextTick(() => {
						this.scrollToUser();
					});
				}
			},
			immediate: true,
		},
		async searchQuery(newQuery) {
			await this.searchMembers(newQuery);
		},
	},

	template: `
		<div
			class="hr-department-detail-content__tab-container"
			ref="tab-container"
		>
			<div
				v-if="showSearchBar"
				class="hr-department-detail-content__content-search"
				:class="{'--focused': hasFocus}"
			>
				<div class="hr-department-detail-content__content-search-icon"/>
				<input
					v-model="searchQuery"
					class="hr-department-detail-content__content-search-input"
					:placeholder="!hasFocus ? loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_SEARCH_PLACEHOLDER') : ''"
					@focus="hasFocus = true"
					@blur="onBlur"
				>
				<div 
					class="hr-department-detail-content__content-search-close-button ui-icon-set --cross-circle-50"
					:class="{'--hide': !hasFocus}"
					style="--ui-icon-set__icon-size: 24px; --ui-icon-set__icon-color: #2FC6F6;"
					@click="clearInput"
				/>
			</div>
			<EmptyStateContainer
				v-if="emptyStateType"
				:type="emptyStateType"
				:departmentId="focusedNode"
				@addToDepartment="addToDepartment"
				@userInvite="userInvite"
				@moveUser="moveUser"
			/>
			<div
				v-else
				v-on="shouldUpdateList ? { scroll: updateList } : {}"
				class="hr-department-detail-content__lists-container"
			>
				<div class="hr-department-detail-content__list --head">
					<div class="hr-department-detail-content__list-header-container">
						<div class="hr-department-detail-content__list-title">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_HEAD_LIST_TITLE') }}
							<span class="hr-department-detail-content__list-count">{{ headCount }}</span>
						</div>
						<UserListActionButton
							role="head"
							@addToDepartment="addToDepartment({ type: 'head' })"
							@editDepartmentUsers="editDepartmentUsers"
							:departmentId="focusedNode"
						/>
					</div>
					<div v-if="!headCount" :class="['hr-department-detail-content__empty-list-item', { '--with-add': canAddUsers }]">
						<div class="hr-department-detail-content__empty-list-item-image"/>
						<div class="hr-department-detail-content__empty-list-item-content">
							<div class="hr-department-detail-content__empty-list-item-title">
								{{ headListEmptyStateTitle }}
							</div>
						</div>
					</div>
					<UserList
						:items="filteredHeads"
						@dragstart="onDragStart"
						@drop="onDropToHead"
						:selectedUserId="selectedUserId"
					/>
				</div>
				<div class="hr-department-detail-content__list --employee">
					<div class="hr-department-detail-content__list-header-container">
						<div class="hr-department-detail-content__list-title">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_USERS_EMPLOYEE_LIST_TITLE') }}
							<span class="hr-department-detail-content__list-count">{{ employeeCount }}</span>
						</div>
						<UserListActionButton
							role="employee"
							@addToDepartment="addToDepartment({ type: 'employee' })"
							@editDepartmentUsers="editDepartmentUsers"
							:departmentId="focusedNode"
						/>
					</div>
					<div v-if="!employeeCount" :class="['hr-department-detail-content__empty-list-item', { '--with-add': canAddUsers }]">
						<div class="hr-department-detail-content__empty-list-item-image"/>
						<div class="hr-department-detail-content__empty-list-item-content">
							<div class="hr-department-detail-content__empty-list-item-title">
								{{ employeesListEmptyStateTitle }}
							</div>
						</div>
					</div>
					<UserList
						:items="filteredEmployees"
						@dragstart="onDragStart"
						@drop="onDropToEmployee"
						:selectedUserId="selectedUserId"
					/>
				</div>
			</div>
		</div>
	`,
};
