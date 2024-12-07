import 'ui.hint';
import { clickableHint } from '../directives/prompt-master-hover-hint';

export const PromptMasterHint = {
	directives: {
		clickableHint,
	},
	props: {
		html: String,
	},
	template: `
		<span class="ui-hint" v-clickable-hint="html">
			<span class="ui-hint-icon"/>
		</span>
	`,
};
