import { ButtonSize, ButtonColor } from 'booking.component.button';
import './cancel-button.css';

type CancelButtonOptions = {
	color: string,
	size: string,
};

export const CancelButton = {
	name: 'CancelButton',
	emits: ['click'],
	setup(): CancelButtonOptions
	{
		return {
			color: ButtonColor.LINK,
			size: ButtonSize.EXTRA_SMALL,
		};
	},
	template: `
		<button
			:class="['ui-btn', 'booking--multi-booking--cancel-button', color, size]"
			type="button"
			ref="button"
			@click="$emit('click')"
		>
			<i
				class="ui-icon-set --cross-25"
				style="--ui-icon-set__icon-base-color: rgba(var(--ui-color-palette-white-base-rgb), 0.3);--ui-icon-set__icon-size: var(--ui-size-2xl)"></i>
		</button>
	`,
};
