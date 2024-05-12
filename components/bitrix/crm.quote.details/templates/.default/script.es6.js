import { Conversion } from 'crm.conversion';
import { Dictionary } from 'crm.integration.analytics';
import type { ItemDetailsComponentParams } from 'crm.item-details-component';
import { ItemDetailsComponent } from 'crm.item-details-component';
import { ajax as Ajax, Reflection, Tag, Text, Type, Uri } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Button } from 'ui.buttons';

const printWindowWidth = 900;
const printWindowHeight = 600;

declare type QuoteDetailsComponentParams = ItemDetailsComponentParams & {
	activityEditorId: string,
	emailSettings: ?EmailSettings,
	printTemplates: PrintTemplate[],
	conversion: ?ConversionSettings,
};

declare type ConversionSettings = {
	lockScript?: Function,
	buttonId?: string,
	menuButtonId?: string,
	converter?: Object,
	schemeSelector?: Object,
};

declare type PrintTemplate = {
	id: number,
	name: string,
};

declare type EmailSettings = {
	communications: Communication[],
	ownerType: string,
	ownerId: number,
	ownerPSID: number,
	subject: string|number,
	storageTypeID?: number,
	webdavelements?: FileInfo[],
	diskfiles?: number[],
	files?: FileInfo[],
};

declare type Communication = {
	entityId: number,
	entityTitle: string,
	type: string,
	value: any,
};

declare type FileInfo = {
	ID: number,
	NAME: string|number,
	SIZE: number,
	VIEW_URL: string,
	FILE_ID?: number,
	BYTES?: number,
	CAN_READ?: boolean,
	PREVIEW_URL?: string,
	EDIT_URL?: string,
	DELETE_URL?: string,
	SHOW_URL?: string
};

const namespace = Reflection.namespace('BX.Crm');

class QuoteDetailsComponent extends ItemDetailsComponent
{
	activityEditorId: string;
	emailSettings: EmailSettings;
	printTemplates: PrintTemplate[];
	isMultipleTemplates: boolean;
	conversionSettings: ?ConversionSettings;

	constructor(params: QuoteDetailsComponentParams)
	{
		super(params);

		if (Type.isPlainObject(params))
		{
			this.activityEditorId = params.activityEditorId;

			if (Type.isPlainObject(params.emailSettings))
			{
				this.emailSettings = params.emailSettings;
			}

			if (Type.isArray(params.printTemplates))
			{
				this.printTemplates = params.printTemplates;
				this.isMultipleTemplates = Boolean(this.printTemplates.length > 1);
			}

			if (Type.isPlainObject(params.conversion))
			{
				this.conversionSettings = params.conversion;
			}
		}
	}

	init(): void
	{
		super.init();

		if (this.conversionSettings)
		{
			this.initConversionApi();
		}
	}

	initConversionApi(): void
	{
		const converter = Conversion.Manager.Instance.initializeConverter(this.entityTypeId, this.conversionSettings.converter);
		const schemeSelector = new Conversion.SchemeSelector(converter, this.conversionSettings.schemeSelector);

		if (this.conversionSettings.lockScript)
		{
			schemeSelector.subscribe('onSchemeSelected', this.conversionSettings.lockScript);
			schemeSelector.subscribe('onContainerClick', this.conversionSettings.lockScript);

			EventEmitter.subscribe('CrmCreateDealFromQuote', this.conversionSettings.lockScript);
			EventEmitter.subscribe('CrmCreateInvoiceFromQuote', this.conversionSettings.lockScript);
		}
		else
		{
			schemeSelector.enableAutoConversion();

			const convertByEvent = (dstEntityTypeId: number) => {
				const schemeItem = converter.getConfig().getScheme().getItemForSingleEntityTypeId(dstEntityTypeId);
				if (!schemeItem)
				{
					console.error('SchemeItem with single entityTypeId ' + dstEntityTypeId  + ' is not found');
					return;
				}

				converter.getConfig().updateFromSchemeItem(schemeItem);

				converter.setAnalyticsElement(Dictionary.ELEMENT_CREATE_LINKED_ENTITY_BUTTON);

				converter.convert(this.id);
			};

			EventEmitter.subscribe('CrmCreateDealFromQuote', () => {
				convertByEvent(BX.CrmEntityType.enumeration.deal);
			});
			EventEmitter.subscribe('CrmCreateInvoiceFromQuote', () => {
				convertByEvent(BX.CrmEntityType.enumeration.invoice);
			});
			EventEmitter.subscribe('BX.Crm.ItemListComponent:onAddNewItemButtonClick', ((event: BaseEvent) => {
				const dstEntityTypeId = Number(event.getData().entityTypeId);
				if (dstEntityTypeId > 0)
				{
					convertByEvent(dstEntityTypeId);
				}
			}));
		}
	}

	bindEvents(): void
	{
		super.bindEvents();
		EventEmitter.subscribe('BX.Crm.ItemDetailsComponent:onClickPrint', this.handlePrintOrPdf.bind(this));
		EventEmitter.subscribe('BX.Crm.ItemDetailsComponent:onClickPdf', this.handlePrintOrPdf.bind(this));
		EventEmitter.subscribe('BX.Crm.ItemDetailsComponent:onClickEmail', this.handleEmail.bind(this));
	}

