import {pos, GetWindowInnerSize, Browser, addCustomEvent, removeCustomEvent} from 'main.core';
import {Pagination} from './pagination';
import Item from './item';
import ItemComment from './itemcomment';
import ItemCalltracker from './itemcalltracker';
import ItemPreview from './itempreview';
import ItemActivity from './itemactivity';
import Form from './form/form';
import {BaseEvent, EventEmitter} from 'main.core.events';

const itemMappings = [
	Item,
	ItemComment,
	ItemCalltracker,
];
function getItemByData(itemData)
{
	let itemClassName = Item;
	itemMappings.forEach((itemClass) => {
		if (itemClass.checkForPaternity(itemData))
		{
			itemClassName = itemClass;
		}
	});
	return new itemClassName(itemData);
}

window.app.exec("enableCaptureKeyboard", true);
let keyBoardIsShown = false;
addCustomEvent("onKeyboardWillShow", function() { keyBoardIsShown = true; });
addCustomEvent("onKeyboardDidHide", function() { keyBoardIsShown = false; });

export default class Timeline extends EventEmitter {
	constructor({
		entity,
		containerScheduleItems,
		scheduleItems,
		containerHistoryItems,
		historyItems
	}) {
		super();
		this.setEventNamespace('CRM:Calltracker:');
		this.activities = new Map();
		this.items = new Map();
		this.entity = entity;
		this.addScheduleItems(scheduleItems, containerScheduleItems);

		this.container = containerHistoryItems;
		this.addItems(historyItems);

		this.pagination = null;
		this.form = new Form();
		this.form.subscribe('onNewComment', this.onNewComment.bind(this));
		this.form.subscribe('onFailedComment', this.onFailedComment.bind(this));
		this.form.subscribe('onSucceedComment', this.onSucceedComment.bind(this));

		//@todo remove this test
/*				setTimeout(() => {
					const previewItem = new ItemPreview({text: 'Text for test error'});
					previewItem.setError(new Error('Just error to check template.'));

					this.container.appendChild(previewItem.getNode());
					const previewItemAnoterOne = new ItemPreview({text: 'Text for preview test'});
					this.container.appendChild(previewItemAnoterOne.getNode());
				}, 100);
*/	}

	addScheduleItems(items, containerScheduleItems) {
		items.forEach((itemData) => {
			const item = new ItemActivity(itemData);

			this.activities.set(item.getOwnerId(), item);
			containerScheduleItems.appendChild(item.getNode());
		});
	}

	addItems(items) {
		let pointerNode = this.container.firstChild;
		items.forEach((itemData) => {
			const item = getItemByData(itemData);
			this.items.set(item.getId(), item);
			if (this.activities.has(item.getOwnerId())) {
				const activity = this.activities.get(item.getOwnerId());
				activity.getNode().parentNode.removeChild(activity.getNode());
				activity.solve();
				this.activities.delete(activity.getOwnerId());
			}
			this.container.insertBefore(item.getNode(), pointerNode);
		});
	}

	onNewComment({data:{comment}}: BaseEvent)
	{
		const previewItem = new ItemPreview(comment);
		this.container.appendChild(previewItem.getNode());
		comment.item = previewItem;
		comment.node = previewItem.getNode();

		let iosPatchNeeded = false;
		if (Browser.isIOS())
		{
			const res = (navigator.appVersion).match(/OS (\d+)_(\d+)_?(\d+)?/);
			const iOSVersion = parseInt(res[1], 10);
			iosPatchNeeded = (iOSVersion >= 11 && keyBoardIsShown);
		}
		const iosPatchDelta = iosPatchNeeded ? 260 : 0;

		const thumbPos = pos(previewItem.getNode());
		const visibleTop = GetWindowInnerSize().innerHeight - iosPatchDelta;

		if (iosPatchNeeded === false || thumbPos.top > visibleTop)
		{
			window.scrollTo(0, thumbPos.top - iosPatchDelta);
		}
	}

	onFailedComment({data:{comment}}: BaseEvent)
	{
		if (comment.item)
		{
			comment.item.setError(comment.error);
		}
	}
	onSucceedComment({data:{comment, commentData, comments}}: BaseEvent)
	{
		console.log('commentData: ', commentData, comments);

		comments.forEach((itemData) => {
			const item = getItemByData(itemData);
			this.items.set(item.getId(), item);
			if (this.activities.has(item.getOwnerId())) {
				const activity = this.activities.get(item.getOwnerId());
				activity.getNode().parentNode.removeChild(activity.getNode());
				activity.solve();
				this.activities.delete(activity.getOwnerId());
			}
			if (item.getId() < commentData['ID'])
			{
				this.container.insertBefore(item.getNode(), comment.node);
			}
			else if (item.getId() > commentData['ID'])
			{
				this.container.insertBefore(item.getNode(), comment.node.nextSibling);
			}
			else
			{
				comment.node.parentNode.replaceChild(item.getNode(), comment.node);
				delete comment.node;
				delete comment.item;
			}
		});

		if (BXMobileApp && this.entity)
		{
			BXMobileApp.Events.postToComponent('onCrmCallTrackerItemCommentAdded', {
				ID: this.entity.ID
			});
			BX.onCustomEvent('onCrmCallTrackerItemCommentAdded', {
				ID: this.entity.ID
			});
		}
	}

	initPagination(itemId)
	{
		this.pagination = new Pagination(itemId, this.addItems.bind(this));
		this.container.parentNode.insertBefore(this.pagination.getNode(), this.container);
	}
}