import 'ui.design-tokens';
import {Action} from '../../../action';

export default {
	props: {
		text: {
			type: String,
			required: true,
		},
		action: Object,
	},
	methods: {
		executeAction(): void
		{
			if (this.action)
			{
				const action = new Action(this.action);
				action.execute(this);
			}
		}
	},
	// language=Vue
	template: `
		<div class="crm-entity-stream-advice-list-btn-box">
			<button
				@click="executeAction"
				class="crm-entity-stream-advice-list-btn"
			>
				{{text}}
			</button>
		</div>
	`
}
