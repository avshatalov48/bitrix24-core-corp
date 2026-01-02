import { PermissionChecker, PermissionActions } from 'humanresources.company-structure.permission-checker';
import { UserManagementDialogAPI } from 'humanresources.company-structure.user-management-dialog';
import { Runtime, Loc, Text } from 'main.core';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { UI } from 'ui.notification';
import { ButtonColor } from 'ui.buttons';
import { DepartmentAPI } from '../../../../department-content/src/api';
import { UrlProvidedParamsService } from '../../classes/url-provided-params-service';
import { TreeNode } from './tree-node/tree-node';
import { Connectors } from './connectors';
import { DragAndDrop } from './directives/drag-and-drop';
import { useChartStore, UserService } from 'humanresources.company-structure.chart-store';
import { MoveEmployeeConfirmationPopup, ConfirmationPopup } from 'humanresources.company-structure.structure-components';
import { mapState } from 'ui.vue3.pinia';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import { events } from '../../consts';
import { chartAPI } from '../../api';
import { sendData as analyticsSendData } from 'ui.analytics';
import { OrgChartActions } from '../../actions';
import type { MountedDepartment, TreeData, DragAndDropData } from '../../types';
import './style.css';

// @vue/component
export const Tree = {
	name: 'companyTree',

	components: {
		TreeNode,
		Connectors,
		MoveEmployeeConfirmationPopup,
		ConfirmationPopup,
	},

	directives: { dnd: DragAndDrop },

	provide(): { [key: string]: Function }
	{
		return {
			getTreeBounds: () => this.getTreeBounds(),
		};
	},

	props: {
		canvasTransform: {
			type: Object,
			required: true,
		},
	},

	emits: ['moveTo', 'showWizard', 'controlDetail'],

	data(): TreeData
	{
		return {
			expandedNodes: [],
			isLocatedDepartmentVisible: false,
			isLoaded: false,
			userDropTargetNodeId: null,
			isUserDropAllowed: false,
			draggedUserData: null,
			showDndConfirmationPopup: false,
			showDndErrorPopup: false,
			dndErrorPopupDescription: '',
			dndUserMoveContext: null,
			isCombineOnly: false,
		};
	},

	computed:
	{
		dndTargetEntityType(): string
		{
			return this.targetDepartment.entityType || '';
		},
		dndSourceEntityType(): string
		{
			return this.departments.get(this.focusedNode)?.entityType || '';
		},
		rootId(): number
		{
			const { id: rootId } = [...this.departments.values()].find((department) => {
				return department.parentId === 0;
			});

			return rootId;
		},
		connectors(): Connectors
		{
			return this.$refs.connectors;
		},
		targetDepartment(): ?Object
		{
			if (!this.dndUserMoveContext?.targetDepartmentId)
			{
				return null;
			}

			return this.departments.get(this.dndUserMoveContext.targetDepartmentId);
		},
		isTeamTarget(): string
		{
			return this.targetDepartment?.entityType === EntityTypes.team;
		},
		dndPopupDescription(): string
		{
			const phrase = this.isTeamTarget
				? 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_DESC_ROLE_TEAM'
				: 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_CONFIRM_POPUP_DESC_ROLE_DEPARTMENT'
			;

			return Loc.getMessage(phrase, {
				'#USER_NAME#': Text.encode(this.dndUserMoveContext.user.name),
				'#DEPARTMENT_NAME#': Text.encode(this.targetDepartment.name),
				'[link]': `<a class="hr-department-detail-content__move-user-department-user-link" href="${this.dndUserMoveContext.user.url}">`,
				'[/link]': '</a>',
			});
		},
		...mapState(useChartStore, ['currentDepartments', 'userId', 'focusedNode', 'departments']),
	},

	created(): void
	{
		this.treeNodes = new Map();
		this.subscribeOnEvents();
		this.loadHeads([this.rootId]);
	},

	mounted(): void
	{
		const departmentToFocus = this.getDepartmentIdForInitialFocus();
		this.currentDepartmentsLocated = [departmentToFocus];

		if (departmentToFocus !== this.rootId)
		{
			this.expandDepartmentParents(departmentToFocus);
			this.focus(departmentToFocus, { expandAfterFocus: true });

			return;
		}

		this.expandLowerDepartments();
		this.focus(departmentToFocus);
	},

	beforeUnmount(): void
	{
		this.unsubscribeFromEvents();
	},

	methods:
	{
		getDepartmentIdForInitialFocus(): ?number
		{
			const providedFocusNodeId = UrlProvidedParamsService.getParams().focusNodeId;
			if (providedFocusNodeId)
			{
				const node = this.departments.get(providedFocusNodeId);
				if (node)
				{
					return providedFocusNodeId;
				}
			}

			for (const currentDepartmentId: number of this.currentDepartments)
			{
				const node = this.departments.get(currentDepartmentId);

				if (node.entityType === EntityTypes.department)
				{
					return currentDepartmentId;
				}
			}

			return this.rootId;
		},
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		getTreeBounds(): DOMRect
		{
			return this.$el.getBoundingClientRect();
		},
		onConnectDepartment({ data }: MountedDepartment): void
		{
			const { id, html } = data;
			this.treeNodes.set(id, html);
			const departmentIdToFocus = this.getDepartmentIdForInitialFocus();
			if (id === departmentIdToFocus && !this.isLoaded) // zoom to  department when loading
			{
				const movingDelay = 1800;
				this.isLoaded = true;
				Runtime.debounce(() => {
					this.moveTo(departmentIdToFocus);
				}, movingDelay)();
			}
		},
		onDisconnectDepartment({ data }: MountedDepartment): void
		{
			const { id } = data;
			const department = this.departments.get(id);
			delete department.prevParentId;
			if (!department.parentId)
			{
				OrgChartActions.removeDepartment(id);
			}
		},
		onDragEntity({ data }: BaseEvent): void
		{
			const { draggedId } = data;
			if (this.expandedNodes.includes(draggedId))
			{
				this.collapseRecursively(draggedId);
			}
		},
		onToggleConnectors({ data }: BaseEvent): void
		{
			const { shouldShowElements } = data;
			this.connectors.toggleAllConnectorsVisibility(shouldShowElements, this.expandedNodes);
		},
		async changeOrder(data: DragAndDropData): Promise<void>
		{
			const { draggedId, targetId, affectedItems, direction } = data;
			const promise = OrgChartActions.orderDepartments(draggedId, targetId, direction, affectedItems.length);
			const fullDepartmentWidth = 302;
			const draggedShift = affectedItems.length * direction * fullDepartmentWidth;
			this.connectors.adaptConnectorsAfterReorder([draggedId], draggedShift, true);
			const affectedShift = -direction * fullDepartmentWidth;
			this.connectors.adaptConnectorsAfterReorder(affectedItems, affectedShift, true);
			const ordered = await promise;
			if (!ordered)
			{
				this.connectors.adaptConnectorsAfterReorder([draggedId], -draggedShift, true);
				this.connectors.adaptConnectorsAfterReorder(affectedItems, -affectedShift, true);
			}
		},
		async updateParent(data: DragAndDropData): Promise<void>
		{
			const { draggedId, targetId, targetIndex, insertion } = data;
			const { id, parentId } = this.departments.get(draggedId);
			if (parentId === targetId && insertion === 'inside')
			{
				return;
			}

			let updateParentPromise = null;
			const store = useChartStore();
			if (insertion === 'sibling-left' || insertion === 'sibling-right')
			{
				const { parentId: targetParentId } = this.departments.get(targetId);
				const position = insertion === 'sibling-left' ? targetIndex : targetIndex + 1;
				store.updateDepartment({ id, parentId: targetParentId }, position);
				updateParentPromise = chartAPI.updateDepartment(draggedId, targetParentId);
			}
			else
			{
				store.updateDepartment({ id, parentId: targetId });
				updateParentPromise = chartAPI.updateDepartment(draggedId, targetId);
			}

			this.locateToDepartment(draggedId);
			try
			{
				await updateParentPromise;
				if (insertion === 'sibling-left' || insertion === 'sibling-right')
				{
					const { parentId: targetParentId } = this.departments.get(targetId);
					const { children } = this.departments.get(targetParentId);
					const shift = insertion === 'sibling-left' ? 1 : 2;
					const affectedCount = children.length - targetIndex - shift;
					await chartAPI.changeOrder(draggedId, -1, affectedCount);
				}
			}
			catch (err)
			{
				console.error(err);
			}
		},
		onDropEntity({ data }: BaseEvent): void
		{
			const { insertion } = data;
			if (insertion === 'reorder')
			{
				this.changeOrder(data);

				return;
			}

			this.updateParent(data);
		},
		onPublicFocusNode({ data }: BaseEvent): void
		{
			const { nodeId } = data;

			const node = this.departments.get(nodeId);
			if (!node)
			{
				return;
			}

			void this.locateToDepartment(nodeId);
		},
		collapse(nodeId: number): void
		{
			this.expandedNodes = this.expandedNodes.filter((expandedId) => expandedId !== nodeId);
			this.connectors.toggleConnectorsVisibility(nodeId, false);
			this.connectors.toggleConnectorHighlighting(nodeId, false);
		},
		collapseRecursively(nodeId: number): void
		{
			const deepCollapse = (id: number) => {
				this.collapse(id);
				const node = this.departments.get(id);
				node.children?.forEach((childId) => {
					if (this.expandedNodes.includes(childId))
					{
						deepCollapse(childId);
					}
				});
			};
			const { parentId } = this.departments.get(nodeId);
			const expandedNode = this.expandedNodes.find((id) => {
				const node = this.departments.get(id);

				return node.parentId === parentId;
			});
			if (expandedNode)
			{
				deepCollapse(expandedNode);
			}
		},
		expand(departmentId: number, options: { isManual?: boolean } = {}): void
		{
			const { isManual = false } = options;

			this.collapseRecursively(departmentId);
			this.expandedNodes = [...this.expandedNodes, departmentId];
			this.connectors.toggleConnectorsVisibility(departmentId, true);
			this.connectors.toggleConnectorHighlighting(departmentId, true);
			const department = this.departments.get(departmentId);
			const childrenWithoutHeads = department.children.filter((childId) => {
				return !this.departments.get(childId).heads;
			});
			if (childrenWithoutHeads.length > 0)
			{
				this.loadHeads(childrenWithoutHeads);
			}

			if (isManual)
			{
				analyticsSendData({ tool: 'structure', category: 'structure', event: 'expand_department' });
			}
		},
		focus(nodeId: number, options: Object = {}): void
		{
			const { expandAfterFocus = false, showEmployees = false, subdivisionsSelected = false } = options;
			const hasChildren = this.departments.get(nodeId).children?.length > 0;

			let shouldExpand = expandAfterFocus || !this.expandedNodes.includes(nodeId);
			if (showEmployees)
			{
				shouldExpand = this.expandedNodes.includes(nodeId);
			}

			if (subdivisionsSelected || !hasChildren)
			{
				this.collapseRecursively(nodeId);
			}

			if (hasChildren && shouldExpand)
			{
				this.expand(nodeId, { isManual: true });
			}

			if (this.focusedNode && !this.expandedNodes.includes(this.focusedNode))
			{
				this.connectors.toggleConnectorHighlighting(this.focusedNode, false);
			}

			OrgChartActions.focusDepartment(nodeId);
			this.connectors.toggleConnectorHighlighting(this.focusedNode, true);
		},
		onFocusDepartment({ data }: { nodeId: number }): void
		{
			const { nodeId, showEmployees, subdivisionsSelected } = data;
			this.focus(nodeId, { showEmployees, subdivisionsSelected });
			this.$emit('controlDetail', {
				showEmployees,
				preventSwitch: subdivisionsSelected,
			});
		},
		tryRemoveDepartment(nodeId: number, entityType: string): void
		{
			const localizationMap = {
				team: {
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_TEAM_TITLE'),
					message: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_TEAM_MESSAGE'),
					success: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_TEAM_REMOVED'),
					error: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_TEAM_ERROR'),
				},
				default: {
					title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_TITLE'),
					message: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_MESSAGE'),
					success: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_REMOVED'),
					error: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_ERROR'),
				},
			};
			const mapIndex = entityType === EntityTypes.team ? 'team' : 'default';
			const messageBox = MessageBox.create({
				title: localizationMap[mapIndex].title,
				message: localizationMap[mapIndex].message,
				buttons: MessageBoxButtons.OK_CANCEL,
				onOk: async (dialog: MessageBox) => {
					try
					{
						await this.removeDepartment(nodeId);
						UI.Notification.Center.notify({
							content: localizationMap[mapIndex].success,
							autoHideDelay: 2000,
						});
						dialog.close();
					}
					catch
					{
						UI.Notification.Center.notify({
							content: localizationMap[mapIndex].error,
							autoHideDelay: 2000,
						});
					}
				},
				onCancel: (dialog: MessageBox) => dialog.close(),
				minWidth: 250,
				maxWidth: 320,
				minHeight: 175,
				okCaption: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_OK_CAPTION'),
				popupOptions: { className: 'humanresources-tree__message-box', overlay: { opacity: 40 } },
			});
			const okButton = messageBox.getOkButton();
			const cancelButton = messageBox.getCancelButton();
			okButton.setRound(true);
			cancelButton.setRound(true);
			okButton.setColor(ButtonColor.DANGER);
			cancelButton.setColor(ButtonColor.LIGHT_BORDER);
			messageBox.show();
		},
		async removeDepartment(nodeId: number): Promise<void>
		{
			const store = useChartStore();
			store.updateChatsInChildrenNodes(nodeId);

			await chartAPI.removeDepartment(nodeId);
			const removableDepartment = this.departments.get(nodeId);
			const { parentId, children: removableDepartmentChildren = [] } = removableDepartment;
			if (removableDepartmentChildren.length > 0)
			{
				this.collapse(nodeId);
			}

			OrgChartActions.moveSubordinatesToParent(nodeId);
			await this.$nextTick();
			OrgChartActions.markDepartmentAsRemoved(nodeId);
			this.focus(parentId, { expandAfterFocus: true });
			this.moveTo(parentId);
		},
		expandDepartmentParents(nodeId: number): void
		{
			let { parentId } = this.departments.get(nodeId);
			while (parentId)
			{
				if (!this.expandedNodes.includes(parentId))
				{
					this.expand(parentId);
				}

				parentId = this.departments.get(parentId).parentId;
			}
		},
		expandLowerDepartments(): void
		{
			let expandLevel = 0;
			const expandRecursively = (departmentId: number) => {
				const { children = [] } = this.departments.get(departmentId);
				if (expandLevel === 4 || children.length === 0)
				{
					return;
				}

				this.expand(departmentId);
				expandLevel += 1;
				const middleBound = Math.trunc(children.length / 2);
				const childId = children[middleBound];
				if (this.departments.get(childId).children?.length > 0)
				{
					expandRecursively(childId);

					return;
				}

				for (let i = middleBound - 1; i >= 0; i--)
				{
					if (traverseSibling(children[i]))
					{
						return;
					}
				}

				for (let i = middleBound + 1; i < children.length; i++)
				{
					if (traverseSibling(children[i]))
					{
						return;
					}
				}
			};

			const traverseSibling = (siblingId: number) => {
				const { children: currentChildren = [] } = this.departments.get(siblingId);

				if (currentChildren.length > 0)
				{
					expandRecursively(siblingId);

					return true;
				}

				return false;
			};
			expandRecursively(this.rootId);
		},
		async locateToCurrentDepartment(): Promise<void>
		{
			// currentDepartmentsLocated manipulation needed to cycle through current departments
			if (this.currentDepartmentsLocated.length === this.currentDepartments.length)
			{
				this.currentDepartmentsLocated = [];
			}
			const currentDepartment = this.currentDepartments.find((item) => !this.currentDepartmentsLocated.includes(item));
			if (!currentDepartment)
			{
				return;
			}

			this.currentDepartmentsLocated.push(currentDepartment);
			await this.locateToDepartment(currentDepartment);
			OrgChartActions.searchUserInDepartment(this.userId);
		},
		async locateToDepartment(nodeId: number): Promise<void>
		{
			this.isLocatedDepartmentVisible = false;
			this.expandDepartmentParents(nodeId);
			this.focus(nodeId, { expandAfterFocus: true });
			// $nextTick makes sure that this.getTreeBounds() returns correct value when nodeId is not visible
			await this.$nextTick();
			this.isLocatedDepartmentVisible = true;
			await this.moveTo(nodeId);
		},
		async moveTo(nodeId: number): Promise<void>
		{
			await this.$nextTick();
			const treeRect = this.getTreeBounds();
			const centerX = treeRect.x + treeRect.width / 2;
			const centerY = treeRect.y + treeRect.height / 2;
			const treeNode = this.treeNodes.get(nodeId);
			const treeNodeRect = treeNode.getBoundingClientRect();
			this.$emit('moveTo', {
				x: centerX - treeNodeRect.x - treeNodeRect.width / 2,
				y: centerY - treeNodeRect.y - treeNodeRect.height / 2,
				nodeId,
			});
		},
		loadHeads(departmentIds: number[]): void
		{
			const store = useChartStore();
			store.loadHeads(departmentIds);
		},
		subscribeOnEvents(): void
		{
			this.events = {
				[events.HR_DEPARTMENT_CONNECT]: this.onConnectDepartment,
				[events.HR_DEPARTMENT_DISCONNECT]: this.onDisconnectDepartment,
				[events.HR_DEPARTMENT_FOCUS]: this.onFocusDepartment,
				[events.HR_DRAG_ENTITY]: this.onDragEntity,
				[events.HR_DROP_ENTITY]: this.onDropEntity,
				[events.HR_ENTITY_TOGGLE_ELEMENTS]: this.onToggleConnectors,
				[events.HR_PUBLIC_FOCUS_NODE]: this.onPublicFocusNode,
				[events.HR_USER_DRAG_START]: this.onUserDragStart,
				[events.HR_USER_DRAG_DROP]: this.onUserDragDrop,
				[events.HR_USER_DRAG_END]: this.onUserDragEnd,
				[events.HR_USER_DRAG_ENTER_NODE]: this.onUserDragEnterNode,
				[events.HR_USER_DRAG_LEAVE_NODE]: this.onUserDragLeaveNode,
			};
			Object.entries(this.events).forEach(([event, handle]) => {
				EventEmitter.subscribe(event, handle);
			});
		},
		unsubscribeFromEvents(): void
		{
			Object.entries(this.events).forEach(([event, handle]) => {
				EventEmitter.unsubscribe(event, handle);
			});
		},
		onUserDragStart({ data }): void
		{
			this.draggedUserData = data.user;
		},
		onUserDragEnterNode({ data }): void
		{
			if (!this.draggedUserData)
			{
				return;
			}

			const hoveredNodeId = data.nodeId;
			const sourceDepartmentId = this.focusedNode;

			if (hoveredNodeId === sourceDepartmentId)
			{
				this.userDropTargetNodeId = hoveredNodeId;
				this.isUserDropAllowed = false;

				return;
			}

			this.userDropTargetNodeId = hoveredNodeId;
			const movePermissions = this.canMoveUser(sourceDepartmentId, hoveredNodeId);
			this.isUserDropAllowed = movePermissions.isAllowed;
			this.isCombineOnly = movePermissions.combineOnly;
		},
		onUserDragLeaveNode({ data }): void
		{
			if (!this.draggedUserData)
			{
				return;
			}

			if (this.userDropTargetNodeId === data.nodeId)
			{
				this.userDropTargetNodeId = null;
				this.isUserDropAllowed = false;
			}
		},
		async onUserDragDrop(): Promise<void>
		{
			if (!this.userDropTargetNodeId || !this.isUserDropAllowed)
			{
				return;
			}

			this.dndUserMoveContext = {
				user: this.draggedUserData,
				sourceDepartmentId: this.focusedNode,
				targetDepartmentId: this.userDropTargetNodeId,
				sourceRole: this.draggedUserData.role,
			};

			this.showDndConfirmationPopup = true;
		},
		async confirmUserMove(payload: {
			role: string;
			roleLabel: string;
			isCombineMode: boolean;
			badgeText: string;
		}): Promise<void>
		{
			this.showDndConfirmationPopup = false;
			const { user } = this.dndUserMoveContext;

			const userInTarget = (this.targetDepartment?.heads ?? []).find((u) => u.id === user.id)
				|| (this.targetDepartment?.employees ?? []).find((u) => u.id === user.id);
			const isAlreadyInTarget = Boolean(userInTarget);
			const isRoleTheSame = isAlreadyInTarget && (userInTarget.role === payload.role);

			if (isAlreadyInTarget && isRoleTheSame)
			{
				this.displayDndErrorPopup(user);
				this.dndUserMoveContext = null;

				return;
			}

			try
			{
				if (payload.isCombineMode)
				{
					await this.handleCombineUser(this.dndUserMoveContext, payload);
				}
				else
				{
					await this.handleMoveUser(this.dndUserMoveContext, payload, isAlreadyInTarget);
				}

				const phrase = this.isTeamTarget
					? 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_MOVED_SUCCESS_TEAM'
					: 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_MOVED_SUCCESS_DEPARTMENT'
				;
				UI.Notification.Center.notify({
					content: this.loc(phrase, {
						'#USER_NAME#': Text.encode(user.name),
						'#DEPARTMENT_NAME#': Text.encode(this.targetDepartment?.name),
						'#USER_ROLE#': Text.encode(payload.roleLabel),
					}),
				});
			}
			catch (error)
			{
				console.error(error);
			}

			this.dndUserMoveContext = null;
		},
		async handleCombineUser(dndContext, payload): Promise<void>
		{
			const { user, targetDepartmentId } = dndContext;
			const data = await UserManagementDialogAPI.addUsersToDepartment(targetDepartmentId, [user.id], payload.role);

			UserService.addUsersToEntity(
				targetDepartmentId,
				[{ ...user, role: payload.role, badgeText: payload.badgeText ?? null }],
				data.userCount,
				payload.role,
			);
		},
		async handleMoveUser(dndContext, payload, isAlreadyInTarget): Promise<void>
		{
			const { user, sourceDepartmentId, targetDepartmentId, sourceRole } = dndContext;
			const targetDepartment = this.departments.get(targetDepartmentId);

			if (isAlreadyInTarget)
			{
				await UserManagementDialogAPI.addUsersToDepartment(targetDepartmentId, [user.id], payload.role);
				await DepartmentAPI.removeUserFromDepartment(sourceDepartmentId, user.id);
			}
			else
			{
				await DepartmentAPI.moveUserToDepartment(
					sourceDepartmentId,
					user.id,
					targetDepartmentId,
					payload.role,
				);
			}

			const finalUserCount = targetDepartment.userCount + (isAlreadyInTarget ? 0 : 1);
			UserService.removeUserFromEntity(sourceDepartmentId, user.id, sourceRole);
			UserService.addUsersToEntity(
				targetDepartmentId,
				[{ ...user, role: payload.role, badgeText: payload.badgeText ?? null }],
				finalUserCount,
				payload.role,
			);
		},
		displayDndErrorPopup(user): void
		{
			const phrase = this.isTeamTarget
				? 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_ERROR_POPUP_DESC_TEAM'
				: 'HUMANRESOURCES_COMPANY_STRUCTURE_DND_ERROR_POPUP_DESC_DEPARTMENT'
			;
			this.dndErrorPopupDescription = Loc.getMessage(phrase, {
				'#USER_NAME#': Text.encode(user.name),
				'#DEPARTMENT_NAME#': Text.encode(this.targetDepartment?.name),
				'[link]': `<a class="hr-department-detail-content__move-user-department-user-link" href="${user.url}">`,
				'[/link]': '</a>',
			});

			this.showDndErrorPopup = true;
		},
		cancelUserMove(): void
		{
			if (!this.dndUserMoveContext)
			{
				return;
			}

			this.showDndConfirmationPopup = false;
			this.dndUserMoveContext = null;
		},
		closeDndErrorPopup(): void
		{
			this.showDndErrorPopup = false;
			this.dndErrorPopupDescription = '';
			this.dndUserMoveContext = null;
		},
		onUserDragEnd(): void
		{
			this.userDropTargetNodeId = null;
			this.isUserDropAllowed = false;
			this.draggedUserData = null;
		},
		canMoveUser(sourceDepartmentId: number, targetDepartmentId: number): { isAllowed: boolean; combineOnly: boolean }
		{
			const permissionChecker = PermissionChecker.getInstance();
			const source = this.departments.get(sourceDepartmentId);
			const target = this.departments.get(targetDepartmentId);

			if (!source || !target)
			{
				return { isAllowed: false, combineOnly: false };
			}

			const isTeamToDept = source.entityType === EntityTypes.team && target.entityType === EntityTypes.department;
			if (isTeamToDept)
			{
				return { isAllowed: false, combineOnly: false };
			}

			const sourcePermission = source.entityType === EntityTypes.team
				? PermissionActions.teamRemoveMember
				: PermissionActions.employeeRemoveFromDepartment;
			const canTakeFromSource = permissionChecker.hasPermission(sourcePermission, sourceDepartmentId);

			const targetPermission = this.isTeamTarget
				? PermissionActions.teamAddMember
				: PermissionActions.employeeAddToDepartment;
			const canPutToTarget = permissionChecker.hasPermission(targetPermission, targetDepartmentId);

			if (!canPutToTarget)
			{
				return { isAllowed: false, combineOnly: false };
			}

			const isDeptToTeam = source.entityType === EntityTypes.department && target.entityType === EntityTypes.team;
			if (isDeptToTeam)
			{
				return { isAllowed: true, combineOnly: true };
			}

			return { isAllowed: true, combineOnly: !canTakeFromSource };
		},
	},

	template: `
		<div
			class="humanresources-tree"
			v-if="departments.size > 0"
			v-dnd="canvasTransform"
		>
			<TreeNode
				class="--root"
				:key="rootId"
				:nodeId="rootId"
				:expandedNodes="[...expandedNodes]"
				:canvasZoom="canvasTransform.zoom"
				:currentDepartments="currentDepartments"
				:userDropTargetNodeId="userDropTargetNodeId"
				:isUserDropAllowed="isUserDropAllowed"
				:isDraggingUser="!!draggedUserData"
			></TreeNode>
			<Connectors
				ref="connectors"
				:isLocatedDepartmentVisible="isLocatedDepartmentVisible"
				:treeNodes="treeNodes"
			></Connectors>
			<MoveEmployeeConfirmationPopup
				v-if="showDndConfirmationPopup"
				:description="dndPopupDescription"
				:showRoleSelect="true"
				:showCombineCheckbox="true"
				:isCombineOnly="isCombineOnly"
				:targetType="dndTargetEntityType"
				:sourceType="dndSourceEntityType"
				@confirm="confirmUserMove"
				@close="cancelUserMove"
			/>
			<ConfirmationPopup
				v-if="showDndErrorPopup"
				:withoutTitleBar = true
				:onlyConfirmButtonMode = true
				:confirmBtnText = "loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_USER_ACTION_MENU_MOVE_TO_ANOTHER_DEPARTMENT_ALREADY_BELONGS_TO_DEPARTMENT_CLOSE_BUTTON')"
				:width="300"
				@action="closeDndErrorPopup"
				@close="closeDndErrorPopup"
			>
				<template v-slot:content>
					<div class="hr-department-detail-content__user-action-text-container">
						<div
							class="hr-department-detail-content__user-belongs-to-department-text-container"
							v-html="dndErrorPopupDescription"
						/>
					</div>
				</template>
			</ConfirmationPopup>
		</div>
	`,
};
