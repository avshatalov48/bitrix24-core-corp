export const RecallButton = {
	props: {
		id: String,
		active: Boolean,
		title: String,
	},
	methods: {
		onClick: function() {
			this.$emit('onClick', this.id);
		},
	},
	template: `
		<button @click="onClick" :class="['ui-btn ui-btn-secondary ui-btn-md ui-btn-no-caps', { active }]">
			{{this.title}}
		</button>
	`
};
