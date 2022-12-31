import {Popup} from 'main.popup';

export const TodoEditorActionBtn = {
	props: {
		icon: {
			type: String,
			required: true,
			default: '',
		},
		action: {
			type: Object,
			required: true,
			default: () => ({}),
		},
		description: {
			type: String,
			required: false,
			default: '',
		}
	},
	data() {
		return {
			popup: null,
		};
	},
	computed: {
		iconClassname()
		{
			return [
				'crm-activity__todo-editor_action-btn-icon',
				`--${this.icon}`,
			]
		}
	},
	methods: {
		onMouseEnter(e)
		{
			if (!this.description) {
				return;
			}

			this.popup = new Popup({
				content: this.description,
				bindElement: e.target,
				darkMode: true,
			});

			setTimeout(() => {
				if (!this.popup)
				{
					return;
				}

				this.popup.show();
			}, 400);
		},
		onMouseLeave()
		{
			if (!this.popup || !this.description)
			{
				return;
			}

			this.popup.close();
			this.popup = null;
		},
	},
	template: `
		<button
			@mouseenter="onMouseEnter"
			@mouseleave="onMouseLeave"
			class="crm-activity__todo-editor_action-btn"
		>
			<i :class="iconClassname"></i>
		</button>
	`
}