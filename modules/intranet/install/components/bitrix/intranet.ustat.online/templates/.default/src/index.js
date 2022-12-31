import {Type, Dom, Reflection, Event} from 'main.core';
import {rest} from "rest.client";
import "pull.client";
import {UserPopup} from './popup';
import {Timeman} from './timeman';
import {Circle} from 'ui.graph.circle';

const namespace = Reflection.namespace('BX.Intranet');

class UstatOnline
{
	constructor(params)
	{
		this.signedParameters = params.signedParameters;
		this.componentName = params.componentName;
		this.ustatOnlineContainerNode = params.ustatOnlineContainerNode || "";
		this.maxUserToShow = params.maxUserToShow;
		this.maxOnlineUserCountToday = params.maxOnlineUserCountToday;
		this.currentUserId = parseInt(params.currentUserId);
		this.isTimemanAvailable = params.isTimemanAvailable === "Y";
		this.isFullAnimationMode = params.isFullAnimationMode === "Y";
		this.limitOnlineSeconds = params.limitOnlineSeconds;
		this.renderingFinished = true;

		if (!Type.isDomNode(this.ustatOnlineContainerNode))
		{
			return;
		}

		this.userBlockNode = this.ustatOnlineContainerNode.querySelector('.intranet-ustat-online-icon-box');
		this.userInnerBlockNode = this.ustatOnlineContainerNode.querySelector('.intranet-ustat-online-icon-inner');
		this.circleNode = this.ustatOnlineContainerNode.querySelector('.ui-graph-circle');
		this.timemanNode = this.ustatOnlineContainerNode.querySelector('.intranet-ustat-online-info');

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

		new UserPopup(this);
		this.timemanObj = new Timeman(this);

		const now = new Date();
		this.currentDate = new Date(now.getFullYear(), now.getMonth(), now.getDate()).valueOf();

		this.checkOnline();

		if (this.isFullAnimationMode)
		{
			setTimeout(() => {
				this.subscribePullEvent();
			}, 3000);

			setInterval(() => this.checkOnline(), 60000);
		}
		else
		{
			BX.addCustomEvent(window, "onImUpdateUstatOnline", BX.proxy(this.updateOnlineRestrictedMode, this));
		}
	}

	updateOnlineRestrictedMode(data)
	{
		this.counter = data.count;
		this.maxOnlineUserCountToday = data.count;
		this.online = data.users;
		this.redrawOnline();
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
		const now = new Date();
		const today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).valueOf();

		if (this.currentDate < today) //new day
		{
			this.maxOnlineUserCountToday = this.online.length;

			if (this.isTimemanAvailable)
			{
				this.timemanObj.checkTimeman();
			}

			this.currentDate = today;

			return true;
		}

