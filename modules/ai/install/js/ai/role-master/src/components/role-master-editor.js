import '../css/role-master-editor.css';

export const RoleMasterEditor = {
	props: {
		text: String,
		maxTextLength: Number,
		minTextLength: {
			type: Number,
			required: false,
			default: 5,
		},
		placeholder: String,
	},
	emits: ['update:text'],
	computed: {
		textLength(): number {
			return this.text?.length || 0;
		},
	},
	methods: {
		handleInput(e: InputEvent) {
			this.$emit('update:text', e.target.value);
		},
	},
	mounted() {
		requestAnimationFrame(() => {
			this.$refs.textField.focus();
		});
	},
	template: `
		<div class="ai__role-master_editor ui-ctl-textarea">
			<textarea
				ref="textField"
				:value="text"
				:maxlength="maxTextLength"
				:minlength="minTextLength"
				:placeholder="placeholder"
				@input="handleInput"
				class="ai__role-master_editor-text-field ui-ctl-element"
			></textarea>
			<div class="ai__role-master_editor-character-counter">
				{{ textLength }}/{{ maxTextLength }}
			</div>
		</div>
	`,
};
