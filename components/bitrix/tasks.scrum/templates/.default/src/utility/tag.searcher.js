import { Loc, Type, Text, Dom } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

import {Dialog, ItemOptions} from 'ui.entity-selector';

import { Filter } from '../service/filter';

import { RequestSender } from './request.sender';
import { Input } from './input';

import type { EpicType } from '../item/task/epic';

import {MessageBox, MessageBoxButtons} from 'ui.dialogs.messagebox';

type Params = {
	requestSender: RequestSender,
	filter: Filter,
	groupId: number,
	tagsAreConverting: number,
}

export class TagSearcher extends EventEmitter
{
	static tagRegExp = '#([^\\s,\\[\\]<>]+)';
	static epicRegExp = '@[^#@](?:[^#@]*[^\s#@])?';

	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.TagSearcher');

		this.requestSender = params.requestSender;
		this.filterService = params.filter;
		this.groupId = parseInt(params.groupId, 10);
		this.tagsAreConverting = params.tagsAreConverting;

		this.messageViewed = false;
	}

	addEpicToSearcher(epic: EpicType, selected = false)
	{
		const epicDialogItem = this.getEpicDialogItem(epic);

		if (selected)
		{
			epicDialogItem.selected = true;
			epicDialogItem.sort = 1;
		}

		if (this.epicDialog)
		{
			this.epicDialog.addItem(epicDialogItem);
		}

		if (this.epicSearchDialog)
		{
			this.epicSearchDialog.addItem(epicDialogItem);
		}
	}

	removeEpicFromSearcher(epic: EpicType)
	{
		if (this.epicDialog)
		{
			this.epicDialog.removeItem(this.getEpicDialogItem(epic));
		}

		if (this.epicSearchDialog)
		{
			this.epicSearchDialog.removeItem(this.getEpicDialogItem(epic));
		}
	}

	getEpicDialogItem(epic: EpicType): ItemOptions
	{
		return {
			id: epic.id,
			entityId: 'epic-selector',
			title: epic.name,
			tabs: 'recents',
			avatarOptions: {
				bgColor: epic.color,
				bgImage: 'none'
			}
		};
	}

	// widget on scrum (ex task)
	showTagsDialog(item: Item, targetNode: HTMLElement): Dialog
	{
		if (this.tagsAreConverting)
		{
			this.showConvertingMessage();
			return;
		}
		let choiceWasMade = false;
		const groupId = this.groupId;
		const statusSuccess = { status: false };
		this.tagDialog = new Dialog({
			id: item.getId().toString(),
			targetNode: targetNode,
			width: 400,
			height: 300,
			multiple: true,
			dropdownMode: true,
			enableSearch: true,
			compactView: true,
			searchOptions: {
				allowCreateItem: false,
			},
			footer: BX.Tasks.EntitySelector.Footer,
			footerOptions: {
				userId: this.userId,
				groupId: groupId,
			},
			offsetTop: 12,
			entities: [
				{
					id: 'task-tag',
					options: {
						groupId: this.groupId,
						taskId: item.getSourceId(),
					},
				},
			],
			clearUnavailableItems: true,
			events: {
				'onLoad': (baseEvent: BaseEvent) => {
					Dom.style(baseEvent.getTarget().getFooterContainer(), 'zIndex', 1);
					this.onShowTaskEditCallback(baseEvent, statusSuccess, item);
					this.hideDialogLabel(baseEvent.getTarget());
				},
				'Item:onSelect': (event) => {
					choiceWasMade = true;
					const selectedItem = event.getData().item;
					const dialog = event.getTarget();
					selectedItem.setSort(1);
					dialog.getTab('all').getRootNode().addItem(selectedItem);
					const tag = selectedItem.getTitle();
					this.emit('attachTagToTask', tag);
				},
				'Item:onDeselect': (event) => {
					choiceWasMade = true;
					const deselectedItem = event.getData().item;
					const tag = deselectedItem.getTitle();
					this.emit('deAttachTagToTask', tag);
				},
				'onSearch': event => {
					this.onSearchCallback(event);
				},
			},
			tagSelectorOptions: {
				events: {
					onInput: (event) => {
						const selector = event.getData().selector;
						if (selector)
						{
							const dialog = selector.getDialog();
							const label = dialog.getContainer().querySelector('.ui-selector-footer-conjunction');
							label.textContent = Text.encode(selector.getTextBoxValue());
						}
					},
				},
			},
		});

		this.tagDialog.subscribe('onHide', () => {
			if (choiceWasMade)
			{
				this.emit('hideTagDialog');
			}
			this.tagDialog = null;
		});

		this.tagDialog.show();
	}

	showEpicDialog(item: Item, targetNode: HTMLElement): Dialog
	{
		const currentEpic = item.getEpic().getValue();

		const preselectedItems = [];
		if (currentEpic)
		{
			preselectedItems.push(['epic-selector' , currentEpic.id]);
		}

		let choiceWasMade = false;

		this.epicDialog = new Dialog({
			id: item.getId(),
			targetNode: targetNode,
			width: 400,
			height: 300,
			multiple: false,
			dropdownMode: true,
			enableSearch: true,
			offsetTop: 12,
			context: 'epic-selector-' + this.groupId,
			preselectedItems: preselectedItems,
			entities: [
				{
					id: 'epic-selector',
					options: {
						groupId: this.groupId
					},
					dynamicLoad: true,
					dynamicSearch: true
				}
			],
			searchOptions: {
				allowCreateItem: true,
				footerOptions: {
					label: Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_EPIC_ADD'),
				},
			},
			hideOnSelect: false,
			hideOnDeselect: false,
			events: {
				'Search:onItemCreateAsync': (event) => {
					return new Promise((resolve) => {
						const { searchQuery } = event.getData();
						const epicName = searchQuery.getQuery();
						this.createEpic(epicName)
							.then((epic: EpicType) => {
								this.addEpicToSearcher(epic, true);
								this.filterService.addItemToListTypeField('EPIC', {
									NAME: epic.name.trim(),
									VALUE: String(epic.id),
								});
								this.emit('updateItemEpic', epic);
								choiceWasMade = true;
								this.epicDialog.hide();
								resolve();
							})
						;
					});
				},
				'Item:onSelect': (event) => {
					choiceWasMade = true;
					const selectedItem = event.getData().item;
					this.getEpic(selectedItem.getId())
						.then((epic: EpicType) => {
							this.emit('updateItemEpic', epic);
							this.epicDialog.hide();
						})
					;
				},
				'Item:onDeselect': () => {
					setTimeout(() => {
						choiceWasMade = true;
						if (this.epicDialog.getSelectedItems().length === 0)
						{
							this.emit('updateItemEpic', null);
							this.epicDialog.hide();
						}
					}, 50);
				},
			},
			tagSelectorOptions: {
				textBoxWidth: 340,
				placeholder: Loc.getMessage('TASKS_SCRUM_ITEM_EPIC_SEARCHER_PLACEHOLDER'),
			},
		});

		this.epicDialog.subscribe('onHide', () => {
			if (choiceWasMade)
			{
				this.emit('hideEpicDialog');
			}
			this.epicDialog = null;
		});

		this.epicDialog.show();
	}

	isEpicDialogShown(): boolean
	{
		return this.epicDialog && this.epicDialog.isOpen();
	}

	hasActionPanelDialog(): boolean
	{
		return (this.epicDialog || this.tagDialog);
	}

	closeActionPanelDialogs()
	{
		if (this.epicDialog)
		{
			this.epicDialog.hide();
		}

		if (this.tagDialog)
		{
			this.tagDialog.hide();
		}
	}

	//widget on scrum (new task)
	showTagsSearchDialog(inputObject: Input, enteredQuery: string): Dialog
	{
		if (this.tagsAreConverting)
		{
			inputObject.setTagsSearchMode(false);
			return;
		}
		const input = inputObject.getInputNode();

		const groupId = this.groupId;

		if (
			this.tagSearchDialog
			&& this.tagSearchDialog.getId() !== inputObject.getNodeId()
		)
		{
			this.tagSearchDialog = null;
		}

		if (!this.tagSearchDialog)
		{
			this.tagSearchDialog = new Dialog({
				id: inputObject.getNodeId(),
				targetNode: inputObject.getNode(),
				width: inputObject.getNode().offsetWidth,
				height: 210,
				multiple: false,
				dropdownMode: true,
				compactView: true,
				entities: [
					{
						id: 'task-tag',
						options: {
							groupId: this.groupId,
						},
					},
				],
				tabOptions: {
					visible: false,
				},
				searchOptions: {
					allowCreateItem: false,
				},
				footer: BX.Tasks.EntitySelector.Footer,
				footerOptions: {
					userId: this.userId,
					groupId: groupId,
				},
				clearUnavailableItems: true,
				events: {
					'onLoad': (event) => {
						Dom.style(event.getTarget().getFooterContainer(), 'zIndex', 1);
						this.onLoadTaskQuickCreateCallback(event, inputObject);
					},
					'onSearch': event => {
						this.onSearchCallback(event);
					},
					'Item:onSelect': (event) => {
						let newValue = '';
						const regex = new RegExp('\\s|#$', 'm');
						const currentPiece = input.value.split(regex).pop();
						input.value.split(regex)
							.forEach((pieceOfValue: string) => {
								if (currentPiece !== pieceOfValue)
								{
									newValue = newValue + ' ' + pieceOfValue;
								}
							})
						;
						const selectedItem = event.getData().item;
						const dialog = event.getTarget();
						selectedItem.setSort(1);
						dialog.getTab('all').getRootNode().addItem(selectedItem);
						newValue = newValue + ' #' + selectedItem.getTitle();
						input.value = newValue.trim();
						input.focus();
						selectedItem.deselect();
					},
				},
			});

			this.tagSearchDialog.subscribe('onHide', () => {
				inputObject.setTagsSearchMode(false);

				this.tagSearchDialog = null;
			});

			inputObject.subscribe('onEnter', event => {
				if (Type.isNil(this.tagSearchDialog))
				{
					return;
				}
				const searchTab = this.tagSearchDialog.getSearchTab();
				if (Type.isNil(searchTab))
				{
					return;
				}
				if (searchTab.isEmptyResult())
				{
					this.tagSearchDialog.hide();
					input.focus();
				}
			});
		}

		inputObject.setTagsSearchMode(true);
		this.tagSearchDialog.show();
		this.tagSearchDialog.search(enteredQuery);
	}

	closeTagsSearchDialog()
	{
		if (this.tagSearchDialog)
		{
			this.tagSearchDialog.hide();
		}
	}

	showEpicSearchDialog(inputObject: Input, enteredQuery: string): Dialog
	{
		const input = inputObject.getInputNode();

		this.epicEnteredQuery = enteredQuery;

		if (
			this.epicSearchDialog
			&& this.epicSearchDialog.getId() !== inputObject.getNodeId()
		)
		{
			this.epicSearchDialog = null;
		}

		if (!this.epicSearchDialog)
		{
			this.epicSearchDialog = new Dialog({
				id: inputObject.getNodeId(),
				targetNode: inputObject.getNode(),
				width: inputObject.getNode().offsetWidth,
				height: 210,
				multiple: false,
				dropdownMode: true,
				searchOptions: {
					allowCreateItem: true,
					footerOptions: {
						label: Loc.getMessage('TASKS_SCRUM_SEARCHER_ACTIONS_EPIC_ADD'),
					},
				},
				context: 'epic-selector-' + this.groupId,
				entities: [
					{
						id: 'epic-selector',
						options: {
							groupId: this.groupId
						},
						dynamicLoad: true,
						dynamicSearch: true
					}
				],
				events: {
					'Search:onItemCreateAsync': (event) => {
						return new Promise((resolve) => {
							const { searchQuery } = event.getData();
							const epicName = searchQuery.getQuery();

							inputObject.disable();

							inputObject.setSelectedEpicLength([...epicName].length);
							input.focus();

							this.createEpic(epicName)
								.then((epic: ?EpicType) => {

									this.addEpicToSearcher(epic);

									this.filterService.addItemToListTypeField('EPIC', {
										NAME: epic.name.trim(),
										VALUE: String(epic.id),
									});

									inputObject.unDisable();
									input.focus();
									inputObject.setEpic(epic);

									resolve();
								})
							;

							this.epicSearchDialog.hide();
						});
					},
					'Item:onSelect': (event: BaseEvent) => {
						const selectedItem = event.getData().item;

						this.getEpic(selectedItem.getId())
							.then((epic: EpicType) => {
								inputObject.setEpic(epic);
							})
						;

						const epicName = selectedItem.getTitle();
						input.value = input.value.replace('@' + this.epicEnteredQuery, '').replace('@', '');
						input.value = input.value + '@' + epicName;
						inputObject.setSelectedEpicLength([...epicName].length);
						input.focus();

						selectedItem.deselect();
					},
				},
			});

			this.epicSearchDialog.subscribe('onHide', () => {
				inputObject.setEpicSearchMode(false);

				this.epicSearchDialog = null;
			});

			inputObject.subscribe('onMetaEnter', () => {
				if (Type.isNil(this.epicSearchDialog))
				{
					return;
				}
				const searchTab = this.epicSearchDialog.getSearchTab();
				if (Type.isNil(searchTab))
				{
					return;
				}
				const lastSearchQuery = searchTab.getLastSearchQuery();
				if (Type.isNil(lastSearchQuery))
				{
					return;
				}
				const epicName = lastSearchQuery.getQuery();

				inputObject.disable();

				inputObject.setSelectedEpicLength([...epicName].length);
				input.focus();

				this.createEpic(epicName)
					.then((epic: ?EpicType) => {

						this.addEpicToSearcher(epic);

						this.filterService.addItemToListTypeField('EPIC', {
							NAME: epic.name.trim(),
							VALUE: String(epic.id),
						});

						inputObject.unDisable();
						input.focus();
						inputObject.setEpic(epic);
					})
				;

				this.epicSearchDialog.hide();
				this.epicSearchDialog = null;
			});
		}

		inputObject.setEpicSearchMode(true);

		this.epicSearchDialog.show();
		this.epicSearchDialog.search(this.epicEnteredQuery);
	}

	closeEpicSearchDialog()
	{
		if (this.epicSearchDialog)
		{
			this.epicSearchDialog.hide();
		}
	}

	createEpic(epicName: string): Promise
	{
		return this.requestSender.createEpic(
				{
					groupId: this.groupId,
					name: epicName,
				},
			)
			.then((response) => {
				return response.data;
			})
		;
	}

	getEpic(epicId: number): Promise
	{
		return this.requestSender.getEpic(
			{
				groupId: this.groupId,
				epicId: epicId
			},
		)
			.then((response) => {
				return response.data;
			})
		;
	}

	static getHashTagNamesFromText(inputText: string): Array
	{
		const regex = new RegExp(TagSearcher.tagRegExp, 'g');

		const matches = [];
		let match;
		while (match = regex.exec(inputText))
		{
			matches.push(match[0].substring(1));
		}

		return matches;
	}

	static getHashEpicNamesFromText(inputText: string): Array
	{
		const regex = new RegExp(TagSearcher.epicRegExp, 'g');

		const matches = [];
		let match;
		while (match = regex.exec(inputText))
		{
			matches.push(match[0].substring(1));
		}

		return matches;
	}

	hideDialogLabel(dialog: Dialog)
	{
		//todo tmp, remove after update selector
		dialog
			.getContainer()
			.querySelectorAll('.ui-selector-tab-label')
			.forEach((label: HTMLElement) => {
				Dom.addClass(label, 'ui-selector-tab-label-hidden');
			})
		;
	}

	onSearchCallback(event)
	{
		const dialog = event.getTarget();
		const query = event.getData().query;
		if (query.trim() !== '')
		{
			dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new').hidden = false;
			dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-conjunction').hidden = false;
		}
		else
		{
			dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new').hidden = true;
			dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-conjunction').hidden = true;
		}
	}

	onLoadTaskQuickCreateCallback(event, inputObject)
	{
		const dialog = event.getTarget();
		this.hideDialogLabel(dialog);
		const input = inputObject.getInputNode();

		inputObject.subscribe('onMetaEnter', (event) => {
			const regex = new RegExp('\\s|#$', 'm');
			const currentPiece = input.value.split(regex).pop();
			const tagName = currentPiece.replace('#', '').trim();
			const data = {
				newTag: tagName,
				groupId: this.groupId,
				taskId: 0,
				action: 'add',
			};
			if (tagName === '')
			{
				return;
			}
			this.requestSender.addTag(data).then(response => {
				if (!response.data.success)
				{
					const alertClass = 'tasks-scrum-tag-already-exists-alert';
					this.showAlert(dialog.getId(), alertClass, response.data.error);
					setTimeout(() => {
							this.removeAlert(dialog, alertClass);
						},
						2000);
					return;
				}
				dialog.search(tagName);
			});
		});

		dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new')
			.addEventListener('click', event => {
				const regex = new RegExp('\\s|#$', 'm');
				const currentPiece = input.value.split(regex).pop();
				const tagName = currentPiece.replace('#', '').trim();
				const data = {
					newTag: tagName,
					groupId: this.groupId,
					taskId: 0,
					action: 'add',
				};
				if (tagName === '')
				{
					return;
				}
				this.requestSender.addTag(data).then(response => {
					if (!response.data.success)
					{
						const alertClass = 'tasks-scrum-tag-already-exists-alert';
						this.showAlert(dialog.getId(), alertClass, response.data.error);
						setTimeout(() => {
								this.removeAlert(dialog, alertClass);
							},
							2000);
						return;
					}
					dialog.search(tagName);
				});
			});
	}

	onShowTaskEditCallback(event, statusSuccess, item)
	{
		const dialog = event.getTarget();
		const events = ['click', 'keydown'];

		const handler = event => {
			if (event.type === 'keydown')
			{
				if (!((event.ctrlKey || event.metaKey) && event.keyCode === 13))
				{
					return;
				}
			}

			const tag = dialog.getTagSelectorQuery();
			if (tag.trim() === '')
			{
				return;
			}
			const data = {
				tag: tag,
				itemIds: [item.getId()],
				groupId: this.groupId,
				action: 'add',
			};
			this.requestSender.updateTaskTags(data).then(response => {
				if (response.data.success)
				{
					statusSuccess.status = true;
					const item = dialog.addItem({
						id: tag,
						entityId: 'task-tag',
						title: tag,
						sort: 1,
						badges: [
							{
								title: response.data.group,
							},
						],
					});

					dialog.getTab('all').getRootNode().addItem(item);
					item.select();
					dialog.getTagSelector().clearTextBox();
					dialog.focusSearch();
					dialog.selectFirstTab();

				}
				else
				{
					const alertClass = 'tasks-scrum-tag-already-exists-alert';
					this.showAlert(dialog.getId(), alertClass, response.data.error);
					setTimeout(() => {
							this.removeAlert(dialog, alertClass);
						},
						3000);
				}
			});
		};

		events.forEach(function(ev) {
			if (ev === 'click')
			{
				dialog.getFooterContainer().querySelector('#tags-widget-custom-footer-add-new')
					.addEventListener(ev, handler);
			}
			else
			{
				dialog.getContainer().addEventListener(ev, handler);
			}
		});
	}

	showAlert(dialogId: string, className: string, error: string, messageType = 'ui-alert ui-alert-xs ui-alert-danger')
	{
		const dialog = BX.UI.EntitySelector.Dialog.getById(dialogId);

		if (dialog.getContainer().querySelector(`div.${className}`))
		{
			return;
		}

		const alert = document.createElement('div');
		alert.className = className;
		alert.innerHTML = `
						<div class='${messageType}'  
							<span class='ui-alert-message'>
								${error}
							</span> 
						</div>
					`;
		dialog.getFooterContainer().before(alert);
	};

	removeAlert(dialog: Dialog, className: string)
	{
		const notification = dialog.getContainer().querySelector(`div.${className}`);
		if (notification)
		{
			notification.remove();
		}
	}

	showConvertingMessage()
	{
		const message = new MessageBox({
			title: Loc.getMessage('TASKS_SCRUM_TAG_SELECTOR_TAGS_ARE_CONVERTING_TITLE'),
			message: Loc.getMessage('TASKS_SCRUM_TAG_SELECTOR_TAGS_ARE_CONVERTING_TEXT'),
			buttons: MessageBoxButtons.OK,
			okCaption: Loc.getMessage('TASKS_SCRUM_TAG_SELECTOR_TAGS_ARE_CONVERTING_COME_BACK_LATER'),
			onOk: function(){
				message.close();
			}
		});
		message.show();
	}
}