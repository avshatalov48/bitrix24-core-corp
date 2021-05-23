import {Reflection} from 'main.core';
import {rest} from "rest.client";
import {PULL, PullClient} from "pull.client";

const namespace = Reflection.namespace('BX.Intranet');

class UstatOnline
{
	constructor(params)
	{
		this.signedParameters = params.signedParameters;
		this.componentName = params.componentName;
		this.userBlockNode = params.userBlockNode || "";
		this.userInnerBlockNode = params.userInnerBlockNode || "";
		this.circleNode = params.circleNode || "";
		this.maxUserToShow = 7;
		this.maxOnlineUserCountToday = params.maxOnlineUserCountToday;
		this.currentUserId = parseInt(params.currentUserId);
		this.isTimemanAvailable = params.isTimemanAvailable === "Y";
		this.isAllOnlinePopupShown = false;

		let users = params.users;
		let allOnlineUserIdToday = params.allOnlineUserIdToday;

		this.users = users.map(user => {
			user.id = parseInt(user.id);
			user.offline_date = this.getOfflineDate(user.last_activity_date);
			return user;
		});

		this.allOnlineUserIdToday = allOnlineUserIdToday.map(id => {
			return parseInt(id);
		});

		this.online = [].concat(this.users);
		this.counter = 0;
		this.maxShowUsers = 10;

		//-------------- for IndexedDb
		this.ITEMS = {
			obClientDb: null,
			obClientDbData: {},
			obClientDbDataSearchIndex: {},
			bMenuInitialized: false,
			initialized: {
				sonetgroups: false,
				menuitems: false
			},
			oDbSearchResult: {}
		};

		BX.Finder(false, 'searchTitle', [], {}, this);
		BX.onCustomEvent(this, 'initFinderDb', [ this.ITEMS, 'searchTitle', null, ['users'], this ]);
		//---------------

		let now = new Date();
		this.currentDate = new Date(now.getFullYear(), now.getMonth(), now.getDate()).valueOf();

		BX.bind(this.userInnerBlockNode, "click", function () {
			this.showAllOnlinePopup();
		}.bind(this));

		this.checkOnline();

		this.subscribePullEvent();
		setInterval(() => this.checkOnline(), 60000);
	}

	checkOnline()
	{
		this.online = this.online.filter(user => {
			return user && user.offline_date > +new Date();
		});

		let prevCounter = this.counter;

		if (this.online.length > 0)
		{
			this.counter = this.online.map(el => 1).reduce(count => count + 1);
		}
		else
		{
			this.counter = 0;
		}

		this.checkNewDay();

		if (prevCounter !== this.counter)
		{
			this.redrawOnline();
		}
	}

	checkNewDay()
	{
		let now = new Date();
		let today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).valueOf();

