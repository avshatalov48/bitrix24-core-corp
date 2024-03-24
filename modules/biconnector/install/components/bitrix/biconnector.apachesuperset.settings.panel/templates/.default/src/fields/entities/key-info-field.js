import { Text, Tag, Dom, Event, Type, Loc, ajax } from 'main.core';
import { UI } from 'ui.notification';
import 'ui.forms';
import 'ui.buttons';

export class KeyInfoField extends BX.UI.EntityEditorCustom
{
	keyInput: HTMLElement;
	eyeButton: HTMLElement;
	refreshButton: HTMLElement;
	refreshKeyLock: boolean = false;

	static create(id, settings)
	{
		const self = new this(id, settings);
		self.initialize(id, settings);

		return self;
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
			'BICONNECTOR_SUPERSET_SETTINGS_COMMON_KEY_FIELD_HINT_LINK',
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
			top.BX.Helper.show('redirect=detail&code=19123608');
		});

		Dom.replace(hint.querySelector('link'), link);

		Dom.insertBefore(hint, this._container);

		this._innerWrapper = Tag.render`<div class='ui-entity-editor-content-block ui-ctl-custom biconnector-superset-settings-panel-key-info-container'></div>`;
		Dom.append(this._innerWrapper, this._wrapper);

		const value = Text.encode(this.getValue());
		this.keyInput = Tag.render`
			<input type="password" class="ui-ctl-element" readonly value="${value}">
		`;

		this.eyeButton = Tag.render`
			<button class="ui-btn-link ui-btn">
				<span class="ui-icon-set --crossed-eye"></span>
			</button>
		`;
		Event.bind(this.eyeButton, 'click', this.toggleKey.bind(this));

		const copyButton = Tag.render`
			<button class="ui-btn-link ui-btn">
				<span class="ui-icon-set --copy-plates"></span>
			</button>
		`;
		Event.bind(copyButton, 'click', this.copyText.bind(this));

		const content = Tag.render`	
			<div class="ui-ctl ui-ctl__combined-input ui-ctl-w100">
				<div class="ui-ctl-icon__set ui-ctl-after">
					${this.eyeButton}
					${copyButton}
				</div>
				${this.keyInput}
			</div>
		`;

		Dom.append(content, this._innerWrapper);

		this.refreshButton = Tag.render`
			<button class="ui-btn-primary ui-btn icon-set-element">
					<div class="ui-icon-set --refresh-7"></div>
					<div class="icon-set-element__class">
						${Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_KEY_FIELD_REFRESH_BUTTON')}
					</div>
			</button>
		`;
		Event.bind(this.refreshButton, 'click', this.refreshKey.bind(this));

		Dom.append(this.refreshButton, this._innerWrapper);

		this.registerLayout(options);
		this._hasLayout = true;
	}

	toggleKey(event: Event): void
	{
		if (!Type.isDomNode(this.keyInput))
		{
			return;
		}

		const eye = this.eyeButton.querySelector('span');
		if (this.keyInput.type === 'password')
		{
			this.keyInput.type = 'text';
			Dom.removeClass(eye, '--crossed-eye');
			Dom.addClass(eye, '--opened-eye');
		}
		else
		{
			this.keyInput.type = 'password';
			Dom.removeClass(eye, '--opened-eye');
			Dom.addClass(eye, '--crossed-eye');
		}
	}

	copyText(event): void
	{
		if (!Type.isDomNode(this.keyInput))
		{
			return;
		}

		BX.clipboard.copy(this.getValue());

		UI.Notification.Center.notify({
			content: Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_KEY_COPIED'),
			autoHideDelay: 2000,
		});
	}

	refreshKey(): void
	{
		if (this.refreshKeyLock)
		{
			return;
		}

		this.refreshKeyLock = true;
		Dom.addClass(this.refreshButton, 'ui-btn-disabled');

		ajax
			.runComponentAction(
				'bitrix:biconnector.apachesuperset.setting',
				'changeBiToken',
				{	mode: 'class' },
			)
			.then(
				(response) => {
					const generatedKey = response.data;
					if (Type.isStringFilled(generatedKey))
					{
						this.keyInput.value = Text.encode(generatedKey);
						UI.Notification.Center.notify({
							content: Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_KEY_UPDATE_SUCCESS'),
							autoHideDelay: 2000,
						});
					}
					else
					{
						UI.Notification.Center.notify({
							content: Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_KEY_UPDATE_FAILED'),
							autoHideDelay: 2000,
						});
					}

					this.refreshKeyLock = false;
					Dom.removeClass(this.refreshButton, 'ui-btn-disabled');
				},
			);
	}
}
