import { BasePopup } from './base-popup';
import { DefaultPopupLayout } from './layout/default-popup-layout';
import { Tag, Event } from 'main.core';
import type { PopupOptions } from 'main.popup';
import './styles/confirmation-popup.css';

export const ConfirmationPopup = {
	name: 'ConfirmationPopup',
	emits: ['close', 'action'],

	components: {
		BasePopup,
		DefaultLayoutPopup: DefaultPopupLayout,
	},

	props: {
		title: {
			type: String,
			required: false,
			default: null,
		},
		withoutTitleBar: {
			type: Boolean,
			required: false,
			default: false,
		},
		description: {
			type: String,
			required: false,
		},
		onlyConfirmButtonMode: {
			type: Boolean,
			required: false,
			default: false,
		},
		confirmBtnText: {
			type: String,
			required: false,
			default: null,
		},
		showActionButtonLoader: {
			type: Boolean,
			required: false,
			default: false,
		},
		lockActionButton: {
			type: Boolean,
			required: false,
			default: false,
		},
		cancelBtnText: {
			type: String,
			required: false,
			default: null,
		},
		bindElement: {
			type: HTMLElement,
			required: false,
			default: null,
		},
		width: {
			type: Number,
			required: false,
			default: 300,
		},
	},

	methods: {
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		closeAction(): void
		{
			if (this.showActionButtonLoader)
			{
				return;
			}

			this.$emit('close');
		},
		performAction(): Promise<void>
		{
			if (this.lockActionButton || this.showActionButtonLoader)
			{
				return;
			}

			this.$emit('action');
		},
		getTitleBar(): Object
		{
			const { root, closeButton } = Tag.render`
				<div class="hr-confirmation-popup__title-bar">
					<span class="hr-confirmation-popup__title-bar-text">
						${this.title ?? ''}
					</span>
					<div
						class="ui-icon-set --cross-25 hr-confirmation-popup__title-bar-close-button"
						ref="closeButton"
					>
					</div>
				</div>
			`;

			Event.bind(closeButton, 'click', () => {
				this.closeAction();
			});

			return { content: root };
		},
	},

	computed: {
		popupConfig(): PopupOptions
		{
			return {
				width: this.width,
				bindElement: this.bindElement,
				borderRadius: 12,
				overlay: this.bindElement === null ? { opacity: 40 } : false,
				contentNoPaddings: true,
				contentPadding: 0,
				padding: 0,
				className: 'hr_structure_confirmation_popup',
				autoHide: false,
				draggable: true,
				titleBar: this.withoutTitleBar ? null : this.getTitleBar(),
			};
		},
	},

	template: `
		<BasePopup
			:id="'id'"
			:config="popupConfig"
		>
			<template v-slot="{ closePopup }">
				<DefaultLayoutPopup>
					<template v-slot:content>
						<div
							class="hr-confirmation-popup__content-container"
							:class="{ '--without-title-bar': withoutTitleBar }"
						>
							<div v-if="$slots.content">
								<slot name="content"></slot>
							</div>
							<div v-else class="hr-confirmation-popup__content-text">
								{{ description }}
							</div>
						</div>
						<div class="hr-confirmation-popup__buttons-container">
							<button
								class="ui-btn ui-btn-primary ui-btn-round"
								:class="{ 'ui-btn-wait': showActionButtonLoader, 'ui-btn-disabled': lockActionButton }"
								@click="performAction"
							>
								{{ confirmBtnText ?? '' }}
							</button>
							<button
								v-if="!onlyConfirmButtonMode"
								class="ui-btn ui-btn-light-border ui-btn-round"
								@click="closeAction"
							>
								{{ cancelBtnText ?? loc('HUMANRESOURCES_COMPANY_STRUCTURE_STRUCTURE_COMPONENTS_POPUP_CONFIRMATION_POPUP_CANCEL_BUTTON') }}
							</button>
						</div>
					</template>
				</DefaultLayoutPopup>
			</template>
		</BasePopup>
	`,
};
