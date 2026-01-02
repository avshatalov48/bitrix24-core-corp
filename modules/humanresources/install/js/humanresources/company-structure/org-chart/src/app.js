import { EventEmitter, type BaseEvent } from 'main.core.events';
import { Confetti } from 'ui.confetti';
import { mapState } from 'ui.vue3.pinia';
import { TransformCanvas } from 'ui.canvas';
import { AnalyticsSourceType } from 'humanresources.company-structure.api';
import { TitlePanel } from './components/title-panel/title-panel';
import { Tree } from './components/tree/tree';
import { TransformPanel } from './components/transfrom-panel';
import { DetailPanel } from './components/detail-panel/detail-panel';
import { FirstPopup } from './components/first-popup/first-popup';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { ChartWizard } from 'humanresources.company-structure.chart-wizard';
import { EntityTypes, getInvitedUserData } from 'humanresources.company-structure.utils';
import { chartAPI } from './api';
import { events, detailPanelWidth } from './consts';
import { OrgChartActions } from './actions';
import { sendData as analyticsSendData } from 'ui.analytics';
import { addCustomEvent, removeCustomEvent } from 'main.core';

import type { ChartData } from './types';
import './style.css';
import 'ui.design-tokens';

// @vue/component
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
				moved: false,
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
			// so we block all controls until transition isn't completed
			isTransitionCompleted: false,
			analyticsQueue: [],
			isAnalyticsBusy: false,
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
		...mapState(useChartStore, ['departments', 'currentDepartments']),
	},

	async created(): Promise<void>
	{
		const slider = BX.SidePanel.Instance.getTopSlider();
		slider?.showLoader();
		const [departmentsData, currentDepartments, userId] = await Promise.all([
			chartAPI.getDepartmentsData(),
			chartAPI.getCurrentDepartments(),
			chartAPI.getUserId(),
		]);
		slider?.closeLoader();
		const departments = departmentsData.structure;
		const structureMap = Object.entries(departmentsData.map).reduce((map, [id, value]) => {
			map.set(Number(id), { id: Number(id), ...value });

			return map;
		}, new Map());
		const multipleUsers = departmentsData.multipleMembers ?? [];
		const parsedDepartments = chartAPI.createTreeDataStore(departments);
		const availableDepartments = currentDepartments.filter((item) => parsedDepartments.has(item));
		OrgChartActions.applyData(parsedDepartments, availableDepartments, userId, structureMap, multipleUsers);
		this.rootOffset = 100;
		this.transformCanvas();
		this.canvas.shown = true;
		this.showConfetti = false;
		EventEmitter.subscribe(events.HR_DEPARTMENT_SLIDER_ON_MESSAGE, this.handleInviteSliderMessage);
		BX.PULL.subscribe({
			type: BX.PullClient.SubscriptionType.Server,
			moduleId: 'humanresources',
			command: 'linkChatsToNodes',
			callback: (data) => this.clearChatLists(data),
		});
		EventEmitter.subscribe(events.HR_ENTITY_SHOW_WIZARD, this.showWizardEventHandler);
		EventEmitter.subscribe(events.HR_ENTITY_REMOVE, this.removeDepartmentEventHandler);
		EventEmitter.subscribe(events.HR_ORG_CHART_TRANSFORM_CANVAS, this.onCanvasTransformWhenDragging);
		EventEmitter.subscribe(events.HR_ORG_CHART_LOCATE_TO_DEPARTMENT, this.onLocate);

		this.handleAnalyticsAjaxSuccess = this.handleAnalyticsAjaxSuccess.bind(this);
		addCustomEvent('OnAjaxSuccess', this.handleAnalyticsAjaxSuccess);
	},

	unmounted(): void
	{
		EventEmitter.unsubscribe(events.HR_DEPARTMENT_SLIDER_ON_MESSAGE, this.handleInviteSliderMessage);
		EventEmitter.unsubscribe(events.HR_ENTITY_SHOW_WIZARD, this.showWizardEventHandler);
		EventEmitter.unsubscribe(events.HR_ENTITY_REMOVE, this.removeDepartmentEventHandler);
		EventEmitter.unsubscribe(events.HR_ORG_CHART_TRANSFORM_CANVAS, this.onCanvasTransformWhenDragging);
		EventEmitter.unsubscribe(events.HR_ORG_CHART_LOCATE_TO_DEPARTMENT, this.onLocate);

		removeCustomEvent('OnAjaxSuccess', this.handleAnalyticsAjaxSuccess);
	},

	methods:
	{
		onMoveTo({ x, y, nodeId }: { x: Number; y: Number; nodeId: Number; }): void
		{
			const { x: prevX, y: prevY, zoom } = this.canvas.modelTransform;
			const detailPanelWidthZoomed = detailPanelWidth * zoom;
			const newX = x - detailPanelWidthZoomed / 2;
			const newY = nodeId === this.rootId ? this.rootOffset : y / zoom;
			const samePoint = Math.round(newX) === Math.round(prevX) && Math.round(y) === Math.round(prevY);
			this.detailPanel = {
				...this.detailPanel,
				collapsed: false,
			};
			if (samePoint)
			{
				return;
			}

			this.canvas = {
				...this.canvas,
				moved: true,
				modelTransform: { ...this.canvas.modelTransform, x: newX / zoom, y: newY, zoom: 1 },
			};
			this.onUpdateTransform();
		},
		onLocate({ data }: BaseEvent): void
		{
			if (data.nodeId)
			{
				this.$refs.tree.locateToDepartment(data.nodeId);

				return;
			}

			this.$refs.tree.locateToCurrentDepartment();
		},
		showWizardEventHandler({ data }: BaseEvent): void
		{
			this.onShowWizard(data);
		},
		sendAnalyticsSequentially(data)
		{
			this.analyticsQueue.push(data);
			this.processAnalyticsQueue();
		},
		processAnalyticsQueue()
		{
			if (this.isAnalyticsBusy || this.analyticsQueue.length === 0)
			{
				return;
			}

			this.isAnalyticsBusy = true;
			const dataToSend = this.analyticsQueue[0];

			analyticsSendData(dataToSend);
		},

		handleAnalyticsAjaxSuccess()
		{
			if (!this.isAnalyticsBusy)
			{
				return;
			}

			this.analyticsQueue.shift();
			this.isAnalyticsBusy = false;

			this.processAnalyticsQueue();
		},
		onShowWizard({
			nodeId = 0,
			isEditMode = false,
			type,
			showEntitySelector = true,
			source = '',
			entityType,
			refToFocus,
		}: {
			nodeId: number;
			isEditMode: boolean,
			type: string,
			showEntitySelector: boolean,
			source: string,
			entityType: string,
			refToFocus: any
		} = {}): void
		{
			let analyticsType = null;
			if (entityType === EntityTypes.team)
			{
				analyticsType = 'team';
			}
			else if (entityType === EntityTypes.department || entityType === EntityTypes.company)
			{
				analyticsType = 'dept';
			}

			this.wizard = {
				...this.wizard,
				shown: true,
				isEditMode,
				showEntitySelector,
				entity: type,
				nodeId,
				source,
				entityType,
				refToFocus,
			};

			if (!isEditMode && source !== AnalyticsSourceType.HEADER)
			{
				this.sendAnalyticsSequentially({
					tool: 'structure',
					category: 'structure',
					event: 'create_wizard',
					type: analyticsType,
					c_element: source,
				});

				if (entityType === EntityTypes.team)
				{
					this.sendAnalyticsSequentially({
						tool: 'structure',
						category: 'structure',
						event: 'create_team_step1',
						type: analyticsType,
						c_element: source,
					});
				}
			}

			if (isEditMode)
			{
				analyticsSendData({
					tool: 'structure',
					category: 'structure',
					event: `edit_${analyticsType}`,
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
						event: `create_${analyticsType}_step1`,
						type: analyticsType,
						c_element: source,
					});
					break;
				case 'employees':
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: `create_${analyticsType}_step2`,
						type: analyticsType,
						c_element: source,
					});
					break;
				case 'bindChat':
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: `create_${analyticsType}_step3`,
						type: analyticsType,
						c_element: source,
					});
					break;
				case 'teamRights':
					analyticsSendData({
						tool: 'structure',
						category: 'structure',
						event: `create_${analyticsType}_step4`,
						type: analyticsType,
						c_element: source,
					});
					break;
			}
		},
		onModifyTree({ id, showConfetti }): void
		{
			this.showConfetti = showConfetti ?? false;
			const { tree } = this.$refs;
			tree.locateToDepartment(id);
		},
		onWizardClose(): void
		{
			this.wizard.shown = false;
		},
		removeDepartmentEventHandler({ data }: BaseEvent): void
		{
			this.onRemoveDepartment(data.nodeId, data.entityType);
		},
		onRemoveDepartment(nodeId: number, entityType: string): void
		{
			const { tree } = this.$refs;
			tree.tryRemoveDepartment(nodeId, entityType);
		},
		async onTransitionEnd(): Promise<void>
		{
			if (this.canvas.moved)
			{
				this.isTransitionCompleted = true;
			}

			this.canvas.moved = false;
			if (!this.showConfetti)
			{
				return;
			}

			this.isTransitionCompleted = false;
			const promise = Confetti.fire({
				particleCount: 300,
				startVelocity: 10,
				spread: 400,
				ticks: 100,
				origin: { y: 0.4, x: 0.37 },
			});
			this.showConfetti = false;
			await promise;
			this.isTransitionCompleted = true;
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
			EventEmitter.emit(events.INTRANET_USER_MINIPROFILE_CLOSE);
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
		clearChatLists(data): void
		{
			const nodeIds = Object.keys(data).map((key) => Number(key));
			OrgChartActions.clearNodesChatLists(nodeIds);
		},
		onKeyDown(event: KeyboardEvent): void
		{
			if (!this.isTransitionCompleted)
			{
				event.preventDefault();
			}
		},
		onCanvasTransformWhenDragging({ data }: BaseEvent): void
		{
			const { directionX, directionY, speed } = data;
			if (directionX !== 0)
			{
				this.canvas.modelTransform.x += -directionX * speed;
			}

			if (directionY !== 0)
			{
				this.canvas.modelTransform.y += -directionY * speed;
			}
		},
	},

	template: `
		<div
			class="humanresources-chart"
			:class="{ '--locked': !isTransitionCompleted }"
			@keydown="onKeyDown"
		>
			<TitlePanel @showWizard="onShowWizard"></TitlePanel>
			<TransformCanvas
				v-if="canvas.shown"
				v-slot="{transform}"
				v-model="canvas.modelTransform"
				@update:modelValue="onUpdateTransform"
				:class="{ '--moved': canvas.moved }"
				@transitionend="onTransitionEnd"
			>
				<Tree
					:canvasTransform="transform"
					ref="tree"
					@moveTo="onMoveTo"
					@showWizard="onShowWizard"
					@controlDetail="onControlDetail"
				></Tree>
			</TransformCanvas>
			<DetailPanel
				@showWizard="onShowWizard"
				@removeDepartment="onRemoveDepartment"
				v-model="detailPanel.collapsed"
				:preventPanelSwitch="detailPanel.preventSwitch"
			></DetailPanel>
			<TransformPanel
				v-model="canvas.modelTransform"
			></TransformPanel>
			<ChartWizard
				v-if="wizard.shown"
				:nodeId="wizard.nodeId"
				:isEditMode="wizard.isEditMode"
				:showEntitySelector="wizard.showEntitySelector"
				:entity="wizard.entity"
				:entityType="wizard.entityType"
				:source="wizard.source"
				:refToFocus="wizard.refToFocus"
				@modifyTree="onModifyTree"
				@close="onWizardClose"
			></ChartWizard>
			<FirstPopup></FirstPopup>
			<div class="humanresources-chart__back"></div>
		</div>
	`,
};
