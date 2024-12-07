/* eslint-disable no-underscore-dangle,@bitrix24/bitrix24-rules/no-pseudo-private */
import { Tag, Dom, Event, Loc, Type } from 'main.core';
import { TagSelector } from 'ui.entity-selector';
import 'ui.forms';
import 'ui.buttons';

export class DashboardOwnerField extends BX.UI.EntityEditorCustom
{
	#ownerId: number;
	#initialOwnerId: number;

	static create(id, settings): DashboardOwnerField
	{
		const self = new this(id, settings);
		self.initialize(id, settings);

		return self;
	}

	initialize(id, settings)
	{
		super.initialize(id, settings);
		this.#ownerId = this._model.getIntegerField(this.getName(), null);
		this.#initialOwnerId = this._model.getIntegerField(this.getName(), null);
	}

	layout(options: {})
	{
		this.ensureWrapperCreated({ classNames: ['ui-entity-editor-field-text'] });
		this.adjustWrapper();

		const message = Loc.getMessage(
			'BICONNECTOR_SUPERSET_SETTINGS_OWNER_HINT_LINK',
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
			top.BX.Helper.show('redirect=detail&code=20337242&anchor=DashboardOwner');
		});

		Dom.replace(hint.querySelector('link'), link);

		Dom.insertBefore(hint, this._container);

		this._innerWrapper = Tag.render`<div class='ui-entity-editor-content-block ui-ctl-custom'></div>`;
		Dom.append(this._innerWrapper, this._wrapper);

		const content = Tag.render`	
			<div class="ui-ctl-w100"></div>
		`;

		Dom.append(content, this._innerWrapper);

		const tagSelector = new TagSelector({
			multiple: false,
			dialogOptions: {
				context: 'biconnector--dashboard-owner',
				dropdownMode: true,
				entities: [
					{
						id: 'user',
						options: {
							selectMode: 'usersOnly',
							inviteEmployeeLink: false,
						},
					},
				],
				preselectedItems: [
					['user', this._model.getField(this.getName(), null)],
				],
			},
			events: {
				onBeforeTagAdd: (event) => {
					const { tag } = event.getData();
					this.#ownerId = tag.getId();
					this.onChange();
				},
				onBeforeTagRemove: (event) => {
					this.#ownerId = null;
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
		if (this.#initialOwnerId !== this.#ownerId)
		{
			this.markAsChanged();

			return;
		}

		this._isChanged = false;
	}

	save()
	{
		if (Type.isDomNode(this._innerWrapper))
		{
			const oldSaveBlock = this._innerWrapper.querySelector('.save-block');
			if (Type.isDomNode(oldSaveBlock))
			{
				Dom.remove(oldSaveBlock);
			}

			const saveBlock = Tag.render`<div class="save-block"></div>`;
			Dom.append(Tag.render`<input type="hidden" name="${this.getName()}" value="${this.#ownerId}">`, saveBlock);
			Dom.append(saveBlock, this._innerWrapper);
		}

		this._model.setField(this.getName(), this.#ownerId);
	}
}
