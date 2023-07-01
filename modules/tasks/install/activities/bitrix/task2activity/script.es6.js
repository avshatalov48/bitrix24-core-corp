import { SelectorManager } from "bizproc.automation";
import { Reflection, Type, Tag, Dom, Text } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { TagSelector } from 'ui.entity-selector';
import { Footer} from "tasks.entity-selector";

const namespace = Reflection.namespace('BX.Tasks.Automation.Activity');

const TagType = {
	EXPRESSION: 'expression',
	NEW:  'new',
	SIMPLE: 'simple',
}

class Task2Activity
{
	#form: HTMLFormElement;
	#selectedGroupId: ?(number | string);
	#groupIdSelector: ?TagSelector;

	#selectedTags: Map<number, string> = new Map();
	#expressionTags: Set<string> = new Set();
	#newTags: Set<string> = new Set();
	#tagsSelector: ?TagSelector;

	#dependsOnSelector: ?TagSelector;
	#selectedDependentTasks: Set<string | number>;

	constructor(options: {
		userId: number,
		formName: string,
		selectedGroupId: ?(number | string),
		selectedTags: Array<{ type: string, id: ?number, name: string } | string>,
		dependsOn: Array<string | number>,
	})
	{
		this.#form = document.forms.namedItem(options.formName);
		if (!Type.isDomNode(this.#form['GROUP_ID']))
		{
			this.#form.appendChild(Tag.render`<select name="GROUP_ID" hidden="true"></select>`);
		}

		this.#selectedGroupId = options.selectedGroupId;

		options.selectedTags.forEach((tag) => {
			if (tag.type === TagType.EXPRESSION)
			{
				this.#expressionTags.add(tag.name);
			}
			else if (tag.type === TagType.SIMPLE && Type.isNumber(tag.id))
			{
				this.#selectedTags.set(tag.id, tag.name);
			}
			else if (tag.type === TagType.NEW)
			{
				this.#newTags.add(tag.name)
			}
		});

		this.#selectedDependentTasks = new Set(options.dependsOn);
	}

	render(): void
	{
		this.watchAllowTimeTracking();

		this.renderGroupId();
		this.renderTags();
		this.renderDependsOn();
	}

	watchAllowTimeTracking()
	{
		const allowTrackingElement = this.#form['ALLOW_TIME_TRACKING'][1];

		const timeEstimateElements = [this.#form['TIME_ESTIMATE_H'], this.#form['TIME_ESTIMATE_M']];
		const timeEstimateFieldElements = timeEstimateElements.map(element => element.parentElement.parentElement);

		if (allowTrackingElement)
		{
			const manageTimeEstimateFields = () => {
				if (allowTrackingElement.checked)
				{
					timeEstimateFieldElements.forEach((element) => {
						element.style.display = '';
					});
				}
				else
				{
					timeEstimateFieldElements.forEach((element) => {
						element.style.display = 'none';
					})
				}
			};

			manageTimeEstimateFields();
			allowTrackingElement.onchange = manageTimeEstimateFields;
		}
	}

	renderGroupId(): void
	{
		const groupIdElement = document.getElementById('bizproc-task2activity-group-id');
		if (groupIdElement)
		{
			const selector = SelectorManager.getSelectorByTarget(groupIdElement);

			if (selector)
			{
				selector.subscribe('Field:Selected', (event) => {
					const { field } = event.getData();

					this.#selectedGroupId = field.Expression;
					this.#getGroupIdSelector().addTag({
						id: field.Expression,
						title: field.Expression,
						entityId: 'project',
					});
					this.#recreateTagSelector();
				});
			}
			this.#getGroupIdSelector().renderTo(groupIdElement);
		}
	}

	#getGroupIdSelector(): TagSelector
	{
		if (Type.isNil(this.#groupIdSelector))
		{
			const self = this;

			this.#groupIdSelector = new TagSelector({
				multiple: false,
				events: {
					onTagAdd(event: BaseEvent)
					{
						const { tag } = event.getData();

						Dom.clean(self.#form['GROUP_ID']);
						self.#selectedGroupId = tag.id;
						self.#form['GROUP_ID'].append(new Option(tag.getTitle(), tag.getId(), true, true));
						self.#recreateTagSelector();
					},
					onTagRemove(event: BaseEvent)
					{
						Dom.clean(self.#form['GROUP_ID']);
						self.#selectedGroupId = undefined;
						self.#recreateTagSelector();
					},
				},
				dialogOptions: {
					preselectedItems: (
						Type.isNumber(this.#selectedGroupId)
							? [['project', this.#selectedGroupId]]
							: undefined
					),
					entities: [
						{
							id: 'project',
						}
					],
				},
			});

			if (Type.isString(this.#selectedGroupId))
			{
				this.#groupIdSelector.addTag({
					id: this.#selectedGroupId,
					entityId: 'project',
					title: this.#selectedGroupId,
				})
			}
		}

		return this.#groupIdSelector;
	}

	renderTags()
	{
		const tagsElement = document.getElementById('bizproc-task2activity-tags');
		if (tagsElement)
		{
			const selector = SelectorManager.getSelectorByTarget(tagsElement);
			if (selector)
			{
				selector.subscribe('Field:Selected', (event) => {
					const { field } = event.getData();

					this.#getTagsSelector().addTag({
						id: field.Expression,
						title: field.Expression,
						entityId: 'task-tag',
						customData: {
							type: TagType.EXPRESSION,
						},
					});
				});
			}
			this.#getTagsSelector().renderTo(tagsElement);
		}
	}

	async #recreateTagSelector(): Promise
	{
		await this.#loadTagsDialog();

		if (!Type.isNil(this.#tagsSelector))
		{
			// Due to the display of the dialog loader by timeout, have to wait for the timer
			await new Promise((resolve, reject) => setTimeout(400, resolve));
			this.#tagsSelector.getDialog().destroy();
			Dom.remove(this.#tagsSelector.getOuterContainer());
			this.#tagsSelector = null;
		}
		this.#form
			.querySelectorAll(`input[name="TAG_NAMES[]"]`)
			.forEach(element => Dom.remove(element));

		this.renderTags();
	}

	async #loadTagsDialog(): Promise
	{
		if (this.#tagsSelector.getDialog().isLoading())
		{
			await this.#fetchTags();
		}
	}

	#getTagsSelector(): TagSelector
	{
		if (Type.isNil(this.#tagsSelector))
		{
			const self = this;
			const selectedGroupId = !Type.isString(this.#selectedGroupId) ? this.#selectedGroupId : undefined;

			this.#tagsSelector = new TagSelector({
				multiple: true,
				events: {
					onTagAdd(event: BaseEvent)
					{
						const {tag} = event.getData();
						const type = tag.getCustomData().get('type') ?? TagType.SIMPLE;

						self.#addTag(type, tag.getId(), tag.getTitle());
					},
					onTagRemove(event: BaseEvent)
					{
						const {tag} = event.getData();

						self.#removeTag(tag.getId());
					},
				},
				dialogOptions: {
					width: 400,
					height: 300,
					dropdownMode: true,
					enableSearch: true,
					compactView: true,
					searchOptions: {
						allowCreateItem: false,
					},
					footer: Footer,
					footerOptions: {
						groupId: selectedGroupId,
					},
					offsetTop: 12,
					entities: [
						{
							id: 'task-tag',
							options: {
								groupId: selectedGroupId,
							}
						}
					],
				}
			});

			this.#fillTagsSelector();
		}

		return this.#tagsSelector;
	}

	#fillTagsSelector()
	{
		this.#updateSavedTags().then(({newTags, newSelectedTags}) => {
			const expressionTags = this.#expressionTags;
			this.#expressionTags = new Set();

			for (const [tagId, tagName] of newSelectedTags.entries())
			{
				this.#tagsSelector.addTag({
					id: tagId,
					title: tagName,
					entityId: 'task-tag',
					customData: {
						type: TagType.SIMPLE,
					},
				});
			}
			for (const tagName of newTags.values())
			{
				this.#tagsSelector.addTag({
					id: String(Math.random()),
					title: tagName,
					entityId: 'task-tag',
					customData: {
						type: TagType.NEW,
					},
				});
			}
			for (const tagName of expressionTags.values())
			{
				this.#tagsSelector.addTag({
					id: tagName,
					title: tagName,
					entityId: 'task-tag',
					customData: {
						type: TagType.EXPRESSION,
					},
				});
			}

			// const preselectedItems = Array.from(this.#selectedTags.keys()).map(tagId => ['task-tag', tagId]);
			// this.#tagsSelector.getDialog().setPreselectedItems(preselectedItems);
		});
	}

	async #updateSavedTags(): Promise<{ newTags: Set<string>, newSelectedTags: Map<number, string> }>
	{
		await this.#fetchTags();

		const knownTags = new Set();
		const knownTagNames = new Map();
		for (const tag of this.#tagsSelector.getDialog().getItems())
		{
			knownTags.add(tag.getId());
			knownTagNames.set(tag.getTitle(), tag.getId());
		}

		const newSelectedTags = new Map();
		const newTags = new Set();
		for (const [tagId, tagName] of this.#selectedTags.entries())
		{
			if (knownTags.has(tagId))
			{
				newSelectedTags.set(tagId, tagName);
			}
			else
			{
				newTags.add(tagName);
			}
		}
		for (const tagName of this.#newTags.values())
		{
			if (knownTagNames.has(tagName))
			{
				newSelectedTags.set(knownTagNames.get(tagName), tagName);
			}
			else
			{
				newTags.add(tagName);
			}
		}

		return {
			newSelectedTags,
			newTags,
		};
	}

	async #fetchTags(): Promise
	{
		const tagsDialog = this.#tagsSelector.getDialog();
		if (!tagsDialog.isLoaded())
		{
			await new Promise((resolve, reject) => {
				const onLoad = () => {
					tagsDialog.unsubscribe('onLoadError', onLoadError);
					resolve();
				};
				const onLoadError = () => {
					tagsDialog.unsubscribe('onLoad', onLoad);
					reject();
				};

				tagsDialog.subscribeOnce('onLoad', onLoad);
				tagsDialog.subscribeOnce('onLoadError', onLoadError);

				tagsDialog.load();
			});
		}
	}

	#addTag(type: string, id: ?(number | string), name: string)
	{
		if (type === TagType.SIMPLE)
		{
			this.#selectedTags.set(id, name);
		}
		else if (type === TagType.EXPRESSION)
		{
			this.#expressionTags.add(name);
		}
		else if (type === TagType.NEW)
		{
			this.#newTags.add(name);
		}
		else
		{
			return;
		}

		this.#form.appendChild(Tag.render`
			<input name="TAG_NAMES[]" value="${Text.encode(name)}" hidden/>
		`);
	}

	#removeTag(id: string | number)
	{
		let name = null;
		if (this.#expressionTags.has(id))
		{
			name = id;
			this.#expressionTags.delete(id);
		}
		else if (this.#selectedTags.has(id))
		{
			name = this.#selectedTags.get(id);
			this.#selectedTags.delete(id);
		}
		else if (this.#newTags.has(id))
		{
			name = id;
			this.#newTags.delete(id);
		}

		const tagValueElement= this.#form.querySelector(
			`input[name="TAG_NAMES[]"][value="${Text.encode(name)}"]`
		);
		if (tagValueElement)
		{
			Dom.remove(tagValueElement);
		}
	}

	renderDependsOn()
	{
		const dependsOnElement = document.getElementById('bizproc-task2activity-depends-on');
		if (dependsOnElement)
		{
			const selector = SelectorManager.getSelectorByTarget(dependsOnElement);
			if (selector)
			{
				selector.subscribe('Field:Selected', (event) => {
					const { field } = event.getData();

					this.#getDependsOnSelector().addTag({
						id: field.Expression,
						title: field.Expression,
						entityId: 'task',
					});
				});
			}
			this.#getDependsOnSelector().renderTo(dependsOnElement);
		}
	}

	#getDependsOnSelector(): TagSelector
	{
		if (Type.isNil(this.#dependsOnSelector))
		{
			const self = this;

			this.#dependsOnSelector = new TagSelector({
				multiple: true,
				events: {
					onTagAdd(event: BaseEvent)
					{
						const { tag } = event.getData();

						self.#addDependsOnTaskId(tag.getId());
					},
					onTagRemove(event: BaseEvent)
					{
						const { tag } = event.getData();

						self.#removeDependsOnTaskId(tag.getId());
					}
				},
				dialogOptions: {
					width: 400,
					height: 300,
					dropdownMode: true,
					compactView: true,
					enableSearch: true,
					searchOptions: {
						allowCreateItem: false,
					},
					offsetTop: 12,
					entities: [
						{
							id: 'task',
						},
					],
					preselectedItems: (
						Array.from(this.#selectedDependentTasks)
							.filter(taskId => Type.isNumber(taskId))
							.map(taskId => ['task', taskId])
					)
				},
			});

			for (const taskId of this.#selectedDependentTasks.values())
			{
				if (Type.isString(taskId))
				{
					this.#dependsOnSelector.addTag({
						id: taskId,
						title: taskId,
						entityId: 'task',
					});
				}
			}
		}

		return this.#dependsOnSelector;
	}

	#addDependsOnTaskId(id: string | number)
	{
		this.#selectedDependentTasks.add(id);

		this.#form.appendChild(Tag.render`
			<input name="DEPENDS_ON[]" value="${Text.encode(id)}" hidden/>
		`);
	}

	#removeDependsOnTaskId(id: string | number)
	{
		this.#selectedDependentTasks.delete(id);

		const taskIdElement = this.#form.querySelector(
			`input[name="DEPENDS_ON[]"][value="${Text.encode(id)}"]`
		);

		if (taskIdElement)
		{
			Dom.remove(taskIdElement);
		}
	}
}

namespace.Task2Activity = Task2Activity;