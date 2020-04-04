import {Event, Loc, Tag, Dom, Text, Type, Cache, pos} from 'main.core';
import {PopupWindow, PopupWindowButton, PopupWindowButtonLink} from 'main.popup';
import Column from '../kanban/column';
import Grid from '../kanban/grid';
import Marker from '../marker/marker';
import type CategoryOptions from '../type/category-options';
import type jsDDObject from '../type/jsdd-object';

export class Category extends Event.EventEmitter
{
	static instances: Array<Category> = [];

	static createGrid(options: {
		renderTo: HTMLElement,
		columns: Array<Column>,
		editable?: boolean,
		columnType?: string,
	})
	{
		return new Grid({
			renderTo: options.renderTo,
			canEditColumn: options.editable === true,
			canRemoveColumn: options.editable === true,
			canAddColumn: options.editable === true,
			canSortColumn: options.editable === true,
			columnType: options.columnType || 'BX.Crm.SalesTunnels.Kanban.Column',
			dropzoneType: 'BX.Crm.SalesTunnels.Kanban.DropZone',
			columns: options.columns,
		});
	}

	constructor(options: CategoryOptions)
	{
		super();

		Category.instances.push(this);

		this.renderTo = options.renderTo;
		this.appContainer = options.appContainer;
		this.id = options.id;
		this.name = options.name;
		this.sort = Number.parseInt(options.sort);
		this.default = options.default;
		this.generatorsCount = Number(options.generatorsCount);
		this.stages = options.stages;
		this.robotsSettingsLink = options.robotsSettingsLink.replace('{category}', this.id);
		this.generatorSettingsLink = options.generatorSettingsLink;
		this.cache = new Cache.MemoryCache();
		this.drawed = false;
		this.allowWrite = Boolean(options.allowWrite);
		this.canEditTunnels = Boolean(options.canEditTunnels);
		this.isAvailableGenerator = options.isAvailableGenerator;
		this.showGeneratorRestrictionPopup = options.showGeneratorRestrictionPopup;
		this.isAvailableRobots = options.isAvailableRobots;
		this.showRobotsRestrictionPopup = options.showRobotsRestrictionPopup;

		if (!options.lazy)
		{
			this.draw();
		}

		if (this.generatorsCount > 0)
		{
			this.showGeneratorLinkIcon();
		}

		const dragButton = this.getDragButton();
		dragButton.onbxdragstart = this.onDragStart.bind(this);
		dragButton.onbxdrag = this.onDrag.bind(this);
		dragButton.onbxdragstop = this.onDragStop.bind(this);

		// eslint-disable-next-line
		jsDD.registerObject(dragButton, 40);
		this.adjustRobotsLinkIcon();

		this.getProgressKanban()
			.emitter
			.subscribe('Kanban.Grid:removeColumn', (event) => {
				this.emit('Category:removeStage', event);
			})
			.subscribe('Kanban.Grid:columns:sort', () => {
				setTimeout(() => {
					this.emit('Column:sort', {
						columns: this.getAllColumns(),
					});
				}, 500);
			});

		this.getSuccessKanban()
			.emitter
			.subscribe('Kanban.Grid:removeColumn', (event) => {
				this.emit('Category:removeStage', event);
			})
			.subscribe('Kanban.Grid:columns:sort', () => {
				setTimeout(() => {
					this.emit('Column:sort', {
						columns: this.getAllColumns(),
					});
				}, 500);
			});

		this.getFailKanban()
			.emitter
			.subscribe('Kanban.Grid:removeColumn', (event) => {
				this.emit('Category:removeStage', event);
			})
			.subscribe('Kanban.Grid:columns:sort', () => {
				setTimeout(() => {
					this.emit('Column:sort', {
						columns: this.getAllColumns(),
					});
				}, 500);
			});
	}

	hasTunnels(): boolean
	{
		return this.getAllColumns()
			.some(column => column.marker.links.size > 0);
	}

	getRectArea(): DOMRect | {middle: number}
	{
		return this.cache.remember('rectArea', () => {
			const rectArea = pos(this.getContainer());
			rectArea.middle = rectArea.top + rectArea.height / 2;

			return rectArea;
		});
	}

