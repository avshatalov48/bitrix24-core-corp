import './css/check-list-item.css';

import {Dom, Event, Loc, Tag, Text} from 'main.core';

import {CompositeTreeItem} from './composite-tree-item';
import {CheckListItemFields} from './check-list-item-fields';

class CheckListItem extends CompositeTreeItem
{
	static makeDangerElement(element)
	{
		const dangerClass = 'ui-ctl-danger';

		if (!Dom.hasClass(element, dangerClass))
		{
			Dom.addClass(element, dangerClass);
		}
	}

	static updateParents(oldParent, newParent)
	{
		if (oldParent !== newParent)
		{
			oldParent.updateCounts();
			newParent.updateCounts();

			oldParent.updateProgress();
			newParent.updateProgress();

			oldParent.updateIndexes();
			newParent.updateIndexes();
		}
		else
		{
			newParent.updateIndexes();
		}
	}

	static getProgressText(completed, total)
	{
		const replaces = {
			'#total#': total,
			'#completed#': completed,
		};
		let progressText = Loc.getMessage('TASKS_CHECKLIST_PROGRESS_BAR_PROGRESS_TEXT');

		Object.keys(replaces).forEach((search) => {
			progressText = progressText.replace(search, replaces[search]);
		});

		return progressText;
	}

	static getFileExtension(ext)
	{
		let fileExtension = ext;

		switch (ext)
		{
			case 'mp4':
			case 'mkv':
			case 'mpeg':
			case 'avi':
			case '3gp':
			case 'flv':
			case 'm4v':
			case 'ogg':
			case 'swf':
			case 'wmv':
				fileExtension = 'mov';
				break;

			case 'txt':
				fileExtension = 'txt';
				break;

			case 'doc':
			case 'docx':
				fileExtension = 'doc';
				break;

			case 'xls':
			case 'xlsx':
				fileExtension = 'xls';
				break;

			case 'php':
				fileExtension = 'php';
				break;

			case 'pdf':
				fileExtension = 'pdf';
				break;

			case 'ppt':
			case 'pptx':
				fileExtension = 'ppt';
				break;

			case 'rar':
				fileExtension = 'rar';
				break;

			case 'zip':
				fileExtension = 'zip';
				break;

			case 'set':
				fileExtension = 'set';
				break;

			case 'mov':
				fileExtension = 'mov';
				break;

			case 'img':
			case 'jpg':
			case 'jpeg':
			case 'gif':
				fileExtension = 'img';
				break;

			default:
				fileExtension = 'empty';
				break;
		}

		return fileExtension;
	}

	static getInputSelection(input)
	{
		let start = 0;
		let end = 0;
		let normalizedValue;
		let range;
		let textInputRange;
		let len;
		let endRange;

		if (typeof input.selectionStart === 'number' && typeof input.selectionEnd === 'number')
		{
			start = input.selectionStart;
			end = input.selectionEnd;
		}
		else
		{
			range = document.selection.createRange();

			if (range && range.parentElement() === input)
			{
				len = input.value.length;
				normalizedValue = input.value.replace(/\r\n/g, '\n');

				// Create a working TextRange that lives only in the input
				textInputRange = input.createTextRange();
				textInputRange.moveToBookmark(range.getBookmark());

				// Check if the start and end of the selection are at the very end
				// of the input, since moveStart/moveEnd doesn't return what we want
				// in those cases
				endRange = input.createTextRange();
				endRange.collapse(false);

				if (textInputRange.compareEndPoints('StartToEnd', endRange) > -1)
				{
					start = len;
					end = len;
				}
				else
				{
					start = -textInputRange.moveStart('character', -len);
					start += normalizedValue.slice(0, start).split('\n').length - 1;

					if (textInputRange.compareEndPoints('EndToEnd', endRange) > -1)
					{
						end = len;
					}
					else
					{
						end = -textInputRange.moveEnd('character', -len);
						end += normalizedValue.slice(0, end).split('\n').length - 1;
					}
				}
			}
		}

		return {start, end};
	}

	static setDefaultStyles(layout, action = 'add')
	{
		if (action === 'add')
		{
			layout.style.overflow = 'hidden';
			layout.style.height = 0;
			layout.style.opacity = 0;

			Dom.addClass(layout, 'checklist-item-show');
		}
		else if (action === 'delete')
		{
			layout.style.overflow = 'hidden';
			layout.style.height = `${layout.scrollHeight}px`;
			layout.style.opacity = 1;

			Dom.addClass(layout, 'checklist-item-hide');
		}
	}

	static getDefaultDisplayTitle(title)
	{
		let defaultDisplayTitle = title;

		if (title.indexOf('BX_CHECKLIST') === 0)
		{
			if (title.length === 12)
			{
				defaultDisplayTitle = Loc.getMessage('TASKS_CHECKLIST_DEFAULT_DISPLAY_TITLE');
			}
			else if (title.match(/BX_CHECKLIST_\d+$/))
			{
				defaultDisplayTitle = title.replace('BX_CHECKLIST_', `${Loc.getMessage('TASKS_CHECKLIST_DEFAULT_DISPLAY_TITLE')} `);
			}
		}

		return defaultDisplayTitle;
	}

	constructor(fields = {})
	{
		const {action} = fields;

		super();
		this.fields = new CheckListItemFields(fields);
		this.action = {
			canUpdate: (action && 'MODIFY' in action) ? action.MODIFY : true,
			canRemove: (action && 'REMOVE' in action) ? action.REMOVE : true,
			canToggle: (action && 'TOGGLE' in action) ? action.TOGGLE : true,
			canDrag: (action && 'DRAG' in action) ? action.DRAG : true,
		};

		this.input = null;
		this.panel = null;
		this.progress = null;
		this.filesLoaderPopup = null;
		this.filesLoaderProgressBars = new Map();
		this.updateMode = false;
	}

	add(item, position = null)
	{
		super.add(item, position);
		item.optionManager = this.optionManager;
	}

	isTaskRoot()
	{
		return this.getNodeId() === 0 && this.getParent() === null;
	}

	isCheckList()
	{
		return !this.isTaskRoot() && this.getParent().isTaskRoot();
	}

	getCheckList()
	{
		let parent = this;

		while (!parent.getParent().isTaskRoot())
		{
			parent = parent.getParent();
		}

		return parent;
	}

	countCompletedCount(recursively = false)
	{
		let completedCount = 0;

		this.getDescendants().forEach((descendant) => {
			if (descendant.fields.getIsComplete())
			{
				completedCount += 1;
			}

			if (recursively)
			{
				completedCount += descendant.countCompletedCount(recursively);
			}
		});

		return completedCount;
	}

	countTotalCount(recursively = false)
	{
		let totalCount = 0;

		if (!recursively)
		{
			totalCount = this.getDescendantsCount();
		}
		else
		{
			this.getDescendants().forEach((descendant) => {
				totalCount += 1;
				totalCount += descendant.countTotalCount(recursively);
			});
		}

		return totalCount;
	}

	updateCompletedCount()
	{
		const completedCount = this.countCompletedCount();
		this.fields.setCompletedCount(completedCount);
	}

	updateTotalCount()
	{
		const totalCount = this.countTotalCount();
		this.fields.setTotalCount(totalCount);
	}

	updateCounts()
	{
		this.updateCompletedCount();
		this.updateTotalCount();
	}

	updateProgress()
	{
		if (this.progress === null)
		{
			return;
		}

		const total = this.fields.getTotalCount();
		const completed = this.fields.getCompletedCount();

		this.progress.setMaxValue(total);
		this.progress.update(completed);

		if (this.isCheckList())
		{
			this.updateProgressText(completed, total);
		}
	}

	updateProgressText(completed, total)
	{
		const progressText = CheckListItem.getProgressText(completed, total);
		this.progress.setTextAfter(progressText);
	}

	delete()
	{
		const parent = this.getParent();

		parent.remove(this);
		parent.updateCounts();
		parent.updateProgress();

		CheckListItem.setDefaultStyles(this.container, 'delete');

		setTimeout(() => {
			this.container.style.height = 0;
			this.container.style.opacity = 0;
			this.container.style.paddingTop = 0;
		}, 1);

		setTimeout(() => {
			Dom.remove(this.container);
		}, 250);
	}

