import { Loc, Dom } from 'main.core';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { memberRoles, AnalyticsSourceType } from 'humanresources.company-structure.api';
import { AddUserDialog } from 'humanresources.company-structure.add-user-dialog';
import { MoveUserFromDialog } from 'humanresources.company-structure.move-user-from-dialog';
import { EventEmitter } from 'main.core.events';
import { events } from '../../../events';
import { HeadList } from './head-list/head-list';

import { DepartmentMenuButton } from './department-menu-button/department-menu-button';
import type { TreeItem, ConnectorData, TreeNodeData } from '../../../types';
import { SubdivisionAddButton } from './subdivision-add-button/subdivision-add-button';
import { PermissionActions, PermissionChecker } from 'humanresources.company-structure.permission-checker';

export const TreeNode = {
	name: 'treeNode',

	inject: ['getTreeBounds', 'getHeightDifference'],

	props: {
		nodeId: {
			type: Number,
			required: true,
		},
		expandedNodes: {
			type: Array,
			required: true,
		},
		zoom: {
			type: Number,
			required: true,
		},
		currentDepartment: {
			type: Number,
			required: true,
		},
	},

	emits: ['calculatePosition'],

	components: {
		DepartmentMenuButton,
		HeadList,
		SubdivisionAddButton,
	},

	data(): TreeNodeData
	{
		return {
			childrenLoaded: false,
			childrenOffset: 0,
			childrenMounted: false,
			showInfo: true,
		};
	},

	created(): void
	{
		this.width = 278;
		this.gap = 20;
		this.prevChildrenOffset = 0;
	},

	async mounted(): Promise<void>
	{
		this.showInfo = PermissionChecker.getInstance().hasPermission(PermissionActions.structureView, this.nodeId);
		this.$emit('calculatePosition', this.nodeId);
		await this.$nextTick();
		EventEmitter.emit(events.HR_DEPARTMENT_CONNECT, {
			id: this.nodeId,
			parentId: this.nodeData.parentId,
			html: this.$el,
			parentsPath: this.getParentsPath(this.nodeData.parentId),
			...this.calculateNodePoints(),
		});
	},
	watch: {
		head()
		{
			this.updateConnectors();
		},
	},
	unmounted(): Promise<void>
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

	computed:
	{
		nodeData(): TreeItem
		{
			return this.departments.get(this.nodeId);
		},
		nodeClass(): { [key: string]: boolean; }
		{
			return {
				'--expanded': this.expandedNodes.includes(this.nodeId),
				'--current-department': this.isCurrentDepartment,
				'--focused': this.focusedNode === this.nodeId,
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
			const isExpanded = this.expandedNodes.includes(this.nodeId);
			if (isExpanded)
			{
				this.childrenMounted = true;
			}

			return isExpanded;
		},
		isCurrentDepartment(): Boolean
		{
			return this.currentDepartment === this.nodeId;
		},
		head(): TreeItem['heads']
		{
			return this.nodeData.heads?.filter((head) => {
				return head.role === memberRoles.head;
			});
		},
		deputy(): ?TreeItem['heads']
		{
			return this.nodeData.heads?.filter((head) => {
				return head.role === memberRoles.deputyHead;
			});
		},
		employeeCount(): number
		{
			return this.nodeData.userCount - this.nodeData.heads.length;
		},
		childNodeStyle(): Object
		{
			const style = { left: `${this.childrenOffset}px` };

			if (!this.childrenLoaded)
			{
				return style;
			}

			const { childrenNode } = this.$refs;

			if (!childrenNode)
			{
				return style;
			}

			const { offsetParent: rootNode } = childrenNode;

			const node = this.departments.get(this.nodeId);

			if (node.children.length === 0)
			{
				return style;
			}

			const top = this.top(node.children[0], rootNode);
			if (top !== null)
			{
				// eslint-disable-next-line unicorn/consistent-destructuring,@bitrix24/bitrix24-rules/no-style
				style.top = `${top}px`;
			}

			return style;
		},
		showSubdivisionAddButton(): boolean
		{
			return this.expandedNodes.includes(this.nodeId) || this.focusedNode === this.nodeId;
		},
		...mapState(useChartStore, ['departments', 'focusedNode']),
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
				this.childrenLoaded = true;
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
		top(id: number, parentNode): ?number
		{
			const heightDifference = this.getHeightDifference(id, parentNode, this.$refs[`node-${id}`][0].$el);

			if (heightDifference === 0)
			{
				return null;
			}

			try
			{
				return parentNode.firstElementChild.offsetHeight + heightDifference;
			}
			catch
			{
				return null;
			}
		},
		controlDepartment(action: string, source: string = AnalyticsSourceType.CARD): void
		{
			EventEmitter.emit(events.HR_DEPARTMENT_CONTROL, {
				nodeId: this.nodeId,
				action,
				source,
			});
		},
		addEmployee(): void
		{
			AddUserDialog.openDialog({ type: 'employee', nodeId: this.nodeId });
		},
		userInvite(): void
		{
			const departmentToInvite = this.departments.get(this.nodeId).accessCode.slice(1);

			BX.SidePanel.Instance.open(
				'/bitrix/services/main/ajax.php?action=getSliderContent'
				+ '&c=bitrix%3Aintranet.invitation&mode=ajax'
				+ `&departments[]=${departmentToInvite}&firstInvitationBlock=invite-with-group-dp`,
				{ cacheable: false, allowChangeHistory: false, width: 1100 },
			);
		},
		moveEmployee(): void
		{
			MoveUserFromDialog.openDialog(this.nodeId);
		},
		locPlural(phraseCode: string, count: number): string
		{
			return Loc.getMessagePlural(phraseCode, count, { '#COUNT#': count });
		},
		calculateNodePoints(): { startPoint: ConnectorData['startPoint'], endPoint: ConnectorData['endPoint'] }
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
					x: (parentLeft - chartX + parentWidth / 2) / this.zoom,
					y: (parentTop - chartY + parentHeight) / this.zoom,
				},
				endPoint: {
					x: (left - chartX + width / 2) / this.zoom,
					y: (top - chartY) / this.zoom,
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
		updateConnectors(): void
		{
			const currentHeight = this.$el.firstElementChild.offsetHeight;

			this.$nextTick(() => {
				const heightDifference = this.$el.firstElementChild.offsetHeight - currentHeight;

				EventEmitter.emit(events.HR_DEPARTMENT_UPDATE, {
					id: this.nodeId,
					parentId: this.nodeData.parentId,
					html: this.$el,
					offset: this.offset,
					parentsPath: this.getParentsPath(this.nodeData.parentId),
					heightDifference,
					...this.calculateNodePoints(),
				});
			});
		},
	},

	template: `
		<div
			:data-id="nodeId"
			:class="nodeClass"
			:data-title="isCurrentDepartment ? loc('HUMANRESOURCES_COMPANY_CURRENT_DEPARTMENT') : null"
			class="humanresources-tree__node"
		>
			<div
				class="humanresources-tree__node_summary"
				@click.stop="onDepartmentClick('department')"
			>
				<div class="humanresources-tree__node_description" :style="{ minHeight: !showInfo ? null : '136px'}">
					<div class="humanresources-tree__node_department" :style="{ justifyContent: !showInfo ? 'center' : 'space-between'}">
						<span class="humanresources-tree__node_department-title">
							<span
								class="humanresources-tree__node_department-title_text"
								:title="nodeData.name"
							>
								{{nodeData.name}}
							</span>
						</span>
						<DepartmentMenuButton
							v-if="showInfo"
							:department-id="nodeId"
							@addDepartment="controlDepartment"
							@editDepartment="controlDepartment"
							@editEmployee="controlDepartment"
							@removeDepartment="controlDepartment"
							@addEmployee="addEmployee"
							@userInvite="userInvite"
							@moveEmployee="moveEmployee"
						></DepartmentMenuButton>
					</div>
				  	<HeadList v-if="showInfo" :items="head"></HeadList>
					<div v-if="showInfo" class="humanresources-tree__node_employees">
						<div>
							<p class="humanresources-tree__node_employees-title">
								{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_EMPLOYEES_TITLE')}}
							</p>
							<span
								class="humanresources-tree__node_employees-count"
								@click.stop="onDepartmentClick('employees')"
							>
								{{locPlural('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_EMPLOYEES_COUNT', employeeCount)}}
							</span>
						</div>
						<div v-if="!deputy.length"></div>
						<HeadList :items="deputy" :title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_DEPUTY_TITLE')" :collapsed="true"></HeadList>
					</div>
				</div>
				<div
					class="humanresources-tree__node_subdivisions"
					:class="subdivisionsClass"
					v-if="showInfo"
					@click.stop="onDepartmentClick('subdivisions')"
				>
					<span>
						{{
							nodeData.children?.length ?
								locPlural('HUMANRESOURCES_COMPANY_DEPARTMENT_CHILDREN_COUNT', nodeData.children.length) :
								loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_NO_SUBDEPARTMENTS')
						}}
					</span>
				</div>
			  	<SubdivisionAddButton
					v-if="showSubdivisionAddButton"
					@addDepartment="controlDepartment('addDepartment', 'plus')"
					:department-id="nodeId"
					@click.stop
				></SubdivisionAddButton>
			</div>
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
						:zoom="zoom"
						:currentDepartment="currentDepartment"
						@calculatePosition="calculatePosition"
					/>
				</TransitionGroup>
			</div>
		</div>
	`,
};