	getIndex(): number | void
	{
		return [...this.getContainer().parentNode.querySelectorAll('.crm-st-category')]
			.findIndex(item => item === this.getContainer());
	}

	getNextCategorySibling(): ?Category
	{
		return Category.instances.find((category, index) => index > this.getIndex()) || null;
	}

	/** @private */
	onDragStart()
	{
		Dom.addClass(this.getContainer(), 'crm-st-category-drag');
		Marker.removeAllLinks();

		// eslint-disable-next-line
		this.dragOffset = jsDD.start_y - this.getRectArea().top;
		this.dragIndex = this.getIndex();
		this.dragTargetCategory = this.dragTargetCategory || this;
	}

	/** @private */
	onDrag(x, y)
	{
		Tag.style(this.getContainer())`
			transform: translate3d(0px, ${y - this.dragOffset - this.getRectArea().top}px, 0px);
		`;

		const categoryHeight = this.getRectArea().height;

		Category.instances.forEach((category, curIndex) => {
			if (
				category === this
				|| Dom.hasClass(category.getContainer(), 'crm-st-category-stub')
			)
			{
				return;
			}

			const categoryContainer = category.getContainer();
			const categoryRectArea = category.getRectArea();
			const categoryMiddle = categoryRectArea.middle;

			if (
				y > categoryMiddle
				&& curIndex > this.dragIndex
				&& categoryContainer.style.transform !== `translate3d(0px, ${(-categoryHeight)}px, 0px)`
			)
			{
				Tag.style(categoryContainer)`
					transition: 200ms;
					transform: translate3d(0px, ${(-categoryHeight)}px, 0px);
				`;

				this.dragTargetCategory = category.getNextCategorySibling();

				category.cache.delete('rectArea');
			}

			if (
				y < categoryMiddle
				&& curIndex < this.dragIndex
				&& categoryContainer.style.transform !== `translate3d(0px, ${categoryHeight}px, 0px)`
			)
			{
				Tag.style(categoryContainer)`
					transition: 200ms;
					transform: translate3d(0px, ${(categoryHeight)}px, 0px);
				`;

				this.dragTargetCategory = category;

				category.cache.delete('rectArea');
			}

			const moveBackTop = (
				y < categoryMiddle
				&& curIndex > this.dragIndex
				&& categoryContainer.style.transform !== ''
				&& categoryContainer.style.transform !== 'translate3d(0, 0, 0)'
			);

			const moveBackBottom = (
				y > categoryMiddle
				&& curIndex < this.dragIndex
				&& categoryContainer.style.transform !== ''
				&& categoryContainer.style.transform !== 'translate3d(0, 0, 0)'
			);

			if (moveBackBottom || moveBackTop)
			{
				Tag.style(categoryContainer)`
					transition: 200ms;
					transform: translate3d(0px, 0px, 0px);
				`;

				this.dragTargetCategory = category;

				if (
					!moveBackTop
					&& Dom.hasClass(category.getNextCategorySibling(), 'crm-st-category-stub')
				)
				{
					this.dragTargetCategory = category.getNextCategorySibling();
				}

				category.cache.delete('rectArea');
			}
		});
	}

	/** @private */
	onDragStop()
	{
		Dom.removeClass(this.getContainer(), 'crm-st-category-drag');

		requestAnimationFrame(() => {
			Marker.restoreAllLinks();
		});

		Category.instances.forEach((category) => {
			Tag.style(category.getContainer())`
				transform: null;
				transition: null;
			`;

			category.cache.delete('rectArea');
		});

		if (this.dragTargetCategory)
		{
			Dom.insertBefore(this.getContainer(), this.dragTargetCategory.getContainer());
		}
		else
		{
			Dom.append(this.getContainer(), this.getContainer().parentElement);
		}

		const before = Category.instances.map(item => item.getIndex());
		Category.instances.sort((a, b) => (
			a.getIndex() > b.getIndex() ? 1 : -1
		));
		const after = Category.instances.map(item => item.getIndex());

		if (JSON.stringify(before) !== JSON.stringify(after))
		{
			this.emit('Category:sort');
		}
	}