	restore()
	{
		const parent = this.getParent();
		const position = this.fields.getSortIndex();

		if (position === 0)
		{
			if (parent.getDescendantsCount() > 0)
			{
				parent.addCheckListItem(this, parent.getFirstDescendant(), 'before');
			}
			else
			{
				parent.addCheckListItem(this);
			}
		}
		else
		{
			parent.addCheckListItem(this, parent.getDescendants()[position - 1]);
		}
	}

	deleteAction(showBalloon = true)
	{
		const title = this.fields.getTitle();

		this.delete();

		if (showBalloon && title.length > 0 && title !== '')
		{
			const action = 'DELETE';
			const data = {
				type: this.isCheckList() ? 'CHECKLIST' : 'ITEM',
			};

			this.getNotificationBalloon(action, data);
		}

		Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');

		this.input = null;
		this.updateMode = false;
	}

	onDeleteClick(e)
	{
		e.preventDefault();

		Dom.hide(this.getRootNode().panel);

		if (this.checkSelectedItems())
		{
			const items = this.getSelectedItems();
			const action = 'DELETE_SELECTED';
			const data = {items};

			this.runForEachSelectedItem((item) => {
				item.fields.setIsSelected(false);
				item.deleteAction(false);
			});

			items.forEach((item) => {
				item.getParent().updateIndexes();
				item.handleCheckListChanges();
			});

			this.getNotificationBalloon(action, data);

			return;
		}

		this.deleteAction();
		this.getParent().updateIndexes();

		this.handleCheckListChanges();
	}

	getNotificationBalloon(action, data)
	{
		const actions = [];
		let content = '';

		switch (action)
		{
			case 'DELETE':
			{
				content = Loc.getMessage(`TASKS_CHECKLIST_NOTIFICATION_BALLOON_ACTION_${action}_${data.type}`);
				actions.push({
					title: Loc.getMessage('TASKS_CHECKLIST_NOTIFICATION_BALLOON_CANCEL'),
					events: {
						click: (event, balloon) =>
						{
							balloon.close();

							this.restore();
							this.handleCheckListChanges();
							this.handleTaskOptions();
						},
					},
				});
				break;
			}

			case 'DELETE_SELECTED':
			{
				content = Loc.getMessage(`TASKS_CHECKLIST_NOTIFICATION_BALLOON_ACTION_${action}_ITEMS`);
				actions.push({
					title: Loc.getMessage('TASKS_CHECKLIST_NOTIFICATION_BALLOON_CANCEL'),
					events: {
						click: (event, balloon) =>
						{
							balloon.close();

							data.items.forEach((item) => {
								item.restore();
								item.handleCheckListChanges();
							});

							this.handleTaskOptions();
						},
					},
				});
				break;
			}

			case 'AUDITOR_ADDED':
			case 'ACCOMPLICE_ADDED':
			{
				content = Tag.render`
					<div class="checklist-notification-balloon-message-container">
						<div class="checklist-notification-balloon-avatar">
							<img class="checklist-notification-balloon-avatar-img" src="${data.avatar}" alt=""/>
						</div>
						<span class="checklist-notification-balloon-message">
							${Loc.getMessage(`TASKS_CHECKLIST_NOTIFICATION_BALLOON_ACTION_${action}`)}
						</span>
					</div>
				`;
				break;
			}

			default:
			{
				break;
			}
		}

		BX.loadExt('ui.notification').then(() => {
			BX.UI.Notification.Center.notify({content, actions});
		});
	}

	onToAnotherCheckListClick(e)
	{
		const rootNode = this.getRootNode();

		if (rootNode.getDescendantsCount() === 1)
		{
			this.moveToNewCheckList(2);
			return;
		}

		new BX.PopupMenuWindow(
			'to-another-checklist',
			e.target,
			this.getToAnotherCheckListPopupItems(),
			{
				autoHide: true,
				closeByEsc: true,
				offsetLeft: e.target.offsetWidth / 3,
				angle: true,
				events: {
					onPopupClose() {
						this.destroy();
					},
				},
			},
		).show();
	}

	getToAnotherCheckListPopupItems()
	{
		const selectMode = this.checkSelectedItems();
		const popupMenuItems = [];
		const toNewCheckListMenuItem = {
			text: Tag.message`+ ${'TASKS_CHECKLIST_PANEL_TO_ANOTHER_CHECKLIST_POPUP_NEW_CHECKLIST'}`,
			onclick: (event, item) => {
				item.getMenuWindow().close();
				this.moveToNewCheckList(this.getRootNode().getDescendantsCount() + 1);
			},
		};

		if (selectMode)
		{
			this.getDescendants().forEach((descendant) => {
				popupMenuItems.push({
					text: descendant.fields.getDisplayTitle(),
					onclick: (event, item) => {
						item.getMenuWindow().close();

						this.runForEachSelectedItem((selectedItem) => {
							selectedItem.makeChildOf(descendant);
							descendant.unselectAll();

							if (!this.checkSelectedItems())
							{
								Dom.hide(this.getRootNode().panel);
							}
						});
					},
				});
			});

			popupMenuItems.push({delimiter: true});
			popupMenuItems.push(toNewCheckListMenuItem);

			return popupMenuItems;
		}

		const checkList = this.getCheckList();
		const checkLists = this.getRootNode().getDescendants().filter(item => item !== checkList);

		checkLists.forEach((descendant) => {
			popupMenuItems.push({
				text: descendant.fields.getDisplayTitle(),
				onclick: (event, item) => {
					item.getMenuWindow().close();
					this.makeChildOf(descendant);
					this.handleUpdateEnding();
				},
			});
		});

		popupMenuItems.push({delimiter: true});
		popupMenuItems.push(toNewCheckListMenuItem);

		return popupMenuItems;
	}

	moveToNewCheckList(number)
	{
		const title = `${Loc.getMessage('TASKS_CHECKLIST_NEW_CHECKLIST_TITLE')}`.replace('#ITEM_NUMBER#', number);
		const newCheckList = new CheckListItem({TITLE: title, DISPLAY_TITLE: title});

		this.getRootNode().addCheckListItem(newCheckList).then(() => {
			if (this.checkSelectedItems())
			{
				this.runForEachSelectedItem((selectedItem) => {
					selectedItem.makeChildOf(newCheckList);
					newCheckList.unselectAll();

					if (!this.checkSelectedItems())
					{
						Dom.hide(this.getRootNode().panel);
					}
				});
			}
			else
			{
				this.makeChildOf(newCheckList);
				this.handleUpdateEnding();
			}
		});
	}

	processMemberSelect(member)
	{
		if (this.memberSelector)
		{
			this.memberSelector.close();
		}

		if (this.checkSelectedItems())
		{
			this.runForEachSelectedItem((selectedItem) => {
				const displayTitle = selectedItem.fields.getDisplayTitle();
				const space = displayTitle.slice(-1) === ' ' ? '' : ' ';
				const newTitle = `${displayTitle}${space}${member.nameFormatted}`.substring(0, 255);

				selectedItem.fields.addMember(member);

				selectedItem.updateTitle(newTitle);
				selectedItem.updateDisplayTitle(newTitle);
			});

			return;
		}

		const start = this.inputCursorPosition.start || 0;
		const inputText = this.input.value;
		const startSpace = (start === 0 || inputText.charAt(start - 1) === ' ') ? '' : ' ';
		const endSpace = inputText.charAt(start) === ' ' ? '' : ' ';

		this.fields.addMember(member);

		const newInputText = `${inputText.slice(0, start)}${startSpace}${Text.decode(member.nameFormatted)}${endSpace}`;

		this.inputCursorPosition.start = newInputText.length;
		this.inputCursorPosition.end = newInputText.length;

		this.input.value = `${newInputText}${inputText.slice(start)}`;

		this.retrieveFocus();
	}

	onSocNetSelectorAuditorSelected(auditor)
	{
		const type = 'auditor';
		const action = `${type.toUpperCase()}_ADDED`;
		const data = {avatar: auditor.avatar};
		const resultAuditor = {...auditor, type};

		this.processMemberSelect(resultAuditor);
		this.getNotificationBalloon(action, data);

		Event.EventEmitter.emit('BX.Tasks.CheckListItem:auditorAdded', auditor);
		Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	}

	onSocNetSelectorAccompliceSelected(accomplice)
	{
		const type = 'accomplice';
		const action = `${type.toUpperCase()}_ADDED`;
		const data = {avatar: accomplice.avatar};
		const resultAccomplice = {...accomplice, type};

		this.processMemberSelect(resultAccomplice);
		this.getNotificationBalloon(action, data);

		Event.EventEmitter.emit('BX.Tasks.CheckListItem:accompliceAdded', accomplice);
		Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	}

