if(typeof(BX.CrmLiveFeedActivity) === "undefined")
{
	BX.CrmLiveFeedActivity = function()
	{
		this._activityID = 0;
		this._params = {};
	};
	
	BX.CrmLiveFeedActivity.prototype = 
	{
		initialize: function(activityId, params)
		{
			this._activityID = parseInt(activityId);
			this._params = params;
			
			var editorId = this._params['editorId'];
			var activityEditor = BX.CrmActivityEditor.items[editorId];
			if(activityEditor)
			{
				activityEditor.addActivityChangeHandler(BX.delegate(this._onActivityChange, this));
			}
		},
		_onActivityChange: function(editor, action, settings)
		{
			var id = parseInt(settings['ID']);
			if (
				id !== this._activityID
				|| action !== 'UPDATE'
			)
			{
				return;
			}

			if(typeof(__logRefreshEntry) !== "undefined")
			{
				__logRefreshEntry({
					node: this._params['node'],
					logId: this._params['logId']
				});
			}
		},
		clientsPopupList: function(users)
		{
			this.clientsPopup = null;
			this.clients = users;	// expected keys are ID, NAME, PHOTO, PROFILE, POSITION, IS_HEAD

			this.showClients = function()
			{
				if (!this.clientsPopup)
				{
					var data = BX.create('DIV', {props: {className: 'feed-activity-client-popup'}});
					var entityCaption = false;
					var strComm = false;

					for (var i=0; i < this.clients.length; i++)
					{
						if (this.clients[i].URL.length > 0)
						{
							entityCaption = '<a' + (this.clients[i].PHOTO ? ' style="background: url(\''+this.clients[i].PHOTO+'\') no-repeat scroll center center transparent;"' : '') + ' class="feed-activity-client-avatar" href="'+this.clients[i].URL+'"></a><div class="feed-activity-client-name"><a href="'+this.clients[i].URL+'">' + BX.util.htmlspecialchars(this.clients[i].NAME) + '</a></div>';
						}
						else
						{
							entityCaption = '<div' + (this.clients[i].PHOTO ? ' style="background: url(\''+this.clients[i].PHOTO+'\') no-repeat scroll center center transparent;"' : '') + ' class="feed-activity-client-avatar"></div><div class="feed-activity-client-name">' + BX.util.htmlspecialchars(this.clients[i].NAME) + '</div>';
						}
						
						strComm = false;
						if (this.clients[i].COMM)
						{
							if (this.clients[i].COMM.TYPE == 'EMAIL')
							{
								strComm = '<div class="feed-activity-client-comm"><a href="mailto:' + BX.util.htmlspecialchars(this.clients[i].COMM.VALUE) + '">' + BX.util.htmlspecialchars(this.clients[i].COMM.VALUE) + '</a></div>';
							}
							else if (this.clients[i].COMM.TYPE == 'CALL')
							{
								strComm = '<div class="feed-activity-client-comm"><a href="callto:' + BX.util.htmlspecialchars(this.clients[i].COMM.VALUE) + '">' + BX.util.htmlspecialchars(this.clients[i].COMM.VALUE) + '</a></div<';
							}
						}

						var obClient = BX.create('DIV', {
							props: {className: 'feed-activity-client-block'},
							attrs: {
								'title': this.clients[i].NAME
							},
							html: '<div class="feed-activity-client-cont">' + entityCaption + strComm + '</div>' + (this.clients[i].COMPANY ? '<div class="feed-activity-client-company">' + BX.util.htmlspecialchars(this.clients[i].COMPANY) + '</div>' : '')
						});

						data.appendChild(obClient);
					}

					this.clientsPopup = new BX.PopupWindow('vis_client_' + Math.random(), BX.proxy_context, {
						closeByEsc: true,
						autoHide: true,
						lightShadow: true,
						zIndex: 2,
						content: data,
						offsetLeft: 47,
						offsetTop: 5,
						angle : true
					});
				};

				this.clientsPopup.show();
			}
		}
	};
	
	BX.CrmLiveFeedActivity.create = function(activityId, params)
	{
		var self = new BX.CrmLiveFeedActivity();
		self.initialize(activityId, params);
		return self;
	}
	
}