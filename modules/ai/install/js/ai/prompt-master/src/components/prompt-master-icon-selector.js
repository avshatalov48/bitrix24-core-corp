import { Tag, Dom, Event } from 'main.core';
import { BIcon } from 'ui.icon-set.api.vue';
import { Icon } from 'ui.icon-set.api.core';
import { availablePromptIcons } from '../helpers/available-prompt-icons';
import { Popup } from 'main.popup';

import '../css/prompt-master-icon-selector.css';

export const PromptMasterIconSelector = {
	components: {
		BIcon,
	},
	props: {
		selectedIcon: {
			type: String,
			required: false,
			default: '',
		},
	},
	data(): { isPopupShown: boolean, popup: Popup } {
		return {
			isPopupShown: false,
			popup: null,
		};
	},
	computed: {
		selectedIconColor(): string {
			return getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary');
		},
	},
	methods: {
		toggleIconsPopup(): void {
			if (this.isPopupShown)
			{
				this.closeIconsPopup();
			}
			else
			{
				this.showIconsPopup();
			}
		},
		closeIconsPopup(): void {
			this.isPopupShown = false;
			this.popup?.close();
			this.popup = null;
		},
		showIconsPopup(): void {
			if (this.isPopupShown)
			{
				return;
			}

			if (!this.popup)
			{
				this.initIconsPopup();
			}

			this.isPopupShown = true;

			this.popup.show();
		},
		initIconsPopup(): void
		{
			const bindElementPosition = Dom.getPosition(this.$el);

			this.popup = new Popup({
				content: this.getPopupContent(),
				bindElement: {
					top: bindElementPosition.bottom,
					left: bindElementPosition.left - 210,
				},
				autoHide: true,
				closeByEsc: true,
				cacheable: false,
				maxWidth: 299,
				maxHeight: 208,
				angle: {
					offset: 253,
					position: 'top',
				},
				events: {
					onPopupClose: () => {
						this.isPopupShown = false;
						this.popup = null;
					},
				},
			});
		},
		getPopupContent(): HTMLElement {
			const container = Tag.render`<div class="ai__prompt-master-icon-selector_popup-content"></div>`;

			availablePromptIcons.forEach((iconCode) => {
				const icon = new Icon({
					icon: iconCode,
					size: 24,
				});

				const iconContainer = Tag.render`<div class="ai__prompt-master-icon-selector_popup-content-icon"></div>`;

				if (iconCode === this.selectedIcon)
				{
					Dom.addClass(iconContainer, '--selected');
				}

				icon.renderTo(iconContainer);

				Event.bind(iconContainer, 'click', () => {
					this.selectIcon(iconCode);
					this.closeIconsPopup();
				});

				Dom.append(iconContainer, container);
			});

			return container;
		},
		selectIcon(iconCode: string): void {
			this.$emit('select', iconCode);
		},
	},
	unmounted() {
		this.popup?.destroy();
		this.popup = null;
	},
	template: `
		<button @click="toggleIconsPopup" class="ai__prompt-master-icon-selector">
			<span ref="selectedIcon" class="ai__prompt-master-icon-selector_selected-icon">
				<BIcon :name="selectedIcon" :size="28" :color="selectedIconColor" />
			</span>
		</button>
	`,
};
