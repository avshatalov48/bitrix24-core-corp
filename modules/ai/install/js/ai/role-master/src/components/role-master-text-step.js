import { RoleMasterEditor } from './role-master-editor';
import { RoleMasterStep } from './role-master-step';
export const RoleMasterTextStep = {
	props: {
		roleText: String,
		stepNumber: Number,
		maxTextLength: Number,
		minTextLength: Number,
		warningText: String,
	},
	components: {
		RoleMasterStep,
		RoleMasterEditor,
	},
	methods: {
		handleRoleTextUpdate(value: string): void {
			this.$emit('update:role-text', value);
		},
	},
	template: `
		<RoleMasterStep
			:title="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_TEXT_STEP_TITLE')"
			:title-hint="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_TEXT_STEP_TITLE_HINT')"
			:warningText="warningText"
			:step-number="stepNumber"
		>
			<slot>
				<RoleMasterEditor
					:text="roleText"
					@update:text="handleRoleTextUpdate"
					:max-text-length="maxTextLength"
					:min-text-length="minTextLength"
					:placeholder="$Bitrix.Loc.getMessage('ROLE_MASTER_ROLE_TEXT_FIELD_PLACEHOLDER')"
				></RoleMasterEditor>
			</slot>
		</RoleMasterStep>
	`,
};
