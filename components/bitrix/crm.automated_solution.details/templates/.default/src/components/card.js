import '../css/card.css';

export const Card = {
	props: {
		title: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: true,
		},
	},
	data(): Object {
		return {
			isShown: true,
		};
	},
	template: `
		<div v-if="isShown" class="crm-type-ui-card crm-type-ui-card-message">
			<div class="crm-type-ui-card-header">
				<div class="crm-type-ui-card-message-icon crm-type-ui-card-message-icon--custom-fields"></div>
				<div class="crm-type-ui-card-message-title">{{ title }}</div>
			</div>
			<div class="crm-type-ui-card-body">
				<div class="crm-type-ui-card-message-description">{{ description }}</div>
			</div>
			<div 
				class="crm-type-ui-card-message-close-button" 
				:title="$Bitrix.Loc.getMessage('CRM_AUTOMATED_SOLUTION_DETAILS_CARD_CLOSE_TITLE')"
				@click="isShown = false"
			></div>
		</div>
	`,
};
