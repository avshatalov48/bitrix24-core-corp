/* eslint-disable no-underscore-dangle,@bitrix24/bitrix24-rules/no-pseudo-private */
import { Tag, Dom, Event, Loc, Type } from 'main.core';
import { TagSelector } from 'ui.entity-selector';
import 'ui.forms';
import 'ui.buttons';

export class ScopeField extends BX.UI.EntityEditorCustom
{
	#scopes: Set;
	#initialScopes: Set;

	static create(id, settings): DashboardOwnerField
	{
		const self = new this(id, settings);
		self.initialize(id, settings);

		return self;
	}

	initialize(id, settings)
	{
		super.initialize(id, settings);
		this.#scopes = new Set();
		this.#initialScopes = new Set();

		const scopes = this._model.getField(this.getName(), []);
		scopes.forEach((scopeCode) => {
			this.#scopes.add(scopeCode);
			this.#initialScopes.add(scopeCode);
		});
	}

	layout(options: {})
	{
		this.ensureWrapperCreated({ classNames: ['ui-entity-editor-field-text'] });
		this.adjustWrapper();

		const message = Loc.getMessage(
			'BICONNECTOR_SUPERSET_SETTINGS_SCOPE_HINT_LINK',
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

		const preselectedItems = [];
		let hasSelectedAutomatedSolutions = false;
		this.#scopes.forEach((scope) => {
			if (scope.startsWith('automated_solution_'))
			{
				hasSelectedAutomatedSolutions = true;
			}
			preselectedItems.push(['biconnector-superset-scope', scope]);
		});

		const tagSelector = new TagSelector({
			multiple: true,
			dialogOptions: {
				id: 'biconnector-superset-scope',
				context: 'biconnector-superset-scope',
				enableSearch: false,
				dropdownMode: true,
				showAvatars: false,
				compactView: true,
				dynamicLoad: true,
				width: 300,
				height: 250,
				entities: [
					{
						id: 'biconnector-superset-scope',
						dynamicLoad: true,
						options: {},
					},
				],
				preselectedItems,
				events: {
					onLoad: (event) => {
						if (hasSelectedAutomatedSolutions)
						{
							const items = event.getTarget()?.getItems();
							const automatedSolutionItem = items.find((item) => item.getId() === 'automated_solution');
							const itemNode = automatedSolutionItem.getNodes()?.values()?.next()?.value;
							itemNode?.setOpen(true);
						}
					},
				},
			},
			events: {
				onBeforeTagAdd: (event) => {
					const { tag } = event.getData();
					this.#scopes.add(tag.getId());
					this.onChange();
				},
				onBeforeTagRemove: (event) => {
					const { tag } = event.getData();
					this.#scopes.delete(tag.getId());
					this.onChange();
				},
			},
		});

		Dom.addClass(tagSelector.getDialog().getContainer(), 'biconnector-settings-scope-selector');
		tagSelector.renderTo(this._innerWrapper);

		this.registerLayout(options);
		this._hasLayout = true;
	}

	onChange(): void
	{
		if (this.#scopes.size === this.#initialScopes.size)
		{
			for (const item of this.#scopes)
			{
				if (!this.#initialScopes.has(item))
				{
					this.markAsChanged();

					return;
				}
			}
		}
		else
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
			for (const scope of this.#scopes)
			{
				Dom.append(Tag.render`<input type="hidden" name="${this.getName()}[]" value="${scope}">`, saveBlock);
			}
			Dom.append(saveBlock, this._innerWrapper);
		}

		this._model.setField(this.getName(), this.#scopes);
	}
}
