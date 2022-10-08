import * as Mixins from "../base/components/mixins";
import { Field } from '../base/components/field';

export const FieldsContainer = {
	name: 'field-container', // for recurrence
	mixins: [Mixins.MixinField],
	components: {
		Field,
	},
	template: `
		<transition-group name="b24-form-field-a-slide" tag="div"
			v-if="field.nestedFields.length > 0"		
		>
			<Field
				v-for="nestedField in field.nestedFields"
				:field="nestedField"
				:key="field.name + '-' + nestedField.name"
				@input-blur=""
				@input-focus=""
				@input-key-down=""
			/>
		</transition-group>
	`,
};