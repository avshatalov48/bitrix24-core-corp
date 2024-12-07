/* eslint-disable no-underscore-dangle,@bitrix24/bitrix24-rules/no-pseudo-private */
import { Tag, Dom, Event, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Button, ButtonSize, ButtonColor } from 'ui.buttons';

export class DeleteSupersetField extends BX.UI.EntityEditorList
{
	#deleteButton: Button;
	#deletePopup: BX.BIConnector.ApacheSupersetCleanPopup;

	static create(id, settings): DashboardOwnerField
	{
		const self = new this(id, settings);
		self.initialize(id, settings);

		return self;
	}

	initialize(id, settings)
	{
		super.initialize(id, settings);
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
			'BICONNECTOR_SUPERSET_SETTINGS_DELETE_SUPERSET_FIELD_HINT',
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
			top.BX.Helper.show('redirect=detail&code=20337242&anchor=Disable');
		});

		Dom.replace(hint.querySelector('link'), link);

		Dom.insertBefore(hint, this._container);

		this._innerWrapper = Tag.render`<div class='ui-entity-editor-content-block ui-ctl-custom'></div>`;
		Dom.append(this._innerWrapper, this._wrapper);
		const deleteButtonBlock = Tag.render`
			<div class="biconnector-superset-delete-superset-button-block"></div>
		`;

		this.#deleteButton = new Button({
			text: Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_DELETE_SUPERSET_FIELD_DELETE_BUTTON'),
			color: ButtonColor.LIGHT_BORDER,
			size: ButtonSize.SMALL,
			onclick: this.deleteSuperset.bind(this),
		});
		this.#deleteButton.renderTo(deleteButtonBlock);

		// Put the clear button into the section header
		EventEmitter.subscribe('BX.UI.EntityEditorSection:onLayout', (event) => {
			if (event.data[1].id === 'DELETE_SUPERSET_SECTION')
			{
				event.data[1].customNodes.push(deleteButtonBlock);
			}
		});

		this.registerLayout(options);
		this._hasLayout = true;
	}

	deleteSuperset()
	{
		this.#deletePopup = new BX.BIConnector.ApacheSupersetCleanPopup({
			onSuccess: () => {
				window.top.location.reload();
			},
			onAccept: () => {
				this.#deleteButton.setClocking();
			},
			onError: () => {
				this.#deleteButton.setClocking(false);
			},
		});

		this.#deletePopup.show();
	}
}
