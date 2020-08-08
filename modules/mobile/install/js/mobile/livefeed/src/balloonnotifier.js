import {Loc} from "main.core";
import {Instance} from "./feed";

class BalloonNotifier
{
	constructor()
	{
		this.classes = {
			show: 'lenta-notifier-shown'
		};
		this.nodeIdList = {
			notifier: 'lenta_notifier',
			notifierCounter: 'lenta_notifier_cnt',
			notifierCounterTitle: 'lenta_notifier_cnt_title',
			refreshNeeded: 'lenta_notifier_2'
		};
	}

	getNotifierNode()
	{
		return document.getElementById(this.nodeIdList.notifier);
	}
	getNotifierCounterNode()
	{
		return document.getElementById(this.nodeIdList.notifierCounter);
	}
	getNotifierCounterTitleNode()
	{
		return document.getElementById(this.nodeIdList.notifierCounterTitle);
	}
	getRefreshNeededNode()
	{
		return document.getElementById(this.nodeIdList.refreshNeeded);
	}

	showRefreshNeededNotifier()
	{
		const refreshNeededBlock = this.getRefreshNeededNode();
		if (refreshNeededBlock)
		{
			refreshNeededBlock.classList.add(this.classes.show);
		}
	}

	hideRefreshNeededNotifier()
	{
		const refreshNeededNode = this.getRefreshNeededNode();
		if (refreshNeededNode)
		{
			refreshNeededNode.classList.remove(this.classes.show);
		}
	}

	showNotifier(params)
	{
		let cnt = parseInt(params.counterValue);

		const
			cnt_cent = cnt % 100,
			reminder = cnt % 10;

		let suffix = '';

		if (
			cnt_cent >= 10
			&& cnt_cent < 15
		)
		{
			suffix = 3;
		}
		else if (reminder == 0)
		{
			suffix = 3;
		}
		else if (reminder == 1)
		{
			suffix = 1;
		}
		else if (
			reminder == 2
			|| reminder == 3
			|| reminder == 4
		)
		{
			suffix = 2;
		}
		else
		{
			suffix = 3;
		}

		if (Instance.getRefreshNeeded())
		{
			this.getNotifierCounterNode().innerHTML = (cnt ? cnt + '+' : '');
			this.hideRefreshNeededNotifier();
		}
		else
		{
			this.getNotifierCounterNode().innerHTML = cnt || '';
		}

		this.getNotifierCounterTitleNode().innerHTML = Loc.getMessage('MOBILE_EXT_LIVEFEED_COUNTER_TITLE_' + suffix);
		this.getNotifierNode().classList.add(this.classes.show);
	}

	hideNotifier()
	{
		const notifierNode = this.getNotifierNode();
		if (notifierNode)
		{
			notifierNode.classList.remove(this.classes.show);
		}
	};
}

export {
	BalloonNotifier
}