	handlePrintOrPdf(event: BaseEvent): void
	{
		if (!this.validatePrintTemplates())
		{
			return;
		}

		const link = this.normalizeUrl(new Uri(event.getData().link));
		const openInNewWindow = Boolean(event.getData().openInNewWindow);

		if (this.isMultipleTemplates)
		{
			this.openTemplateSelectDialog().then((templateId) =>
			{
				this.openPrintWindow(link, templateId, openInNewWindow);
			}).catch( () => {} );
		}
		else
		{
			const selectedPrintTemplate = this.getSinglePrintTemplate();
			this.openPrintWindow(link, selectedPrintTemplate.id, openInNewWindow);
		}
	}

	validatePrintTemplates(): boolean
	{
		if (!Type.isArray(this.printTemplates) || (this.printTemplates.length <= 0))
		{
			this.showError(this.messages.errorNoPrintTemplates);
			return false;
		}

		return true;
	}

	getSinglePrintTemplate(): PrintTemplate
	{
		return this.printTemplates[this.printTemplates.length - 1];
	}

	openTemplateSelectDialog(): Promise<number>
	{
		return new Promise((resolve, reject) =>
		{
			const templateSelectDialogContent: HTMLElement = Tag.render`
				<div class="ui-form ui-form-line">
					<div class="ui-form-row">
						<div class="ui-form-label">
							<div class="ui-ctl-label-text">${this.messages.template}</div>
						</div>
						<div class="ui-form-content">
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element">
								</select>
							</div>
						</div>
					</div>
				</div>
			`;

			const select = templateSelectDialogContent.querySelector('select');

			this.printTemplates.forEach( ({id, name}) =>
			{
				select.appendChild(Tag.render` <option value="${Text.encode(id)}">${Text.encode(name)}</option> `);
			});

			const popup = new Popup({
				titleBar: this.messages.selectTemplate,
				content: templateSelectDialogContent,
				closeByEsc: true,
				closeIcon: true,
				buttons: [
					new Button({
						text: this.messages.print,
						onclick: (button: Button, event: MouseEvent) =>
						{
							const selectedTemplateId = select.value;
							popup.destroy();
							resolve(Number(selectedTemplateId));
						},
					}),
				],
				events: {
					onClose: () =>
					{
						reject('Template select dialog was closed');
					}
				}
			});

			popup.show();
		});
	}

	openPrintWindow(link: Uri, templateId: number, openInNewWindow: boolean): void
	{
		link.setQueryParam('PAY_SYSTEM_ID', templateId);

		if (openInNewWindow)
		{
			jsUtils.OpenWindow(link.toString(), printWindowWidth, printWindowHeight);
		}
		else
		{
			jsUtils.Redirect([], link.toString());
		}
	}

	handleEmail(): void
	{
		if (!this.validatePrintTemplates())
		{
			return;
		}

		if (!this.emailSettings)
		{
			this.showError(this.messages.errorNoEmailSettings);
			return;
		}

		if (this.isMultipleTemplates)
		{
			this.openTemplateSelectDialog().then((templateId: number) =>
			{
				this.sendViaEmail(templateId);
			}).catch( () => {} );
		}
		else
		{
			const selectedPrintTemplate = this.getSinglePrintTemplate();
			this.sendViaEmail(selectedPrintTemplate.id);
		}
	}

	sendViaEmail(templateId: number): void
	{
		this.emailSettings.ownerPSID = templateId;

		if (!(top.BX.SidePanel.Instance))
		{
			this.modifyEmailSettings(this.emailSettings).then((emailSettings) =>
			{
				this.getActivityEditor().addEmail(emailSettings);
			}
			).catch(this.showErrorsFromResponse.bind(this));
			return;
		}

		this.getActivityEditor().addEmail(this.emailSettings);
	}

	modifyEmailSettings(emailSettings: EmailSettings): Promise<EmailSettings>
	{
		return Ajax.runComponentAction(
			'bitrix:crm.quote.details',
			'createEmailAttachment',
			{
				mode: 'class',
				signedParameters: this.signedParameters,
				analyticsLabel: 'crmQuoteDetailsSendViaEmail',
				data: {
					entityTypeId: this.entityTypeId,
					id: this.id,
					paymentSystemId: emailSettings.ownerPSID,
				}
			}).then( (response) =>
			{
				const data: FileInfo = response.data;

				emailSettings.storageTypeID = data['STORAGE_TYPE_ID'];

				if (emailSettings.storageTypeID === BX.CrmActivityStorageType.webdav)
				{
					emailSettings.webdavelements = [ data ];
				}
				else if (emailSettings.storageTypeID === BX.CrmActivityStorageType.disk)
				{
					emailSettings.diskfiles = [ Number(data.ID) ];
				}
				else if (emailSettings.storageTypeID === BX.CrmActivityStorageType.file)
				{
					emailSettings.files = [ data ];
				}

				return emailSettings;
			});
	}

	getActivityEditor(): BX.CrmActivityEditor
	{
		return BX.CrmActivityEditor.items[this.activityEditorId];
	}
}

namespace.QuoteDetailsComponent = QuoteDetailsComponent;