	getContainer(): HTMLDivElement
	{
		return this.cache.remember('container', () => (
			Tag.render`
				<div class="crm-st-category" data-id="${this.id}">
					<div class="crm-st-category-action">
						${this.getDragButton()}
					</div>
					<div class="crm-st-category-info">
						${this.getTitleContainer()}
						<div class="crm-st-category-info-links">
							<div class="crm-st-category-info-links-item">
								${this.getRobotsLink()}
								${this.getRobotsHelpLink()}
							</div>
							<div class="crm-st-category-info-links-item">
								${this.getGeneratorLink()}
								${this.getGeneratorHelpLink()}
							</div>
						</div>
					</div>
					<div class="crm-st-category-stages">
						${this.getProgressContainer()}
						${this.getSuccessContainer()}
						${this.getFailContainer()}
					</div>
				</div>
			`
		));
	}

	getRobotsHelpLink()
	{
		return this.cache.remember('robotsHelpLink', () => {
			const onClick = () => {
				if (window.top.BX.Helper)
				{
					window.top.BX.Helper.show('redirect=detail&code=6908975');
				}
			};

			return Tag.render`
				<span 
					class="crm-st-category-info-links-help" 
					onclick="${onClick}"
					title="${Text.encode(Loc.getMessage('CRM_ST_ROBOTS_HELP_BUTTON'))}"
					> </span>
			`;
		});
	}

	getGeneratorHelpLink()
	{
		return this.cache.remember('generatorHelpLink', () => {
			const onClick = () => {
				if (window.top.BX.Helper)
				{
					window.top.BX.Helper.show('redirect=detail&code=7530721');
				}
			};

			return Tag.render`
				<span 
					class="crm-st-category-info-links-help" 
					onclick="${onClick}"
					title="${Text.encode(Loc.getMessage('CRM_ST_GENERATOR_HELP_BUTTON'))}"
					> </span>
			`;
		});
	}

	getProgressContainer(): HTMLDivElement
	{
		return this.cache.remember('progressContainer', () => (
			Tag.render`
				<div class="crm-st-category-stages-group crm-st-category-stages-group-in-progress">
					<div class="crm-st-category-stages-group-header">
						<span class="crm-st-category-stages-group-header-text">
							${Loc.getMessage('CRM_ST_STAGES_GROUP_IN_PROGRESS')}
						</span>
					</div>
					${this.getProgressStagesContainer()}
				</div>
			`
		));
	}

	getProgressStagesContainer(): HTMLDivElement
	{
		return this.cache.remember('progressStagesContainer', () => (
			Tag.render`
				<div class="crm-st-category-stages-list"></div>
			`
		));
	}

	getProgressKanban(): Grid
	{
		return this.cache.remember('progressKanban', () => (
			Category.createGrid({
				renderTo: this.getProgressStagesContainer(),
				editable: this.canEditTunnels,
				columns: this.stages.P.map(stage => (
					new Column({
						id: stage.STATUS_ID,
						name: stage.NAME,
						color: stage.COLOR.replace('#', ''),
						data: this.getColumnData(stage),
					})
				)),
			})
		));
	}

	getSuccessContainer(): HTMLDivElement
	{
		return this.cache.remember('successContainer', () => (
			Tag.render`
				<div class="crm-st-category-stages-group crm-st-category-stages-group-success">
					<div class="crm-st-category-stages-group-header">
						<span class="crm-st-category-stages-group-in-success"> </span> 
						<span class="crm-st-category-stages-group-header-text">
							${Loc.getMessage('CRM_ST_STAGES_GROUP_SUCCESS')}
						</span>
					</div>
					${this.getSuccessStagesContainer()}
				</div>
			`
		));
	}

	getSuccessStagesContainer(): HTMLDivElement
	{
		return this.cache.remember('successStagesContainer', () => (
			Tag.render`
				<div class="crm-st-category-stages-list"></div>
			`
		));
	}

