import { BitrixVueComponentProps } from 'ui.vue3';
import { MiniCardItem } from '../lib/model/mini-card-item';

import { ImageAvatar } from './avatar/avatar-list/image-avatar/image-avatar';
import { IconAvatar } from './avatar/avatar-list/icon-avatar/icon-avatar';

import { ButtonControl } from './control/control-list/button-control/button-control';

import { CommonField } from './field/field-list/common-field/common-field';
import { PhoneField } from './field/field-list/phone-field/phone-field';
import { EmailField } from './field/field-list/email-field/email-field';
import { LinkField } from './field/field-list/link-field/link-field';
import { MoneyField } from './field/field-list/money-field/money-field';
import { StageField } from './field/field-list/stage-field/stage-field';
import { ClientField } from './field/field-list/client-field/client-field';
import { ProductField } from './field/field-list/product-field/product-field';

import { CommonFooterNote } from './footer-note/footer-note-list/common-footer-note/common-footer-note';

export const MiniCardContent: BitrixVueComponentProps = {
	name: 'MiniCardContent',

	components: {
		ImageAvatar,
		IconAvatar,

		ButtonControl,

		CommonField,
		PhoneField,
		EmailField,
		LinkField,
		MoneyField,
		StageField,
		ClientField,
		ProductField,

		CommonFooterNote,
	},

	props: {
		miniCard: {
			type: MiniCardItem,
			required: true,
		},
		popup: {
			type: Object,
			default: () => null,
		},
	},

	mounted()
	{
		this.adjustPopup();
	},

	methods: {
		adjustPopup(): void
		{
			if (!this.popup)
			{
				return;
			}

			this.popup.adjustPosition({
				forceBindPosition: true,
				forceTop: true,
			});

			this.popup.bindOptions = {
				forceBindPosition: true,
			};
		},
	},

	template: `
		<div class="crm-mini-card-content" :id="miniCard.id()">
			<div class="crm-mini-card-content__header">
				<div class="crm-mini-card-content__header-info">
					<div class="crm-mini-card-content__header-icon">
						<component :is="miniCard.avatar().componentName()" v-bind="miniCard.avatar().componentProps()" />
					</div>
					<div class="crm-mini-card-content__header-title">
						{{ miniCard.title() }}
					</div>
				</div>
				<div class="crm-mini-card-content__header-control-container">
					<div class="crm-mini-card-content__header-control-list">
						<div class="crm-mini-card-content__header-control-item" v-for="control in miniCard.controls()">
							<component :is="control.componentName()" v-bind="control.componentProps()" />
						</div>
					</div>
				</div>
			</div>
			<div class="crm-mini-card-content__body">
				<div class="crm-mini-card-content__body-field-container">
					<div class="crm-mini-card-content__body-field-list">
						<div class="crm-mini-card-content__body-field-item" v-for="field in miniCard.fields()">
							<component :is="field.componentName()" v-bind="field.componentProps()" />
						</div>
					</div>
				</div>
			</div>
			<div class="crm-mini-card-content__footer">
				<div class="crm-mini-card-content__footer-notes-list">
					<div v-for="footerNote in miniCard.footerNotes()" class="crm-mini-card-content__footer-note-item">
						<component :is="footerNote.componentName()" v-bind="footerNote.componentProps()" />
					</div>
				</div>
			</div>
		</div>
	`,
};
