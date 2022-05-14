import Activity from ".//activity";

/** @memberof BX.Crm.Timeline.Actions */
export class Email extends Activity
{
	constructor()
	{
		super();
		this._clickHandler = BX.delegate(this.onClick, this);
		this._saveHandler = BX.delegate(this.onSave, this);
	}

	onClick(e)
	{
		const settings =
			{
				"ownerType": BX.CrmEntityType.resolveName(BX.prop.getInteger(this._entityData, "OWNER_TYPE_ID", 0)),
				"ownerID": BX.prop.getInteger(this._entityData, "OWNER_ID", 0),
				"ownerUrl": BX.prop.getString(this._entityData, "OWNER_URL", ""),
				"ownerTitle": BX.prop.getString(this._entityData, "OWNER_TITLE", ""),
				"originalMessageID": BX.prop.getInteger(this._entityData, "ID", 0),
				"messageType": "RE"
			};

		if (BX.CrmActivityProvider && top.BX.Bitrix24 && top.BX.Bitrix24.Slider)
		{
			const activity = this._activityEditor.addEmail(settings);
			activity.addOnSave(this._saveHandler);
		}
		else
		{
			this.loadActivityCommunications(
				BX.delegate(
					function(communications)
					{
						settings['communications'] = BX.type.isArray(communications) ? communications : [];
						settings['communicationsLoaded'] = true;

						BX.CrmActivityEmail.prepareReply(settings);

						const activity = this._activityEditor.addEmail(settings);
						activity.addOnSave(this._saveHandler);
					},
					this
				)
			);
		}
		return BX.PreventDefault(e);
	}

	onSave(activity, data)
	{
		if(BX.type.isFunction(this._item.onActivityCreate))
		{
			this._item.onActivityCreate(activity, data);
		}
	}
}

/** @memberof BX.Crm.Timeline.Actions */
export class HistoryEmail extends Email
{
	constructor()
	{
		super();
	}

	doLayout()
	{
		this._container.appendChild(
			BX.create("A",
				{
					attrs: { className: "crm-entity-stream-content-action-reply-btn" },
					events: { "click": this._clickHandler }
				})
		);
	}

	static create(id, settings)
	{
		const self = new HistoryEmail();
		self.initialize(id, settings);
		return self;
	}
}

/** @memberof BX.Crm.Timeline.Actions */
export class ScheduleEmail extends Email
{
	constructor()
	{
		super();
	}

	doLayout()
	{
		this._container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-action-reply-btn" },
					events: { "click": this._clickHandler }
				}
			)
		);
	}

	static create(id, settings)
	{
		const self = new ScheduleEmail();
		self.initialize(id, settings);
		return self;
	}
}