	getSuccessKanban(): Grid
	{
		return this.cache.remember('successKanban', () => (
			Category.createGrid({
				renderTo: this.getSuccessStagesContainer(),
				canEditColumn: this.canEditTunnels,
				editable: this.canEditTunnels,
				canRemoveColumn: this.allowWrite,
				columns: this.stages.S.map(stage => (
					new Column({
						id: stage.STATUS_ID,
						name: stage.NAME,
						color: stage.COLOR.replace('#', ''),
						data: this.getColumnData(stage),
						canRemove: false,
						canSort: false,
					})
				)),
			})
		));
	}

	getFailContainer(): HTMLDivElement
	{
		return this.cache.remember('failContainer', () => (
			Tag.render`
				<div class="crm-st-category-stages-group crm-st-category-stages-group-fail">
					<div class="crm-st-category-stages-group-header">
						<span class="crm-st-category-stages-group-in-fail"> </span> 
						<span class="crm-st-category-stages-group-header-text">
							${Loc.getMessage('CRM_ST_STAGES_GROUP_FAIL')}
						</span>
					</div>
					${this.getFailStagesContainer()}
				</div>
			`
		));
	}

	getFailStagesContainer(): HTMLDivElement
	{
		return this.cache.remember('failStagesContainer', () => (
			Tag.render`
				<div class="crm-st-category-stages-list"></div>
			`
		));
	}

	getFailKanban(): Grid
	{
		return this.cache.remember('failKanban', () => (
			Category.createGrid({
				renderTo: this.getFailStagesContainer(),
				editable: this.canEditTunnels,
				canEditColumn: this.canEditTunnels,
				canRemoveColumn: this.canEditTunnels,
				columns: this.stages.F.map(stage => (
					new Column({
						id: stage.STATUS_ID,
						name: stage.NAME,
						color: stage.COLOR.replace('#', ''),
						data: this.getColumnData(stage),
					})
				)),
			})
		));
	}

	getColumnData(stage)
	{
		return {
			stageId: stage.ID,
			entityId: stage.ENTITY_ID,
			stage,
			onLink: (link) => {
				this.emit('Column:link', link);
				this.adjustRobotsLinkIcon();
			},
			onRemoveLinkFrom: (link) => {
				this.emit('Column:removeLinkFrom', link);
				this.adjustRobotsLinkIcon();
			},
			onEditLink: (link) => {
				this.emit('Column:editLink', link);
				this.adjustRobotsLinkIcon();
			},
			onNameChange: (column) => {
				this.emit('Column:nameChange', {column});
			},
			onColorChange: (column) => {
				this.emit('Column:colorChange', {column});
			},
			onAddColumn: (column) => {
				this.emit('Column:addColumn', {column});
			},
			onRemove: (event) => {
				this.emit('Column:remove', event);
			},
			onChange: (column) => {
				this.emit('Column:change', {column});
			},
			onSort: () => {
				this.emit('Column:sort', {
					columns: this.getAllColumns(),
				});
			},
			onError: (event) => {
				this.emit('Column:error', event);
			},
			category: this,
			appContainer: this.appContainer,
			categoryContainer: this.getFailContainer(),
			stagesGroups: {
				progressStagesGroup: this.getProgressContainer(),
				successStagesGroup: this.getSuccessContainer(),
				failStagesGroup: this.getFailContainer(),
			},
			currentStageGroup: this.getFailContainer(),
			categoryName: this.getTitle().innerText,
			canEditTunnels: this.canEditTunnels,
		};
	}

	getRobotsLink(): HTMLSpanElement
	{
		return this.cache.remember('robotsLink', () => {
			const onClick = this.onRobotsLinkClick.bind(this);

			return Tag.render`
				${!this.isAvailableRobots ? ' <span class="tariff-lock"></span>' : ''}
				<span class="crm-st-category-info-links-link crm-st-robots-link" onclick="${onClick}">
					${Loc.getMessage('CRM_ST_ROBOT_SETTINGS_LINK_LABEL')}
				</span>
			`;
		});
	}

