import { Hint } from 'ui.vue3.components.hint';
import { RoleMasterProgress } from './role-master-progress';
import { RoleMasterWarning } from './role-master-warning';

import '../css/role-master-step.css';

export const RoleMasterStep = {
	name: 'RoleMasterStep',
	components: {
		RoleMasterProgress,
		RoleMasterWarning,
		Hint,
	},
	props: {
		title: String,
		titleHint: String,
		stepNumber: Number,
		warningText: String,
	},
	template: `
		<div class="ai__role-master_step">
			<div class="ai__role-master_step-header">
				<div class="ai__role-master_step-title-with-hint">
					<h4 class="ai__role-master_step-title">{{ title }}</h4>
					<span v-if="titleHint" class="ai__role-master_step-title-hint">
						<Hint :text="titleHint"></Hint>
					</span>
				</div>
				<div class="ai__role-master_step-progress">
					<RoleMasterProgress
						:current="stepNumber"
					/>
				</div>
				<div v-if="warningText" class="ai__role-master_step-warning">
					<RoleMasterWarning :text="warningText" />
				</div>
			</div>
			<div class="ai__role-master_step-content">
				<slot></slot>
			</div>
		</div>
	`,
};
