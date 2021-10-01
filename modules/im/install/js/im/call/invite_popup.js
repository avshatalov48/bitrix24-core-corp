;(function()
{
	BX.namespace("BX.Call");

	if(BX.Call.InvitePopup)
	{
		return;
	}

	BX.Call.InvitePopup = function(config)
	{
		if(!BX.type.isPlainObject(config))
		{
			config = {};
		}
		this.idleUsers = config.idleUsers || [];
		this.recentUsers = [];
		this.bindElement = config.bindElement;
		this.viewElement = config.viewElement || document.body;

		this.allowNewUsers = config.allowNewUsers;

		this.elements = {
			root: null,
			inputBox: null,
			input: null,
			destinationContainer: null,
			contactList: null,
			moreButton: null
		};

		this.popup = null;
		this.zIndex = config.zIndex || 0;

		this.searchPhrase = '';
		this.searchNext = 0;
		this.searchResult = [];
		this.searchTotalCount = 0;

		this.searchTimeout = 0;

		this.fetching = false;

		this.callbacks = {
			onSelect: BX.type.isFunction(config.onSelect) ? config.onSelect : BX.DoNothing,
			onClose: BX.type.isFunction(config.onClose) ? config.onClose : BX.DoNothing,
			onDestroy: BX.type.isFunction(config.onDestroy) ? config.onDestroy : BX.DoNothing,
		}
	};

	BX.Call.InvitePopup.prototype = {

		show: function()
		{
			if(!this.elements.root)
			{
				this.render();
			}
			this.createPopup();
			this.popup.show();

			if(this.allowNewUsers)
			{
				this.showLoader();
				this.getRecent().then(this.updateContactList.bind(this));
			}
			else
			{
				this.updateContactList();
			}
		},

		close: function()
		{
			if(this.popup)
			{
				this.popup.close();
			}
			clearTimeout(this.searchTimeout);
		},

		createPopup: function()
		{
			var self = this;

			this.popup = new BX.PopupWindow('bx-call-popup-invite', this.bindElement, {
				targetContainer: this.viewElement,
				zIndex: this.zIndex,
				lightShadow : true,
				darkMode: BX.MessengerTheme.isDark(),
				autoHide: true,
				closeByEsc: true,
				content: this.elements.root,
				bindOptions: {
					position: "top"
				},
				angle: {position: "bottom", offset: 49},
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message("IM_CALL_INVITE_INVITE"),
						className: "popup-window-button-accept",
						events: {
							click: function(e)
							{
								if(this.selectedUser)
								{
									this.callbacks.onSelect({
										user: this.selectedUser
									});
								}
							}.bind(this)
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message("IM_CALL_INVITE_CANCEL"),
						events: {
							click: function()
							{
								self.popup.close();
							}
						}})
				],
				events: {
					onPopupClose : function()
					{
						this.destroy()
					},
					onPopupDestroy : function()
					{
						self.popup = null;
						self.elements.contactList = null;
						clearTimeout(self.searchTimeout);
						self.callbacks.onDestroy();
					}
				}
			});
			BX.addClass(this.popup.popupContainer, "bx-messenger-mark");
		},

		getRecent: function()
		{
			var self = this;
			return new Promise(function(resolve, reject)
			{
				BX.CallEngine.getRestClient().callMethod("im.recent.get", {"SKIP_OPENLINES": "Y", "SKIP_CHAT": "Y"}).then(function(response)
				{
					var answer = response.answer;
					self.recentUsers = Object.values(answer.result).map(function(r){return r.user}).filter(function(user) {
						if (user.bot || user.network)
						{
							return false;
						}

						return true;
					});
					resolve();
				});
			});
		},

		search: function()
		{
			var self = this;
			return new Promise(function(resolve, reject)
			{
				self.searchResult = self.recentUsers.filter(function(element) {
					return element.name.toString().toLowerCase().includes(self.searchPhrase.toLowerCase());
				});
				self.searchTotalCount = self.searchResult.length;
				self.searchNext = 0;

				if (self.searchPhrase.length < 3)
				{
					resolve();
					return;
				}

				BX.CallEngine.getRestClient().callMethod("im.search.user.list", {"FIND": self.searchPhrase}).then(function(response)
				{
					var answer = response.answer;

					self.searchTotalCount = answer.total;
					self.searchNext = answer.next;

					var existsUserId = self.searchResult.map(function (element) {
						return parseInt(element.id);
					});

					var result = Object.values(answer.result).filter(function(element) {
						if (element.bot || element.network)
						{
							return false;
						}
						return !existsUserId.includes(parseInt(element.id));
					});

					self.searchResult = self.searchResult.concat(result);
					self.searchTotalCount = self.searchResult.length;

					resolve();
				});
			});
		},

		fetchMoreSearchResults: function()
		{
			var self = this;
			return new Promise(function(resolve, reject)
			{
				BX.CallEngine.getRestClient().callMethod("im.search.user.list", {"FIND": self.searchPhrase, "OFFSET": self.searchNext}).then(function(response)
				{
					var answer = response.answer;

					self.searchTotalCount = answer.total;
					self.searchNext = answer.next;

					var existsUserId = self.searchResult.map(function (element) {
						return parseInt(element.id);
					});

					var result = Object.values(answer.result).filter(function(element) {
						if (element.bot || element.network)
						{
							return false;
						}
						return !existsUserId.includes(parseInt(element.id));
					});

					self.searchResult = self.searchResult.concat(result);
					self.searchTotalCount = self.searchResult.length;

					resolve(result);
				});
			})
		},

		render: function()
		{
			this.elements.root = BX.create("div", {
				props: {className: "bx-messenger-popup-newchat-wrap"},
				children: [
					BX.create("div", {
						props: {className: "bx-messenger-popup-newchat-caption"},
						text: BX.message("IM_CALL_INVITE_INVITE_USER")
					})
				]
			});

			this.elements.inputBox = BX.create("div", {
				props: {className: "bx-messenger-popup-newchat-box bx-messenger-popup-newchat-dest bx-messenger-popup-newchat-dest-even"},
				children: [
					this.elements.destinationContainer = BX.create("span", {
						props: { className: "bx-messenger-dest-items"}
					}),
					this.elements.input = BX.create("input", {
						props: {className: "bx-messenger-input"},
						attrs: {
							type: "text",
							placeholder: this.allowNewUsers ? BX.message('IM_M_SEARCH_PLACEHOLDER'): BX.message('IM_M_CALL_REINVITE_PLACEHOLDER'),
							value: '',
							disabled: !this.allowNewUsers
						},
						events: {
							keyup: this._onInputKeyUp.bind(this)
						}
					})
				]
			});
			this.elements.root.appendChild(this.elements.inputBox);

			this.elements.contactList = BX.create("div", {
				props: {className: "bx-messenger-popup-newchat-box bx-messenger-popup-newchat-cl bx-messenger-recent-wrap"},
				children: []
			});

			this.elements.root.appendChild(this.elements.contactList);
		},

		updateDestination: function()
		{
			if(!this.elements.inputBox)
			{
				return;
			}

			BX.cleanNode(this.elements.destinationContainer);

			if (this.selectedUser)
			{
				this.elements.destinationContainer.appendChild(this.renderDestinationUser(this.selectedUser));
				this.elements.input.style.display = "none";
			}
			else
			{
				this.elements.input.style.removeProperty("display");
				this.elements.input.focus();
			}
		},

		updateContactList: function()
		{
			BX.cleanNode(this.elements.contactList);

			if (this.elements.contactList)
			{
				this.elements.contactList.appendChild(this.renderContactList());
			}
		},

		showLoader: function()
		{
			BX.cleanNode(this.elements.contactList);

			this.elements.contactList.appendChild(BX.create("div", {
				props: {className: "bx-messenger-cl-item-load"},
				text: BX.message('IM_CL_LOAD')
			}));
		},

		renderContactList: function()
		{
			var result = document.createDocumentFragment();
			var i;
			if(this.idleUsers.length > 0)
			{
				result.appendChild(this.renderSeparator(BX.message("IM_CALL_INVITE_CALL_PARTICIPANTS")));

				for (i = 0; i < this.idleUsers.length; i++)
				{
					result.appendChild(this.renderUser(this.idleUsers[i]));
				}
			}

			if(this.searchPhrase != '')
			{
				if(this.searchResult.length > 0)
				{
					result.appendChild(this.renderSeparator(BX.message("IM_CALL_INVITE_SEARCH_RESULTS")));
					for (i = 0; i < this.searchResult.length; i++)
					{
						result.appendChild(this.renderUser(this.searchResult[i]));
					}

					if(this.searchTotalCount > this.searchResult.length)
					{
						this.elements.moreButton = this.renderMoreButton();
						result.appendChild(this.elements.moreButton);
					}
				}
				else
				{

				}
			}
			else if(this.recentUsers.length > 0)
			{
				result.appendChild(this.renderSeparator(BX.message("IM_CALL_INVITE_RECENT")));
				for (i = 0; i < this.recentUsers.length; i++)
				{
					result.appendChild(this.renderUser(this.recentUsers[i]));
				}
			}
			return result;
		},

		/**
		 * @param {string} text
		 * @return {Element}
		 */
		renderSeparator: function(text)
		{
			return BX.create("div", {
				props: {className: "bx-messenger-chatlist-group"},
				children: [
					BX.create("span", {
						props: {className: "bx-messenger-chatlist-group-title"},
						text: text
					})
				]
			})
		},

		renderUser: function(userData)
		{
			var element = BX.MessengerCommon.drawContactListElement({
				'id': userData.id,
				'data': userData,
				'showUserLastActivityDate': true,
				'showLastMessage': false,
				'showCounter': false,
			});

			BX.bind(element, 'click', function (){
				this.setSelectedUser(userData);
			}.bind(this));

			return element;
		},

		renderDestinationUser: function(userData)
		{
			return BX.create("span", {
				props: {className: "bx-messenger-dest-block"},
				children: [
					BX.create("span", {
						props: {className: "bx-messenger-dest-text"},
						text: userData.name
					}),
					BX.create("span", {
						props: {className: "bx-messenger-dest-del"},
						events: {
							click: this.removeSelectedUser.bind(this)
						}
					})
				]
			})
		},

		renderMoreButton: function()
		{
			return BX.create("div", {
				props: {className: "bx-messenger-chatlist-more-wrap"},
				events: {
					click: this._onMoreButtonClick.bind(this)
				},
				children: [
					BX.create("span", {
						props: {className: "bx-messenger-chatlist-more"},
						text: BX.message("IM_CALL_INVITE_MORE") + " " + (this.searchTotalCount - this.searchResult.length)
					})
				]
			});
		},

		setSelectedUser: function(userData)
		{
			this.selectedUser = userData;
			this.updateDestination();
		},

		removeSelectedUser: function()
		{
			this.selectedUser = null;
			this.updateDestination();
		},

		_onMoreButtonClick: function()
		{
			if(this.fetching)
			{
				return;
			}

			this.fetching = true;
			this.fetchMoreSearchResults().then(function(moreUsers)
			{
				var df = document.createDocumentFragment();
				var newMoreButton = null;
				for (var i = 0; i < moreUsers.length; i++)
				{
					df.appendChild(this.renderUser(moreUsers[i]));
				}

				if(this.searchTotalCount > this.searchResult.length)
				{
					newMoreButton = this.renderMoreButton();
					df.appendChild(newMoreButton);
				}

				BX.replace(this.elements.moreButton, df);
				this.elements.moreButton = newMoreButton;

				this.fetching = false;
			}.bind(this));
		},

		_onInputKeyUp: function(event)
		{
			var self = this;
			if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
			{
				return false;
			}

			if (event.keyCode == 27 && this.elements.input.value !== '')
			{
				BX.MessengerCommon.preventDefault(event);
			}

			if (event.keyCode == 27)
			{
				this.elements.input.value = '';
			}

			if(this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			this.searchTimeout = setTimeout(
				function()
				{
					self.searchPhrase = self.elements.input.value;
					if(self.searchPhrase == '')
					{
						self.updateContactList();
					}
					else
					{
						self.search().then(function()
						{
							self.updateContactList();
						})
					}
				},
				300
			);
		},
	}
})();