	/** @private */
	onRobotsLinkClick(event)
	{
		event.preventDefault();

		if (!this.isAvailableRobots)
		{
			this.showRobotsRestrictionPopup();
		}
		else
		{
			// eslint-disable-next-line
			BX.SidePanel.Instance.open(
				this.robotsSettingsLink,
				{
					cacheable: false,
					events: {
						onClose: () => {
							this.emit('Category:slider:close');
							this.emit('Category:slider:robots:close');
						},
					},
				},
			);
		}
	}

	getGeneratorLink(): HTMLSpanElement
	{
		return this.cache.remember('generatorLink', () => {
			const onClick = this.onGeneratorLinkClick.bind(this);

			return Tag.render`
				${!this.isAvailableGenerator ? ' <span class="tariff-lock"></span>' : ''}
				<span class="crm-st-category-info-links-link crm-st-generator-link" onclick="${onClick}">
					${Loc.getMessage('CRM_ST_GENERATOR_SETTINGS_LINK_LABEL')}
				</span>
			`;
		});
	}

	/** @private */
	onGeneratorLinkClick(event)
	{
		event.preventDefault();

		if (!this.isAvailableGenerator)
		{
			this.showGeneratorRestrictionPopup();
		}
		else
		{
			// eslint-disable-next-line
			BX.SidePanel.Instance.open(
				this.generatorSettingsLink,
				{
					cacheable: false,
					events: {
						onClose: () => {
							this.emit('Category:slider:close');
							this.emit('Category:slider:generator:close', {category: this});
						},
					},
				},
			);
		}
	}

	getEditButton(): HTMLSpanElement
	{
		return this.cache.remember('editButton', () => (
			Tag.render`
				<span 
					class="crm-st-edit-button" 
					onmousedown="${this.onEditButtonClick.bind(this)}"
					title="${Loc.getMessage('CRM_ST_EDIT_CATEGORY_TITLE')}"
					> </span>
			`
		));
	}

	activateEditButton()
	{
		Dom.addClass(this.getEditButton(), 'crm-st-edit-button-active');
	}

	deactivateEditButton()
	{
		Dom.removeClass(this.getEditButton(), 'crm-st-edit-button-active');
	}

	onEditButtonClick(event?: MouseEvent)
	{
		if (event)
		{
			event.preventDefault();
		}

		if (this.isTitleEditEnabled())
		{
			this.disableTitleEdit();
			this.saveTitle();
			return;
		}

		this.enableTitleEdit();
	}

	showTitleEditor(value: ?string = null)
	{
		const titleEditor = this.getTitleEditor();
		const {innerText} = this.getTitle();

		titleEditor.value = Type.isString(value) ? value : Text.decode(innerText);

		Tag.style(titleEditor)`
			display: block;
		`;
	}

	hideTitleEditor()
	{
		const titleEditor = this.getTitleEditor();

		Tag.style(titleEditor)`
			display: null;
		`;
	}

	focusOnTitleEditor()
	{
		const titleEditor = this.getTitleEditor();
		titleEditor.focus();

		const title = this.getTitle();
		const titleLength = title.innerText.length;
		titleEditor.setSelectionRange(titleLength, titleLength);
	}

	showTitle()
	{
		Tag.style(this.getTitle())`
			display: null;
		`;
	}

	hideTitle()
	{
		Tag.style(this.getTitle())`
			display: none;
		`;
	}

	saveTitle()
	{
		const title = this.getTitle();
		const titleEditor = this.getTitleEditor();
		const {value} = titleEditor;
		const safeValue = Text.encode(value.trim()) || Loc.getMessage('CRM_ST_TITLE_EDITOR_PLACEHOLDER');

		if (title.innerHTML !== safeValue)
		{
			title.innerHTML = safeValue;

			Tag.attrs(title)`
				title: ${value.trim()};
			`;

			this.emit('Category:title:save', {categoryId: this.id, value: safeValue});
		}
	}

	enableTitleEdit(value = null)
	{
		this.hideTitle();
		this.showTitleEditor(value);
		this.activateEditButton();
		this.focusOnTitleEditor();
	}

	disableTitleEdit()
	{
		this.showTitle();
		this.hideTitleEditor();
		this.deactivateEditButton();
	}

