export const TodoEditorActionDelimiter = {
	computed: {
		className(): Array
		{
			return [
				'crm-activity__todo-editor_action-delimiter',
			]
		}
	},

	template: `
		<span :class="className">
			<i class="crm-activity__todo-editor_action-delimiter-icon"></i>
		</span>
	`
};
