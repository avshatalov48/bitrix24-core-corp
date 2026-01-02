import { Dom, Loc } from 'main.core';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { getMemberRoles, type MemberRolesType } from 'humanresources.company-structure.api';
import { Hint } from 'humanresources.company-structure.structure-components';
import { EventEmitter } from 'main.core.events';
import { events } from '../../../consts';
import { EntityTypes } from 'humanresources.company-structure.utils';
import { HeadList } from './head-list';
import { DepartmentMenuButton } from './department-menu-button';
import { DepartmentInfoIconButton } from './department-info-icon-button';
import { SubdivisionAddButton } from './subdivision-add-button';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';
import type { MountedDepartment, TreeItem, TreeNodeData } from '../../../types';
import './style.css';

// @vue/component
export const TreeNode = {
	name: 'treeNode',

	components: {
		DepartmentMenuButton,
		HeadList,
		SubdivisionAddButton,
		DepartmentInfoIconButton,
	},

	directives: { hint: Hint },

	inject: ['getTreeBounds'],

	props: {
		nodeId: {
			type: Number,
			required: true,
		},
		expandedNodes: {
			type: Array,
			required: true,
		},
		canvasZoom: {
			type: Number,
			required: true,
		},
		currentDepartments: {
			type: Array,
			required: true,
		},
		isShown: {
			type: Boolean,
			required: false,
			default: true,
		},
		userDropTargetNodeId: {
			type: Number,
			default: null,
		},
		isUserDropAllowed: {
			type: Boolean,
			default: false,
		},
		isDraggingUser: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['calculatePosition'],

	data(): TreeNodeData
	{
		return {
			childrenOffset: 0,
			childrenMounted: false,
			showInfo: true,
			showDnd: true,
		};
	},

	computed:
	{
		memberRoles(): MemberRolesType
		{
			return getMemberRoles(this.nodeData.entityType);
		},
		nodeData(): TreeItem
		{
			return this.departments.get(this.nodeId);
		},
		nodeClass(): { [key: string]: boolean; }
		{
			const isDragSource = this.isDraggingUser && this.nodeId === this.focusedNode;

			return {
				'--expanded': this.expandedNodes.includes(this.nodeId),
				'--current-department': this.isCurrentDepartment,
				'--focused': this.focusedNode === this.nodeId,
				'--with-restricted-access-rights': !this.showInfo,
				'--team': this.isTeamEntity,
				'--drag-source': isDragSource,
			};
		},
		subdivisionsClass(): { [key: string]: boolean; }
		{
			return {
				'humanresources-tree__node_arrow': this.hasChildren,
				'--up': this.hasChildren && this.isExpanded,
				'--down': this.hasChildren && !this.isExpanded,
				'--transparent': !this.hasChildren,
			};
		},
		hasChildren(): boolean
		{
			return this.nodeData.children?.length > 0;
		},
		isExpanded(): boolean
		{
			return this.expandedNodes.includes(this.nodeId);
		},
		isCurrentDepartment(): Boolean
		{
			return this.currentDepartments.includes(this.nodeId);
		},
		head(): ?TreeItem['heads']
		{
			return this.nodeData.heads?.filter((head) => {
				return head.role === this.memberRoles.head;
			});
		},
		deputy(): ?TreeItem['heads']
		{
			return this.nodeData.heads?.filter((head) => {
				return head.role === this.memberRoles.deputyHead;
			});
		},
		employeeCount(): number
		{
			return this.nodeData.userCount - this.nodeData.heads.length;
		},
		childNodeStyle(): { left: String; }
		{
			return { left: `${this.childrenOffset}px` };
		},
		showSubdivisionAddButton(): boolean
		{
			return this.expandedNodes.includes(this.nodeId) || this.focusedNode === this.nodeId;
		},
		isTeamEntity(): boolean
		{
			return this.nodeData.entityType === EntityTypes.team;
		},
		nodeDataTitle(): ?string
		{
			if (!this.isCurrentDepartment)
			{
				return null;
			}

			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_CURRENT_TEAM')
				: this.loc('HUMANRESOURCES_COMPANY_CURRENT_DEPARTMENT');
		},
		subdivisionsText(): string
		{
			if (this.isTeamEntity)
			{
				return this.nodeData.children?.length
					? this.locPlural('HUMANRESOURCES_COMPANY_TEAM_CHILDREN_COUNT', this.nodeData.children.length)
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_TEAM_NO_SUBDEPARTMENTS');
			}

			if (!(this.nodeData.children?.length))
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_NO_SUBDEPARTMENTS');
			}

			const childDepartmentsCount = [...this.departments.values()].filter((department) => {
				return department.entityType === EntityTypes.department && this.nodeData.children.includes(department.id);
			}).length;

			const childTeamsCount = [...this.departments.values()].filter((department) => {
				return department.entityType === EntityTypes.team && this.nodeData.children.includes(department.id);
			}).length;

			if ((childTeamsCount > 0) && (childDepartmentsCount > 0))
			{
				return this.loc('HUMANRESOURCES_COMPANY_DEPARTMENT_CHILDREN_COUNT_WITH_CONJUNCTION', {
					'#DEPT_COUNT#': this.locPlural('HUMANRESOURCES_COMPANY_DEPARTMENT_CHILDREN_COUNT', childDepartmentsCount),
					'#TEAM_COUNT#': this.locPlural('HUMANRESOURCES_COMPANY_TEAM_CHILDREN_COUNT', childTeamsCount),
				});
			}

			if (childDepartmentsCount > 0)
			{
				return this.locPlural('HUMANRESOURCES_COMPANY_DEPARTMENT_CHILDREN_COUNT', childDepartmentsCount);
			}

			if (childTeamsCount > 0)
			{
				return this.locPlural('HUMANRESOURCES_COMPANY_TEAM_CHILDREN_COUNT', childTeamsCount);
			}

			return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_NO_SUBDEPARTMENTS');
		},
		deputyTitle(): string
		{
			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_TEAM_DEPUTY_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_DEPUTY_TITLE')
			;
		},
		dropOverlayText(): string
		{
			const sourceNode = this.departments.get(this.focusedNode);

			if (
				sourceNode.entityType === EntityTypes.team
				&& this.nodeData.entityType === EntityTypes.department
			)
			{
				return this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_MOVED_FORBIDDEN');
			}

			return this.isUserDropAllowed
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_MOVED_APPROVED')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DND_USER_MOVED_NO_RIGHTS')
			;
		},
		showOverlay(): boolean
		{
			return this.nodeId === this.userDropTargetNodeId && this.nodeId !== this.focusedNode;
		},
		...mapState(useChartStore, ['departments', 'focusedNode']),
	},

	watch:
	{
		head(): void
		{
			this.adaptHeight();
		},
		isShown(): void
		{
			this.adaptHeight();
		},
		isExpanded:
		{
			handler(isExpanded: boolean): void
			{
				if (isExpanded && !this.childrenMounted)
				{
					this.childrenMounted = true;
				}
			},
			immediate: true,
		},
	},

	created(): void
	{
		this.width = 278;
		this.gap = 24;
		this.prevChildrenOffset = 0;
		this.prevHeight = 0;
	},

	async mounted(): Promise<void>
	{
		let editAction = '';
		let viewAction = '';
		if (this.isTeamEntity)
		{
			viewAction = PermissionActions.teamView;
			editAction = PermissionActions.teamEdit;
		}
		else
		{
			viewAction = PermissionActions.structureView;
			editAction = PermissionActions.departmentEdit;
		}

		const permissionChecker = PermissionChecker.getInstance();
		this.showInfo = permissionChecker.hasPermission(viewAction, this.nodeId);
		this.showDnd =	permissionChecker.hasPermission(editAction, this.nodeId)
			&& permissionChecker.hasPermission(editAction, this.nodeData.parentId);

		this.$emit('calculatePosition', this.nodeId);
		await this.$nextTick();
		this.prevHeight = this.$el.offsetHeight;
		EventEmitter.emit(events.HR_DEPARTMENT_CONNECT, {
			id: this.nodeId,
			parentId: this.nodeData.parentId,
			html: this.$el,
			parentsPath: this.getParentsPath(this.nodeData.parentId),
			...this.calculateNodePoints(),
		});
	},
	unmounted(): void
	{
		Dom.remove(this.$el);
		const { prevParentId } = this.nodeData;
		if (!prevParentId)
		{
			return;
		}

		this.$emit('calculatePosition', this.nodeId);
		EventEmitter.emit(events.HR_DEPARTMENT_DISCONNECT, {
			id: this.nodeId,
			parentId: prevParentId,
		});
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onDepartmentClick(targetId: string): void
		{
			if (!this.showInfo)
			{
				return;
			}

			EventEmitter.emit(events.HR_DEPARTMENT_FOCUS, {
				nodeId: this.nodeId,
				showEmployees: targetId === 'employees',
				subdivisionsSelected: targetId === 'subdivisions',
			});
		},
		calculatePosition(nodeId: number): void
		{
			const node = this.departments.get(this.nodeId);

			if (node.children.length === 0)
			{
				this.childrenOffset = 0;
			}
			else
			{
				const gap = this.gap * (node.children.length - 1);
				this.prevChildrenOffset = this.childrenOffset;
				this.childrenOffset = (this.width - (this.width * node.children.length + gap)) / 2;
			}

			const offset = this.childrenOffset - this.prevChildrenOffset;
			if (offset !== 0)
			{
				EventEmitter.emit(events.HR_DEPARTMENT_ADAPT_SIBLINGS, {
					parentId: this.nodeId,
					nodeId,
					offset,
				});
			}
		},
		locPlural(phraseCode: string, count: number): string
		{
			return Loc.getMessagePlural(phraseCode, count, { '#COUNT#': count });
		},
		calculateNodePoints(): { startPoint: MountedDepartment['startPoint'], endPoint: MountedDepartment['endPoint'] }
		{
			const { left, top, width } = this.$el.getBoundingClientRect();
			const { $el: parentNode } = this.$parent.$parent;
			const {
				left: parentLeft,
				top: parentTop,
				width: parentWidth,
				height: parentHeight,
			} = parentNode.getBoundingClientRect();
			const { x: chartX, y: chartY } = this.getTreeBounds();

			return {
				startPoint: {
					x: (parentLeft - chartX + parentWidth / 2) / this.canvasZoom,
					y: (parentTop - chartY + parentHeight) / this.canvasZoom,
				},
				endPoint: {
					x: (left - chartX + width / 2) / this.canvasZoom,
					y: (top - chartY) / this.canvasZoom,
				},
			};
		},
		getParentsPath(parentId: number): Array<number>
		{
			let topLevelId = parentId;
			const parentsPath = [parentId];
			while (topLevelId)
			{
				const parentNode = this.departments.get(topLevelId);
				topLevelId = parentNode.parentId;
				if (topLevelId)
				{
					parentsPath.push(topLevelId);
				}
			}

			return parentsPath;
		},
		async adaptHeight(): Promise<void>
		{
			if (!this.isShown)
			{
				return;
			}

			await this.$nextTick();
			const shift = this.$el.offsetHeight - this.prevHeight;
			if (shift !== 0)
			{
				EventEmitter.emit(events.HR_DEPARTMENT_ADAPT_CONNECTOR_HEIGHT, {
					nodeId: this.nodeId,
					shift,
				});
				this.prevHeight = this.$el.offsetHeight;
			}
		},
		onDragMouseEnter(): void
		{
			EventEmitter.emit(events.HR_USER_DRAG_ENTER_NODE, { nodeId: this.nodeId });
		},

		onDragMouseLeave(): void
		{
			EventEmitter.emit(events.HR_USER_DRAG_LEAVE_NODE, { nodeId: this.nodeId });
		},
	},

	template: `
		<div
			:data-id="nodeId"
			:class="nodeClass"
			:data-title="nodeDataTitle"
			class="humanresources-tree__node"
			:style="{
				'--team-bubble-background': nodeData.teamColor?.bubbleBackground,
				'--team-focused-border-color': nodeData.teamColor?.focusedBorderColor,
				'--node-expanded-color': nodeData.teamColor?.expandedBorderColor,
			}"
		>
			<div v-if="showOverlay"
				class="humanresources-tree__node_drop-overlay"
			>
				<div class="ui-icon-set"
					 :class="{
					'--circle-plus': isUserDropAllowed,
					'--cross-circle-50': !isUserDropAllowed
				}"
				></div>
				<div class="humanresources-tree__node_drop-overlay-text">
					{{ dropOverlayText }}
				</div>
			</div>
			<div
				class="humanresources-tree__node_summary"
				@click.stop="onDepartmentClick('department')"
				@mouseenter="onDragMouseEnter"
				@mouseleave="onDragMouseLeave"
			>
				<template v-if="showInfo">
					<div
						class="humanresources-tree__node_department"
						:style="{ 'background-color': nodeData.teamColor?.treeHeadBackground}"
					>
						<div class="humanresources-tree__node_department-title">
							<div
								v-if="nodeData.parentId !== 0 && showDnd"
								class="humanresources-tree__node_dnd-icon ui-icon-set --more-points"
								:class="{ '--team': isTeamEntity }"
							>
							</div>
							<span
								v-hint
								class="humanresources-tree__node_department-title_text"
								:class="{ '--no-dnd': !showDnd }"
							>
								{{nodeData.name}}
							</span>
						</div>
						<div class="humanresources-tree__node_department-menu-icons">
							<DepartmentInfoIconButton
								v-if="isTeamEntity"
								:description="nodeData.description"
								:entityId="nodeId"
								:canvasZoom="canvasZoom"
							/>
							<DepartmentMenuButton
								v-if="head && deputy"
								:entityId="nodeId"
								:entityType="nodeData.entityType"
							></DepartmentMenuButton>
						</div>
					</div>
					<div class="humanresources-tree__node_description">
						<HeadList v-if="head"
								:entityId="nodeId"
								:items="head"
								:isTeamEntity="isTeamEntity"
						/>
						<div
							v-else
							class="humanresources-tree__node_load-skeleton --heads"
						></div>
						<div v-if="deputy" class="humanresources-tree__node_employees">
							<div>
								<p class="humanresources-tree__node_employees-title">
									{{
										isTeamEntity
											? loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_TEAM_EMPLOYEES_TITLE')
											: loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_EMPLOYEES_TITLE')
									}}
								</p>
								<span
									class="humanresources-tree__node_employees-count"
									@click.stop="onDepartmentClick('employees')"
								>
									{{locPlural('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_EMPLOYEES_COUNT', employeeCount)}}
								</span>
							</div>
							<div v-if="!deputy.length"></div>
							<HeadList
								:items="deputy"
								:title="deputyTitle"
								:collapsed="true"
								:type="'deputy'"
							>
							</HeadList>
						</div>
						<div
							v-else
							class="humanresources-tree__node_load-skeleton --deputies"
						></div>
					</div>
					<div
						class="humanresources-tree__node_subdivisions"
						:class="subdivisionsClass"
						@click.stop="onDepartmentClick('subdivisions')"
					>
						<span>{{ subdivisionsText }}</span>
					</div>
				</template>
				<svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" class="humanresources-tree__node_lock">
					<path d="M12.0646 4.231C13.9529 4.231 15.4836 5.76968 15.4836 7.66775V10.5612H17.2905V7.66775C17.2905 4.76657 14.9507 2.41476 12.0645 2.41476C9.17827 2.41476 6.83847 4.76657 6.83847 7.66775V10.5612H8.64544V7.66775C8.64544 5.76968 10.1762 4.231 12.0646 4.231Z" fill="#D5D7DB"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M7.14721 10.3237C6.12854 10.3237 5.30273 11.1495 5.30273 12.1682V18.506C5.30273 19.5246 6.12854 20.3504 7.14722 20.3504H17.1029C18.1216 20.3504 18.9474 19.5246 18.9474 18.506V12.1682C18.9474 11.1495 18.1216 10.3237 17.1029 10.3237H7.14721ZM12.9216 15.5869C12.9216 15.5685 12.93 15.5512 12.944 15.5392C13.2142 15.3085 13.3859 14.9643 13.3859 14.5797C13.3859 13.8847 12.8259 13.3214 12.1353 13.3214C11.4446 13.3214 10.8846 13.8847 10.8846 14.5797C10.8846 14.9643 11.0563 15.3085 11.3266 15.5392C11.3406 15.5512 11.3489 15.5685 11.3489 15.5869V16.7572C11.3489 17.1915 11.701 17.5435 12.1353 17.5435C12.5696 17.5435 12.9216 17.1915 12.9216 16.7572V15.5869Z" fill="#D5D7DB"/>
				</svg>
				<SubdivisionAddButton
					v-if="showSubdivisionAddButton"
					:entityId="nodeId"
					:entityType="nodeData.entityType"
					@click.stop
				></SubdivisionAddButton>
			</div>
			<div
				v-if="nodeData.parentId === 0 && !hasChildren"
				class="humanresources-tree__node_empty-skeleton"
			></div>
			<div
				v-if="hasChildren"
				ref="childrenNode"
				class="humanresources-tree__node_children"
				:style="childNodeStyle"
			>
				<TransitionGroup>
					<treeNode
						v-for="id in nodeData.children"
						v-if="isExpanded || childrenMounted"
						v-show="isExpanded"
						:ref="'node-' + id"
						:key="id"
						:nodeId="id"
						:expandedNodes="expandedNodes"
						:canvasZoom="canvasZoom"
						:currentDepartments="currentDepartments"
						:isShown="isExpanded"
						@calculatePosition="calculatePosition"
						:userDropTargetNodeId="userDropTargetNodeId"
						:isUserDropAllowed="isUserDropAllowed"
						:isDraggingUser="isDraggingUser"
					/>
				</TransitionGroup>
			</div>
		</div>
	`,
};
