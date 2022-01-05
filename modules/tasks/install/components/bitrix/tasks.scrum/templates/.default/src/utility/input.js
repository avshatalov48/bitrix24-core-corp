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
	}

	setEntity(entity: Entity)
	{
		this.entity = entity;
	}

	getEntity(): ?Entity
	{
		return this.entity;
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
			<div id="${Text.encode(this.nodeId)}" class="tasks-scrum__item --add-block">
				<textarea
					placeholder="${Loc.getMessage('TASKS_SCRUM_TASK_ADD_INPUT_TASK_PLACEHOLDER')}"
					class="tasks-scrum__item--textarea"
				>${Text.encode(this.value)}</textarea>
				<div class="tasks-scrum__item--textarea-help">
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
		if (event.isComposing || event.key === 'Escape' || event.key === 'Enter')
		{
			if (!this.isTagsSearchMode() && !this.isEpicSearchMode())
			{
				this.getInputNode().blur();

				event.stopImmediatePropagation();
			}

			if (event.key === 'Enter')
			{
				this.emit('onEnter', { event });

				if ((Browser.isMac() && event.metaKey) || event.ctrlKey)
				{
					this.emit('onMetaEnter', { event });
				}
			}
		}
	}

	onBlur()
	{
		if (this.isTagsSearchMode() || this.isEpicSearchMode())
		{
			return;
		}

		this.disable();

		const input = this.getInputNode();

		if (input.value === '')
		{
			this.removeYourself();
		}
		else
		{
			this.createTaskItem();
		}
	}

	onTagSearch(event)
	{
		const inputNode = event.target;
		const enteredHashTags = TagSearcher.getHashTagNamesFromText(inputNode.value);

		if (event.data === '#')
		{
			this.setEpicSearchMode(false);
			this.setTagsSearchMode(true);
		}
		if (this.isTagsSearchMode())
		{
			const enteredHashTagName = enteredHashTags.pop();

			this.emit('tagsSearchOpen', Type.isUndefined(enteredHashTagName) ? '' : enteredHashTagName);
		}
		else
		{
			this.emit('tagsSearchClose');
		}
	}

	onEpicSearch(event)
	{
		const inputNode = event.target;
		const enteredHashEpics = TagSearcher.getHashEpicNamesFromText(inputNode.value);
		if (event.data === '@')
		{
			this.setTagsSearchMode(false);
			this.setEpicSearchMode(true);
		}
		if (this.isEpicSearchMode())
		{
			const enteredHashTagName = enteredHashEpics.pop();
			this.emit('epicSearchOpen', Type.isUndefined(enteredHashTagName) ? '' : enteredHashTagName);
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

	createTaskItem()
	{
		if (!this.isTagsSearchMode() && !this.isEpicSearchMode())
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
}
