import { Event } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { UI } from 'ui.notification';
import { ButtonColor } from 'ui.buttons';
import { TreeNode } from './tree-node/tree-node';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { EventEmitter } from 'main.core.events';
import { events } from '../../events';
import { chartAPI } from '../../api';
import { MenuActions } from './tree-node/department-menu-button';
import { sendData as analyticsSendData } from 'ui.analytics';
import { OrgChartActions } from '../../actions';
import type { ConnectorData, TreeData } from '../../types';
import './style.css';

export const Tree = {
	name: 'tree',

	components: { TreeNode },

	props: {
		zoom: {
			type: Number,
			required: true,
		},
	},

	emits: ['moveTo', 'showWizard', 'controlDetail'],

	data(): TreeData
	{
		return {
			connectors: {},
			expandedNodes: [],
		};
	},

	created(): void
	{
		this.treeNodes = new Map();
		this.subscribeOnEvents();
		this.loadHeads([this.rootId]);
		this.prevWindowWidth = window.innerWidth;
		const [currentDepartment] = this.currentDepartments;
		if (!currentDepartment)
		{
			return;
		}

		if (currentDepartment !== this.rootId)
		{
			this.expandDepartmentParents(currentDepartment);
			this.focus(currentDepartment, { expandAfterFocus: true });

			return;
		}

		this.expandLowerDepartments();
		this.focus(currentDepartment);
	},

	beforeUnmount(): void
	{
		this.unsubscribeOnEvents();
	},

	provide(): { [key: string]: Function }
	{
		return {
			getTreeBounds: () => this.getTreeBounds(),
		};
	},

	computed:
	{
		rootId(): number
		{
			const { id: rootId } = [...this.departments.values()].find((department) => {
				return department.parentId === 0;
			});

			return rootId;
		},
		...mapState(useChartStore, ['currentDepartments', 'userId', 'focusedNode', 'departments']),
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		getTreeBounds(): DOMRect
		{
			return this.$el.getBoundingClientRect();
		},
		getPath(id: string): string
		{
			const connector = this.connectors[id];
			const { startPoint, endPoint } = connector;

			if (!startPoint || !endPoint)
			{
				return '';
			}

			const lineLength = 90;
			const shiftY = 1;

			const startY = startPoint.y - shiftY;
			const shadowOffset = this.focusedNode === connector.id ? 9 : 0;
			const rounded = { start: '', end: '' };
			let arcRadius = 0;

			if (Math.round(startPoint.x) > Math.round(endPoint.x))
			{
				arcRadius = 15;
				rounded.start = 'a15,15 0 0 1 -15,15';
				rounded.end = 'a15,15 0 0 0 -15,15';
			}
			else if (Math.round(startPoint.x) < Math.round(endPoint.x))
			{
				arcRadius = -15;
				rounded.start = 'a15,15 0 0 0 15,15';
				rounded.end = 'a15,15 0 0 1 15,15';
			}

			const adjustedEndY = endPoint.y - shadowOffset;

			return [
				`M${startPoint.x} ${startY}`,
				`V${startY + lineLength}`,
				`${String(rounded.start)}`,
				`H${endPoint.x + arcRadius}`,
				`${String(rounded.end)}`,
				`V${adjustedEndY}`,
			].join('');
		},
		onConnectDepartment({ data }: ConnectorData): void
		{
			const { id, parentId, html } = data;
			this.treeNodes.set(id, html);
			const [currentDepartment] = this.currentDepartments;
			if (id === currentDepartment)
			{
				setTimeout(() => {
					this.moveTo(currentDepartment);
				}, 1800);
			}

			if (!parentId)
			{
				return;
			}

			const connector = this.connectors[`${parentId}-${id}`] ?? {};
			Object.assign(connector, data);
			if (connector.highlighted)
			{
				delete this.connectors[`${parentId}-${id}`];
			}

			this.connectors[`${parentId}-${id}`] = {
				show: true,
				highlighted: false,
				...connector,
			};
		},
		onDisconnectDepartment({ data }: ConnectorData): void
		{
			const { id, parentId } = data;
			delete this.connectors[`${parentId}-${id}`];
			const department = this.departments.get(id);
			delete department.prevParentId;
			if (!department.parentId)
			{
				OrgChartActions.removeDepartment(id);
			}
		},
		onAdaptSiblings({ data }): void
		{
			const { nodeId, parentId, offset } = data;
			const parentDepartment = this.departments.get(parentId);
			if (parentDepartment.children.includes(nodeId))
			{
				this.adaptConnectorsAfterMount(parentId, nodeId, offset);

				return;
			}

			this.adaptConnectorsAfterUnmount(parentId, nodeId, offset);
		},
		adaptConnectorsAfterMount(parentId: number, nodeId: number, offset: number): void
		{
			Object.entries(this.connectors).forEach(([key, connector]) => {
				if (!connector.id)
				{
					return;
				}

				if (connector.parentId === parentId)
				{
					const { x } = connector.endPoint;
					Object.assign(connector.endPoint, { x: x + offset });

					return;
				}

				if (connector.parentsPath.includes(parentId))
				{
					const { startPoint: currentStartPoint, endPoint } = connector;
					Object.assign(currentStartPoint, { x: currentStartPoint.x + offset });
					Object.assign(endPoint, { x: endPoint.x + offset });
				}
			});
		},
		adaptConnectorsAfterUnmount(parentId: number, nodeId: number, offset: number): void
		{
			const entries = Object.entries(this.connectors);
			const { endPoint } = this.connectors[`${parentId}-${nodeId}`];
			const parsedSiblingConnectors = entries.reduce((acc, [key, connector]) => {
				const { endPoint: currentEndPoint, id, parentId: currentParentId } = connector;
				if (currentParentId !== parentId || id === nodeId)
				{
					return acc;
				}

				const sign = endPoint.x > currentEndPoint.x ? 1 : -1;

				return {
					...acc,
					[id]: sign,
				};
			}, {});
			entries.forEach(([key, connector]) => {
				const {
					id: currentId,
					parentId: currentParentId,
					parentsPath,
					endPoint: currentEndPoint,
					startPoint: currentStartPoint,
				} = connector;
				if (currentId === nodeId)
				{
					return;
				}

				if (currentParentId === parentId)
				{
					const { x } = currentEndPoint;
					const sign = parsedSiblingConnectors[currentId];
					Object.assign(currentEndPoint, { x: x + offset * sign });

					return;
				}

				const ancestorId = parentsPath?.find((id) => {
					return Boolean(parsedSiblingConnectors[id]);
				});
				if (ancestorId)
				{
					const ancestorSign = parsedSiblingConnectors[ancestorId];
					Object.assign(currentStartPoint, { x: currentStartPoint.x + offset * ancestorSign });
					Object.assign(currentEndPoint, { x: currentEndPoint.x + offset * ancestorSign });
				}
			});
		},
		onAdaptConnectorHeight({ data }): void
		{
			const { shift, nodeId } = data;
			Object.entries(this.connectors).forEach(([id, connector]) => {
				if (connector.parentId === nodeId)
				{
					Object.assign(connector.startPoint, { y: connector.startPoint.y + shift });
				}
			});
		},
		collapse(nodeId: number): void
		{
			this.expandedNodes = this.expandedNodes.filter((expandedId) => expandedId !== nodeId);
			this.toggleConnectorsVisibility(nodeId, false);
			this.toggleConnectorHighlighting(nodeId, false);
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
		expand(departmentId: number): void
		{
			this.collapseRecursively(departmentId);
			this.expandedNodes = [...this.expandedNodes, departmentId];
			this.toggleConnectorsVisibility(departmentId, true);
			this.toggleConnectorHighlighting(departmentId, true);
			const department = this.departments.get(departmentId);
			const childrenWithoutHeads = department.children.filter((childId) => {
				return !this.departments.get(childId).heads;
			});
			if (childrenWithoutHeads.length > 0)
			{
				this.loadHeads(childrenWithoutHeads);
			}

			analyticsSendData({ tool: 'structure', category: 'structure', event: 'expand_department' });
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
				this.expand(nodeId);
			}

			if (this.focusedNode && !this.expandedNodes.includes(this.focusedNode))
			{
				this.toggleConnectorHighlighting(this.focusedNode, false);
			}

			OrgChartActions.focusDepartment(nodeId);
			this.toggleConnectorHighlighting(this.focusedNode, true);
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
		onControlDepartment({ data }): void
		{
			const { action, nodeId, source } = data;
			const isEditMode = action === MenuActions.editDepartment
				|| action === MenuActions.editEmployee;

			if (isEditMode)
			{
				const type = action === MenuActions.editDepartment
					? 'department' : 'employees';
				this.$emit('showWizard', { nodeId, isEditMode: true, type, source });

				return;
			}

			if (action === MenuActions.addDepartment)
			{
				this.$emit('showWizard', { nodeId, isEditMode: false, showEntitySelector: false, source });

				return;
			}

			this.tryRemoveDepartment(nodeId);
		},
		tryRemoveDepartment(nodeId: number): void
		{
			const messageBox = MessageBox.create({
				title: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_TITLE'),
				message: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_MESSAGE'),
				buttons: MessageBoxButtons.OK_CANCEL,
				onOk: async (dialog: MessageBox) => {
					try
					{
						await this.removeDepartment(nodeId);
						UI.Notification.Center.notify({
							content: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_REMOVED'),
							autoHideDelay: 2000,
						});
						dialog.close();
					}
					catch
					{
						UI.Notification.Center.notify({
							content: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_CONFIRM_REMOVE_DEPARTMENT_ERROR'),
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
			await chartAPI.removeDepartment(nodeId);
			const removableDepartment = this.departments.get(nodeId);
			const { parentId, children: removableDeparmentChildren = [] } = removableDepartment;
			if (removableDeparmentChildren.length > 0)
			{
				this.collapse(nodeId);
			}

			OrgChartActions.moveSubordinatesToParent(nodeId);
			await this.$nextTick();
			OrgChartActions.markDepartmentAsRemoved(nodeId);
			this.focus(parentId, { expandAfterFocus: true });
			this.moveTo(parentId);
		},
		toggleConnectorsVisibility(parentId: number, show: boolean): void
		{
			const { children } = this.departments.get(parentId);
			children.forEach((childId) => {
				const connector = this.connectors[`${parentId}-${childId}`] ?? {};
				this.connectors = {
					...this.connectors,
					[`${parentId}-${childId}`]: { ...connector, show },
				};
				if (this.expandedNodes.includes(childId))
				{
					this.toggleConnectorsVisibility(childId, show);
				}
			});
		},
		toggleConnectorHighlighting(nodeId: number, expanded: boolean): void
		{
			const { parentId } = this.departments.get(nodeId);
			if (!parentId)
			{
				return;
			}

			if (!expanded)
			{
				this.connectors[`${parentId}-${nodeId}`] = {
					...this.connectors[`${parentId}-${nodeId}`],
					highlighted: false,
				};

				return;
			}

			const highlightedConnector = this.connectors[`${parentId}-${nodeId}`] ?? {};
			delete this.connectors[`${parentId}-${nodeId}`];
			this.connectors = {
				...this.connectors,
				[`${parentId}-${nodeId}`]: {
					...highlightedConnector,
					highlighted: true,
				},
			};
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
		locateToCurrentDepartment(): void
		{
			const [currentDepartment] = this.currentDepartments;
			if (!currentDepartment)
			{
				return;
			}

			this.expandDepartmentParents(currentDepartment);
			this.focus(currentDepartment, { expandAfterFocus: true });
			this.moveTo(currentDepartment);
			OrgChartActions.searchUserInDepartment(this.userId);
		},
		async locateToDepartment(nodeId: number): Promise<void>
		{
			await this.expandDepartmentParents(nodeId);
			await this.focus(nodeId, { expandAfterFocus: true });
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
				[events.HR_DEPARTMENT_CONTROL]: this.onControlDepartment,
				[events.HR_DEPARTMENT_ADAPT_SIBLINGS]: this.onAdaptSiblings,
				[events.HR_DEPARTMENT_ADAPT_CONNECTOR_HEIGHT]: this.onAdaptConnectorHeight,
			};
			Object.entries(this.events).forEach(([event, handle]) => {
				EventEmitter.subscribe(event, handle);
			});
			Event.bind(window, 'resize', this.onResizeWindow);
		},
		unsubscribeOnEvents(): void
		{
			Object.entries(this.events).forEach(([event, handle]) => {
				EventEmitter.unsubscribe(event, handle);
			});
			Event.unbind(window, 'resize', this.onResizeWindow);
		},
		onResizeWindow(): void
		{
			const offset = (window.innerWidth - this.prevWindowWidth) / 2;
			this.prevWindowWidth = window.innerWidth;
			if (offset === 0)
			{
				return;
			}

			Object.keys(this.connectors).forEach((key) => {
				const connector = this.connectors[key];
				if (connector.startPoint && connector.endPoint)
				{
					const startPointX = connector.startPoint.x;
					const endPointX = connector.endPoint.x;
					Object.assign(connector.startPoint, { x: startPointX + offset });
					Object.assign(connector.endPoint, { x: endPointX + offset });
				}
			});
		},
	},

	template: `
		<div
			class="humanresources-tree"
			v-if="departments.size > 0"
		>
			<TreeNode
				class="--root"
				:key="rootId"
				:nodeId="rootId"
				:expandedNodes="[...expandedNodes]"
				:zoom="zoom"
				:currentDepartment="currentDepartments[0]"
			/>
			<svg class="humanresources-tree__connectors" fill="none">
				<marker
					id='arrow'
					markerUnits='userSpaceOnUse'
					markerWidth='20'
					markerHeight='12'
					refX='10'
					refY='10.5'
				>
					<path d="M1 1L10 10L19 1" class="--highlighted" />
				</marker>
				<path
					v-for="(connector, id) in connectors"
					v-show="connector.show"
					:ref="id"
					:marker-end="connector.highlighted ? 'url(#arrow)' : null"
					:class="{ '--highlighted': connector.highlighted }"
					:id="id"
					:d="getPath(id)"
				></path>
			</svg>
		</div>
	`,
};