	isTitleEditEnabled(): boolean
	{
		return Dom.hasClass(this.getEditButton(), 'crm-st-edit-button-active');
	}

	getRemoveButton(): HTMLSpanElement
	{
		return this.cache.remember('removeButton', () => {
			const button = Tag.render`
				<span 
					class="crm-st-remove-button" 
					onclick="${this.onRemoveButtonClick.bind(this)}" 
					title="${Loc.getMessage('CRM_ST_REMOVE_CATEGORY')}"
					> </span>
			`;

			if (String(this.id) === '0')
			{
				Tag.style(button)`
					display: none;
				`;
			}

			return button;
		});
	}

	onRemoveButtonClick()
	{
		this.showConfirmRemovePopup()
			.then(({confirm}) => {
				if (confirm)
				{
					this.emit('Category:remove', {
						categoryId: this.id,
						onConfirm: () => {
							this.remove();
						},
						onCancel: () => {
							this.removeBlur();
						},
					});

					return;
				}

				this.removeBlur();
			});

		this.addBlur();
	}

	getAllColumns()
	{
		const progressColumns = this.getProgressKanban()
			.getColumns()
			.sort((a, b) => (a.getIndex() > b.getIndex() ? 1 : -1));
		const successColumn = this.getSuccessKanban()
			.getColumns()
			.sort((a, b) => (a.getIndex() > b.getIndex() ? 1 : -1));
		const failColumns = this.getFailKanban()
			.getColumns()
			.sort((a, b) => (a.getIndex() > b.getIndex() ? 1 : -1));

		return [
			...progressColumns,
			...successColumn,
			...failColumns,
		];
	}

	addBlur()
	{
		Dom.addClass(this.getContainer(), 'crm-st-blur');

		this.getAllColumns().forEach((column) => {
			column.marker.blurLinks();
		});
	}

	removeBlur()
	{
		Dom.removeClass(this.getContainer(), 'crm-st-blur');
		Marker.unblurLinks();
	}

	remove()
	{
		Dom.remove(this.getContainer());

		Marker.getAllLinks()
			.forEach((link) => {
				const columnFrom = link.from.data.column;
				const categoryFrom = columnFrom.getData().category;
				const columnTo = link.to.data.column;
				const categoryTo = columnTo.getData().category;

				if (String(categoryFrom.id) === String(this.id))
				{
					link.from.removeLink(link);
				}

				if (String(categoryTo.id) === String(this.id))
				{
					link.to.removeLink(link);
				}
			});

		Marker.getAllStubLinks()
			.forEach((link) => {
				const columnFrom = link.from.data.column;
				const categoryFrom = columnFrom.getData().category;
				const columnTo = link.to.data.column;
				const categoryTo = columnTo.getData().category;

				if (String(categoryFrom.id) === String(this.id))
				{
					link.from.removeStubLink(link);
				}

				if (String(categoryTo.id) === String(this.id))
				{
					link.to.removeStubLink(link);
				}
			});

		Category.instances = Category.instances.filter(item => item !== this);
	}

	getTitle(): HTMLHeadingElement
	{
		return this.cache.remember('title', () => (
			Tag.render`
				<h3 class="crm-st-category-info-title" title="${this.name}">${this.name}</h3>
			`
		));
	}

	getTitleEditor(): HTMLInputElement
	{
		return this.cache.remember('titleEditor', () => {
			const onKeyDown = this.onTitleEditorKeyDown.bind(this);
			const onBlur = this.onTitleEditorBlur.bind(this);
			return Tag.render`
				<input class="crm-st-category-info-title-editor" 
					 onkeydown="${onKeyDown}"
					 onblur="${onBlur}"
					 value="${this.name}"
					 placeholder="${Loc.getMessage('CRM_ST_TITLE_EDITOR_PLACEHOLDER')}"
				 >
			`;
		});
	}

