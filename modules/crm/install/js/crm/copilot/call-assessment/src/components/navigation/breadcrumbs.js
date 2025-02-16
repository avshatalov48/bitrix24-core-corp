import { Loc } from 'main.core';

export const BreadcrumbsEvents = {
	itemClick: 'crm:copilot:call-assessment:breadcrumb-item-click',
};

export const Breadcrumbs = {
	props: {
		activeTabId: {
			type: String,
		},
	},

	data(): Object
	{
		return {
			tabs: this.getTabsData(),
		};
	},

	methods: {
		itemTitleClassList(id: string, isSoon: boolean): string[]
		{
			return [
				'crm-copilot__call-assessment_breadcrumbs-item-title',
				{ '--active': id === this.activeTabId },
				{ '--soon': isSoon },
			];
		},
		getTab(id: string): Object
		{
			return this.getTabsData().find((tab) => tab.id === id);
		},
		getTabsData(): Object[]
		{
			return [
				{
					id: 'about',
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ABOUT'),
				},
				{
					id: 'client',
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT'),
				},
				{
					id: 'settings',
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS'),
				},
				{
					id: 'control',
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL'),
				},
			];
		},
		emitClickEvent(itemId: string): void
		{
			const tab = this.getTab(itemId);

			if (!tab.soon)
			{
				this.$Bitrix.eventEmitter.emit(BreadcrumbsEvents.itemClick, { itemId });
			}
		},
	},

	computed: {
		soonTitle(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_BREADCRUMBS_SOON');
		},
	},

	template: `
		<div class="crm-copilot__call-assessment_breadcrumbs-wrapper">
			<div
				v-for="(tab, index) in tabs"
				@click="emitClickEvent(tab.id)"
				class="crm-copilot__call-assessment_breadcrumbs-item"
			>
				<span v-if="tab.soon" class="crm-copilot__call-assessment_breadcrumbs-soon">
					{{ soonTitle }}
				</span>
				<span :class="itemTitleClassList(tab.id, tab.soon)">
					{{ tab.title }}
				</span>
				<span 
					v-if="index+1 < tabs.length"
					class="crm-copilot__call-assessment_breadcrumbs-divider"
				></span>
			</div>
		</div>
	`,
};
