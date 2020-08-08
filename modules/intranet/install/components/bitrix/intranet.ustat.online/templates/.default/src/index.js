import {Popup} from './popup';

import {Type, Dom, Reflection} from 'main.core';
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
		this.timemanNode = params.timemanNode || "";
		this.ustatOnlineContainerNode = params.ustatOnlineContainerNode || "";
		this.maxUserToShow = 7;
		this.maxOnlineUserCountToday = params.maxOnlineUserCountToday;
		this.currentUserId = parseInt(params.currentUserId);
		this.isTimemanAvailable = params.isTimemanAvailable === "Y";
		this.limitOnlineSeconds = params.limitOnlineSeconds;

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

		if (Type.isDomNode(this.ustatOnlineContainerNode))
		{
			BX.UI.Hint.init(this.ustatOnlineContainerNode);
		}

		new Popup(this);

		let now = new Date();
		this.currentDate = new Date(now.getFullYear(), now.getMonth(), now.getDate()).valueOf();

		this.checkOnline();

		if (this.isTimemanAvailable && Type.isDomNode(this.timemanNode))
		{
			this.timemanValueNodes = this.timemanNode.querySelectorAll('.intranet-ustat-online-value');
			this.timemanTextNodes =  this.timemanNode.querySelectorAll('.js-ustat-online-timeman-text');

			this.resizeTimemanText();
		}

		setTimeout(() => {
			this.subscribePullEvent();
		}, 3000);

		setInterval(() => this.checkOnline(), 60000);
	}

	getOfflineDate(date)
	{
		return date? new Date(date).getTime() + parseInt(this.limitOnlineSeconds)*1000: null;
	}

	checkOnline()
	{
		this.online = this.online.filter(user => {
			return (user && (user.offline_date > +new Date() || user.id === this.currentUserId));
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

		if (this.checkNewDay() || prevCounter !== this.counter)
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

			if (this.isTimemanAvailable)
			{
				this.checkTimeman();
			}

			this.currentDate = today;

			return true;
		}

		return false;
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

		let isUserOnline = false;
		this.online.forEach((element) => {
			if (element.id === user.id)
			{
				isUserOnline = true;
			}
		});
		if (!isUserOnline)
		{
			this.online.unshift(user);
		}

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
			if (requestUserList.length <= 0)
			{
				return false;
			}

			let requestCount = this.maxUserToShow-counterFindUser;
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
				return true;
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
					if (user.hasOwnProperty("entityId"))
					{
						user.id = user.entityId;
					}
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
				/*else if (data.command === 'list')
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
				}*/
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

		this.resizeTimemanText();
	}

	resizeTimemanText()
	{
		if (!Type.isDomNode(this.timemanNode))
		{
			return;
		}

		let textSum = 0;
		let valueSum = 0;

		if (Type.isArrayLike(this.timemanTextNodes))
		{
			this.timemanTextNodes.forEach(text => {
				let textItems = text.textContent.length;
				textSum += textItems;
			});
		}

		if (Type.isArrayLike(this.timemanValueNodes))
		{
			this.timemanValueNodes.forEach(value => {
				let valueItems = value.textContent.length;
				valueSum += valueItems;
			});
		}

		if (textSum >= 17 && valueSum >= 6 || textSum >= 19 && valueSum >= 4)
		{
			Dom.addClass(this.timemanNode, 'intranet-ustat-online-info-text-resize');
		}
		else
		{
			Dom.removeClass(this.timemanNode, 'intranet-ustat-online-info-text-resize');
		}
	}

	redrawOnline()
	{
		this.showCircleAnimation(this.circleNode, this.counter, this.maxOnlineUserCountToday);
		this.renderAllUser();
	}

	renderAllUser()
	{
		let renderedUserIds = [];
		let newUserIds = [];

		this.online.forEach((item) => {
			newUserIds.push(parseInt(item.id));
		});

		let renderedUserNodes = this.userBlockNode.querySelectorAll(".js-ustat-online-user");
		if (renderedUserNodes)
		{
			for (let item in renderedUserNodes)
			{
				if (!renderedUserNodes.hasOwnProperty(item))
				{
					continue;
				}
				let renderedItemId = parseInt(renderedUserNodes[item].getAttribute("data-user-id"));

				if (newUserIds.indexOf(renderedItemId) === -1)
				{
					if (Type.isDomNode(renderedUserNodes[item]))
					{
						Dom.remove(renderedUserNodes[item]); //remove offline avatars
					}
				}
				else
				{
					renderedUserIds.push(parseInt(renderedItemId));
				}
			}
		}

		renderedUserNodes = this.userBlockNode.querySelectorAll(".js-ustat-online-user");
		let renderedUserCount = renderedUserNodes.length;

		this.online.forEach((item, i) => {
			if (i >= this.maxUserToShow || !item.hasOwnProperty("id"))
			{
				return;
			}

			if (renderedUserIds.indexOf(item.id) === -1)
			{
				if (renderedUserCount < this.maxUserToShow)
				{
					this.renderUser(item);
					renderedUserCount++;
				}
				else
				{
					let element = this.userBlockNode.querySelector(".js-ustat-online-user");

					if (Type.isDomNode(element))
					{
						const removedUserId = parseInt(element.getAttribute("data-user-id"));
						Dom.remove(element);
						this.renderUser(item);
						renderedUserIds = renderedUserIds.filter(id => id !== removedUserId);
						renderedUserIds.push(item.id)
					}
				}
			}
		});
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
			this.circle = new BX.UI.Graph.Circle(circleNode, 68, progressPercent, currentUserOnlineCount);
			this.circle.show();
		}
		else
		{
			this.circle.updateCounter(progressPercent, currentUserOnlineCount);
		}
	}
}

namespace.UstatOnline = UstatOnline;
