import { Loc, Type } from 'main.core';
import { headOfDepartment } from '../common/head-department';
import { HeadSelector } from '../common/head-selector';
import { Switcher } from '../common/switcher';
import { TextWithDialog } from '../common/text-with-dialog';
import { userType } from '../enum/user-type';
import { BasePage } from './base-page';

export const EncouragementPage = {
	extends: BasePage,

	components: {
		Switcher,
		HeadSelector,
		TextWithDialog,
	},

	data(): Object
	{
		const headItems = this.data.headItems ?? null;
		const headItem = (Type.isArrayFilled(headItems)
			? [...headItems[0]]
			: [headOfDepartment.entityId, headOfDepartment.id]
		);

		return {
			id: 'encouragement',
			isAutoEncourage: this.data.isAutoEncourage ?? false,
			encourageCallCount: this.data.encourageCallCount ?? 1,
			headItem,
			users: this.data.users ?? {},
		};
	},

	methods: {
		getData(): Object
		{
			return {
				encouragementData: {
					isAutoEncourage: this.isAutoEncourage,
					encourageCallCount: this.encourageCallCount,
				},
			};
		},
		onToggleControlType(value: boolean): void
		{
			this.isAutoEncourage = value;
		},
		prepareCallCount(messageId: string, inactive: boolean = false): string
		{
			const inactiveClass = inactive ? '--inactive' : '';
			const callNumberStart = `<span id="crm-copilot__call-assessment-item-selector" class="crm-copilot__call-assessment-item-selector ${inactiveClass}">`;
			const callNumberFinish = '</span>';

			return Loc.getMessage(
				messageId,
				{
					'[callNumber]': callNumberStart,
					'[/callNumber]': callNumberFinish,
				},
			);
		},
		setEncourageCallCount(value: number): void
		{
			this.encourageCallCount = value;
		},
	},

	computed: {
		pageTitle(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ENCOURAGEMENT_TITLE');
		},
		pageDescription(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ENCOURAGEMENT_DESCRIPTION');
		},
		leftItem(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ENCOURAGEMENT_WITH_HEAD');
		},
		rightItem(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ENCOURAGEMENT_AUTO');
		},
		callCountForAuto(): string
		{
			const messageId = 'CRM_COPILOT_CALL_ASSESSMENT_PAGE_ENCOURAGEMENT_CALL_NUMBER_AUTO';

			return this.prepareCallCount(messageId, true);
		},
		callCountForHead(): string
		{
			const messageId = `CRM_COPILOT_CALL_ASSESSMENT_PAGE_ENCOURAGEMENT_CALL_NUMBER_EVERY_${this.encourageCallCount}`;

			return this.prepareCallCount(messageId);
		},
		articleCode(): string
		{
			return '9474707'; // @todo set correct article code
		},
		getDialogItems(): Array<Object>
		{
			const items = [];

			for (let i = 1; i <= 10; i++)
			{
				items.push({
					id: i,
					entityId: 'callNumber',
					title: Loc.getMessage(`CRM_COPILOT_CALL_ASSESSMENT_PAGE_ENCOURAGEMENT_CALL_NUMBER_DIALOG_EVERY_${i}`),
					tabs: 'items',
				});
			}

			return items;
		},
		getDialogTabs(): Array<Object>
		{
			return [
				{
					id: 'items',
					title: null,
				},
			];
		},
		getDialogSelectedItems(): Array<Object>
		{
			return [{
				id: this.encourageCallCount,
				entityId: 'callNumber',
				title: Loc.getMessage(`CRM_COPILOT_CALL_ASSESSMENT_PAGE_ENCOURAGEMENT_CALL_NUMBER_DIALOG_EVERY_${this.encourageCallCount}`),
				tabs: 'items',
			}];
		},
		headSelectorItems(): Array<Object>
		{
			const headType = this.headItem[0];
			const headId = this.headItem[1];

			const title = (
				headType === userType.divisionHead
					? headOfDepartment.title
					: this.users[headId]?.FORMATTED_NAME ?? ''
			);

			return [{
				id: headId,
				entityId: headType,
				title,
				tabs: 'users',
			}];
		},
	},

	template: `
		<div v-if="isActive">
			<div class="crm-copilot__call-assessment_page-section">
				<Breadcrumbs :active-tab-id="id" />
				<header class="crm-copilot__call-assessment_page-section-header">
					<div class="crm-copilot__call-assessment_page-section-header-title">
						{{ pageTitle }}
					</div>
					<div class="crm-copilot__call-assessment_page-section-header-description">
						{{ pageDescription }}
					</div>
				</header>
				<div class="crm-copilot__call-assessment_page-section-body">
					<div class="crm-copilot__call-assessment_page-section-body-field">
						<Switcher
							id="crm-copilot__call-assessment_encouragement-switcher"
							:is-checked="isAutoEncourage"
							:left-item="leftItem"
							:right-item="rightItem"
							@onToggle="onToggleControlType"
						/>
					</div>
					<div class="crm-copilot__call-assessment_page-section-body-field  --control-call-count">
						<TextWithDialog
							v-if="isAutoEncourage"
							id="callCountToChatWithHeadEncouragement"
							:article-code="articleCode"
							:content="callCountForAuto"
						/>
						<TextWithDialog
							v-else
							id="callCountToChatWithHeadEncouragement"
							:article-code="articleCode"
							dialog-target-id="crm-copilot__call-assessment-item-selector"
							:content="callCountForHead"
							:value="encourageCallCount"
							:selectedItems="getDialogSelectedItems"
							:items="getDialogItems"
							:tabs="getDialogTabs"
							@onSelectItem="setEncourageCallCount"
						/>
					</div>
					<div class="crm-copilot__call-assessment_page-section-body-field  --control-head">
						<HeadSelector
							:selectedItems="headSelectorItems"
							:is-read-only="true"
						/>
					</div>
				</div>
			</div>
		</div>
	`,
};
