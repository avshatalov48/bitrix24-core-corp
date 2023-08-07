import { Loc } from 'main.core';
import { Popup } from 'main.popup';

import type { BaseEvent } from 'main.core.events';
import type { BitrixVueComponentProps } from 'ui.vue3';

import './css/insert-into-text-button.css';

export const InsertIntoTextButton: BitrixVueComponentProps = {
	name: 'InsertIntoTextButton',
	inject: ['uploader', 'postForm'],
	props: {
		item: {
			type: Object,
			default: {}
		},
	},
	methods: {
		click(): void
		{
			this.postForm.getParser().insertFile(this.item);
		},

		handleMouseEnter(event: MouseEvent): void
		{
			if (this.hintPopup)
			{
				return;
			}

			const targetNode: HTMLElement = event.currentTarget;
			const targetNodeWidth: number = targetNode.offsetWidth;

			this.hintPopup = new Popup({
				content: Loc.getMessage('DISK_UF_WIDGET_INSERT_INTO_THE_TEXT'),
				cacheable: false,
				animation: 'fading-slide',
				bindElement: targetNode,
				offsetTop: 0,
				bindOptions: {
					position: 'top',
				},
				darkMode: true,
				events: {
					onClose: (): void => {
						this.hintPopup.destroy();
						this.hintPopup = null;
					},
					onShow: (event: BaseEvent): void => {
						const popup = event.getTarget();
						const popupWidth = popup.getPopupContainer().offsetWidth;
						const offsetLeft: number = (targetNodeWidth / 2) - (popupWidth / 2);
						const angleShift: number = Popup.getOption('angleLeftOffset') - Popup.getOption('angleMinTop');

						popup.setAngle({ offset: popupWidth / 2 - angleShift });
						popup.setOffset({ offsetLeft: offsetLeft + Popup.getOption('angleLeftOffset') });
					}
				}
			});

			this.hintPopup.show();
		},

		handleMouseLeave(event: Event): void
		{
			if (this.hintPopup)
			{
				this.hintPopup.close();
				this.hintPopup = null;
			}
		}
	},
	// language=Vue
	template: `
		<div 
			class="disk-user-field-insert-into-text-button"
			:class="[{ '--inserted': item.tileWidgetData.selected }]"
			@mouseenter="handleMouseEnter" 
			@mouseleave="handleMouseLeave" 
			@click="click"
		></div>
	`
};
