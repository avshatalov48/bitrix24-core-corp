import {Loc, Tag} from 'main.core';
import {Label, LabelColor} from 'ui.label';
import {EntityEditorPaymentDocuments} from './entity-editor';

export class TimelineSummaryDocuments extends EntityEditorPaymentDocuments
{
	static _rootNodeClass = 'crm-entity-stream-content-detail-table crm-entity-stream-content-documents-table';

	_renderDealTotalSum() {
		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row crm-entity-stream-content-document-table-row">
				<div class="crm-entity-stream-content-detail-description">
					${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DEAL_SUM')}
				</div>
				<div class="crm-entity-stream-content-detail-cost">
					<span>
						${this._renderMoney(this._options.DEAL_AMOUNT)}
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

	_renderShipmentDocument(doc: PaymentDocument): HTMLElement
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

	_renderTotalSum(): HTMLElement
	{
		const totalSum = this._calculateTotalSum();

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row">
				<div class="crm-entity-stream-content-detail-description">
					<span>
						${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM')}
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
			);
		});
	}
}