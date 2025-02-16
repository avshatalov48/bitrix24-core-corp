import { Button as UiButton, ButtonSize, ButtonColor } from 'booking.component.button';
import './dialog-footer.css';

export const DialogFooter = {
	name: 'DialogFooter',
	emits: ['reset'],
	computed: {
		buttonSettings(): { size: string, color: string }
		{
			return Object.freeze({
				size: ButtonSize.SMALL,
				color: ButtonColor.LINK,
			});
		},
		buttonLabel(): string
		{
			return this.loc('BOOKING_BOOKING_RESOURCES_DIALOG_RESET');
		},
	},
	components: {
		UiButton,
	},
	template: `
		<div class="booking--booking--select-resources-dialog-footer">
			<UiButton
				:size="buttonSettings.size"
				:color="buttonSettings.color"
				:text="buttonLabel"
				button-class="booking--booking--select-resources-dialog-footer__button"
				@click="$emit('reset')"
			/>
		</div>
	`,
};
