import { Popup, PopupOptions } from 'main.popup';
import {Event, Runtime, Dom} from 'main.core';
import { Action } from '../../../action';

export const Hint = {
	data(): Object {
		return {
			isMouseOnHintArea: false,
			hintPopup: null,
		}
	},
	props: {
		icon: {
			type: String,
			required: false,
			default: '',
		},
		textBlocks: {
			type: Array,
			required: false,
			default: [],
		},
	},
	computed: {
		hintContentIcon(): ?HTMLElement {
			if (this.icon === '')
			{
				return null;
			}

			const iconElement = Dom.create('i');

			return Dom.create('div', {
				attrs: {
					classname: this.hintContentIconClassname,
				},
				children: [iconElement],
			});
		},

		hintContentText(): HTMLElement {
			return Dom.create('div', {
				attrs: {
					classname: 'crm-timeline__hint_popup-content-text',
				},
				children: this.hintContentTextBlocks,
			});
		},

		hintContentTextBlocks(): HTMLElement[] {
			return this.textBlocks.map(this.getContentBlockNode);
		},

		hintContentIconClassname(): string {
			const baseClassname = 'crm-timeline__hint_popup-content-icon';
			return `${baseClassname} --${this.icon}`;
		},

		hintIconClassname(): Array {
			return [
				'ui-hint',
				'crm-timeline__header-hint', {
				'--active': this.hintPopup,
				}
			]
		},

		hasContent(): boolean {
			return this.textBlocks.length > 0;
		},
	},
	methods: {
		getHintContent(): HTMLElement {
			return Dom.create('div', {
				attrs: {
					classname: 'crm-timeline__hint_popup-content'
				},
				style: {
					display: 'flex',
				},
				children: [this.hintContentIcon, this.hintContentText],
			});
		},

		getPopupOptions(): PopupOptions {
			return {
				darkMode: true,
				autoHide: false,
				content: this.getHintContent(),
				maxWidth: 400,
				bindOptions: {
					position: 'top',
				},
				animation: 'fading-slide',
			}
		},

		getPopupPosition(): {left: number, right: number} {
			const hintElem = this.$refs.hint;
			const defaultAngleLeftOffset = Popup.getOption('angleLeftOffset');
			const { width: hintWidth, left: hintLeftOffset, top: hintTopOffset } = Dom.getPosition(hintElem);
			const { width: popupWidth } = Dom.getPosition(this.hintPopup?.getPopupContainer());
			return {
				left: hintLeftOffset + defaultAngleLeftOffset - (popupWidth - hintWidth) / 2,
				top: hintTopOffset + 15,
			}
		},

		getPopupAngleOffset(popupContainer: HTMLElement): number {
			const angleWidth = 33;
			const { width: popupWidth } = Dom.getPosition(popupContainer);
			return (popupWidth - angleWidth) / 2;
		},

		onMouseEnterToPopup(): void {
			this.isMouseOnHintArea = true;
		},

		onHintAreaMouseLeave(): void {
			this.isMouseOnHintArea = false;
			setTimeout(() => {
				if (!this.isMouseOnHintArea)
				{
					this.hideHintPopup();
				}
			}, 400);
		},

		onMouseEnterToHint(): void {
			this.isMouseOnHintArea = true;
			this.showHintPopupWithDebounce();
		},

		showHintPopup(): void {
			if (!this.isMouseOnHintArea || this.hintPopup && this.hintPopup.isShown())
			{
				return;
			}
			this.hintPopup = new Popup(this.getPopupOptions());
			const popupContainer = this.hintPopup.getPopupContainer();
			Event.bind(popupContainer, 'mouseenter', this.onMouseEnterToPopup);
			Event.bind(popupContainer, 'mouseleave', this.onHintAreaMouseLeave);
			this.hintPopup.show();
			this.hintPopup.setBindElement(this.getPopupPosition());
			this.hintPopup.setAngle(false);
			this.hintPopup.setAngle({offset: this.getPopupAngleOffset(popupContainer, this.$refs.hint)});
			this.hintPopup.adjustPosition();
			this.hintPopup.show();
		},

		showHintPopupWithDebounce() {
			Runtime.debounce(this.showHintPopup, 300, this)();
		},

		hideHintPopup(): void {
			if (!this.hintPopup)
			{
				return;
			}
			this.hintPopup.close();
			const popupContainer = this.hintPopup.getPopupContainer();
			Event.unbind(popupContainer, 'mouseenter', this.onMouseEnterToPopup);
			Event.unbind(popupContainer, 'mouseleave', this.onHintAreaMouseLeave);
			this.hintPopup.destroy();
			this.hintPopup = null;
		},

		hideHintPopupWithDebounce(): void {
			return Runtime.debounce(this.hideHintPopup, 300, this);
		},

		getContentBlockNode(contentBlock): HTMLElement | null {
			if (contentBlock.type === 'text')
			{
				return this.getTextNode(contentBlock.options);
			}
			else if (contentBlock.type === 'link')
			{
				return this.getLinkNode(contentBlock.options);
			}

			return null;
		},

		getTextNode(textOptions = {}): HTMLElement {
			return Dom.create('span', {text: textOptions.text});
		},

		getLinkNode(linkOptions = {}): HTMLElement {
			const link = Dom.create('span', {text: linkOptions.text});
			Dom.addClass(link, 'crm-timeline__hint_popup-content-link');
			link.onclick = () => {
				this.executeAction(linkOptions.action);
			}

			return link;
		},

		executeAction(actionObj): void {
			if (actionObj)
			{
				const action = new Action(actionObj);
				action.execute(this);
			}
		},
	},

	template: `
		<span
			ref="hint"
			@click.stop.prevent
			@mouseenter="onMouseEnterToHint"
			@mouseleave="onHintAreaMouseLeave"
			v-if="hasContent"
			:class="hintIconClassname"
		>
			<span class="ui-hint-icon" />
		</span>
	`
}