		if (this.currentDate < today) //new day
		{
			this.maxOnlineUserCountToday = this.online.length;
			this.redrawOnline();

			if (this.isTimemanAvailable)
			{
				this.checkTimeman();
			}

			this.currentDate = today;
		}
	}

	checkTimeman()
	{
		BX.ajax.runComponentAction(this.componentName, "checkTimeman", {
			signedParameters: this.signedParameters,
			mode: 'class'
		}).then(function (response) {
			if (response.data)
			{
				this.redrawTimeman(response.data);
			}
		}.bind(this), function (response) {

		}.bind(this));
	}

	setUserOnline(params)
	{
		let userId = parseInt(params.id);
		this.findUser(userId).then(user => {

			if (typeof params.last_activity_date !== "undefined")
			{
				user.last_activity_date = params.last_activity_date;
			}

			this.setUserToLocal(user);

			this.checkOnline();
		}).catch(error => {});
	}

	setUserToLocal(user)
	{
		user.offline_date = this.getOfflineDate(user.last_activity_date);

		this.users = this.users.filter(element => element.id !== user.id);
		this.users.push(user);

		this.online = [].concat([user], this.online.filter(element => element.id !== user.id));

		if (this.allOnlineUserIdToday.indexOf(user.id) === -1)
		{
			this.allOnlineUserIdToday.push(user.id);
			this.maxOnlineUserCountToday++;
		}
	}

	setUserOnlineMultiply(list)
	{
		let requestUserList = [];
		let counterFindUser = 0;
		let promises = [];

		this.online = [];

		list.forEach(user => {

			let userId = parseInt(user.id);
			promises.push(new Promise((resolve, reject) => {
				this.findUser(userId, true).then(user => {
					counterFindUser++;
					this.setUserToLocal(user);
					resolve();
				}).catch(error => {
					requestUserList.push(userId);
					resolve();
				});
			}));
		});

		Promise.all(promises).then(() => {

			this.checkOnline();

			if (requestUserList.length <= 0)
			{
				return false;
			}

			let requestCount = this.maxShowUsers-counterFindUser;
			if (requestCount <= 0)
			{
				return true;
			}

			requestUserList = requestUserList.slice(0, requestCount);

			BX.rest.callMethod('im.user.list.get', {ID: requestUserList}).then(result => {
				let collection = result.data();
				if (!collection)
				{
					return false;
				}

				for (let userId in collection)
				{
					if (!collection.hasOwnProperty(userId))
					{
						continue;
					}

					let userData = collection[userId];
					if (!userData)
					{
						continue;
					}

					let user = {};
					user.id = parseInt(userData.id);
					user.name = userData.name;
					user.avatar = BX.MessengerCommon.isBlankAvatar(userData.avatar)? '': userData.avatar;
					user.last_activity_date = userData.last_activity_date;

					this.setUserToLocal(user);
				}

				this.checkOnline();
			}).catch(error => {});
		});
	}

	findUser(userId, skipRest = false)
	{
		userId = parseInt(userId);

		return new Promise((resolve, reject) => {

			let user = null;

			user = this.users.find(element => element.id === userId);
			if (user)
			{
				resolve(user);
				return true;
			}

			user = this.getUserFromMessenger(userId);
			if (user)
			{
				resolve(user);
				return true;
			}

			this.getUserFromDb(userId).then(user => {
				resolve(user);
			}).catch(error => {

				if (skipRest)
				{
					reject(null);
					return true;
				}

				this.getUserFromServer(userId).then(user => {
					this.addUserToDb(user);
					resolve(user);
				}).catch(error => {
					reject(null);
				});

			});

			return true;
		});
	}

	getUserFromMessenger(userId)
	{
		if (typeof window.BX.MessengerCommon === 'undefined')
		{
			return null;
		}

		let result = BX.MessengerCommon.getUser(userId);
		if (!result)
		{
			return null;
		}

		let user = {
			id: parseInt(result.id),
			name: result.name,
			avatar: BX.MessengerCommon.isBlankAvatar(result.avatar)? '': result.avatar,
			last_activity_date: null,
		};

		if (result.last_activity_date instanceof Date)
		{
			user.last_activity_date = result.last_activity_date.toISOString()
		}
		if (typeof result.last_activity_date === 'string')
		{
			user.last_activity_date = result.last_activity_date
		}

		return user;
	}

	getUserFromDb(userId)
	{
		return new Promise((resolve, reject) => {
			BX.indexedDB.getValue(this.ITEMS.obClientDb, "users", "U" + userId).then(function(user){
				if (user && typeof user === 'object')
				{
					resolve(user);
				}
				else
				{
					resolve(null);
				}
			}).catch(error => {
				reject(null);
			});
		});
	}

	addUserToDb(user)
	{
		user.id = "U" + user.id;
		BX.indexedDB.addValue(this.ITEMS.obClientDb, 'users', user);
	}

	getUserFromServer(userId)
	{
		return new Promise((resolve, reject) => {

			if (typeof window.BX.MessengerCommon === 'undefined')
			{
				resolve(null);
				return false;
			}

			rest.callMethod('im.user.get', {id: userId}).then(result => {
				if (result.data())
				{
					let user = {};
					user.id = parseInt(result.data().id);
					user.name = result.data().name;
					user.avatar = BX.MessengerCommon.isBlankAvatar(result.data().avatar)? '': result.data().avatar;
					user.last_activity_date = result.data().last_activity_date;
					user.isExtranet = result.data().extranet ? "Y" : "N";
					user.active = "Y";
					user.entityId = parseInt(result.data().id);

					resolve(user);
				}
				else
				{
					resolve(null);
				}
			}).catch(error => {
				resolve(null);
			});
		});
	}

	getOfflineDate(date)
	{
		return date? new Date(date).getTime() + parseInt(BX.message.LIMIT_ONLINE)*1000: null;
	}

	subscribePullEvent()
	{
		PULL.subscribe({
			type: PullClient.SubscriptionType.Online,
			callback: (data) =>
			{
				if (data.command === 'userStatus')
				{
					for (let userId in data.params.users)
					{
						if (!data.params.users.hasOwnProperty(userId))
						{
							continue;
						}

						this.setUserOnline(data.params.users[userId]);
					}
				}
				else if (data.command === 'list')
				{
					let list = [];

					for (let userId in data.params.users)
					{
						if (data.params.users.hasOwnProperty(userId))
						{
							list.push({
								id: data.params.users[userId].id,
								last_activity_date: data.params.users[userId].last_activity_date
							});
						}
					}

					this.setUserOnlineMultiply(list);
				}
			}
		});

		PULL.subscribe({
			moduleId: 'intranet',
			command: 'timemanDayInfo',
			callback: (data) =>
			{
				this.redrawTimeman(data);
			}
		});
	}

	redrawTimeman(data)
	{
		if (data.hasOwnProperty("OPENED"))
		{
			let openedNode = document.querySelector('.js-ustat-online-timeman-opened');
			if (BX.type.isDomNode(openedNode))
			{
				openedNode.innerHTML = data["OPENED"];
			}
		}

		if (data.hasOwnProperty("CLOSED"))
		{
			let closedNode = document.querySelector('.js-ustat-online-timeman-closed');
			if (BX.type.isDomNode(closedNode))
			{
				closedNode.innerHTML = data["CLOSED"];
			}
		}
	}

	redrawOnline()
	{
		this.showCircleAnimation(this.circleNode, this.counter, this.maxOnlineUserCountToday);
		this.renderAllUser(this.online);
	}

	renderAllUser(users)
	{
		let currentUserIds = [];
		let newUserIds = [];

		users.forEach(function(item) {
			newUserIds.push(parseInt(item.id));
		}.bind(this));

		let currentUserNodes = BX.findChildrenByClassName(this.userBlockNode, "js-ustat-online-user");
		if (currentUserNodes)
		{
			for (let item in currentUserNodes)
			{
				if (!currentUserNodes.hasOwnProperty(item))
				{
					continue;
				}
				let id = parseInt(currentUserNodes[item].getAttribute("data-user-id"));

				if (newUserIds.indexOf(id) === -1)
				{
					if (id !== this.currentUserId)
					{
						BX.remove(currentUserNodes[item]); //remove offline avatars
					}
				}
				else
				{
					currentUserIds.push(parseInt(id));
				}
			}
		}

		let userCounter = currentUserIds.length;
		users.forEach(function(item) {

			if (currentUserIds.indexOf(item.id) === -1 && userCounter < this.maxUserToShow)
			{
				this.renderUser(item);
				userCounter++;
			}
		}.bind(this));
	}

	renderUser(user)
	{
		if (!user || typeof user !== 'object')
		{
			return;
		}

		let userStyle = "";
		if (user.avatar)
		{
			userStyle = 'background-image: url("' + user.avatar + '");';
		}

		this.userItem = BX.create('span', {
			attrs: {
				className: 'ui-icon ui-icon-common-user intranet-ustat-online-icon intranet-ustat-online-icon-show js-ustat-online-user',
				"data-user-id" : user.id
			},
			children: [
				BX.create('i', {
					attrs: { style: userStyle}
				})
			]
		});

		this.userBlockNode.appendChild(this.userInnerBlockNode);
		this.userInnerBlockNode.appendChild(this.userItem);
	}

	showCircleAnimation(circleNode, currentUserOnlineCount, maxUserOnlineCount)
	{
		maxUserOnlineCount = parseInt(maxUserOnlineCount);
		currentUserOnlineCount = parseInt(currentUserOnlineCount);

		if (currentUserOnlineCount <= 0)
		{
			currentUserOnlineCount = 1;
		}

		if (currentUserOnlineCount > maxUserOnlineCount)
		{
			maxUserOnlineCount = currentUserOnlineCount;
		}

		let progressPercent = (currentUserOnlineCount*100)/maxUserOnlineCount;

		if (!this.circle)
		{
			this.circle = new BX.UI.Graph.Circle(circleNode, 200, progressPercent, currentUserOnlineCount);
			this.circle.show();
		}
		else
		{
			this.circle.updateCounter(progressPercent, currentUserOnlineCount);
		}
	}

	showAllOnlinePopup()
	{
		if (this.isAllOnlinePopupShown)
		{
			return;
		}

		this.allOnlinePopupCurrentPage = 1;
		this.popupInnerContainer = "";

		this.allOnlineUserPopup = new BX.PopupWindow('intranet-ustat-online-popup', this.userInnerBlockNode, {
			lightShadow : true,
			offsetLeft: -22,
			autoHide: true,
			closeByEsc: true,
			bindOptions: {
				position: 'bottom'
			},
			animationOptions: {
				show: {
					type: 'opacity-transform'
				},
				close: {
					type: 'opacity'
				}
			},
			events : {
				onPopupDestroy : function() {
					this.isAllOnlinePopupShown = false;
				}.bind(this),
				onPopupClose: function() {
					this.destroy();
				},
				onAfterPopupShow:function(popup)
				{
					let popupContent = popup.contentContainer;

					let popupContainer = BX.create('div', {
						props: {
							className: 'intranet-ustat-online-popup-container'
						},
					});

					this.popupInnerContainer = BX.create('div', {
						props: {
							className: 'intranet-ustat-online-popup-inner'
						},
					});

					let popupInnerContent = BX.create('div', {
						props: {
							className: 'intranet-ustat-online-popup-content'
						},
					});

					let popupInnerContentBox = BX.create('div', {
						props: {
							className: 'intranet-ustat-online-popup-content-box'
						},
					});

					popupContent.appendChild(popupContainer);
					popupContainer.appendChild(popupInnerContent);
					popupInnerContent.appendChild(popupInnerContentBox);
					popupInnerContentBox.appendChild(this.popupInnerContainer);

					this.loader = this.showLoader({node: popup.contentContainer, loader: null, size: 40});
					this.showUsersInPopup();

					this.isAllOnlinePopupShown = true;

				}.bind(this)
			},
			className: 'intranet-ustat-online-popup'
		});

		/*BX.bind(BX('intranet-ustat-online-popup'), 'mouseout' , BX.delegate(function() {
			clearTimeout(this.popupTimeout);
			this.popupTimeout = setTimeout(BX.delegate(function() {
				this.allOnlineUserPopup.close();
			}, this), 1000);
		}, this));

		BX.bind(BX('intranet-ustat-online-popup'), 'mouseover' , BX.delegate(function() {
			clearTimeout(this.popupTimeout);
			clearTimeout(this.mouseLeaveTimeoutId);
		}, this));

		BX.bind(this.userInnerBlockNode, 'mouseleave' , BX.delegate(function() {
			this.mouseLeaveTimeoutId = setTimeout(BX.delegate(function() {
				this.allOnlineUserPopup.close();
			}, this), 1000);
		}, this));*/

		this.popupScroll();

		this.allOnlineUserPopup.show();
	}

	popupScroll()
	{
		if (!BX.type.isDomNode(this.popupInnerContainer))
		{
			return;
		}

		BX.bind(this.popupInnerContainer, 'scroll', BX.delegate(function() {
			var _this = BX.proxy_context;
			if (_this.scrollTop > (_this.scrollHeight - _this.offsetHeight) / 1.5)
			{
				this.showUsersInPopup();
				BX.unbindAll(_this);
			}
		}, this));
	};

	showUsersInPopup()
	{
		BX.ajax.runComponentAction(this.componentName, "getAllOnlineUser", {
			signedParameters: this.signedParameters,
			mode: 'class',
			data: {
				pageNum: this.allOnlinePopupCurrentPage
			}
		}).then(function (response) {
			if (response.data)
			{
				this.renderPopupUsers(response.data);
				this.allOnlinePopupCurrentPage++;
				this.popupScroll();
				this.hideLoader({loader: this.loader});
			}
		}.bind(this), function (response) {
			this.hideLoader({loader: this.loader});
		}.bind(this));
	}

	renderPopupUsers(users)
	{
		if (!this.allOnlineUserPopup || !BX.type.isDomNode(this.popupInnerContainer))
		{
			return;
		}

		if (!users || typeof users !== "object")
		{
			return;
		}

		for (var i in users)
		{
			if (!users.hasOwnProperty(i))
			{
				continue;
			}

			let avatarNode;

			if (BX.type.isNotEmptyString(users[i]['AVATAR']))
			{
				avatarNode = BX.create("div", {
					props: {className: "ui-icon ui-icon-common-user intranet-ustat-online-popup-avatar-img"},
					children: [
						BX.create('i', {
							style : { backgroundImage : "url('" + users[i]['AVATAR'] + "')"}
						})
					]
				});
			}
			else
			{
				avatarNode = BX.create("div", {
					props: {className: "ui-icon ui-icon-common-user intranet-ustat-online-popup-avatar-img"},
					children: [
						BX.create('i', {})
					]
				});
			}

			this.popupInnerContainer.appendChild(
				BX.create("A", {
					attrs: {
						href: users[i]['PATH_TO_USER_PROFILE'],
						target: '_blank',
					},
					props: {
						className: "intranet-ustat-online-popup-item"
					},
					children: [
						BX.create("SPAN", {
							props: {
								className: "intranet-ustat-online-popup-avatar-new"
							},
							children: [
								avatarNode,
								BX.create("SPAN", {
									props: {className: "intranet-ustat-online-popup-avatar-status-icon"}
								})
							]
						}),
						BX.create("SPAN", {
							props: {
								className: "intranet-ustat-online-popup-name"
							},
							html: users[i]['NAME']
						})
					]
				})
			);
		}

	}

	showLoader(params)
	{
		var loader = null;

		if (params.node)
		{
			if (params.loader === null)
			{
				loader = new BX.Loader({
					target: params.node,
					size: params.hasOwnProperty("size") ? params.size : 40
				});
			}
			else
			{
				loader = params.loader;
			}

			loader.show();
		}

		return loader;
	}

	hideLoader(params)
	{
		if (params.loader !== null)
		{
			params.loader.hide();
		}

		if (params.node)
		{
			BX.cleanNode(params.node);
		}

		if (params.loader !== null)
		{
			params.loader = null;
		}
	}
}

namespace.UstatOnline = UstatOnline;