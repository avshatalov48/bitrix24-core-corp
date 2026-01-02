import type { BitrixVueComponentProps } from 'ui.vue3';
import { Popup as MainPopup, PopupManager, type PopupOptions } from 'main.popup';
import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';
import { Button as UiButton, AirButtonStyle, ButtonSize } from 'ui.vue3.components.button';

export const DeletionWarningPopup: BitrixVueComponentProps = {
	emits: ['confirm', 'close'],
	props: {},
	setup(): Object
	{
		return {
			ButtonSize,
			AirButtonStyle,
			Outline,
		};
	},
	data(): Object
	{
		return {
			popupInstance: null,
			popupId: 'biconnector-dashboard-deletion',
			dontShow: false,
		};
	},
	computed: {
		popupOptions(): PopupOptions
		{
			return {
				id: this.popupId,
				content: this.$refs.content,
				width: 410,
				padding: 12,
				borderRadius: '16px',
				autoHide: true,
				overlay: true,
				events: {
					onPopupClose: this.closePopup,
				},
				fixed: true,
			};
		},
	},
	methods: {
		closePopup(): void
		{
			if (this.popupInstance)
			{
				PopupManager.getPopupById(this.popupId)?.destroy();
				this.popupInstance = null;
				this.$emit('close');
			}
		},
		onConfirm(): void
		{
			this.$emit('confirm', { dontShow: this.dontShow });
			this.closePopup();
		},
		getPopupText(): string
		{
			return this.$Bitrix.Loc.getMessage('BI_GROUP_SAVE_WARN_TEXT_MSGVER_1');
		},
	},
	mounted(): void
	{
		if (!this.popupInstance)
		{
			this.popupInstance = new MainPopup(this.popupOptions);
		}

		this.popupInstance.show();
	},
	beforeUnmount(): void
	{
		this.closePopup();
	},
	components: {
		BIcon,
		UiButton,
	},
	template: `
		<div ref="content" class="group-warn-popup">
			<div class="group-warn-popup-title-block">
				<div class="group-warn-popup-title">{{ $Bitrix.Loc.getMessage('BI_GROUP_SAVE_WARN_TITLE') }}</div>
				<BIcon
					:name="Outline.CROSS_L"
					:size="24"
					color="#A7A7A7"
					:class="'group-warn-popup-close'"
					@click="closePopup"
				></BIcon>
			</div>
			<div class="group-warn-popup-box" v-html="getPopupText()"></div>
			<div class="group-warn-popup-buttons">
				<UiButton
					:text="$Bitrix.Loc.getMessage('BI_GROUP_SAVE_WARN_YES')"
					:size="ButtonSize.MEDIUM"
					:style="AirButtonStyle.FILLED"
					@click="onConfirm"
				/>
				<UiButton
					:text="$Bitrix.Loc.getMessage('BI_GROUP_SAVE_WARN_NO')"
					:size="ButtonSize.MEDIUM"
					:style="AirButtonStyle.PLAIN"
					@click="closePopup"
				/>
			</div>
		</div>
	`,
};
