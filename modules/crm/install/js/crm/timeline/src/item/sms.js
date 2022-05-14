import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Items */
export default class Sms extends HistoryActivity
{
	constructor()
	{
		super();
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(this.prepareMessageStatusLayout());
		header.appendChild(this.prepareTimeLayout());

		return header;
	}

	prepareMessageStatusLayout()
	{
		return this._messageStatusNode = BX.create("SPAN");
	}

	prepareContent()
	{
		const entityData = this.getAssociatedEntityData();

		const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
		const communicationTitle = BX.prop.getString(communication, "TITLE", "");
		const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
		const communicationValue = BX.prop.getString(communication, "VALUE", "");
		const smsInfo = BX.prop.getObject(entityData, "SMS_INFO", {});

		const wrapperClassName = "crm-entity-stream-section-sms";
		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history" + " " + wrapperClassName}});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-sms" } })
		);

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		const contentWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		const header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		const detailWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail"}});
		contentWrapper.appendChild(detailWrapper);

		const messageWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail-sms"}});

		if (smsInfo.senderId)
		{
			const senderId = smsInfo.senderId;
			let senderName = smsInfo.senderShortName;
			if (senderId === 'rest' && smsInfo.fromName)
			{
				senderName = smsInfo.fromName;
			}

			const messageSenderWrapper = BX.create("DIV", {
				attrs: {className: "crm-entity-stream-content-detail-sms-status"},
				children: [
					BX.message('CRM_TIMELINE_SMS_SENDER') + ' ',
					BX.create('STRONG', {text: senderName})
				]
			});
			if (senderId !== 'rest' && smsInfo.fromName)
			{
				messageSenderWrapper.innerHTML += ' '+BX.message('CRM_TIMELINE_SMS_FROM')+' ';
				messageSenderWrapper.appendChild(BX.create('STRONG', {text: smsInfo.fromName}));
			}
			messageWrapper.appendChild(messageSenderWrapper);
		}

		if (smsInfo.statusId !== '')
		{
			this.setMessageStatus(smsInfo.statusId, smsInfo.errorText);
		}

		const bodyText = BX.util.htmlspecialchars(entityData['DESCRIPTION_RAW']).replace(/\r\n|\r|\n/g, "<br/>");
		const messageBodyWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail-sms-fragment"}});
		messageBodyWrapper.appendChild(BX.create('SPAN', {html: bodyText}));

		messageWrapper.appendChild(messageBodyWrapper);
		detailWrapper.appendChild(messageWrapper);

		const communicationWrapper = BX.create("DIV", {
			attrs: {className: "crm-entity-stream-content-detail-contact-info"},
			text: BX.message('CRM_TIMELINE_SMS_TO') + ' '
		});
		detailWrapper.appendChild(communicationWrapper);

		if(communicationTitle !== '')
		{
			if(communicationShowUrl !== '')
			{
				communicationWrapper.appendChild(BX.create("A", { attrs: { href: communicationShowUrl }, text: communicationTitle }));
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		communicationWrapper.appendChild(BX.create("SPAN", { text: " " + communicationValue }));

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			contentWrapper.appendChild(this.prepareFixedSwitcherLayout());

		return wrapper;
	}

	prepareActions()
	{
	}

	showActions(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	}

	setMessageStatus(status, errorText)
	{
		status = parseInt(status);
		if (isNaN(status) || !this._messageStatusNode)
			return;

		const statuses = this.getSetting('smsStatusDescriptions', {});
		if (statuses.hasOwnProperty(status))
		{
			this._messageStatusNode.textContent = statuses[status];
			this.setMessageStatusErrorText(errorText);

			const statusSemantic = this.getMessageStatusSemantic(status);
			this.setMessageStatusSemantic(statusSemantic);
		}
	}

	setMessageStatusSemantic(semantic)
	{
		const classMap =
			{
				process: 'crm-entity-stream-content-event-process',
				success: 'crm-entity-stream-content-event-successful',
				failure: 'crm-entity-stream-content-event-missing'
			};

		for (let checkSemantic in classMap)
		{
			const fn = (checkSemantic === semantic) ? 'addClass' : 'removeClass';
			BX[fn](this._messageStatusNode, classMap[checkSemantic]);
		}
	}

	setMessageStatusErrorText(errorText)
	{
		if (!errorText)
		{
			this._messageStatusNode.removeAttribute('title');
			BX.removeClass(this._messageStatusNode,'crm-entity-stream-content-event-error-tip');
		}
		else
		{
			this._messageStatusNode.setAttribute('title', errorText);
			BX.addClass(this._messageStatusNode,'crm-entity-stream-content-event-error-tip');
		}
	}

	getMessageStatusSemantic(status)
	{
		const semantics = this.getSetting('smsStatusSemantics', {});
		return semantics.hasOwnProperty(status) ? semantics[status] : 'failure';
	}

	subscribe()
	{
		if (!BX.CrmSmsWatcher)
			return;

		const entityData = this.getAssociatedEntityData();
		const smsInfo = BX.prop.getObject(entityData, "SMS_INFO", {});

		if (smsInfo.id)
		{
			BX.CrmSmsWatcher.subscribeOnMessageUpdate(
				smsInfo.id,
				this.onMessageUpdate.bind(this)
			);
		}
	}

	onMessageUpdate(message)
	{
		if (message.STATUS_ID)
		{
			this.setMessageStatus(message.STATUS_ID, message.EXEC_ERROR);
		}
	}

	static create(id, settings)
	{
		const self = new Sms();
		self.initialize(id, settings);
		self.subscribe();
		return self;
	}
}