	onAddAuditorClick(e)
	{
		this.memberSelector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
			scope: e.target,
			mode: 'user',
			useSearch: true,
			useAdd: false,
			controlBind: e.target,
			parent: this,
		});

		this.memberSelector.bindEvent('item-selected', this.onSocNetSelectorAuditorSelected.bind(this));
		this.memberSelector.open();
	}

	onAddAccompliceClick(e)
	{
		this.memberSelector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
			scope: e.target,
			mode: 'user',
			useSearch: true,
			useAdd: false,
			controlBind: e.target,
			parent: this,
		});

		this.memberSelector.bindEvent('item-selected', this.onSocNetSelectorAccompliceSelected.bind(this));
		this.memberSelector.open();
	}

	onUploadAttachmentClick(e)
	{
		const nodeId = this.getNodeId();
		const {prefix, diskUrls} = this.optionManager;
		const {urlSelect, urlRenameFile, urlDeleteFile, urlUpload} = diskUrls;

		if (this.filesLoaderPopup === null)
		{
			this.filesLoaderPopup = new BX.PopupWindow({
				content: this.getAttachmentsLoaderLayout(),
				bindElement: e.target,
				offsetLeft: e.target.offsetWidth / 2,
				autoHide: true,
				closeByEsc: true,
				angle: true,
			});
		}
		else
		{
			this.filesLoaderPopup.setBindElement(e.target);
		}

		this.filesLoaderPopup.show();

		BX.Disk.UF.add({
			UID: nodeId,
			controlName: `${prefix}[${nodeId}][UF_CHECKLIST_FILES][]`,
			hideSelectDialog: false,
			urlSelect,
			urlRenameFile,
			urlDeleteFile,
			urlUpload,
		});

		BX.onCustomEvent(
			this.filesLoaderPopup.contentContainer.querySelector('#files_chooser'),
			'DiskLoadFormController',
			['show'],
		);
	}

	onDeleteAttachmentClick(fileId)
	{
		this.fields.removeAttachment(fileId);
		Dom.remove(this.getAttachmentsContainer().querySelector(`#disk-attach-${fileId}`));
		Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	}

	getPanelBodyLayout()
	{
		const membersLayout = Tag.render`
			<div class="checklist-item-editor-panel-btn checklist-item-editor-panel-btn-auditor" onclick="${this.onAddAuditorClick.bind(this)}">
				${Tag.message`+ ${'TASKS_CHECKLIST_PANEL_AUDITOR'}`}
			</div>
			<div class="checklist-item-editor-panel-separator"></div>
			<div class="checklist-item-editor-panel-btn checklist-item-editor-panel-btn-accomplice" onclick="${this.onAddAccompliceClick.bind(this)}">
				${Tag.message`+ ${'TASKS_CHECKLIST_PANEL_ACCOMPLICE'}`}
			</div>
			<div class="checklist-item-editor-panel-separator"></div>
		`;
		const attachmentButtonLayout = Tag.render`
			<div class="checklist-item-editor-panel-btn checklist-item-editor-panel-btn-attachment" onclick="${this.onUploadAttachmentClick.bind(this)}"></div>
		`;

		const itemsActionButtonsLayout = Tag.render`
			${this.checkSelectedItems() ? '' : attachmentButtonLayout}
			<div class="checklist-item-editor-panel-btn checklist-item-editor-panel-btn-tabin" onclick="${this.onTabInClick.bind(this)}"></div>
			<div class="checklist-item-editor-panel-btn checklist-item-editor-panel-btn-tabout" onclick="${this.onTabOutClick.bind(this)}"></div>
			<div class="checklist-item-editor-panel-separator"></div>
			<div class="checklist-item-editor-panel-btn checklist-item-editor-panel-btn-important
				${this.fields.getIsImportant() ? ' checklist-item-editor-panel-btn-important-selected' : ''}" onclick="${this.onImportantClick.bind(this)}">
				${Loc.getMessage('TASKS_CHECKLIST_PANEL_IMPORTANT')}
			</div>
			<div class="checklist-item-editor-panel-separator"></div>
			<div class="checklist-item-editor-panel-btn checklist-item-editor-panel-btn-checklist" onclick="${this.onToAnotherCheckListClick.bind(this)}">
				${Loc.getMessage('TASKS_CHECKLIST_PANEL_TO_ANOTHER_CHECKLIST')}
			</div>
			<div class="checklist-item-editor-panel-separator"></div>
			<div class="checklist-item-editor-panel-btn checklist-item-editor-panel-btn-remove" onclick="${this.onDeleteClick.bind(this)}"></div>
		`;

		return Tag.render`
			<div class="checklist-item-editor-panel ${this.isTaskRoot() || this.isCheckList() ? 'checklist-item-editor-group-panel' : ''}">
				${this.checkCanAddAccomplice() ? membersLayout : ''}
				${!this.isCheckList() ? itemsActionButtonsLayout : ''}
			</div>
		`;
	}

	updateTitle(text)
	{
		this.fields.setTitle(text);
	}

	updateDisplayTitle(text)
	{
		const oldTitleNode = this.container.querySelector(this.isCheckList() ? '.checklist-header-name-text' : '.checklist-item-description-text');
		const newTitleNode = this.getTitleLayout();

		this.fields.setDisplayTitle(text);

		Dom.replace(oldTitleNode, newTitleNode);
	}

	getTitleLayout()
	{
		const {userPath} = this.optionManager;
		let title = this.fields.getTitle();

		title = this.isCheckList() ? CheckListItem.getDefaultDisplayTitle(title) : title;

		this.fields.setDisplayTitle(title);
		this.fields.getMembers().forEach(({id, nameFormatted, type}) => {
			const regExp = new RegExp(nameFormatted, 'g');
			const href = userPath.replace('#user_id#', id);

			title = title.replace(regExp, `<a href=${href} class="checklist-item-${type}">${nameFormatted}</a>`);
		});

		title = title.replace(/(https?:\/\/[^\s]+)/g, url => `<a class="checklist-item-link" href="${url}" target="_blank">${url}</a>`);

		return Tag.render`
			<div class="${this.isCheckList() ? 'checklist-header-name-text' : 'checklist-item-description-text'}">
				${title}
			</div>
		`;
	}

	processMembersFromText(text)
	{
		const membersToDelete = [];

		this.fields.getMembers().forEach(({id, nameFormatted}) => {
			if (text.indexOf(nameFormatted) === -1)
			{
				membersToDelete.push(id);
			}
		});

		membersToDelete.forEach((id) => {
			this.fields.removeMember(id);
		});
	}

	updateIndexes()
	{
		this.updateSortIndexes();
		this.updateDisplaySortIndexes();
	}

	updateSortIndexes()
	{
		let sortIndex = 0;

		this.getDescendants().forEach((descendant) => {
			descendant.fields.setSortIndex(sortIndex);
			sortIndex += 1;
		});
	}

	updateDisplaySortIndexes()
	{
		const parentSortIndex = (this.isCheckList() || this.isTaskRoot() ? '' : `${this.fields.getDisplaySortIndex()}.`);
		let localSortIndex = 0;

		this.getDescendants().forEach((descendant) => {
			localSortIndex += 1;
			const newSortIndex = `${parentSortIndex}${localSortIndex}`;

			descendant.fields.setDisplaySortIndex(newSortIndex);

			if (!descendant.isCheckList())
			{
				descendant.container.querySelector('.checklist-item-number').innerText = newSortIndex;
			}

			descendant.updateDisplaySortIndexes();
		});
	}

	handleTaskOptions()
	{
		const {userId, showCompleted, showOnlyMine} = this.optionManager;

		this.getRootNode().hideByCondition((item) => {
			const isComplete = item.fields.getIsComplete();
			const hasUserInMembers = item.fields.getMembers().has(userId.toString());
			let condition;

			if (!showCompleted && showOnlyMine)
			{
				condition = isComplete || !hasUserInMembers;
			}
			else if (!showCompleted)
			{
				condition = isComplete;
			}
			else if (showOnlyMine)
			{
				condition = !hasUserInMembers;
			}
			else
			{
				condition = false;
			}

			return condition;
		});
	}

	hideByCondition(condition)
	{
		if (this.checkCanHide(condition))
		{
			this.hide();
		}
		else
		{
			this.show();
			this.getDescendants().forEach((descendant) => {
				descendant.hideByCondition(condition);
			});
		}
	}

	checkCanHide(condition)
	{
		if (
			this.isTaskRoot()
			|| this.updateMode
			|| Dom.hasClass(this.container, 'checklist-item-show')
			|| !condition(this)
		)
		{
			return false;
		}

		let canHide = true;

		this.getDescendants().forEach((descendant) => {
			if (!condition(descendant))
			{
				canHide = false;
			}
			else if (canHide)
			{
				canHide = descendant.checkCanHide(condition);
			}
		});

		return canHide;
	}

	hide()
	{
		Dom.hide(this.container);
	}

	show()
	{
		Dom.show(this.container);
	}

	checkIsComplete()
	{
		let isComplete;

		if (this.isTaskRoot())
		{
			isComplete = false;
		}
		else if (this.isCheckList())
		{
			const completedCount = this.countCompletedCount(true);
			const totalCount = this.countTotalCount(true);

			isComplete = (completedCount === totalCount && totalCount > 0);
		}
		else
		{
			isComplete = this.fields.getIsComplete();
		}

		return isComplete;
	}

	checkActiveUpdateExist()
	{
		if (this.updateMode)
		{
			return true;
		}

		let found = false;

		this.getDescendants().forEach((descendant) => {
			if (found === false)
			{
				found = descendant.checkActiveUpdateExist();
			}
		});

		return found;
	}

	disableAllGroup()
	{
		this.getDescendants().forEach((descendant) => {
			if (descendant.fields.getIsSelected())
			{
				descendant.toggleGroup();
			}
		});
	}

	disableAllUpdateModes()
	{
		if (this.updateMode)
		{
			this.handleUpdateEnding();
		}

		this.getDescendants().forEach((descendant) => {
			descendant.disableAllUpdateModes();
		});
	}

	rememberInputState()
	{
		this.input = this.container.querySelector(`#text_${this.getNodeId()}`);
		this.inputCursorPosition = CheckListItem.getInputSelection(this.input);
	}

	clearInput(e)
	{
		e.preventDefault();

		this.container.querySelector(`#text_${this.getNodeId()}`).value = '';
		this.retrieveFocus();
	}

	retrieveFocus()
	{
		if (this.input !== null && this.inputCursorPosition)
		{
			const {start, end} = this.inputCursorPosition;

			setTimeout(() => {
				this.input.focus();
				this.input.setSelectionRange(start, end);
			}, 10);
		}
	}

	getUpdateModeLayout()
	{
		const nodeId = this.getNodeId();

		if (this.isCheckList())
		{
			return Tag.render`
				<div class="checklist-header-name">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-after-icon ui-ctl-xs ui-ctl-no-padding ui-ctl-underline 
								checklist-header-name-editor">
						<input class="ui-ctl-element" type="text" id="text_${nodeId}"
							   value="${this.fields.getDisplayTitle()}"
							   onkeypress="${this.onInputKeyPressed.bind(this)}"
							   onblur="${this.rememberInputState.bind(this)}"/>
						<button class="ui-ctl-after ui-ctl-icon-clear" onclick="${this.clearInput.bind(this)}"/>
					</div>
				</div>
			`;
		}

		const progressBarLayout = new BX.UI.ProgressRound({
			value: this.fields.getCompletedCount(),
			maxValue: this.fields.getTotalCount(),
			width: 20,
			lineSize: 3,
			fill: false,
			color: BX.UI.ProgressRound.Color.PRIMARY,
		});

		return Tag.render`
			<div class="checklist-item-inner checklist-item-new ${this.fields.getIsComplete() ? 'checklist-item-solved' : ''}">
				<div class="checklist-item-flag-block">
					<div class="checklist-item-flag">
						<label class="checklist-item-flag-element" onclick="${this.onCompleteButtonClick.bind(this)}">
							<span class="checklist-item-flag-sub-checklist-progress">
								${progressBarLayout.getContainer()}
							</span>
							<span class="checklist-item-flag-element-decorate"/>
						</label>
					</div>
				</div>
				<div class="checklist-item-content-block">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-after-icon ui-ctl-w100">
						<input class="ui-ctl-element" type="text" id="text_${nodeId}"
							   placeholder="${Loc.getMessage('TASKS_CHECKLIST_NEW_ITEM_PLACEHOLDER')}"
							   value="${this.fields.getDisplayTitle()}"
							   onkeypress="${this.onInputKeyPressed.bind(this)}"
							   onblur="${this.rememberInputState.bind(this)}"/>
						<button class="ui-ctl-after ui-ctl-icon-clear" onclick="${this.clearInput.bind(this)}"/>
					</div>
				</div>
			</div>
		`;
	}

	showEditorPanel(item, nodeToPosition = null)
	{
		const position = BX.pos(nodeToPosition || item.getContainer());

		if (!this.panel)
		{
			this.panel = Tag.render`
				<div class="checklist-item-editor-panel-container">
					${item.getPanelBodyLayout()}
				</div>
			`;

			this.panel.style.top = `${position.top}px`;
			this.panel.style.left = `${position.left}px`;

			Dom.append(this.panel, document.body);
		}
		else
		{
			Dom.replace(this.panel.querySelector('.checklist-item-editor-panel'), item.getPanelBodyLayout());

			this.panel.style.top = `${position.top}px`;
			this.panel.style.left = `${position.left}px`;
		}

		if (!Dom.isShown(this.panel))
		{
			Dom.show(this.panel);
		}

		if (Dom.isShown(this.panel) && item.isCheckList() && !item.checkCanAddAccomplice())
		{
			Dom.hide(this.panel);
		}
	}

	enableUpdateMode()
	{
		const viewModeLayout = this.container.querySelector(
			this.isCheckList() ? '.checklist-header-name' : '.checklist-item-inner',
		);
		const updateModeLayout = this.getUpdateModeLayout();

		Dom.addClass(viewModeLayout, 'checklist-item-hidden');
		Dom.insertBefore(updateModeLayout, viewModeLayout);

		this.input = updateModeLayout.querySelector(`#text_${this.getNodeId()}`);
		this.input.focus();
		this.input.setSelectionRange(this.input.value.length, this.input.value.length);

		if (this.input.value === '' || this.input.value.length === 0)
		{
			setTimeout(() => {
				if (Dom.isShown(this.input))
				{
					this.getRootNode().showEditorPanel(this, this.input);
				}
			}, 250);
		}
		else
		{
			this.getRootNode().showEditorPanel(this, this.input);
		}

		this.updateMode = true;
	}

	disableUpdateMode()
	{
		const currentInner = this.container.querySelector(this.isCheckList() ? '.checklist-header-name' : '.checklist-item-inner');
		const text = Text.encode(currentInner.querySelector(`#text_${this.getNodeId()}`).value.trim().substring(0, 255));

		this.processMembersFromText(text);
		this.updateTitle(text);
		this.updateDisplayTitle(text);

		Dom.removeClass(currentInner.nextElementSibling, 'checklist-item-hidden');
		Dom.remove(currentInner);
		Dom.hide(this.getRootNode().panel);

		this.input = null;
		this.updateMode = false;
	}

	checkCanDeleteOnUpdateEnding()
	{
		return this.getDescendantsCount() === 0
			&& Object.keys(this.fields.getAttachments()).length === 0
			&& this.filesLoaderProgressBars.size === 0;
	}

	handleUpdateEnding(createNewItem = false)
	{
		const input = this.container.querySelector(`#text_${this.getNodeId()}`);
		const text = input.value.trim();

		if (text.length === 0)
		{
			if (this.checkCanDeleteOnUpdateEnding())
			{
				this.deleteAction(false);
				this.getParent().updateIndexes();
				this.handleCheckListIsEmpty();

				Dom.hide(this.getRootNode().panel);
			}
			else
			{
				CheckListItem.makeDangerElement(input.parentElement);
				if (this.input !== null)
				{
					this.getRootNode().showEditorPanel(this, this.input);
				}
			}
		}
		else if (createNewItem)
		{
			this.getParent().addCheckListItem(null, this);
		}
		else
		{
			this.disableUpdateMode();
			this.handleTaskOptions();
		}

		if (this.filesLoaderPopup !== null)
		{
			this.filesLoaderPopup.close();
		}
	}

	toggleUpdateMode(e)
	{
		if (this.updateMode)
		{
			if (e.keyCode === 13)
			{
				this.handleUpdateEnding(!this.isCheckList());
			}
		}
		else
		{
			const rootNode = this.getRootNode();

			rootNode.disableAllUpdateModes();
			rootNode.disableAllGroup();

			if (!rootNode.checkActiveUpdateExist())
			{
				this.enableUpdateMode();
				Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
			}
		}
	}

	onInputKeyPressed(e)
	{
		if (e.keyCode === 13)
		{
			this.toggleUpdateMode(e);
			e.preventDefault();
		}

		Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	}

	onHeaderNameClick(e)
	{
		if (
			!this.checkCanUpdate()
			|| e.target.closest('.checklist-item-auditor')
			|| e.target.closest('.checklist-item-accomplice')
			|| e.target.closest('.checklist-item-link')
		)
		{
			return;
		}

		this.toggleUpdateMode(e);
	}

	onInnerContainerClick(e)
	{
		if (
			!this.checkCanUpdate()
			|| e.target.closest('.checklist-item-auditor')
			|| e.target.closest('.checklist-item-accomplice')
			|| e.target.closest('.checklist-item-link')
			|| e.target.closest('.checklist-item-important')
			|| e.target.closest('.checklist-item-dragndrop')
			|| e.target.closest('.checklist-item-group-checkbox')
			|| e.target.closest('.checklist-item-remove')
			|| e.target.closest('.checklist-item-flag-block')
		)
		{
			return;
		}

		if (this.getCheckList().fields.getIsSelected())
		{
			this.toggleSelect(e);
			return;
		}

		this.toggleUpdateMode(e);
	}

	getImportantLayout()
	{
		return Tag.render`
			<div class="checklist-item-important" onclick="${this.onImportantClick.bind(this)}"></div>
		`;
	}

	toggleImportant()
	{
		if (this.fields.getIsImportant())
		{
			this.fields.setIsImportant(false);
			Dom.remove(this.container.querySelector('.checklist-item-important'));
		}
		else
		{
			this.fields.setIsImportant(true);
			Dom.insertBefore(this.getImportantLayout(), this.container.querySelector('.checklist-item-description'));
		}

		Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');

		this.retrieveFocus();
	}

	checkSelectedItems()
	{
		return this.getRootNode().getSelectedItems().length > 0;
	}

	runForEachSelectedItem(callback, reverse = false)
	{
		let selectedItems = this.getRootNode().getSelectedItems();

		if (reverse)
		{
			selectedItems = [...selectedItems.reverse()];
		}

		selectedItems.forEach((item) => {
			callback(item);
		});
	}

	onImportantClick(e)
	{
		if (!this.checkCanUpdate())
		{
			return;
		}

		if (this.checkSelectedItems())
		{
			this.runForEachSelectedItem((selectedItem) => {
				selectedItem.toggleImportant();
			});

			return;
		}

		this.toggleImportant();

		const panelImportantButton = e.target.closest('.checklist-item-editor-panel-btn-important');
		if (panelImportantButton)
		{
			Dom.toggleClass(panelImportantButton, 'checklist-item-editor-panel-btn-important-selected');
		}
	}

	onCompleteButtonClick()
	{
		if (
			this.getCheckList().fields.getIsSelected()
			|| this.updateMode
			|| !this.checkCanToggle()
		)
		{
			return;
		}

		this.toggleComplete();
	}

	toggleComplete()
	{
		const isComplete = this.fields.getIsComplete();

		this.fields.setIsComplete(!isComplete);
		this.getParent().updateCounts();
		this.getParent().updateProgress();

		Dom.toggleClass(this.getInnerContainer(), 'checklist-item-solved');

		this.handleCheckListChanges();
		this.handleTaskOptions();

		this.runAjaxToggleComplete();
	}

	runAjaxToggleComplete()
	{
		const id = this.fields.getId();

		if (!id)
		{
			return;
		}

		const data = {};
		const {ajaxActions, entityId, entityType, stableTreeStructure} = this.optionManager;
		const actionName = this.fields.getIsComplete() ? ajaxActions.COMPLETE : ajaxActions.RENEW;

		data[`${entityType.toLowerCase()}Id`] = entityId;
		data.checkListItemId = id;

		BX.ajax.runAction(actionName, {data}).then((response) => {
			const isComplete = response.data.checkListItem.isComplete;
			this.updateStableTreeStructure(isComplete, stableTreeStructure, stableTreeStructure);
		});
	}

	updateStableTreeStructure(isComplete, item, parent)
	{
		if (this.fields.getId() === item.FIELDS.id)
		{
			item.FIELDS.isComplete = isComplete;
			parent.FIELDS.completedCount += (isComplete ? 1 : -1);

			return this;
		}

		let found = null;
		item.DESCENDANTS.forEach((descendant) => {
			if (found === null)
			{
				found = this.updateStableTreeStructure(isComplete, descendant, item);
			}
		});

		return found;
	}

	unselectAll()
	{
		const checkBox = this.container.querySelector(`#select_${this.getNodeId()}`);

		if (checkBox && checkBox.checked === true)
		{
			this.fields.setIsSelected(false);

			checkBox.checked = false;
			Dom.removeClass(this.getInnerContainer(), 'checklist-item-selected');
		}

		this.getDescendants().forEach((descendant) => {
			descendant.unselectAll();
		});
	}

	getSelected()
	{
		let selected = [];

		if (this.fields.getIsSelected())
		{
			selected.push(this);
		}

		this.getDescendants().forEach((descendant) => {
			selected = [...selected, ...descendant.getSelected()];
		});

		return selected;
	}

	getSelectedItems()
	{
		return this.getSelected().filter(item => !item.isCheckList() && !item.isTaskRoot());
	}

	onSelectCheckboxClick(e)
	{
		if (!this.checkCanUpdate())
		{
			e.target.checked = false;
			return;
		}

		this.toggleSelect();
	}

	toggleSelect()
	{
		const rootNode = this.getRootNode();

		if (this.fields.getIsSelected())
		{
			this.container.querySelector(`#select_${this.getNodeId()}`).checked = false;
			this.fields.setIsSelected(false);

			rootNode.showEditorPanel(rootNode, this.container);

			if (!this.checkSelectedItems())
			{
				Dom.hide(rootNode.panel);
			}
		}
		else
		{
			this.container.querySelector(`#select_${this.getNodeId()}`).checked = true;
			this.fields.setIsSelected(true);

			rootNode.showEditorPanel(rootNode, this.container);
		}

		Dom.toggleClass(this.getInnerContainer(), 'checklist-item-selected');
	}

	onGroupButtonClick()
	{
		if (!this.getRootNode().checkActiveUpdateExist())
		{
			this.toggleGroup();
		}
	}

	toggleGroup()
	{
		if (this.fields.getIsSelected())
		{
			this.unselectAll();
			this.fields.setIsSelected(false);

			if (!this.checkSelectedItems())
			{
				Dom.hide(this.getRootNode().panel);
			}
		}
		else
		{
			this.fields.setIsSelected(true);
		}

		Dom.toggleClass(this.container, 'checklist-item-group-editor-collapse');
		Dom.toggleClass(this.container, 'checklist-item-group-editor-expand');
	}

	onCollapseButtonClick()
	{
		this.toggleCollapse();
	}

	toggleCollapse()
	{
		Dom.toggleClass(this.container, 'checklist-collapse');

		const wrapperList = this.container.querySelector('.checklist-items-wrapper');
		const wrapperListHeight = `${BX.pos(wrapperList).height}px`;

		if (Dom.hasClass(this.container, 'checklist-collapse'))
		{
			this.fields.setIsCollapse(true);

			wrapperList.style.overflow = 'hidden';
			wrapperList.style.height = wrapperListHeight;
			setTimeout(() => { wrapperList.style.height = 0; }, 0);
		}
		else
		{
			this.fields.setIsCollapse(false);

			wrapperList.style.height = 0;
			setTimeout(() => { wrapperList.style.height = `${wrapperList.scrollHeight}px`; }, 0);
			setTimeout(() => {
				wrapperList.style.height = '';
				wrapperList.style.overflow = '';
			}, 250);
		}
	}

	toggleEmpty()
	{
		Dom.toggleClass(this.container, 'checklist-empty');
	}

	handleCheckListIsComplete()
	{
		const checkList = this.getCheckList();
		const checkListIsComplete = checkList.checkIsComplete();

		checkList.fields.setIsComplete(checkListIsComplete);

		if (checkListIsComplete && !Dom.hasClass(checkList.container, 'checklist-collapse'))
		{
			checkList.toggleCollapse();
		}
	}

	handleCheckListIsEmpty()
	{
		const checkList = this.getCheckList();
		const checkListIsEmpty = checkList.getDescendantsCount() === 0;

		if (
			(checkListIsEmpty && !Dom.hasClass(checkList.container, 'checklist-empty'))
			|| (!checkListIsEmpty && Dom.hasClass(checkList.container, 'checklist-empty'))
		)
		{
			checkList.toggleEmpty();
		}
	}

	handleCheckListChanges()
	{
		this.handleCheckListIsComplete();
		this.handleCheckListIsEmpty();
	}

	checkCanAdd()
	{
		return this.optionManager.getCanAdd();
	}

	checkCanAddAccomplice()
	{
		return this.optionManager.getCanAddAccomplice();
	}

	checkCanUpdate()
	{
		return this.action.canUpdate;
	}

	checkCanRemove()
	{
		return this.action.canRemove;
	}

	checkCanToggle()
	{
		return this.action.canToggle;
	}

	checkCanDrag()
	{
		return this.action.canDrag;
	}

	getAttachmentsContainer()
	{
		return this.container.querySelector(`#attachments_${this.getNodeId()}`);
	}

	getSubItemsContainer()
	{
		return this.container.querySelector(`#subItems_${this.getNodeId()}`);
	}

	getInnerContainer()
	{
		return this.container.querySelector('.checklist-item-inner');
	}

	getContainer()
	{
		return this.container;
	}

	move(item, position = 'bottom')
	{
		if (
			this.getNodeId() === item.getNodeId()
			|| this.findChild(item.getNodeId()) !== null
		)
		{
			return;
		}

		const oldParent = this.getParent();
		const newParent = item.getParent();

		oldParent.remove(this);

		if (position === 'top')
		{
			newParent.addBefore(this, item);
		}
		else
		{
			newParent.addAfter(this, item);
		}

		CheckListItem.updateParents(oldParent, newParent);

		if (position === 'top')
		{
			Dom.insertBefore(this.container, item.container);
		}
		else
		{
			Dom.insertAfter(this.container, item.container);
		}

		this.handleCheckListIsEmpty();
		this.handleTaskOptions();

		Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	}

	makeChildOf(item, position = 'bottom')
	{
		if (item.getDescendantsCount() > 0)
		{
			const borderItems = {
				top: item.getFirstDescendant(),
				bottom: item.getLastDescendant(),
			};

			this.move(borderItems[position], position);
		}
		else
		{
			const oldParent = this.getParent();
			const newParent = item;

			oldParent.remove(this);
			newParent.add(this);

			CheckListItem.updateParents(oldParent, newParent);

			Dom.append(this.container, newParent.getSubItemsContainer());

			this.handleCheckListIsEmpty();
			this.handleTaskOptions();

			Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
		}
	}

	tabIn()
	{
		const descendants = this.getParent().getDescendants();
		const index = descendants.findIndex(item => item === this);

		if (index === 0)
		{
			return;
		}

		this.makeChildOf(descendants[index - 1]);
	}

	onTabInClick()
	{
		if (this.checkSelectedItems())
		{
			this.runForEachSelectedItem((selectedItem) => {
				selectedItem.tabIn();
			});

			return;
		}

		this.tabIn();
		this.retrieveFocus();
	}

	tabOut()
	{
		const parent = this.getParent();

		if (parent.isCheckList())
		{
			return;
		}

		this.move(parent, 'bottom');
	}

	onTabOutClick()
	{
		if (this.checkSelectedItems())
		{
			this.runForEachSelectedItem((selectedItem) => {
				selectedItem.tabOut();
			}, true);

			return;
		}

		this.tabOut();
		this.retrieveFocus();
	}

	addCheckListItem(item = null, dependsOn = null, position = 'after')
	{
		const itemGet = item instanceof CheckListItem;

		return new Promise((resolve) => {
			const newCheckListItem = itemGet ? item : new CheckListItem();
			let newCheckListItemLayout;

			if (dependsOn instanceof CheckListItem)
			{
				if (position === 'before')
				{
					this.addBefore(newCheckListItem, dependsOn);

					newCheckListItemLayout = newCheckListItem.getLayout();
					CheckListItem.setDefaultStyles(newCheckListItemLayout);

					Dom.insertBefore(newCheckListItemLayout, dependsOn.container);
				}
				else if (position === 'after')
				{
					this.addAfter(newCheckListItem, dependsOn);

					newCheckListItemLayout = newCheckListItem.getLayout();
					CheckListItem.setDefaultStyles(newCheckListItemLayout);

					Dom.insertAfter(newCheckListItemLayout, dependsOn.container);
				}
			}
			else
			{
				this.add(newCheckListItem);

				newCheckListItemLayout = newCheckListItem.getLayout();
				CheckListItem.setDefaultStyles(newCheckListItemLayout);

				Dom.append(newCheckListItemLayout, this.getSubItemsContainer());
			}

			this.updateCounts();
			this.updateIndexes();

			if (!this.isTaskRoot())
			{
				this.updateProgress();
				this.handleCheckListIsEmpty();

				if (!itemGet)
				{
					newCheckListItem.toggleUpdateMode();
				}
			}

			setTimeout(() => {
				newCheckListItemLayout.style.height = `${newCheckListItemLayout.scrollHeight}px`;
				newCheckListItemLayout.style.opacity = 1;
			}, 1);

			setTimeout(() => {
				newCheckListItemLayout.style.overflow = '';
				newCheckListItemLayout.style.height = '';
				newCheckListItemLayout.style.opacity = '';

				Dom.removeClass(newCheckListItemLayout, 'checklist-item-show');

				resolve(newCheckListItem);
			}, 250);
		});
	}

	onAddCheckListItemClick()
	{
		if (this.getRootNode().checkActiveUpdateExist())
		{
			return;
		}

		this.addCheckListItem();
	}

	getRequestData(inputData = null)
	{
		const title = this.fields.getTitle();
		let data = inputData || [];

		if (!this.isTaskRoot() && title !== '' && title.length > 0)
		{
			const requestData = {
				NODE_ID: this.getNodeId(),
				PARENT_NODE_ID: this.getParent().getNodeId(),
				ID: this.fields.getId(),
				COPIED_ID: this.fields.getCopiedId(),
				PARENT_ID: this.fields.getParentId(),
				TITLE: Text.decode(this.fields.getTitle()),
				SORT_INDEX: this.fields.getSortIndex(),
				IS_COMPLETE: this.fields.getIsComplete(),
				IS_IMPORTANT: this.fields.getIsImportant(),
				MEMBERS: [],
				ATTACHMENTS: {},
			};

			this.fields.getMembers().forEach((value, key) => {
				const {nameFormatted, type} = value;
				requestData.MEMBERS.push({
					[key]: {
						TYPE: type,
						NAME: Text.decode(nameFormatted),
					},
				});
			});

			const attachments = this.fields.getAttachments();
			Object.keys(attachments).forEach((id) => {
				requestData.ATTACHMENTS[id] = attachments[id];
			});

			data.push(requestData);
		}

		this.getDescendants().forEach((descendant) => {
			data = descendant.getRequestData(data);
		});

		return data;
	}

	appendRequestLayout()
	{
		if (!this.isTaskRoot())
		{
			const nodeId = this.getNodeId();
			const attachments = this.fields.getAttachments();
			const prefix = `${this.optionManager.prefix}[${nodeId}]`;

			let membersLayout = '';
			let attachmentsLayout = '';

			this.fields.getMembers().forEach((value, key) => {
				membersLayout += `<input type="hidden" id="MEMBERS_${key}" name="${prefix}[MEMBERS][${key}][TYPE]" value="${value.type}"/>`;
			});

			Object.keys(attachments).forEach((id) => {
				attachmentsLayout += `<input type="hidden" id="ATTACHMENTS_${id}" name="${prefix}[ATTACHMENTS][${id}]" value="${attachments[id]}"/>`;
			});

			const requestLayout = Tag.render`
				<div id="request_${nodeId}">
					<input type="hidden" id="NODE_ID" name="${prefix}[NODE_ID]" value="${nodeId}"/>
					<input type="hidden" id="PARENT_NODE_ID" name="${prefix}[PARENT_NODE_ID]" value="${this.getParent().getNodeId()}"/>
					<input type="hidden" id="ID" name="${prefix}[ID]" value="${this.fields.getId()}"/>
					<input type="hidden" id="COPIED_ID" name="${prefix}[COPIED_ID]" value="${this.fields.getCopiedId()}"/>
					<input type="hidden" id="PARENT_ID" name="${prefix}[PARENT_ID]" value="${this.fields.getParentId()}"/>
					<input type="hidden" id="TITLE" name="${prefix}[TITLE]" value="${this.fields.getTitle()}"/>
					<input type="hidden" id="SORT_INDEX" name="${prefix}[SORT_INDEX]" value="${this.fields.getSortIndex()}"/>
					<input type="hidden" id="IS_COMPLETE" name="${prefix}[IS_COMPLETE]" value="${this.fields.getIsComplete()}"/>
					<input type="hidden" id="IS_IMPORTANT" name="${prefix}[IS_IMPORTANT]" value="${this.fields.getIsImportant()}"/>
					${membersLayout}
					${attachmentsLayout}
				</div>
			`;

			Dom.remove(this.container.querySelector(`#request_${nodeId}`));
			Dom.append(requestLayout, this.container);
		}

		this.getDescendants().forEach((descendant) => {
			descendant.appendRequestLayout();
		});
	}

	getAttachmentsLayout()
	{
		const searchId = this.fields.getId() || this.fields.getCopiedId();
		const {optionManager} = this;
		const optionAttachments = optionManager.attachments;
		let attachmentsLayout = '';

		if (optionAttachments && (searchId in optionAttachments))
		{
			const attachments = Tag.render`${optionAttachments[searchId]}`;
			const stableAttachments = this.getStableAttachments(optionManager.getStableTreeStructure());
			const attachmentsToDelete = [];

			if (!attachments)
			{
				return attachmentsLayout;
			}

			attachmentsLayout = attachments;

			if (!Array.isArray(attachments))
			{
				attachmentsLayout = [attachments];
			}

			Object.keys(attachmentsLayout).forEach((key) => {
				const attachment = attachmentsLayout[key];
				const fileId = attachment.getAttribute('data-bx-id');
				const extension = CheckListItem.getFileExtension(attachment.getAttribute('data-bx-extension'));
				const extensionClass = `ui-icon-file-${extension}`;
				const iconContainer = attachment.querySelector(`#disk-attach-file-${fileId}`);
				const deleteButton = Tag.render`
					<div class="checklist-item-attachment-file-remove"
						 onclick="${this.onDeleteAttachmentClick.bind(this, fileId)}"></div>
				`;
				const has = Object.prototype.hasOwnProperty;

				if (!has.call(stableAttachments, fileId))
				{
					attachmentsToDelete.push(key);
					return;
				}

				if (iconContainer && !Dom.hasClass(iconContainer, extensionClass))
				{
					Dom.addClass(iconContainer, extensionClass);
				}

				if (this.checkCanUpdate())
				{
					Dom.append(deleteButton, attachment.querySelector('.checklist-item-attachment-file-cover'));
				}
			});

			attachmentsToDelete.sort((a, b) => b - a);
			attachmentsToDelete.forEach((id) => {
				attachmentsLayout.splice(id, 1);
			});
		}
		else
		{
			this.fields.setAttachments({});
		}

		return attachmentsLayout;
	}

	getStableAttachments(item)
	{
		const fields = item.FIELDS;
		const id = fields.id || fields.copiedId;

		if (id === this.fields.getId() || id === this.fields.getCopiedId())
		{
			return fields.attachments;
		}

		let found = null;
		item.DESCENDANTS.forEach((descendant) => {
			if (found === null)
			{
				found = this.getStableAttachments(descendant);
			}
		});

		return found;
	}

	getLoadedAttachmentLayout(attachment)
	{
		const {id, name, viewUrl, size, ext} = attachment;
		let img = '';

		if (viewUrl)
		{
			img = Tag.render`
				<div class="checklist-item-attachment-file-cover" style="background-image: url(${viewUrl})">
					<div class="checklist-item-attachment-file-remove" onclick="${this.onDeleteAttachmentClick.bind(this, id)}"></div>
				</div>
			`;
		}
		else
		{
			const extension = CheckListItem.getFileExtension(ext);

			img = Tag.render`
				<div class="checklist-item-attachment-file-cover">
					<div class="ui-icon ui-icon-file-${extension}"><i></i></div>
					<div class="checklist-item-attachment-file-remove" onclick="${this.onDeleteAttachmentClick.bind(this, id)}"></div>
				</div>
			`;
		}

		return Tag.render`
			<div class="checklist-item-attachment-file" id="disk-attach-${id}" data-bx-id="${id}">
				${img}
				<div class="checklist-item-attachment-file-name">
					<label class="checklist-item-attachment-file-name-text" title="${name}">${name}</label>
				</div>
				<div class="checklist-item-attachment-file-size">
					<label class="checklist-item-attachment-file-size-text">${size}</label>
				</div>
			</div>
		`;
	}

	onAttachmentsLoaderMenuItemClick()
	{
		if (this.filesLoaderPopup !== null)
		{
			this.filesLoaderPopup.close();
		}
	}

	getAttachmentsLoaderLayout()
	{
		const nodeId = this.getNodeId();
		const {prefix} = this.optionManager;
		const filesChooser = Tag.render`
			<div id="files_chooser">
				<div id="diskuf-selectdialog-${nodeId}" class="diskuf-files-entity diskuf-selectdialog bx-disk">
					<div class="diskuf-files-block checklist-loader-files">
						<div class="diskuf-placeholder">
							<table class="files-list">
								<tbody class="diskuf-placeholder-tbody"></tbody>
							</table>
						</div>
					</div>
					<div class="diskuf-extended" style="display: block">
						<input type="hidden" name="${prefix}[${nodeId}][UF_CHECKLIST_FILES][]" value=""/>
						<div class="diskuf-extended-item">
							<label for="file_loader_${nodeId}" onclick="${this.onAttachmentsLoaderMenuItemClick.bind(this)}">
								${Loc.getMessage('TASKS_CHECKLIST_FILES_LOADER_POPUP_FROM_COMPUTER')}
							</label>
							<input class="diskuf-fileUploader" id="file_loader_${nodeId}" type="file"
								   multiple="multiple" size="1" style="display: none"/>
						</div>
						<div class="diskuf-extended-item" onclick="${this.onAttachmentsLoaderMenuItemClick.bind(this)}">
							<span class="diskuf-selector-link">
								${Loc.getMessage('TASKS_CHECKLIST_FILES_LOADER_POPUP_FROM_B24')}
							</span>
						</div>
						<div class="diskuf-extended-item" onclick="${this.onAttachmentsLoaderMenuItemClick.bind(this)}">
							<span class="diskuf-selector-link-cloud" data-bx-doc-handler="gdrive">
								<span>${Loc.getMessage('TASKS_CHECKLIST_FILES_LOADER_POPUP_FROM_CLOUD')}</span>
							</span>
						</div>
					</div>
				</div>
			</div>
		`;

		BX.addCustomEvent(filesChooser, 'OnFileUploadSuccess', this.OnFileUploadSuccess.bind(this));
		BX.addCustomEvent(filesChooser, 'DiskDLoadFormControllerInit', (uf) => {
			uf._onUploadProgress = this.onUploadProgress.bind(this);
		});

		return filesChooser;
	}

	onUploadProgress(item, progress)
	{
		const {id, name, size} = item;
		const newProgress = Math.min(progress, 98);

		if (!this.filesLoaderProgressBars.has(id))
		{
			const myProgress = new BX.UI.ProgressRound({
				id: `load_progress_${id}`,
				value: newProgress,
				maxValue: 100,
				width: 69,
				lineSize: 5,
				fill: false,
				color: BX.UI.ProgressRound.Color.PRIMARY,
				statusType: BX.UI.ProgressRound.Status.INCIRCLE,
			});
			const filePreview = Tag.render`
				<div class="checklist-item-attachment-file" id="disk-attach-${id}">
					${myProgress.getContainer()}
					<div class="checklist-item-attachment-file-name">
						<label class="checklist-item-attachment-file-name-text" title="${name}">${name}</label>
					</div>
					<div class="checklist-item-attachment-file-size">
						<label class="checklist-item-attachment-file-size-text">${size}</label>
					</div>
				</div>
			`;

			this.filesLoaderProgressBars.set(id, myProgress);
			Dom.append(filePreview, this.getAttachmentsContainer());
		}

		if (!item.progressBarWidth)
		{
			item.progressBarWidth = 5;
		}

		if (newProgress > item.progressBarWidth)
		{
			item.progressBarWidth = Math.ceil(newProgress);
			item.progressBarWidth = item.progressBarWidth > 100 ? 100 : item.progressBarWidth;

			if (this.filesLoaderProgressBars.has(id))
			{
				this.filesLoaderProgressBars.get(id).update(item.progressBarWidth);
			}
		}
	}

	OnFileUploadSuccess(fileResult, uf, file, uploaderFile)
	{
		if (typeof file === 'undefined' || typeof uploaderFile === 'undefined')
		{
			return;
		}

		const attachmentId = fileResult.element_id.toString();
		const attachment = {
			id: attachmentId,
			name: fileResult.element_name,
			viewUrl: fileResult.element_url,
			size: uploaderFile.size,
			ext: uploaderFile.ext,
		};

		this.fields.addAttachments({[attachmentId]: attachmentId});
		this.filesLoaderProgressBars.delete(uploaderFile.id);

		const attachmentProgress = this.getAttachmentsContainer().querySelector(`#disk-attach-${uploaderFile.id}`);
		const attachmentLayout = this.getLoadedAttachmentLayout(attachment);

		if (attachmentProgress)
		{
			Dom.replace(attachmentProgress, attachmentLayout);
		}
		else
		{
			Dom.append(attachmentLayout, this.getAttachmentsContainer());
		}

		const id = this.fields.getId();
		const optionAttachments = this.optionManager.attachments;

		if (optionAttachments)
		{
			if (id in optionAttachments)
			{
				this.optionManager.attachments[id] += attachmentLayout.outerHTML;
			}
			else
			{
				this.optionManager.attachments[id] = attachmentLayout.outerHTML;
			}
		}

		Event.EventEmitter.emit('BX.Tasks.CheckListItem:CheckListChanged');
	}

	getTaskRootLayout(children)
	{
		this.container = Tag.render`
			<div class="checklist-task-root">
				<div id="subItems_${this.getNodeId()}">
					${children}
				</div>
			</div>
		`;

		return this.container;
	}

	getCheckListLayout(children)
	{
		const nodeId = this.getNodeId();
		const value = this.fields.getCompletedCount();
		const maxValue = this.fields.getTotalCount();
		const layouts = {
			listActionsPanel: Tag.render`<div class="checklist-items-list-actions droppable"></div>`,
			groupButton: '',
			dndButton: Tag.render`<div class="checklist-wrapper-dragndrop"></div>`,
		};

		if (this.checkCanAdd())
		{
			const addButtonLayout = Tag.render`
				<a class="checklist-item-add-btn" onclick="${this.onAddCheckListItemClick.bind(this)}">
					${Loc.getMessage('TASKS_CHECKLIST_ADD_NEW_ITEM')}
				</a>
			`;
			const groupButton = Tag.render`
				<div class="checklist-action-group-btn" onclick="${this.onGroupButtonClick.bind(this)}">
					${Loc.getMessage('TASKS_CHECKLIST_GROUP_ACTIONS')}
				</div>
			`;

			Dom.append(addButtonLayout, layouts.listActionsPanel);
			layouts.groupButton = groupButton;
		}

		if (this.checkCanRemove())
		{
			const removeButtonLayout = Tag.render`
				<a class="checklist-item-remove-btn" onclick="${this.onDeleteClick.bind(this)}">
					${Loc.getMessage('TASKS_CHECKLIST_DELETE_CHECKLIST')}
				</a>
			`;
			Dom.append(removeButtonLayout, layouts.listActionsPanel);
		}

		if (!this.checkCanDrag())
		{
			layouts.dndButton.style.visibility = 'hidden';
		}

		this.progress = new BX.UI.ProgressBar({
			value,
			maxValue,
			size: BX.UI.ProgressBar.Size.MEDIUM,
			textAfter: CheckListItem.getProgressText(value, maxValue),
		});

		this.container = Tag.render`
			<div class="checklist-wrapper checklist-item-group-editor-collapse" id="${nodeId}">
				<div class="checklist-header-wrapper droppable">
					${layouts.dndButton}
					<div class="checklist-header-block">
						<div class="checklist-header-inner">
							<div class="checklist-header-name" onclick="${this.onHeaderNameClick.bind(this)}">
								${this.getTitleLayout()}
							</div>
							<div class="checklist-header-progress-block">
								<div class="checklist-header-progress" id="progress_${nodeId}">
									${this.progress.getContainer()}
								</div>
							</div>
						</div>
					</div>
					<div class="checklist-header-actions">
						${layouts.groupButton}
						<div class="checklist-action-collapse-btn collapsed" onclick="${this.onCollapseButtonClick.bind(this)}"></div>
					</div>
				</div>
				<div class="checklist-items-wrapper">
					<div class="checklist-items-list" id="subItems_${nodeId}">
						${children}
					</div>
					${layouts.listActionsPanel}
				</div>
			</div>
		`;

		return this.container;
	}

	getCheckListItemLayout(children)
	{
		const nodeId = this.getNodeId();
		const layouts = {
			deleteButton: Tag.render`<button class="checklist-item-remove" onclick="${this.onDeleteClick.bind(this)}"/>`,
			dndButton: Tag.render`<div class="checklist-item-dragndrop"></div>`,
			attachments: this.getAttachmentsLayout(),
		};

		if (!this.checkCanRemove())
		{
			layouts.deleteButton = '';
		}

		if (!this.checkCanDrag())
		{
			layouts.dndButton.style.visibility = 'hidden';
		}

		this.progress = new BX.UI.ProgressRound({
			id: `progress_${nodeId}`,
			value: this.fields.getCompletedCount(),
			maxValue: this.fields.getTotalCount(),
			width: 20,
			lineSize: 3,
			fill: false,
			color: BX.UI.ProgressRound.Color.PRIMARY,
		});

		this.container = Tag.render`
			<div class="checklist-item" id="${nodeId}">
				<div class="checklist-item-inner droppable ${this.fields.getIsComplete() ? 'checklist-item-solved' : ''}"
					 onclick="${this.onInnerContainerClick.bind(this)}">
					${layouts.dndButton}
					<div class="checklist-item-flag-block">
						<div class="checklist-item-flag">
							<label class="checklist-item-flag-element" onclick="${this.onCompleteButtonClick.bind(this)}">
								<span class="checklist-item-flag-sub-checklist-progress" id="progress_${nodeId}">
									${this.progress.getContainer()}
								</span>
								<span class="checklist-item-flag-element-decorate"/>
							</label>
						</div>
					</div>
					<div class="checklist-item-content-block">
						<div class="checklist-item-number">${this.fields.getDisplaySortIndex()}</div>
						${this.fields.getIsImportant() ? this.getImportantLayout() : ''}
						<div class="checklist-item-description">
							${this.getTitleLayout()}
						</div>
					</div>
					<div class="checklist-item-additional-block">
						${layouts.deleteButton}
					</div>
					<div class="checklist-item-actions-block">
						<input class="checklist-item-group-checkbox" id="select_${nodeId}" type="checkbox"
							   onclick="${this.onSelectCheckboxClick.bind(this)}"/>
					</div>
				</div>
				<div class="checklist-item-attachment">
					<div class="checklist-item-attachment-list" id="attachments_${nodeId}">
						${layouts.attachments}
					</div>
				</div>
				<div class="checklist-sublist-items-wrapper" id="subItems_${nodeId}">
					${children}
				</div>
			</div>
		`;

		return this.container;
	}

	getLayout()
	{
		const children = [];

		this.descendants.forEach((descendant) => {
			children.push(descendant.getLayout());
		});

		if (this.isTaskRoot())
		{
			return this.getTaskRootLayout(children);
		}

		if (this.isCheckList())
		{
			const checkListLayout = this.getCheckListLayout(children);

			this.handleCheckListChanges();

			return checkListLayout;
		}

		return this.getCheckListItemLayout(children);
	}
}

export {CheckListItem};