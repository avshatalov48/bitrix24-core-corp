import {Loc, Tag, Text, Type, Uri} from 'main.core';

export default class Contact
{
	static selectorUrl = '';//defined in template
	static newIdsCounter = 0;
	static ids = [];

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
			Contact.ids.push(this.id);
		}
		else
		{
			this.isNew = true;
			this.id = ['n', Contact.newIdsCounter++].join('');
		}
		this.name = Type.isString(data.name) ? data.name : '';
		this.avatar = Type.isString(data.avatar) ? data.avatar : '';
		this.draw();
	}

	getNode()
	{
		const avatar = this.avatar !== '' ? `style="background-image: url('${Text.encode(this.avatar)}')"`  : '';
		const avatar2 = this.avatar !== '' ? `<i style="background-image:url('${Text.encode(this.avatar)}')"></i>` : '';

		const onclick = this.onclick.bind(this);
		return Tag.render`
		<div class="crm-phonetracker-detail-control-container">
			<div class="crm-phonetracker-detail-control-icon crm-phonetracker-detail-control-icon-avatar" ${avatar}></div>
			<div class="crm-phonetracker-detail-control-inner">
				<div class="crm-phonetracker-detail-control-title">${Loc.getMessage('CRM_CONTACT')}</div>
				<div class="crm-phonetracker-detail-control-field-container">
					<input
						onchange="BX.onCustomEvent('onCrmCallTrackerNeedToSendForm${this.formId}')"
						onclick="${onclick}"
						name="CONTACTS[${this.id}][FULL_NAME]"
						placeholder="${Loc.getMessage('CRM_CONTACT_PLACEHOLDER')}"
						value="${Text.encode(this.name)}"
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
			const eventName = ['onCrmContactSelectForDeal', this.id].join('_');
			BX.Mobile.Crm.loadPageModal(Uri.addParam(Contact.selectorUrl, {entity: 'contact', event: eventName}));
			const funct = function(data) {
				BX.removeCustomEvent(eventName, funct);
				if (data && data.id)
				{
					this.init(data);
					BX.onCustomEvent(`onCrmCallTrackerNeedToSendForm${this.formId}`);
				}
			}.bind(this);
			BXMobileApp.addCustomEvent(eventName, funct);
		}
	}

	static bind(container, formId, contacts)
	{
		contacts.forEach(({id, name, avatar}) => {
			new Contact(container, formId,{id, name, avatar})
		});
		if (contacts.length <= 0)
		{
			new Contact(container, formId,{});
		}
	}
}