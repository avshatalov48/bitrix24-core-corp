import {Dom, Tag, Text} from "main.core";

export class TaskCounts
{
	constructor(options)
	{
		this.itemId = (options.itemId ? options.itemId : Text.getRandom());

		this.attachedFilesCount = (options.attachedFilesCount ? parseInt(options.attachedFilesCount, 10) : 0);
		this.checkListComplete = (options.checkListComplete ? parseInt(options.checkListComplete, 10) : 0);
		this.checkListAll = (options.checkListAll ? parseInt(options.checkListAll, 10) : 0);
		this.newCommentsCount = (options.newCommentsCount ? parseInt(options.newCommentsCount, 10) : 0);
	}

	createIndicators(): ?HTMLElement
	{
		this.indicatorsNodeId = 'tasks-scrum-item-indicators-' + this.itemId;
		return Tag.render`
			<span id="${this.indicatorsNodeId}" class="task-title-indicators">
				<div class="task-attachment-counter ui-label ui-label-sm ui-label-light">
					<span class="ui-label-inner">${this.attachedFilesCount}</span>
				</div>
				<div class='task-checklist-counter ui-label ui-label-sm ui-label-light'>
					<span class='ui-label-inner'>${this.checkListComplete}/${this.checkListAll}</span>
				</div>
				<div class='task-comments-counter'>
					<div class='ui-counter ui-counter-success'>
						<div class='ui-counter-inner'>${this.newCommentsCount}</div>
					</div>
				</div>
			</span>
		`;
	}

	onAfterAppend()
	{
		this.indicatorsNode = document.getElementById(this.indicatorsNodeId);

		this.attachmentNode = this.indicatorsNode.querySelector('.task-attachment-counter');
		this.checklistNode = this.indicatorsNode.querySelector('.task-checklist-counter');
		this.commentsNode = this.indicatorsNode.querySelector('.task-comments-counter');

		this.updateVisibility();
	}

	updateIndicators(data: Object)
	{
		if (!this.indicatorsNode)
		{
			return;
		}

		if (data.attachedFilesCount)
		{
			this.attachedFilesCount = parseInt(data.attachedFilesCount, 10);
			this.attachmentNode.firstElementChild.textContent = this.attachedFilesCount;
		}
		if (data.checkListComplete)
		{
			this.checkListComplete = parseInt(data.checkListComplete, 10);
			this.checklistNode.firstElementChild.textContent = this.checkListComplete + '/' + this.checkListAll;
		}
		if (data.checkListAll)
		{
			this.checkListAll = parseInt(data.checkListAll, 10);
			this.checklistNode.firstElementChild.textContent = this.checkListComplete + '/' + this.checkListAll;
		}
		if (data.newCommentsCount)
		{
			this.newCommentsCount = parseInt(data.newCommentsCount, 10);
			const innerCommentCounter = this.commentsNode.querySelector('.ui-counter-inner');
			innerCommentCounter.textContent = this.newCommentsCount;
		}

		this.updateVisibility();
	}

	updateVisibility()
	{
		if (this.attachedFilesCount > 0)
		{
			this.showNode(this.attachmentNode);
		}
		else
		{
			this.hideNode(this.attachmentNode);
		}

		if (this.checkListAll > 0)
		{
			this.showNode(this.checklistNode);
		}
		else
		{
			this.hideNode(this.checklistNode);
		}

		if (this.newCommentsCount > 0)
		{
			this.showNode(this.commentsNode);
		}
		else
		{
			this.hideNode(this.commentsNode);
		}
	}

	showNode(node)
	{
		Dom.style(node, 'display', 'inline-flex');
	}

	hideNode(node)
	{
		Dom.style(node, 'display', 'none');
	}
}