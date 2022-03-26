import { ajax as Ajax, Runtime } from 'main.core';
import { EventEmitter } from 'main.core.events';

class TaskResultMobile
{
	taskId = 0;
	userId = 0;
	itemsContentNode = null;
	itemsNodes = null;
	itemsWrapperNode = null;
	needTutorial = false;
	messages = {};

	constructor(taskId, userId, params)
	{
		this.init(taskId, userId, params);
		this.setHeightAutoFunction = this.setHeightAuto.bind(this);
	}

	init(taskId, userId, params)
	{
		this.taskId = taskId;
		this.userId = userId;
		this.needTutorial = params.needTutorial;
		this.messages = params.messages;

		this.initExpand();

		BXMobileApp.addCustomEvent('onPull-tasks', this.onPush.bind(this));
	}

	blockResize()
	{
		this.contentNode.style.height = `${this.containerNode.scrollHeight}px`;
	}

	initExpand()
	{
		if (this.contentNode)
		{
			this.blockResize();
		}

		this.contentNode = document.getElementById(`mobile-tasks-result-list-container-${this.taskId}`);
		this.containerNode = document.getElementById(`tasks-result-list-wrapper-${this.taskId}`);

		if (!this.containerNode)
		{
			return;
		}

		this.itemsContentNode = this.containerNode.querySelector('[data-role="mobile-tasks-widget--content"]');
		this.itemsNodes = this.containerNode.querySelectorAll('[data-role="mobile-tasks-widget--result-item"]');
		this.itemsWrapperNode = this.containerNode.querySelector('[data-role="mobile-tasks-widget--wrapper"]');

		if (this.itemsWrapperNode && this.itemsNodes.length > 1)
		{
			this.itemsNodes.length === 2
				? this.itemsContentNode.classList.add('--two-results')
				: this.itemsContentNode.classList.add('--many-results');
		}

		this.itemsContentNode && this.itemsContentNode.addEventListener('click', () => {
			this.itemsContentNode.classList.add('--open');
			this.itemsWrapperNode.style.height = `${this.itemsWrapperNode.scrollHeight}px`;
			this.itemsWrapperNode.addEventListener('transitionend', this.setHeightAutoFunction);

			if (this.contentNode && this.itemsWrapperNode.offsetHeight === 0)
			{
				this.contentNode.style.height = `${this.itemsWrapperNode.scrollHeight + this.containerNode.scrollHeight}px`;
			}
		});

		EventEmitter.subscribe('BX.Forum.Spoiler:toggle', this.onSpoilerToggle.bind(this));
	}

	onSpoilerToggle(event)
	{
		const [ eventData ] = event.getCompatData();

		if (!eventData.node)
		{
			return;
		}

		const targetContentNode = eventData.node.closest('.mobile-tasks-result-list-container');
		if (
			!targetContentNode
			|| !this.contentNode
			|| targetContentNode.id !== this.contentNode.id
		)
		{
			return;
		}

		this.blockResize();
	}

	setHeightAuto()
	{
		this.itemsWrapperNode.style.height = 'auto';
		this.itemsWrapperNode.removeEventListener('transitionend', this.setHeightAutoFunction);
	};

	onPush(event)
	{
		const command = event.command;
		const params = event.params;

		if (command === 'comment_add')
		{
			this.onCommentAdd(command, params);
		}
		else if (
			command === 'task_result_create'
			|| command === 'task_result_update'
			|| command === 'task_result_delete'
		)
		{
			this.onResultUpdate(command, params);
		}
	}

	onResultUpdate(command, params)
	{
		if (
			!params.result
			|| !params.result.taskId
			|| params.result.taskId != this.taskId
		)
		{
			return;
		}

		this.reloadResults();
	}

	onCommentAdd(command, params)
	{
		if (!this.needTutorial)
		{
			return;
		}

		if (
			!params.taskId
			|| params.taskId != this.taskId
			|| !params.ownerId
			|| params.ownerId != this.userId
		)
		{
			return;
		}

		(new BXMobileApp.UI.NotificationBar({
			title: this.messages.tutorialTitle,
			message: this.messages.tutorialMessage,
			color: "#af000000",
			textColor: "#ffffff",
			isGlobal: true,
			autoHideTimeout: 6500,
			hideOnTap: true
		}, 'copy')).show();

		// send ajax request to disable tutorial
		Ajax.runComponentAction('bitrix:tasks.widget.result', 'disableTutorial', {
			mode: 'class',
			data: {},
		}).then((response) => {});
	}

	reloadResults()
	{
		Ajax.runComponentAction('bitrix:tasks.widget.result', 'getResults', {
			mode: 'class',
			data: {
				taskId: this.taskId,
				mode: 'mobile',
			}
		}).then((response) => {
			if (!response.data)
			{
				return;
			}

			this.containerNode.innerHTML = response.data;
			Runtime.html(this.containerNode, response.data).then(() => {
				this.initExpand();
			});
		});
	}
}

export {
	TaskResultMobile,
}
