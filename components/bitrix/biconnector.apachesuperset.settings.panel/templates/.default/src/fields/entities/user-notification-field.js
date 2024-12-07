import { Tag, Dom, Event, Loc, Type, Text } from 'main.core';
import { TagSelector } from 'ui.entity-selector';
import 'ui.forms';
import 'ui.buttons';

export class UserNotificationField extends BX.UI.EntityEditorCustom
{
	keyInput: HTMLElement;
	eyeButton: HTMLElement;
	refreshButton: HTMLElement;
	refreshKeyLock: boolean = false;
	#values: Set;
	#currentValues: Set;

	static create(id, settings)
	{
		const self = new this(id, settings);
		self.initialize(id, settings);

		return self;
	}

	initialize(id, settings)
	{
		super.initialize(id, settings);
		this.#values = new Set();
		this.#currentValues = new Set();

		this._model
			.getField(this.getName(), [])
			.forEach((id) => {
				id = Text.toNumber(id);
				this.#values.add(id);
				this.#currentValues.add(id);
			})
		;
	}

	createTitleNode(): HTMLElement
	{
		return Tag.render`<span></span>`;
	}

	layout(options: {}): void
	{
		this.ensureWrapperCreated({ classNames: ['ui-entity-editor-field-text'] });
		this.adjustWrapper();

		const message = Loc.getMessage(
			'BICONNECTOR_SUPERSET_SETTINGS_NEW_DASHBOARD_NOTIFICATION_HINT_LINK',
			{
				'#HINT_LINK#': '<link></link>',
			},
		);

		const hint = Tag.render`
			<div class="biconnector-superset-settings-panel-range__hint">
				${message}
			</div>
		`;

		const link = Tag.render`
			<a class="biconnector-superset-settings-panel-range__hint-link">
				${Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DASHBOARD_HINT_LINK')}
			</a>
		`;

		Event.bind(link, 'click', () => {
			top.BX.Helper.show('redirect=detail&code=20337242&anchor=UpdateNotification');
		});

		Dom.replace(hint.querySelector('link'), link);

		Dom.insertBefore(hint, this._container);

		this._innerWrapper = Tag.render`<div class='ui-entity-editor-content-block ui-ctl-custom biconnector-superset-settings-panel-key-info-container'></div>`;
		Dom.append(this._innerWrapper, this._wrapper);

		const content = Tag.render`	
			<div class="ui-ctl-w100"></div>
		`;

		Dom.append(content, this._innerWrapper);

		const preselectedItems = [];
		this.#values.forEach((id) => {
			preselectedItems.push(['user', id]);
		});

		const tagSelector = new TagSelector({
			dialogOptions: {
				context: 'biconnector--new-dashboard-notify',
				entities: [
					{
						id: 'user',
						options: {
							selectMode: 'usersOnly',
						},
					},
				],
				preselectedItems,
			},
			events: {
				onBeforeTagAdd: (event) => {
					const { tag } = event.getData();
					this.#values.add(tag.getId());

					this.onChange();
				},
				onBeforeTagRemove: (event) => {
					const { tag } = event.getData();
					this.#values.delete(tag.getId());

					this.onChange();
				},
			},
		});

		tagSelector.renderTo(content);

		Dom.addClass(tagSelector.getOuterContainer(), 'ui-ctl-element');

		this.registerLayout(options);
		this._hasLayout = true;
	}

	onChange(): void
	{
		if (this.#currentValues.size !== this.#values.size)
		{
			this.markAsChanged();

			return;
		}

		this._isChanged = false;

		this.#values.forEach((id: number) => {
			if (!this.#currentValues.has(id))
			{
				this.markAsChanged();
			}
		});
	}

	save(): void
	{
		const values = [];

		if (Type.isDomNode(this._innerWrapper))
		{
			const oldSaveBlock = this._innerWrapper.querySelector('.save-block');
			if (Type.isDomNode(oldSaveBlock))
			{
				Dom.remove(oldSaveBlock);
			}

			const saveBlock = Tag.render`<div class="save-block"></div>`;
			this.#values.forEach((id) => {
				values.push(id);

				Dom.append(Tag.render`<input type="hidden" name="${this.getName()}[]" value="${id}">`, saveBlock);
			});
			Dom.append(saveBlock, this._innerWrapper);
		}

		this._model.setField(this.getName(), values);
	}
}
