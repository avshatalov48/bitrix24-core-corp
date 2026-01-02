import { Event } from 'main.core';
import { mapState } from 'ui.vue3.pinia';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { events } from '../../consts';
import type { ConnectorsData, MountedDepartment } from '../../types';

// @vue/component
export const Connectors = {
	name: 'companyTreeConnectors',

	expose: [
		'toggleConnectorsVisibility',
		'toggleConnectorHighlighting',
		'toggleAllConnectorsVisibility',
		'adaptConnectorsAfterReorder',
	],

	props: {
		isLocatedDepartmentVisible: {
			type: Boolean,
			required: true,
		},
		/** @type Map */
		treeNodes: {
			type: Object,
			required: true,
		},
	},

	data(): { connectors: Record<string, ConnectorsData> }
	{
		return { connectors: {} };
	},

	computed:
	{
		...mapState(useChartStore, ['focusedNode', 'departments']),
	},

	created(): void
	{
		this.subscribeOnEvents();
		this.prevWindowWidth = window.innerWidth;
	},

	beforeUnmount(): void
	{
		this.unsubscribeFromEvents();
	},

	methods:
	{
		onAddConnector({ data }: MountedDepartment): void
		{
			const { id, parentId } = data;
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
		onRemoveConnector({ data }: MountedDepartment): void
		{
			const { parentId, id } = data;
			delete this.connectors[`${parentId}-${id}`];
		},
		onAdaptSiblingConnectors({ data }: { nodeId: number; parentId: number; offset: number; }): void
		{
			const { nodeId, parentId, offset } = data;
			const parentDepartment = this.departments.get(parentId);
			const isMounted = parentDepartment.children.includes(nodeId);
			const values = Object.values(this.connectors);
			const parsedSiblingConnectors = values.reduce((acc, connector) => {
				const { endPoint: currentEndPoint, id: currentId, parentId: currentParentId } = connector;
				if (!currentId || currentParentId !== parentId || currentId === nodeId)
				{
					return acc;
				}

				let sign = 0;
				if (isMounted)
				{
					sign = parentDepartment.children.indexOf(nodeId)
						> parentDepartment.children.indexOf(currentId) ? 1 : -1;
				}
				else
				{
					const { endPoint } = this.connectors[`${parentId}-${nodeId}`];
					sign = endPoint.x > currentEndPoint.x ? 1 : -1;
				}

				return {
					...acc,
					[currentId]: sign,
				};
			}, {});
			values.forEach((connector) => {
				const {
					id: currentId,
					parentId: currentParentId,
					parentsPath,
					endPoint: currentEndPoint,
					startPoint: currentStartPoint,
				} = connector;
				if (!currentId || currentId === nodeId)
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
		adaptConnectorsAfterReorder(ids: number[], shift: Number, isRoot: boolean): void
		{
			ids.forEach((departmentId) => {
				const { parentId, children } = this.departments.get(departmentId);
				const connector = this.connectors[`${parentId}-${departmentId}`];
				if (!connector)
				{
					return;
				}

				Object.assign(connector.endPoint, { x: connector.endPoint.x + shift });
				if (!isRoot)
				{
					Object.assign(connector.startPoint, { x: connector.startPoint.x + shift });
				}

				if (children?.length > 0)
				{
					this.adaptConnectorsAfterReorder(children, shift, false);
				}
			});
		},
		onAdaptConnectorHeight({ data }: BaseEvent): void
		{
			const { shift, nodeId } = data;
			Object.values(this.connectors).forEach((connector) => {
				if (connector.parentId === nodeId)
				{
					Object.assign(connector.startPoint, { y: connector.startPoint.y + shift });
				}
			});
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
			});
		},
		toggleAllConnectorsVisibility(shouldShow: boolean, expandedNodes: number[]): void
		{
			Object.keys(this.connectors).forEach((key) => {
				const connector = this.connectors[key];
				if (!shouldShow || (expandedNodes.includes(connector.parentId) && shouldShow))
				{
					connector.show = shouldShow;
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
		getPath(id: string): string
		{
			const connector = this.connectors[id];
			const { startPoint, endPoint, parentId } = connector;

			if (!startPoint || !endPoint)
			{
				return '';
			}

			let verticalLineHeight = 115;
			const parentNode = this.treeNodes.get(parentId);
			const parentNodeStyle = getComputedStyle(parentNode);
			const minDepartmentHeight = parseInt(parentNodeStyle.getPropertyValue('--min-height'), 10);
			const diffHeight = parentNode.offsetHeight - minDepartmentHeight;
			verticalLineHeight = diffHeight > 0 ? verticalLineHeight - diffHeight : verticalLineHeight;
			const shiftY = 1;

			const startY = startPoint.y - shiftY;
			const shadowOffset = this.focusedNode === connector.id ? 12 : 0;
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
				`V${startY + verticalLineHeight}`,
				`${String(rounded.start)}`,
				`H${endPoint.x + arcRadius}`,
				`${String(rounded.end)}`,
				`V${adjustedEndY}`,
			].join('');
		},
		subscribeOnEvents(): void
		{
			this.events = {
				[events.HR_DEPARTMENT_CONNECT]: this.onAddConnector,
				[events.HR_DEPARTMENT_DISCONNECT]: this.onRemoveConnector,
				[events.HR_DEPARTMENT_ADAPT_SIBLINGS]: this.onAdaptSiblingConnectors,
				[events.HR_DEPARTMENT_ADAPT_CONNECTOR_HEIGHT]: this.onAdaptConnectorHeight,
			};
			Object.entries(this.events).forEach(([event, handle]) => {
				EventEmitter.subscribe(event, handle);
			});
			Event.bind(window, 'resize', this.onResizeWindow);
		},
		unsubscribeFromEvents(): void
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
	`,
};
