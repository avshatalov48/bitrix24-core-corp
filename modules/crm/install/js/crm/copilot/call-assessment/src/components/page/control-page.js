import { Loc, Type } from 'main.core';
import { headOfDepartment } from '../common/head-department';
import { HeadSelector } from '../common/head-selector';
import { HelpLink } from '../common/help-link';
import { Switcher } from '../common/switcher';
import { TextWithDialog } from '../common/text-with-dialog';
import { userType } from '../enum/user-type';
import { BasePage } from './base-page';

export const ControlPage = {
	extends: BasePage,

	components: {
		HelpLink,
		Switcher,
		TextWithDialog,
		HeadSelector,
	},

	data(): Object
	{
		const defaultFluidCallCount = 2;

		const headItems = this.data.headItems ?? null;
		const headItem = (Type.isArrayFilled(headItems)
			? [...headItems[0]]
			: [headOfDepartment.entityId, headOfDepartment.id]
		);

		return {
			id: 'control',
			isStrictControl: this.data.isStrictControl ?? false,
			fluidCallCount: this.data.fluidCallCount ?? defaultFluidCallCount,
			headItem,
			useSummary: this.data.useSummary ?? false,
			users: this.data.users ?? {},
		};
	},

	methods: {
		getData(): Object
		{
			return {
				controlData: {
					isStrictControl: this.isStrictControl,
					fluidCallCount: this.fluidCallCount,
					headItems: [
						[...this.headItem],
					],
				},
				useSummary: this.useSummary,
			};
		},
		onToggleControlType(value: boolean): void
		{
			this.isStrictControl = value;
		},
		prepareCallCountToChatWithHead(messageId: string, inactive: boolean = false): string
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
		setFluidCallCount(value: number): void
		{
			this.fluidCallCount = value;
		},
		setHeadItem(value: Array): void
		{
			this.headItem = value;
		},
	},

	computed: {
		pageTitle(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_TITLE');
		},
		pageDescription(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_DESCRIPTION');
		},
		callCountToChatWithHeadFluid(): string
		{
			const messageId = `CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_CALL_NUMBER_EVERY_${this.fluidCallCount}`;

			return this.prepareCallCountToChatWithHead(messageId);
		},
		callCountToChatWithHeadStrict(): string
		{
			const messageId = 'CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_CALL_NUMBER_EVERY_1';

			return this.prepareCallCountToChatWithHead(messageId, true);
		},
		leftItem(): Object
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_FLEXIBLE');
		},
		rightItem(): Object
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_STRICT');
		},
		articleCode(): string
		{
			return '9474707'; // @todo set correct article code
		},
		summaryByManagers(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_GET_SUMMARY_MANAGERS');
		},
		getDialogItems(): Array<Object>
		{
			const items = [];
			const minCallCount = 2;
			const maxCallCount = 10;

			for (let i = minCallCount; i <= maxCallCount; i++)
			{
				items.push({
					id: i,
					entityId: 'callNumber',
					title: Loc.getMessage(`CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_DIALOG_CALL_NUMBER_EVERY_${i}`),
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
				id: this.fluidCallCount,
				entityId: 'callNumber',
				title: Loc.getMessage(`CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_DIALOG_CALL_NUMBER_EVERY_${this.fluidCallCount}`),
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
				<Breadcrumbs :active-tab-id="id"/>
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
							id="crm-copilot__call-assessment_control-switcher"
							:is-checked="isStrictControl"
							:left-item="leftItem"
							:right-item="rightItem"
							@onToggle="onToggleControlType"
						/>
					</div>
					<div class="crm-copilot__call-assessment_page-section-body-field  --control-call-count">
						<TextWithDialog
							v-if="isStrictControl"
							id="callCountToChatWithHeadControl"
							:article-code="articleCode"
							:content="callCountToChatWithHeadStrict"
						/>
						<TextWithDialog
							v-else
							id="callCountToChatWithHeadControl"
							:article-code="articleCode"
							dialog-target-id="crm-copilot__call-assessment-item-selector"
							:content="callCountToChatWithHeadFluid"
							:value="fluidCallCount"
							:selectedItems="getDialogSelectedItems"
							:items="getDialogItems"
							:tabs="getDialogTabs"
							@onSelectItem="setFluidCallCount"
						/>
					</div>
					<div class="crm-copilot__call-assessment_page-section-body-field  --control-head">
						<HeadSelector
							:selectedItems="headSelectorItems"
							@onSelectItem="setHeadItem"
						/>
					</div>
					<div class="crm-copilot__call-assessment_page-section-body-field --checkbox">
						<label class="ui-ctl ui-ctl-w100 ui-ctl-checkbox">
							<input class="ui-ctl-element" type="checkbox" v-model="useSummary">
							<span class="ui-ctl-label-text">{{ summaryByManagers }}</span>
						</label>
					</div>
				</div>
			</div>
		</div>
	`,
};
