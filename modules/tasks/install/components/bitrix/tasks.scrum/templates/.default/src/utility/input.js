import {Browser, Dom, Event, Loc, Tag, Text, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Entity} from '../entity/entity';

import {TagSearcher} from './tag.searcher';

import '../css/input.css';

import type {EpicType} from '../item/task/epic';

export class Input extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.Input');

		this.entity = null;
		this.bindNode = null;

		this.node = null;

		this.value = '';
		this.epic = null;
		this.taskCreated = false;

		this.selectedEpicLength = 0;
	}

	setEntity(entity: Entity)
	{
		this.entity = entity;
	}

	getEntity(): ?Entity
	{
		return this.entity;
	}

	hasEntity(entity: Entity): boolean
	{
		return this.entity && this.entity.getId() === entity.getId();
	}

	setBindNode(node: HTMLElement)
	{
		this.bindNode = node;
	}

	getBindNode(): ?HTMLElement
	{
		return this.bindNode;
	}

	cleanBindNode()
	{
		this.bindNode = null;
	}

	render(): HTMLElement
	{
		this.nodeId = Text.getRandom();

		this.node = Tag.render`
			<div id="${Text.encode(this.nodeId)}" class="tasks-scrum__input --add-block">
				<textarea
					placeholder="${Loc.getMessage('TASKS_SCRUM_TASK_ADD_INPUT_TASK_PLACEHOLDER')}"
					class="tasks-scrum__input--textarea"
				>${Text.encode(this.value)}</textarea>
				<div class="tasks-scrum__input--textarea-help">
					${Loc.getMessage('TASKS_SCRUM_TASK_ADD_INPUT_TASK_PLACEHOLDER_HELPER')}
				</div>
			</div>
		`;

		Event.bind(this.getInputNode(), 'input', (event) => {
			this.onTagSearch(event);
			this.onEpicSearch(event);
		});
		Event.bind(this.getInputNode(), 'keydown', this.onKeydown.bind(this));
		Event.bind(this.getInputNode(), 'blur', this.onBlur.bind(this));

		this.emit('render');

		this.taskCreated = false;

		return this.node;
	}

	onKeydown(event: KeyboardEvent)
	{
		if (event.key === 'Escape' || event.key === 'Enter')
		{
			if (!this.isTagsSearchMode() && !this.isEpicSearchMode())
			{
				this.submit();

				event.stopImmediatePropagation();
			}

			if (
				event.key === 'Enter'
				&& !((Browser.isMac() && event.metaKey) || event.ctrlKey)
			)
			{
				this.emit('onEnter', { event });
			}
			if (
				event.key === 'Enter'
				&& ((Browser.isMac() && event.metaKey) || event.ctrlKey)
			)
			{
				this.emit('onMetaEnter', { event });
			}
		}
	}

	onBlur()
	{
		const input = this.getInputNode();

		if (input.value === '')
		{
			this.removeYourself();
		}
	}

	onTagSearch(event)
	{
		const currentPieceOfName = event.target.value.split(' ').pop();
		const enteredHashTags = TagSearcher.getHashTagNamesFromText(currentPieceOfName);

		const query = enteredHashTags.length ? enteredHashTags.pop() : '';

		if (query || event.data === '#')
		{
			this.setEpicSearchMode(false);
			this.setTagsSearchMode(true);

			this.emit('tagsSearchOpen', query);
		}
		else
		{
			this.emit('tagsSearchClose');
		}
	}

	submit()
	{
		this.disable();

		if (this.isEmpty())
		{
			this.removeYourself();
		}
		else
		{
			this.createTaskItem();
		}
	}

	onEpicSearch(event)
	{
		const inputNode = event.target;
		const enteredHashEpics = TagSearcher.getHashEpicNamesFromText(inputNode.value);

		const query = enteredHashEpics.length ? enteredHashEpics.pop() : '';

		if (
			this.selectedEpicLength > 0
			&& this.selectedEpicLength <= [...query].length
		)
		{
			return;
		}

		this.selectedEpicLength = 0;

		if (query || event.data === '@')
		{
			this.setTagsSearchMode(false);
			this.setEpicSearchMode(true);

			this.emit('epicSearchOpen', query);
		}
		else
		{
			this.emit('epicSearchClose');
		}
	}

	focus()
	{
		const input = this.getInputNode();

		const length = input.value.length;

		input.focus();
		input.setSelectionRange(length, length);
	}

	disable()
	{
		Dom.addClass(this.node, '--disabled');

		this.getInputNode().disabled = true;
	}

	unDisable()
	{
		Dom.removeClass(this.node, '--disabled');

		this.getInputNode().disabled = false;

		this.emit('unDisable');
	}

	isExists(): boolean
	{
		return !Type.isNull(this.node);
	}

	isEmpty(): boolean
	{
		if (!this.isExists())
		{
			return true;
		}

		const input = this.getInputNode();

		return input.value === '';
	}

	isTaskCreated(): boolean
	{
		return this.taskCreated;
	}

	setEpic(epic: ?EpicType)
	{
		this.epic = epic;
	}

	getNode(): HTMLElement
	{
		return this.node;
	}

	getNodeId(): String
	{
		return this.nodeId;
	}

	getInputNode(): HTMLTextAreaElement
	{
		return this.node.querySelector('textarea');
	}

	getEpic(): ?EpicType
	{
		return this.epic;
	}

	removeYourself()
	{
		Dom.remove(this.node);

		this.node = null;

		this.emit('remove');
	}

	setTagsSearchMode(value: boolean)
	{
		Dom.attr(this.getInputNode(), 'data-tag-disabled', value);
	}

	isTagsSearchMode(): boolean
	{
		return Dom.attr(this.getInputNode(), 'data-tag-disabled');
	}

	setEpicSearchMode(value: boolean)
	{
		Dom.attr(this.getInputNode(), 'data-epic-disabled', value);
	}

	isEpicSearchMode(): boolean
	{
		return Dom.attr(this.getInputNode(), 'data-epic-disabled');
	}

	setSelectedEpicLength(length: number)
	{
		this.selectedEpicLength = parseInt(length, 10);
	}

	createTaskItem()
	{
		const input = this.getInputNode();

		if (input.value)
		{
			this.emit('createTaskItem', input.value);

			this.taskCreated = true;

			input.value = '';
		}
	}
}