	onTitleEditorKeyDown(event: KeyboardEvent | UIEvent)
	{
		event.stopPropagation();

		if (this.isTitleEditEnabled())
		{
			if (event.key.startsWith('Enter'))
			{
				this.onEditButtonClick();
			}

			if (event.key.startsWith('Esc'))
			{
				this.disableTitleEdit();
			}
		}
	}

	onTitleEditorBlur()
	{
		if (this.isTitleEditEnabled())
		{
			this.onEditButtonClick();
		}
	}

	getActionsButtons(): HTMLDivElement
	{
		return this.cache.remember('getActionsButtons', () => (
			Tag.render`
				<div class="crm-st-category-action-buttons">
					${this.canEditTunnels ? this.getEditButton() : ''}
					${this.canEditTunnels ? this.getRemoveButton() : ''}
				</div>
			`
		));
	}

	getTitleContainer(): HTMLDivElement
	{
		return this.cache.remember('titleContainer', () => (
			Tag.render`
				<div class="crm-st-category-info-title-container">
					${this.getTitle()}
					${this.getTitleEditor()}
					${this.getActionsButtons()}
				</div>
			`
		));
	}

	getDragButton(): HTMLSpanElement | jsDDObject
	{
		return this.cache.remember('dragButton', () => (
			Tag.render`
				<span 
					class="crm-st-category-action-drag"
					title="${Loc.getMessage('CRM_ST_CATEGORY_DRAG_BUTTON')}"
					>&nbsp;</span>
			`
		));
	}

	isDrawed()
	{
		return this.drawed;
	}

	draw()
	{
		if (!this.isDrawed())
		{
			this.drawed = true;
			Dom.append(this.getContainer(), this.renderTo);

			this.getProgressKanban().draw();
			this.getSuccessKanban().draw();
			this.getFailKanban().draw();
		}
	}

	getKanbanColumn(columnId)
	{
		const columns = [
			...this.getProgressKanban().getColumns(),
			...this.getSuccessKanban().getColumns(),
			...this.getFailKanban().getColumns(),
		];

		return columns.find(column => (
			columnId === column.getId() || columnId === column.getData().statusId
		));
	}

	showConfirmRemovePopup(): Promise<{confirm: boolean}>
	{
		return new Promise((resolve) => {
			void (new PopupWindow({
				width: 400,
				overlay: {
					opacity: 30,
				},
				titleBar: Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_POPUP_TITLE')
					.replace('#name#', this.getTitle().innerText),
				content: Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_POPUP_DESCRIPTION'),
				buttons: [
					new PopupWindowButton({
						text: Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_REMOVE_BUTTON_LABEL'),
						className: 'popup-window-button-decline',
						events: {
							click() {
								resolve({confirm: true});
								this.popupWindow.destroy();
							},
						},
					}),
					new PopupWindowButtonLink({
						text: Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_CANCEL_BUTTON_LABEL'),
						events: {
							click() {
								resolve({confirm: false});
								this.popupWindow.destroy();
							},
						},
					}),
				],
			})).show();
		});
	}

	getRobotsLinkIcon(): HTMLSpanElement
	{
		return this.cache.remember('robotsLinkIcon', () => (
			Tag.render`
				<span class="crm-st-robots-link-icon"> </span>
			`
		));
	}

	showRobotsLinkIcon()
	{
		Dom.insertAfter(this.getRobotsLinkIcon(), this.getRobotsLink());
	}

	hideRobotsLinkIcon()
	{
		Dom.remove(this.getRobotsLinkIcon());
	}

	adjustRobotsLinkIcon()
	{
		setTimeout(() => {
			if (this.hasTunnels())
			{
				this.showRobotsLinkIcon();
				return;
			}

			this.hideRobotsLinkIcon();
		});
	}

	getGeneratorLinkIcon(): HTMLSpanElement
	{
		return this.cache.remember('generatorLinkIcon', () => {
			const onClick = () => window.top.open('/marketing/rc/');
			return Tag.render`
				<span class="crm-st-generator-link-icon" onclick="${onClick}">${this.generatorsCount}</span>
			`;
		});
	}

	showGeneratorLinkIcon()
	{
		Dom.insertAfter(this.getGeneratorLinkIcon(), this.getGeneratorLink());
	}
}