import './style.css';

const FieldLayout = {
	props: ['field'],
	template: `
		<hr v-if="field.content.type=='hr'" class="b24-form-field-layout-hr">
		<div v-else-if="field.content.type=='br'" class="b24-form-field-layout-br"></div>
		<div v-else-if="field.content.type=='section'" class="b24-form-field-layout-section">
			{{ field.label }}
		</div>
		<div v-else-if="field.content.html" v-html="field.content.html"></div>
	`
};

export {
	FieldLayout
}