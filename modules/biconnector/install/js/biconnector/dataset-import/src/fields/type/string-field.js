import { Dom, Tag } from 'main.core';
import { Popup, PopupOptions } from 'main.popup';
import { ErrorPopup } from '../../layout/error-popup';
import { BaseField } from './base-field';
import '../../css/dataset-import-field.css';
import 'ui.hint';

export const StringField = {
	extends: BaseField,
	data()
	{
		return {
			errorPopup: null,
			errorPopupTimeout: null,
		};
	},
	methods: {
		onInput(event)
		{
			this.areValidationErrorsShown = false;
			this.onInputChange(event.target.value);
		},
		onBlur()
		{
			this.areValidationErrorsShown = true;
			if (!this.isValid)
			{
				this.showErrorHint();
				this.errorPopupTimeout = setTimeout(() => {
					this.hideErrorHint();
					this.errorPopupTimeout = null;
				}, 3000);
			}
		},
		showErrorHint()
		{
			if (this.errorPopupTimeout)
			{
				clearTimeout(this.errorPopupTimeout);
				this.errorPopupTimeout = null;
			}
			else
			{
				this.errorPopup = this.createErrorPopup();
				this.errorPopup.show();
			}
		},
		hideErrorHint()
		{
			if (this.errorPopup)
			{
				this.errorPopup.close();
			}
		},
		createErrorPopup()
		{
			return ErrorPopup.create(this.errorMessage, this.$refs.errorIconWrapper);
		},
	},
	mounted()
	{
		if (this.hintText)
		{
			Dom.append(BX.UI.Hint.createNode(this.hintText), this.$refs.title);
		}
	},
	// language=Vue
	template: `
		<div class="ui-form-row">
			<div class="ui-form-label">
				<div class="ui-ctl-label-text" ref="title">
					{{ title }}
				</div>
			</div>
			<div class="ui-ctl ui-ctl-after-icon ui-ctl-textbox ui-ctl-w100 dataset-import-control">
				<input
					class="ui-ctl-element dataset-import-field"
					type="text"
					:class="{ 'data-import-field--invalid': !isValid && areValidationErrorsShown }"
					:disabled="isDisabled"
					:placeholder="placeholder"
					v-model="value"
					@input="onInput"
					@blur="onBlur"
				>
				<div class="ui-ctl-after" ref="errorIconWrapper">
					<div
						class="ui-icon-set --warning format-table__error-icon"
						@mouseenter="showErrorHint"
						@mouseleave="hideErrorHint"
						v-if="!isValid && areValidationErrorsShown"
					></div>
				</div>
			</div>
		</div>
	`,
};
