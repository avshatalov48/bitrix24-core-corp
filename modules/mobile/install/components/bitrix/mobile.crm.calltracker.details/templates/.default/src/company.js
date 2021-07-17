import {Loc, Tag, Text, Type, Uri} from 'main.core';

export default class Company
{
	static selectorUrl = ''; // defined in template
	constructor(container, formId, data)
	{
		this.container = container;
		this.formId = formId;
		this.init(data);
	}
	init(data)
	{
		if (data.id > 0)
		{
			this.isNew = false;
			this.id = parseInt(data.id);
		}
		else
		{
			this.isNew = true;
			this.id = null;
		}
		this.title = Type.isString(data.title) ? data.title : '';
		this.logo = Type.isString(data.logo) ? data.logo : '';
		this.draw();
	}

	getNode()
	{
		const logo = this.logo !== '' ? `style="background-image: url('${Text.encode(this.logo)}')"`  : '';
		const logo2 = this.logo !== '' ? `<i style="background-image:url('${Text.encode(this.logo)}')"></i>` : '';

		const onclick = this.onclick.bind(this);
		return Tag.render`
		<div class="crm-phonetracker-detail-control-container">
			<div class="crm-phonetracker-detail-control-icon crm-phonetracker-detail-control-icon-avatar" ${logo}></div>
			<div class="crm-phonetracker-detail-control-inner">
				<div class="crm-phonetracker-detail-control-title">${Loc.getMessage('CRM_COMPANY')}</div>
				<div class="crm-phonetracker-detail-control-field-container">
					<input type="hidden" name="COMPANY[ID]" value="${this.id}" >
					<input
						onchange="BX.onCustomEvent('onCrmCallTrackerNeedToSendForm${this.formId}')"
						onclick="${onclick}"
						type="text"
						name="COMPANY[TITLE]"
						value="${Text.encode(this.title)}"
						${this.id <= 0 ? ' readonly ' : ''}
						placeholder="${Loc.getMessage('CRM_COMPANY_PLACEHOLDER')}"
						class="crm-phonetracker-detail-control-field">
				</div>
			</div>
		</div>`
	}
	draw()
	{
		const newNode = this.getNode();
		this.container.parentNode.replaceChild(newNode, this.container);
		this.container = newNode;
	}
	onclick()
	{
		if (this.isNew)
		{
			const eventName = ['onCrmCompanySelectForDeal', this.id].join('_');
			BX.Mobile.Crm.loadPageModal(Uri.addParam(Company.selectorUrl, {entity: 'company', event: eventName}));
			const funct = function(data) {
				BX.removeCustomEvent(eventName, funct);
				if (data && data.id)
				{
					this.init({
						id: data.id,
						title: data.name,
						logo: data.image,
						// multi: data.multy,
					});
					BX.onCustomEvent(`onCrmCallTrackerNeedToSendForm${this.formId}`);
				}
			}.bind(this);
			BXMobileApp.addCustomEvent(eventName, funct);
		}
	}

	static bind(container, formId, company)
	{
		new Company(container, formId, company);
	}
}