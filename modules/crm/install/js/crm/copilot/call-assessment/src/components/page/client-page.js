import { Loc, Type } from 'main.core';
import { clientType as clientTypeEnum } from '../enum/client-type';
import { BasePage } from './base-page';

export const ClientPage = {
	extends: BasePage,

	data(): Object
	{
		return {
			id: 'client',
			activeClientTypeIds: this.data.activeClientTypeIds ?? [],
		};
	},

	methods: {
		getData(): Object
		{
			return {
				clientTypeIds: [...this.activeClientTypeIds],
			};
		},
		getClientTypeClassList(clientTypeId: string): string[]
		{
			const clientType = this.clientTypes.find((client) => client.id === clientTypeId);

			return [
				'crm-copilot__call-assessment_page-section-body-field-client-type',
				`--client-${clientType.id}`,
				{ '--active': this.activeClientTypeIds.includes(clientTypeId) },
				{ '--readonly': this.readOnly },
			];
		},
		onClientTypeSelect(clientTypeId: string): void
		{
			if (this.readOnly)
			{
				return;
			}

			if (this.activeClientTypeIds.includes(clientTypeId))
			{
				const index = this.activeClientTypeIds.indexOf(clientTypeId);
				this.activeClientTypeIds.splice(index, 1);
			}
			else
			{
				this.activeClientTypeIds.push(clientTypeId);
			}

			this.emitChangeData();
		},
		onCheckboxClick(): boolean
		{
			return !this.readOnly;
		},
		validate(): boolean
		{
			return Type.isArrayFilled(this.activeClientTypeIds);
		},
	},

	computed: {
		pageTitle(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TITLE');
		},
		pageDescription(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_DESCRIPTION');
		},
		clientTypes(): Array<Object>
		{
			return [
				{
					id: clientTypeEnum.new,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_NEW_TITLE'),
					description: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_NEW_DESCRIPTION'),
				},
				{
					id: clientTypeEnum.inWork,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_IN_WORK_TITLE'),
					description: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_IN_WORK_DESCRIPTION'),
				},
				{
					id: clientTypeEnum.return,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_RETURN_CUSTOMER_TITLE'),
					description: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_RETURN_CUSTOMER_DESCRIPTION'),
				},
				{
					id: clientTypeEnum.repeated,
					title: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_REPEATED_APPROACH_TITLE'),
					description: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_REPEATED_APPROACH_DESCRIPTION'),
				},
			];
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
					<AiDisabledInSettings v-if="!isEnabled" />
					<div :class="this.getBodyFieldClassList()">
						<div v-for="(clientType, index) in clientTypes">
							<div
								:class=getClientTypeClassList(clientType.id)
								@click="onClientTypeSelect(clientType.id)"
							>
								<div class="crm-copilot__call-assessment_page-section-body-field-client-type-avatar">
								</div>
								<div class="crm-copilot__call-assessment_page-section-body-field-client-type-info">
									<div class="crm-copilot__call-assessment_page-section-body-field-client-type-title">
										{{ clientType.title }}
									</div>
									<div class="crm-copilot__call-assessment_page-section-body-field-client-type-description">
										{{ clientType.description }}
									</div>
								</div>
								<input
									:class="(readOnly ? 'readonly' : '')"
									:onclick="onCheckboxClick"
									type="checkbox"
									v-model="activeClientTypeIds"
									:value="clientType.id"
								>
							</div>
							<hr
								v-if="index + 1 < clientTypes.length"
								class="crm-copilot__call-assessment_page-section-body-field-client-type-divider"
							>
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