		return false;
	}

	setUserOnline(params)
	{
		const userId = this.getNumberUserId(params.id);

		this.findUser(userId).then(user => {
			user.id = this.getNumberUserId(user.id);

			if (typeof params.last_activity_date !== "undefined")
			{
				user.last_activity_date = params.last_activity_date;
			}

			if (user.isExtranet !== "Y")
			{
				this.setUserToLocal(user);
				this.checkOnline();
			}
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

			const requestCount = this.maxUserToShow-counterFindUser;

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

					const userData = collection[userId];
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
			BX.indexedDB.getValue(this.ITEMS.obClientDb, "users", "U" + userId).then((user) => {
				if (user && typeof user === 'object')
				{
					if (user.hasOwnProperty("entityId"))
					{
						user.id = this.getNumberUserId(user.entityId);
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
				if (result.data() && result.data().external_auth_id !== "__controller")
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
		BX.PULL.subscribe({
			type: 'online',
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
	}

	getNumberUserId(id)
	{
		if (!id)
		{
			return;
		}

		let userId = String(id);
		userId = userId.replace('U', '');
		return parseInt(userId);
	}

	isDocumentVisible()
	{
		return document.visibilityState === 'visible';
	}

	redrawOnline()
	{
		this.showCircleAnimation(this.circleNode, this.counter, this.maxOnlineUserCountToday);
		if (this.renderingFinished)
		{
			this.renderingFinished = false;
			this.renderAllUser();
		}
	}

	renderAllUser()
	{
		let renderedUserIds = [];
		let newUserIds = [];

		this.online.forEach((item) => {
			newUserIds.push(parseInt(item.id));
		});

		const onlineToShow = newUserIds.slice(0, this.maxUserToShow);

		let renderedUserNodes = this.userBlockNode.querySelectorAll(".js-ustat-online-user");

		if (this.online.length > 100 && renderedUserNodes.length >= this.maxUserToShow)
		{
			return;
		}

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
					/*renderedUserNodes[item].classList.add('intranet-ustat-online-icon-hide');
					setTimeout( () => {

					}, 800);*/
				}
			}
			else
			{
				renderedUserIds.push(parseInt(renderedItemId));
			}
		}

		renderedUserNodes = this.userBlockNode.querySelectorAll(".js-ustat-online-user");

		let renderedUserCount = renderedUserNodes.length;
		const showAnimation = renderedUserCount !== 0;
		this.userIndex = this.online.length;

		let stepRender = (i) => {
			if (i >= this.maxUserToShow || i >= this.online.length)
			{
				this.renderingFinished = true;
				return;
			}

			new Promise((resolve) => {
				let item = this.online[i];

				if (renderedUserIds.indexOf(item.id) >= 0)
				{
					resolve();
					return;
				}

				if (renderedUserCount < this.maxUserToShow)
				{
					if (showAnimation)
					{
						this.userIndex++;
					}
					this.renderUser(item, showAnimation);
					renderedUserIds.push(item.id);
					renderedUserCount++;
					if (!showAnimation)
					{
						this.userIndex = this.userIndex - 1;
					}

					resolve();
				}
				else
				{
					const elements = this.userBlockNode.querySelectorAll(".js-ustat-online-user");
					const firstElement = elements[0];
					let lastElement = "";

					for (let i = elements.length - 1; i >= 0; i--)
					{
						if (Type.isDomNode(elements[i]))
						{
							const elementUserId = parseInt(elements[i].getAttribute("data-user-id"));
							if (onlineToShow.indexOf(elementUserId) === -1)
							{
								lastElement = elements[i];
								break;
							}
						}
					}

					if (Type.isDomNode(lastElement))
					{
						const removedUserId = parseInt(lastElement.getAttribute("data-user-id"));

						Dom.removeClass(lastElement, 'intranet-ustat-online-icon-show');
						if (this.isDocumentVisible())
						{
							Dom.addClass(lastElement, 'intranet-ustat-online-icon-hide');
						}

						this.userIndex = parseInt(firstElement.style.zIndex);
						this.userIndex++;

						this.renderUser(item, showAnimation);
						renderedUserIds = renderedUserIds.filter(id => id !== removedUserId);
						renderedUserIds.push(item.id);

						if (this.isDocumentVisible())
						{
							Event.bind(lastElement, 'animationend', (event) => {
								Dom.remove(lastElement);
								resolve();
							});
						}
						else
						{
							Dom.remove(lastElement);
							resolve();
						}
					}
					else
					{
						resolve();
					}
				}
			}).then(() => {
				stepRender(++i);
			});
		};

		stepRender(0);
	}

	renderUser(user, showAnimation)
	{
		if (!user || typeof user !== 'object')
		{
			return;
		}

		let userStyle = "";
		if (user.avatar)
		{
			userStyle = 'background-image: url("' + encodeURI(user.avatar) + '");';
		}

		const userId = this.getNumberUserId(user.id);

		let itemsClasses = `ui-icon ui-icon-common-user intranet-ustat-online-icon js-ustat-online-user
			${showAnimation && this.isDocumentVisible() ? ' intranet-ustat-online-icon-show' : ''}`;

		this.userItem = BX.create('span', {
			attrs: {
				className: itemsClasses,
				"data-user-id" : userId
			},
			style: {
				zIndex: this.userIndex
			},
			children: [
				BX.create('i', {
					attrs: { style: userStyle}
				})
			]
		});

		if (showAnimation)
		{
			Dom.prepend(this.userItem, this.userInnerBlockNode);
		}
		else
		{
			this.userInnerBlockNode.appendChild(this.userItem);
		}
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
			this.circle = new Circle(
				circleNode,
				68,
				progressPercent,
				currentUserOnlineCount,
				true,
			);
			this.circle.show();
		}
		else
		{
			this.circle.updateCounter(progressPercent, currentUserOnlineCount);
		}
	}
}

namespace.UstatOnline = UstatOnline;
export {UstatOnline};
