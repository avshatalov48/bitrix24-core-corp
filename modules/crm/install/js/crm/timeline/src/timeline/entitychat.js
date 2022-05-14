import Stream from "../stream";

/** @memberof BX.Crm.Timeline.Timelines */
export default class EntityChat extends Stream
{
	static LayoutType = {
		none: 0,
		invitation:  1,
		summary: 2,
	}

	constructor()
	{
		super();
		this._data = null;
		this._layoutType = EntityChat.LayoutType.none;

		this._wrapper = null;
		this._contentWrapper = null;
		this._messageWrapper = null;
		this._messageDateNode = null;
		this._messageTexWrapper = null;
		this._messageTextNode = null;
		this._userWrapper = null;
		this._extraUserCounter = null;

		this._openChatHandler = BX.delegate(this.onOpenChat, this);
	}

	doInitialize()
	{
		this._data = BX.prop.getObject(this._settings, "data", {});
	}

	getData()
	{
		return this._data;
	}

	setData(data)
	{
		this._data = BX.type.isPlainObject(data) ? data : {};
	}

	isEnabled()
	{
		return BX.prop.getBoolean(this._data, "ENABLED", true);
	}

	/**
	 * @private
	 * @return {boolean}
	 */
	isRestricted()
	{
		return BX.prop.getBoolean(this._data, "IS_RESTRICTED", false);
	}

	/**
	 * @private
	 * @return {void}
	 */
	applyLockScript()
	{
		const lockScript = BX.prop.getString(this._data, "LOCK_SCRIPT", null);
		if (BX.Type.isString(lockScript) && lockScript !== '')
		{
			eval(lockScript);
		}
	}

	getChatId()
	{
		return BX.prop.getInteger(this._data, "CHAT_ID", 0);
	}

	getUserId()
	{
		const userId = parseInt(top.BX.message("USER_ID"));
		return !isNaN(userId) ? userId : 0;
	}

	getMessageData()
	{
		return BX.prop.getObject(this._data, "MESSAGE", {});
	}

	setMessageData(data)
	{
		this._data["MESSAGE"] = BX.type.isPlainObject(data) ? data : {};
	}

	getUserInfoData()
	{
		return BX.prop.getObject(this._data, "USER_INFOS", {});
	}

	setUserInfoData(data)
	{
		this._data["USER_INFOS"] = BX.type.isPlainObject(data) ? data : {};
	}

	hasUserInfo(userId)
	{
		return userId > 0 && BX.type.isPlainObject(this.getUserInfoData()[userId]);
	}

	getUserInfo(userId)
	{
		const userInfos = this.getUserInfoData();
		return userId > 0 && BX.type.isPlainObject(userInfos[userId]) ? userInfos[userId] : null;
	}

	removeUserInfo(userId)
	{
		const userInfos = this.getUserInfoData();
		if(userId > 0 && BX.type.isPlainObject(userInfos[userId]))
		{
			delete userInfos[userId];
		}
	}

	setUnreadMessageCounter(userId, counter)
	{
		const userInfos = this.getUserInfoData();
		if(userId > 0 && BX.type.isPlainObject(userInfos[userId]))
		{
			userInfos[userId]["counter"] = counter;
		}
	}

