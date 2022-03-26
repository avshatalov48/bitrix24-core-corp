import {Loc, Tag} from 'main.core';
import {Label, LabelColor} from 'ui.label';
import {EntityEditorPaymentDocuments} from './entity-editor';

export class TimelineSummaryDocuments extends EntityEditorPaymentDocuments
{
	static _rootNodeClass = 'crm-entity-stream-content-detail-table crm-entity-stream-content-documents-table';

	_renderDealTotalSum() {
		let phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DEAL_SUM';
		if (Number(this._options.OWNER_TYPE_ID) === BX.CrmEntityType.enumeration.smartinvoice)
		{
			phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_INVOICE_SUM';
		}

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row crm-entity-stream-content-document-table-row">
				<div class="crm-entity-stream-content-detail-description">
					${Loc.getMessage(phrase)}
				</div>
				<div class="crm-entity-stream-content-detail-cost">
					<span>
						${this._renderMoney(this._options.ENTITY_AMOUNT)}
					</span>
				</div>
			</div>
		`;
	}

	render(): HTMLElement {
		this._menus.forEach((menu) => menu.destroy());
		this._rootNode.innerHTML = '';
		this._setupCurrencyFormat();

		if (this.hasContent())
		{
			this._filterSuccessfulDocuments();
			this._rootNode.classList.remove('is-hidden');
			this._rootNode.append(Tag.render`
				<div>
					${this._renderDealTotalSum()}
					${this._renderDocuments()}
					${this._renderTotalSum()}
				</div>
			`);
		}
		else
		{
			this._rootNode.classList.add('is-hidden');
		}

		return this._rootNode;
	}

	_renderPaymentDocument(doc: PaymentDocument): HTMLElement
	{
		const title = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_DATE').replace(/#DATE#/gi, doc.FORMATTED_DATE);
		const labelOptions = {
			text: Loc.getMessage(`CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_${doc.STAGE}`),
			customClass: 'crm-entity-widget-payment-label',
			color: LabelColor.LIGHT,
			fill: true,
		};
		if (doc.STAGE && doc.STAGE === 'PAID')
		{
			labelOptions.color = LabelColor.LIGHT_GREEN;
		}
		else if (doc.STAGE && doc.STAGE === 'VIEWED_NO_PAID')
		{
			labelOptions.color = LabelColor.LIGHT_BLUE;
		}

		if (!labelOptions.text)
		{
			labelOptions.text = doc.PAID === 'Y'
				? Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_PAID')
				: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_NOT_PAID');
		}

		const openSlider = () => this._resendPaymentSlider(doc.ORDER_ID, doc.ID);

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row">
				<div class="crm-entity-stream-content-document-description">
					<a class="ui-link" onclick="${openSlider}">${title}</a>
					${(new Label(labelOptions)).render()}
				</div>
				<div class="crm-entity-stream-content-detail-cost">
					<span>
						${this._renderMoney(doc.SUM)}
					</span>
				</div>
			</div>
		`;
	}

	_renderDeliveryDocument(doc: PaymentDocument): HTMLElement
	{
		const labelOptions = {
			text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING'),
			customClass: 'crm-entity-widget-payment-label',
			color: LabelColor.LIGHT,
			fill: true,
		};
		if (doc.DEDUCTED === 'Y')
		{
			labelOptions.text = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_DELIVERED');
			labelOptions.color = LabelColor.LIGHT_GREEN;
		}
		const title = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DELIVERY_DATE').replace(/#DATE#/gi, doc.FORMATTED_DATE);

		const openSlider = () => this._viewDeliverySlider(doc.ORDER_ID, doc.ID);

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row">
				<div class="crm-entity-stream-content-document-description">
					<a class="ui-link" onclick="${openSlider}">
						${title} (${doc.DELIVERY_NAME})
					</a>
					${(new Label(labelOptions)).render()}
				</div>
				<div class="crm-entity-stream-content-detail-cost">
					<span>
						${this._renderMoney(doc.SUM)}
					</span>
				</div>
			</div>
		`;
	}

	_renderRealizationDocument(doc: PaymentDocument): HTMLElement
	{
		const labelOptions = {
			fill: true,
			customClass: 'crm-entity-widget-payment-label',
		};
		if (doc.DEDUCTED === 'Y')
		{
			labelOptions.text = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_DEDUCTED');
			labelOptions.color = LabelColor.LIGHT_GREEN;
		}
		else
		{
			if (doc.EMP_DEDUCTED_ID)
			{
				labelOptions.text = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_CANCELLED');
				labelOptions.color = LabelColor.LIGHT_ORANGE;
			}
			else
			{
				labelOptions.text = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_DRAFT');
				labelOptions.color = LabelColor.LIGHT;
			}
		}

		let title = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_DATE').replace(/#DATE#/gi, doc.FORMATTED_DATE);
		title = title.replace(/#DOCUMENT_ID#/gi, doc.ACCOUNT_NUMBER);
		title = BX.util.htmlspecialchars(title);

		const sum = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));

		const openSlider = () => this._viewRealizationSlider(doc.ID);

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row">
				<div class="crm-entity-stream-content-document-description">
					<a class="ui-link" onclick="${openSlider}">
						${title} (${sum})
					</a>
					${(new Label(labelOptions)).render()}
				</div>
				<div class="crm-entity-stream-content-detail-cost">
					<span>
						${this._renderMoney(doc.SUM)}
					</span>
				</div>
			</div>
		`;
	}

	_renderTotalSum(): HTMLElement
	{
		const totalSum = this._calculateTotalSum();

		let phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM';
		if (Number(this._options.OWNER_TYPE_ID) === BX.CrmEntityType.enumeration.smartinvoice)
		{
			phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_INVOICE_SUM';
		}

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row">
				<div class="crm-entity-stream-content-detail-description">
					<span>
						${Loc.getMessage(phrase)}
					</span>
				</div>
				<div class="crm-entity-stream-content-detail-cost">
					<span>
						${this._renderMoney(totalSum)}
					</span>
				</div>
			</div>
		`;
	}

	_filterSuccessfulDocuments()
	{
		this._options.DOCUMENTS = this._options.DOCUMENTS.filter((item) => {
			return (
				(item.TYPE === 'PAYMENT' && item.PAID === 'Y')
				|| (item.TYPE === 'SHIPMENT' && item.DEDUCTED === 'Y')
				|| (item.TYPE === 'SHIPMENT_DOCUMENT' && item.DEDUCTED === 'Y')
			);
		});
	}
}