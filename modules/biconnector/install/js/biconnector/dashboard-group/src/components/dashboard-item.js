import { EventEmitter } from 'main.core.events';
import type { BitrixVueComponentProps } from 'ui.vue3';
// eslint-disable-next-line no-unused-vars
import { DashboardType, type Dashboard } from '../type';
import { DashboardScopeSelector } from './dashboard-scope-selector';
import { BIcon, Set } from 'ui.icon-set.api.vue';

export const DashboardItem: BitrixVueComponentProps = {
	props: {
		dashboard: {
			/** @type Dashboard */
			type: Object,
			required: true,
		},
	},
	computed: {
		iconPath(): string
		{
			if (this.dashboard.type === DashboardType.system)
			{
				return '/bitrix/images/biconnector/superset-dashboard-selector/icon-type-system.png';
			}

			if (this.dashboard.type === DashboardType.market)
			{
				return '/bitrix/images/biconnector/superset-dashboard-selector/icon-type-market.png';
			}

			return '/bitrix/images/biconnector/superset-dashboard-selector/icon-type-custom.png';
		},
		set(): Set
		{
			return Set;
		},
	},
	methods: {
		onRemoveClick(): void
		{
			EventEmitter.emit(
				'BIConnector.GroupPopup.DashboardList:onDashboardRemove',
				{
					isDashboardListEdited: true,
				},
			);
			this.$emit('onDashboardRemove', { dashboardId: this.dashboard.id });
			this.emitDashboardChange();
		},
		emitDashboardChange(): void
		{
			this.$emit('onDashboardChange');
		},
		onGroupScopeChanged(): void
		{
			this.$refs.scopeSelector.onGroupScopeChanged();
		},
	},
	emits: [
		'onDashboardChange',
		'onDashboardRemove',
	],
	components: {
		BIcon,
		DashboardScopeSelector,
	},
	template: `
		<div class="group-dashboard">
			<img class="group-dashboard-icon" :src="iconPath" width="32" height="32" alt="Dashboard icon">
			<div class="group-dashboard-name" :title="dashboard.name">{{dashboard.name}}</div>
			<DashboardScopeSelector :dashboard="dashboard" ref="scopeSelector" @on-scope-change="emitDashboardChange"/>
			<BIcon
				:name="set.CROSS_20"
				:size="20"
				color="#BDC1C6"
				:class="'group-dashboard-remove-icon'"
				@click="onRemoveClick"
			></BIcon>
		</div>
	`,
};
