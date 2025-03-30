import { EventEmitter, type BaseEvent } from 'main.core.events';
import { Confetti } from 'ui.confetti';
import { mapState } from 'ui.vue3.pinia';
import { TransformCanvas } from 'humanresources.company-structure.canvas';
import { AnalyticsSourceType } from 'humanresources.company-structure.api';
import { TitlePanel } from './components/title-panel/title-panel';
import { Tree } from './components/tree/tree';
import { TransformPanel } from './components/transfrom-panel';
import { DetailPanel } from './components/detail-panel/detail-panel';
import { FirstPopup } from './components/first-popup/first-popup';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { ChartWizard } from 'humanresources.company-structure.chart-wizard';
import { getInvitedUserData } from 'humanresources.company-structure.utils';
import { chartAPI } from './api';
import { events } from './events';
import { OrgChartActions } from './actions';
import { sendData as analyticsSendData } from 'ui.analytics';

import type { ChartData } from './types';
import './style.css';
import 'ui.design-tokens';

export const Chart = {
	components: {
		TransformCanvas,
		Tree,
		TransformPanel,
		ChartWizard,
		FirstPopup,
		DetailPanel,
		TitlePanel,
	},

	data(): ChartData
	{
		return {
			canvas: {
				shown: false,
				moving: false,
				modelTransform: {
					x: 0,
					y: 0,
					zoom: 0.3,
				},
			},
			wizard: {
				shown: false,
				isEditMode: false,
				showEntitySelector: true,
				entity: '',
				nodeId: 0,
				source: '',
			},
			detailPanel: {
				collapsed: true,
				preventSwitch: false,
			},
		};
	},

	async created(): Promise<void>
	{
		const slider = BX.SidePanel.Instance.getTopSlider();
		slider?.showLoader();
		const [departments, currentDepartments, userId] = await Promise.all([
			chartAPI.getDepartmentsData(),
			chartAPI.getCurrentDepartments(),
			chartAPI.getUserId(),
		]);
		slider?.closeLoader();
		const parsedDepartments = chartAPI.createTreeDataStore(departments);
		OrgChartActions.applyData(parsedDepartments, currentDepartments, userId);
		this.rootOffset = 100;
		this.transformCanvas();
		this.canvas.shown = true;
		this.showConfetti = false;
		EventEmitter.subscribe(events.HR_DEPARTMENT_SLIDER_ON_MESSAGE, this.handleInviteSliderMessage);
	},

	unmounted(): void
	{
		EventEmitter.unsubscribe(events.HR_DEPARTMENT_SLIDER_ON_MESSAGE, this.handleInviteSliderMessage);
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
		...mapState(useChartStore, ['departments', 'currentDepartments']),
	},

	methods:
	{
		onMoveTo({ x, y, nodeId }: { x: Number; y: Number; nodeId: Number; }): void
		{
			const { x: prevX, y: prevY, zoom } = this.canvas.modelTransform;
			const detailPanelWidth = 364 * zoom;
			const newX = x - detailPanelWidth / 2;
			const newY = nodeId === this.rootId ? this.rootOffset : y / zoom;
			const notSamePoint = Math.round(newX) !== Math.round(prevX) || Math.round(y) !== Math.round(prevY);
			const shouldMove = notSamePoint && !this.canvas.moving;
			this.detailPanel = {
				...this.detailPanel,
				collapsed: false,
			};
			if (!shouldMove)
			{
				return;
			}

			this.canvas = {
				...this.canvas,
				moving: true,
				modelTransform: { ...this.canvas.modelTransform, x: newX / zoom, y: newY, zoom: 1 },
			};
		},
		onLocate(nodeId: ?number): void
		{
			if (nodeId)
			{
				this.$refs.tree.locateToDepartment(nodeId);

				return;
			}

			this.$refs.tree.locateToCurrentDepartment();
		},
		onShowWizard({
			nodeId = 0,
			isEditMode = false,
			type,
			showEntitySelector = true,
			source = '',
		}: { nodeId: number; isEditMode: boolean, showEntitySelector: boolean, source: string } = {}): void
		{
			this.wizard = {
				...this.wizard,
				shown: true,
				isEditMode,
				showEntitySelector,
				entity: type,
				nodeId,
				source,
			};

			if (!isEditMode && source !== AnalyticsSourceType.HEADER)
			{
				analyticsSendData({
					tool: 'structure',
					category: 'structure',
					event: 'create_dept_step1',
					c_element: source,
				});
			}

			// eslint-disable-next-line default-case
			switch (type)
			{
				case 'department':
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: 'create_dept_step1',
						c_element: source,
					});
					break;
				case 'employees':
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: 'create_dept_step2',
						c_element: source,
					});
					break;
				case 'bindChat':
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: 'create_dept_step3',
						c_element: source,
					});
					break;
			}
		},
		async onModifyTree({ id, parentId, showConfetti }): Promise<void>
		{
			this.showConfetti = showConfetti ?? false;
			const { tree } = this.$refs;
			tree.expandDepartmentParents(id);
			tree.focus(id, { expandAfterFocus: true });
			await this.$nextTick();
			tree.moveTo(id);
		},
		onWizardClose(): void
		{
			this.wizard.shown = false;
		},
		onRemoveDepartment(nodeId: number): void
		{
			const { tree } = this.$refs;
			tree.tryRemoveDepartment(nodeId);
		},
		onTransitionEnd(): void
		{
			this.canvas.moving = false;
			if (this.showConfetti)
			{
				Confetti.fire({
					particleCount: 300,
					startVelocity: 10,
					spread: 400,
					ticks: 100,
					origin: { y: 0.4, x: 0.37 },
				});
				this.showConfetti = false;
			}
		},
		onControlDetail({ showEmployees, preventSwitch }): void
		{
			this.detailPanel = {
				...this.detailPanel,
				preventSwitch,
			};
			if (!showEmployees)
			{
				return;
			}

			this.detailPanel = {
				...this.detailPanel,
				collapsed: false,
			};
		},
		transformCanvas(): void
		{
			const { zoom } = this.canvas.modelTransform;
			const { offsetWidth, offsetHeight } = this.$el;
			const [currentDepartment] = this.currentDepartments;
			const y = currentDepartment === this.rootId ? this.rootOffset : offsetHeight / 2 - (offsetHeight * zoom) / 2;
			this.canvas.modelTransform = {
				...this.canvas.modelTransform,
				x: offsetWidth / 2 - (offsetWidth * zoom) / 2,
				y,
			};
		},
		onUpdateTransform(): void
		{
			EventEmitter.emit(events.HR_DEPARTMENT_MENU_CLOSE);
		},
		handleInviteSliderMessage(event: BaseEvent): void
		{
			const [messageEvent] = event.getData();
			const eventId = messageEvent.getEventId();
			if (eventId !== 'BX.Intranet.Invitation:onAdd')
			{
				return;
			}

			const { users } = messageEvent.getData();
			users.forEach((user) => {
				const invitedUserData = getInvitedUserData(user);
				OrgChartActions.inviteUser(invitedUserData);
			});
		},
	},

	template: `
		<div class="humanresources-chart">
			<TitlePanel @showWizard="onShowWizard" @locate="onLocate"></TitlePanel>
			<TransformCanvas
				v-if="canvas.shown"
				v-slot="{transform}"
				v-model="canvas.modelTransform"
				@update:modelValue="onUpdateTransform"
				:class="{ '--moving': canvas.moving }"
				@transitionend="onTransitionEnd"
			>
				<Tree
					:zoom="transform.zoom"
					ref="tree"
					@moveTo="onMoveTo"
					@showWizard="onShowWizard"
					@controlDetail="onControlDetail"
				/>
			</TransformCanvas>
			<DetailPanel
				@showWizard="onShowWizard"
				@removeDepartment="onRemoveDepartment"
				v-model="detailPanel.collapsed"
				:preventPanelSwitch="detailPanel.preventSwitch"
			></DetailPanel>
			<TransformPanel
				v-model="canvas.modelTransform"
				@locate="onLocate"
			></TransformPanel>
			<ChartWizard
				v-if="wizard.shown"
				:nodeId="wizard.nodeId"
				:isEditMode="wizard.isEditMode"
				:showEntitySelector="wizard.showEntitySelector"
				:entity="wizard.entity"
				:source="wizard.source"
				@modifyTree="onModifyTree"
				@close="onWizardClose"
			></ChartWizard>
			<FirstPopup/>
			<div class="humanresources-chart__back"></div>
		</div>
	`,
};
