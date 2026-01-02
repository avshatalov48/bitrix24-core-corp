import { BitrixVueComponentProps } from 'ui.vue3';
import { Text } from 'main.core';
import { Button, ButtonSize, AirButtonStyle, type ButtonOptions } from 'ui.buttons';
import { Outline } from 'ui.icon-set.api.core';
import { ServiceLocator } from '../../../../../lib/service-locator';

const CommunicationType = Object.freeze({
	Phone: 'PHONE',
	Email: 'EMAIL',
	Im: 'IM',
});

declare type CommunicationTypeValue = $Values<typeof CommunicationType>;

export const BUTTON_CONFIGURATION: Object<CommunicationTypeValue, ButtonOptions> = Object.freeze({
	[CommunicationType.Phone]: {
		icon: Outline.PHONE_UP,
		removeRightCorners: true,
	},
	[CommunicationType.Email]: {
		icon: Outline.MAIL,
		removeLeftCorners: true,
		removeRightCorners: true,
	},
	[CommunicationType.Im]: {
		icon: Outline.OPEN_CHANNELS,
		removeLeftCorners: true,
	},
});

export type Communication = {
	typeId: string,
	valueTypeCaption: string,
	valueType: string,
	value: string,
};

export type Entity = {
	entityTypeId: number,
	entityId: number,
	ownerTypeId: number,
	ownerId: number,
};

export const CommunicationControl: BitrixVueComponentProps = {
	name: 'CommunicationControl',

	props: {
		communications: {
			/** @type Communication[] */
			type: Array,
			required: true,
		},
		entity: {
			/** @type Entity */
			type: Object,
			required: true,
		},
	},

	buttons: null,

	data(): Object
	{
		return {
			CommunicationType,
			communicationsByType: {
				[CommunicationType.Phone]: this.filterCommunication(CommunicationType.Phone),
				[CommunicationType.Email]: this.filterCommunication(CommunicationType.Email),
				[CommunicationType.Im]: this.filterCommunication(CommunicationType.Im),
			},
		};
	},

	mounted(): void
	{
		this.createButton(CommunicationType.Phone);
		this.createButton(CommunicationType.Email);
		this.createButton(CommunicationType.Im);
	},

	methods: {
		createButton(typeId: CommunicationTypeValue): Button
		{
			const communicationButtonOptions = BUTTON_CONFIGURATION[typeId];
			const communicationsByType = this.communicationsByType[typeId];

			const buttonOptions: ButtonOptions = {
				size: ButtonSize.EXTRA_SMALL,
				style: AirButtonStyle.OUTLINE,
				useAirDesign: true,
				dropdown: false,
				...communicationButtonOptions,
			};

			if (communicationsByType.length === 1)
			{
				buttonOptions.onclick = () => {
					this.communicate(communicationsByType[0]);
				};
			}

			if (communicationsByType.length > 1)
			{
				buttonOptions.menu = {
					items: communicationsByType.map((communication: Communication) => {
						const itemHtmlContent = this.$Bitrix.Loc.getMessage('CRM_MINI_CARD_FIELD_CLIENT_COMMUNICATION_CONTROL_MENU_ITEM')
							.replace('#VALUE#', Text.encode(communication.value))
							.replace('[VALUE_TYPE_TAG]', '<span style="opacity: .5;">')
							.replace('#VALUE_TYPE#', Text.encode(communication.valueTypeCaption))
							.replace('[/VALUE_TYPE_TAG]', '</span>')
						;

						return {
							html: `<span>${itemHtmlContent}</span>`,
							onclick: () => {
								this.communicate(communication);
							},
						};
					}),
				};
			}

			const button = new Button(buttonOptions);

			if (communicationsByType.length === 0)
			{
				button.setDisabled(true);
				button.setState(Button.State.DISABLED);
			}

			const menuWindow = button.getMenuWindow();
			if (menuWindow)
			{
				ServiceLocator
					.getInstance()
					.getEventService()
					.registerChildPopup(this.$Bitrix.eventEmitter, menuWindow.getPopupWindow());
			}

			button.renderTo(this.$refs[typeId]);

			this.buttons ??= {};
			this.buttons[typeId] = button;
		},
		communicate(communication: Communication): void
		{
			const communicationService = ServiceLocator.getInstance().getCommunicationService();

			if (communication.typeId === CommunicationType.Phone)
			{
				communicationService
					.communicateByPhone({
						phone: communication.value,
						entityTypeId: this.entity.entityTypeId,
						entityId: this.entity.entityId,
						ownerTypeId: this.entity.ownerTypeId,
						ownerId: this.entity.ownerId,
					})
				;

				return;
			}

			if (communication.typeId === CommunicationType.Email)
			{
				this.buttons[CommunicationType.Email].setState(Button.State.WAITING);

				void communicationService
					.communicateByEmail({
						email: communication.value,
						ownerTypeId: this.entity.ownerTypeId,
						ownerId: this.entity.ownerId,
						entityTypeId: this.entity.entityTypeId,
						entityId: this.entity.entityId,
						activityEditorContainer: this.$refs.activityEditorContainer,
					})
					.finally(() => {
						this.buttons[CommunicationType.Email].setState(null);
					})
				;

				return;
			}

			if (communication.typeId === CommunicationType.Im)
			{
				communicationService
					.communicateByIM({
						dialogId: communication.value,
					})
				;
			}
		},
		filterCommunication(typeId: CommunicationTypeValue): Communication[]
		{
			return this.communications.filter((communication: Communication) => communication.typeId === typeId);
		},
	},

	template: `
		<div class="crm-mini-card__communication-control">
			<div class="crm-mini-card__communication-phone" :ref="CommunicationType.Phone"></div>
			<div class="crm-mini-card__communication-mail" :ref="CommunicationType.Email"></div>
			<div class="crm-mini-card__communication-im" :ref="CommunicationType.Im"></div>
			<div hidden ref="activityEditorContainer"></div>
		</div>
	`,
};
