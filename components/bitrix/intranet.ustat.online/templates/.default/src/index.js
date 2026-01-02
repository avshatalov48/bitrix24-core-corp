import { Type, Dom, Reflection } from 'main.core';
import { UserPopup } from './popup';
import { Timeman } from './timeman';
import { Circle } from 'ui.graph.circle';

const namespace = Reflection.namespace('BX.Intranet');

class UstatOnline
{
	constructor(params: Object)
	{
		this.signedParameters = params.signedParameters;
		this.componentName = params.componentName;
		this.ustatOnlineContainerNode = params.ustatOnlineContainerNode || '';
		this.maxOnlineUserCountToday = parseInt(params.maxOnlineUserCountToday, 10);
		this.isTimemanAvailable = params.isTimemanAvailable === 'Y';

		if (!Type.isDomNode(this.ustatOnlineContainerNode))
		{
			return;
		}

		this.userInnerBlockNode = this.ustatOnlineContainerNode.querySelector('.intranet-ustat-online-icon-inner');
		this.circleNode = this.ustatOnlineContainerNode.querySelector('.ui-graph-circle');
		this.timemanNode = this.ustatOnlineContainerNode.querySelector('.intranet-ustat-online-info');

		this.users = params.users;
		this.counter = parseInt(params.user_count, 10);
		this.currentDate = this.getToday();

		if (Type.isDomNode(this.ustatOnlineContainerNode))
		{
			BX.UI.Hint.init(this.ustatOnlineContainerNode);
		}

		new UserPopup(this);
		this.timemanObj = new Timeman(this);

		this.redrawOnline();

		setInterval(() => {
			this.checkNewDay();
		}, 3600000); //1 hour
	}

	getToday(): Date
	{
		const now = new Date();

		return new Date(now.getFullYear(), now.getMonth(), now.getDate()).valueOf();
	}

	checkNewDay(): void
	{
		const today = this.getToday();

		if (this.currentDate < today) //new day
		{
			if (this.isTimemanAvailable)
			{
				this.timemanObj.checkTimeman();
			}

			this.currentDate = today;

			return true;
		}

		return false;
	}

	isDocumentVisible(): boolean
	{
		return document.visibilityState === 'visible';
	}

	redrawOnline(): void
	{
		this.showCircleAnimation(this.circleNode, this.counter);
		this.renderAllUser();
	}

	renderAllUser(): void
	{
		for (const index in this.users)
		{
			this.renderUser(this.users[index]);
		}
	}

	renderUser(user: Object): void
	{
		let userStyle = '';
		if (user.avatar)
		{
			userStyle = `background-image: url("${encodeURI(user.avatar)}");`;
		}

		const itemsClasses = `
			ui-icon ui-icon-common-user intranet-ustat-online-icon js-ustat-online-user
						${this.isDocumentVisible() ? ' intranet-ustat-online-icon-show' : ''}
		`;

		this.userItem = BX.create('span', {
			attrs: {
				className: itemsClasses,
				'data-user-id': user.id,
			},
			style: {
				zIndex: this.userIndex,
			},
			children: [
				BX.create('i', {
					attrs: {
						style: userStyle,
					},
				}),
			],
		});

		Dom.prepend(this.userItem, this.userInnerBlockNode);
	}

	showCircleAnimation(circleNode: Element, currentUserOnlineCount: number): void
	{
		if (currentUserOnlineCount <= 0)
		{
			currentUserOnlineCount = 1;
		}

		const progressPercent = (currentUserOnlineCount * 100) / this.maxOnlineUserCountToday;

		if (this.circle)
		{
			this.circle.updateCounter(progressPercent, currentUserOnlineCount);
		}
		else
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
	}
}

namespace.UstatOnline = UstatOnline;
export { UstatOnline };
