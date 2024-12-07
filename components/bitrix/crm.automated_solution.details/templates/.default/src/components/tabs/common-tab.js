import { Card } from '../card';
import '../../css/tabs/common-tab.css';

export const CommonTab = {
	components: {
		Card,
	},
	computed: {
		title: {
			get(): ?string
			{
				return this.$store.state.automatedSolution.title;
			},
			set(title: string): void
			{
				this.$store.dispatch('setTitle', title);
			},
		},
	},
	template: `
		<div data-tab="common">
			<div class="ui-title-3">{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_TAB_TITLE_COMMON') }}</div>
			<Card
				:title="$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_CARD_COMMON_TITLE')"
				:description="$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_CARD_COMMON_DESCRIPTION')"
			/>
			<div class="ui-form-row crm-automated-solution-details-form-label-xs">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">{{ $Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_FIELD_LABEL_TITLE') }}</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						<input
							v-model="title"
							type="text"
							class="ui-ctl-element"
							:placeholder="$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_FIELD_PLACEHOLDER_TITLE')"
						/>
					</div>
				</div>
			</div>
		</div>
	`,
};
