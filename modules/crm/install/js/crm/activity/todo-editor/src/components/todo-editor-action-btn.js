import { Popup } from 'main.popup';

export const TodoEditorActionBtn = {
	props: {
		icon: {
			type: String,
			required: true,
			default: '',
		},
		action: {
			type: Function,
			required: true,
			default: () => {}
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
		onMouseEnter(event): void
		{
			if (!this.description) {
				return;
			}

			this.popup = new Popup({
				content: this.description,
				bindElement: event.target,
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

		onMouseLeave(): void
		{
			if (!this.popup || !this.description)
			{
				return;
			}

			this.popup.close();
			this.popup = null;
		},

		onButtonClick(): void
		{
			this.action.call(this);
		}
	},
	template: `
		<button
			@mouseenter="onMouseEnter"
			@mouseleave="onMouseLeave"
			@click="onButtonClick"
			class="crm-activity__todo-editor_action-btn"
		>
			<i :class="iconClassname"></i>
		</button>
	`
}