	layout()
	{
		if(!this.isEnabled() || this.isStubMode())
		{
			return;
		}

		this._wrapper = BX.create("div", { props: { className: "crm-entity-stream-section crm-entity-stream-section-live-im" } });
		this._container.appendChild(this._wrapper);

		this._wrapper.appendChild(
			BX.create("div", { props: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-live-im" } })
		);

		this._contentWrapper = BX.create("div", { props: { className: "crm-entity-stream-content-live-im-detail" } });

		this._wrapper.appendChild(
			BX.create("div",
				{
					props: { className: "crm-entity-stream-section-content" },
					children:
						[
							BX.create("div",
								{
									props: { className: "crm-entity-stream-content-event" },
									children: [ this._contentWrapper ]
								}
							)
						]
				}
			)
		);

		this._userWrapper = BX.create("div", { props: { className: "crm-entity-stream-live-im-user-avatars" } });
		this._contentWrapper.appendChild(
			BX.create("div",
				{
					props: { className: "crm-entity-stream-live-im-users" },
					children: [ this._userWrapper ]
				}
			)
		);

		this._extraUserCounter = BX.create("div",{ props: { className: "crm-entity-stream-live-im-user-counter" } });
		this._contentWrapper.appendChild(this._extraUserCounter);

		this._layoutType = EntityChat.LayoutType.none;
		if(this.getChatId() > 0)
		{
			this.renderSummary();
		}
		else
		{
			this.renderInvitation();
		}

		BX.bind(this._contentWrapper, "click", this._openChatHandler);
		BX.addCustomEvent("onPullEvent-im", this.onChatEvent.bind(this));
	}

	refreshLayout()
	{
		BX.cleanNode(this._contentWrapper);
		this._userWrapper = BX.create("div", { props: { className: "crm-entity-stream-live-im-user-avatars" } });
		this._contentWrapper.appendChild(
			BX.create("div",
				{
					props: { className: "crm-entity-stream-live-im-users" },
					children: [ this._userWrapper ]
				}
			)
		);

		this._extraUserCounter = BX.create("div",{ props: { className: "crm-entity-stream-live-im-user-counter" } });
		this._contentWrapper.appendChild(this._extraUserCounter);

		this._layoutType = EntityChat.LayoutType.none;
		if(this.getChatId() > 0)
		{
			this.renderSummary();
		}
		else
		{
			this.renderInvitation();
		}
	}

	renderInvitation()
	{
		this._layoutType = EntityChat.LayoutType.invitation;
		this.refreshUsers();

		this._messageTextNode = BX.create("div", { props: { className: "crm-entity-stream-live-im-user-invite-text" } });
		this._contentWrapper.appendChild(this._messageTextNode);

		this._messageTextNode.innerHTML = this.getMessage("invite");
	}

	renderSummary()
	{
		this._layoutType = EntityChat.LayoutType.summary;
		this.refreshUsers();

		this._contentWrapper.appendChild(
			BX.create("div", { props: { className: "crm-entity-stream-live-im-separator" } })
		);

		this._messageWrapper = BX.create("div", { props: { className: "crm-entity-stream-live-im-messanger" } });
		this._contentWrapper.appendChild(this._messageWrapper);

		this._messageDateNode = BX.create("div", { props: { className: "crm-entity-stream-live-im-time" } });
		this._messageWrapper.appendChild(this._messageDateNode);

		this._messageTexWraper = BX.create("div", { props: { className: "crm-entity-stream-live-im-message" } });
		this._messageWrapper.appendChild(this._messageTexWraper);

		this._messageTextNode = BX.create("div", { props: { className: "crm-entity-stream-live-im-message-text" } });
		this._messageTexWraper.appendChild(this._messageTextNode);

		this._messageCounterNode = BX.create("div", { props: { className: "crm-entity-stream-live-im-message-counter" } });
		this._messageWrapper.appendChild(this._messageCounterNode);

		this.refreshSummary();
	}

	refreshUsers()
	{
		BX.cleanNode(this._userWrapper);

		const infos = this.getUserInfoData();
		const list = Object.values(infos);

		if(list.length === 0)
		{
			this._userWrapper.appendChild(
				BX.create("span",
					{
						props: { className: "crm-entity-stream-live-im-user-avatar ui-icon ui-icon-common-user" },
						children: [ BX.create("i") ]
					}
				)
			);
		}
		else
		{
			const count = list.length >= 3 ? 3 : list.length;
			for(let i = 0; i < count; i++)
			{
				const info = list[i];

				const icon = BX.create("i");
				const imageUrl = BX.prop.getString(info, "avatar", "");
				if(imageUrl !== "")
				{
					icon.style.backgroundImage = "url(" +  imageUrl + ")";
				}

				this._userWrapper.appendChild(
					BX.create("span",
						{
							props: { className: "crm-entity-stream-live-im-user-avatar ui-icon ui-icon-common-user" },
							children: [ icon ]
						}
					)
				);
			}
		}

		if(this._layoutType === EntityChat.LayoutType.summary)
		{
			if(list.length > 3)
			{
				this._extraUserCounter.display = "";
				this._extraUserCounter.innerHTML = "+" + (list.length - 3).toString();
			}
			else
			{
				if(this._extraUserCounter.innerHTML !== "")
				{
					this._extraUserCounter.innerHTML = "";
				}
				this._extraUserCounter.display = "none";
			}
		}
		else //if(this._layoutType === EntityChat.LayoutType.invitation)
		{
			if(this._extraUserCounter.innerHTML !== "")
			{
				this._extraUserCounter.innerHTML = "";
			}
			this._extraUserCounter.display = "none";

			this._userWrapper.appendChild(
				BX.create("span", { props: { className: "crm-entity-stream-live-im-user-invite-btn" } })
			);
		}
	}

	refreshSummary()
	{
		if(this._layoutType !== EntityChat.LayoutType.summary)
		{
			return;
		}

		const message = this.getMessageData();

		//region Message Date
		const isoDate = BX.prop.getString(message, "date", "");
		if(isoDate === "")
		{
			this._messageDateNode.innerHTML = "";
		}
		else
		{
			const remoteDate = (new Date(isoDate)).getTime() / 1000 + this.getServerTimezoneOffset() + this.getUserTimezoneOffset();
			const localTime = (new Date).getTime() / 1000 + this.getServerTimezoneOffset() + this.getUserTimezoneOffset();
			this._messageDateNode.innerHTML = this.formatTime(remoteDate, localTime, true);
		}
		//endregion

		//region Message Text
		let text = BX.prop.getString(message, "text", "");
		const params = BX.prop.getObject(message, "params", {});
		if(text === "")
		{
			this._messageTextNode.innerHTML = "";
		}
		else
		{
			if(typeof(top.BX.MessengerCommon) !== "undefined")
			{
				text = top.BX.MessengerCommon.purifyText(text, params);
			}
			this._messageTextNode.innerHTML = text;
		}
		//endregion

		//region Unread Message Counter
		let counter = 0;
		const userId = this.getUserId();
		if(userId > 0)
		{
			counter = BX.prop.getInteger(
				BX.prop.getObject(
					BX.prop.getObject(this._data, "USER_INFOS", {}),
					userId,
					null
				),
				"counter",
				0
			);
		}

		this._messageCounterNode.innerHTML = counter.toString();
		this._messageCounterNode.style.display = counter > 0 ? "" : "none";
		//endregion
	}

	refreshUsersAnimated()
	{
		BX.removeClass(this._userWrapper, 'crm-entity-stream-live-im-message-show');
		BX.addClass(this._userWrapper, 'crm-entity-stream-live-im-message-hide');

		window.setTimeout(
			function()
			{
				this.refreshUsers();
				window.setTimeout(
					function()
					{
						BX.removeClass(this._userWrapper, 'crm-entity-stream-live-im-message-hide');
						BX.addClass(this._userWrapper, 'crm-entity-stream-live-im-message-show');
					}.bind(this),
					50
				);
			}.bind(this),
			500
		);
	}

	refreshSummaryAnimated()
	{
		BX.removeClass(this._messageWrapper, 'crm-entity-stream-live-im-message-show');
		BX.addClass(this._messageWrapper, 'crm-entity-stream-live-im-message-hide');

		window.setTimeout(
			function()
			{
				this.refreshSummary();
				window.setTimeout(
					function()
					{
						BX.removeClass(this._messageWrapper, 'crm-entity-stream-live-im-message-hide');
						BX.addClass(this._messageWrapper, 'crm-entity-stream-live-im-message-show');
					}.bind(this),
					50
				);
			}.bind(this),
			500
		);
	}

	onOpenChat(e)
	{
		if(typeof(top.BXIM) === "undefined")
		{
			return;
		}

		if (this.isRestricted())
		{
			this.applyLockScript();

			return;
		}

		let slug = "";

		const chatId = this.getChatId();
		if(chatId > 0 && this.hasUserInfo(this.getUserId()))
		{
			slug = "chat" + chatId.toString();
		}
		else
		{
			const ownerInfo = this.getOwnerInfo();
			const entityId = BX.prop.getInteger(ownerInfo, "ENTITY_ID", 0);
			const entityTypeName = BX.prop.getString(ownerInfo, "ENTITY_TYPE_NAME", "");

			if(entityTypeName !== "" && entityId > 0)
			{
				slug = "crm|" + entityTypeName + "|" + entityId.toString();
			}
		}

		if(slug !== "")
		{
			top.BXIM.openMessengerSlider(slug, { RECENT: "N", MENU: "N" });
		}
	}

	onChatEvent(command, params, extras)
	{
		const chatId = this.getChatId();
		if(chatId <= 0 || chatId !== BX.prop.getInteger(params, "chatId", 0))
		{
			return;
		}

		if(command === "chatUserAdd")
		{
			this.setUserInfoData(BX.mergeEx(this.getUserInfoData(), BX.prop.getObject(params, "users", {})));
			this.refreshUsersAnimated();
		}
		else if(command === "chatUserLeave")
		{
			this.removeUserInfo(BX.prop.getInteger(params, "userId", 0));
			this.refreshUsersAnimated();
		}
		else if(command === "messageChat")
		{
			//Message was added.
			this.setMessageData(BX.prop.getObject(params, "message", {}));
			this.setUnreadMessageCounter(this.getUserId(), BX.prop.getInteger(params, "counter", 0));
			this.refreshSummaryAnimated();
		}
		else if(command === "messageUpdate" || command === "messageDelete")
		{
			//Message was modified or removed.
			if(command === "messageDelete")
			{
				//HACK: date is not in ISO format
				delete params["date"];
			}

			const message = this.getMessageData();
			if(BX.prop.getInteger(message, "id", 0) === BX.prop.getInteger(params, "id", 0))
			{
				this.setMessageData(BX.mergeEx(message, params));
				this.refreshSummaryAnimated();
			}
		}
		else if(command === "readMessageChat")
		{
			this.setUnreadMessageCounter(this.getUserId(), 0);
			this.refreshSummaryAnimated();
		}
		else if(command === "unreadMessageChat")
		{
			this.setUnreadMessageCounter(this.getUserId(), BX.prop.getInteger(params, "counter", 0));
			this.refreshSummaryAnimated();
		}
	}

	getMessage(name)
	{
		return BX.prop.getString(EntityChat.messages, name, name);
	}

	static create(id, settings)
	{
		const self = new EntityChat();
		self.initialize(id, settings);
		EntityChat.items[self.getId()] = self;
		return self;
	}

	static items = {};
	static messages = {};
}
