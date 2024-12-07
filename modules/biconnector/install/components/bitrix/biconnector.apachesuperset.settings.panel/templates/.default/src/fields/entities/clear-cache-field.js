/* eslint-disable no-underscore-dangle,@bitrix24/bitrix24-rules/no-pseudo-private */
import { ajax as Ajax, Tag, Dom, Event, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Button, ButtonState, ButtonSize, ButtonColor } from 'ui.buttons';
import { Countdown } from 'ui.countdown';
import { UI } from 'ui.notification';
import 'ui.forms';

export class ClearCacheField extends BX.UI.EntityEditorCustom
{
	#clearCacheButton: Button;
	#canClearCache: boolean = true;
	#clearTimeout: number = 0;

	static create(id, settings): ClearCacheField
	{
		const self = new this(id, settings);
		self.initialize(id, settings);

		return self;
	}

	initialize(id, settings)
	{
		super.initialize(id, settings);

		const fieldSettings = settings.model.getData();
		this.#canClearCache = fieldSettings.canClearCache;
		this.#clearTimeout = parseInt(fieldSettings.clearCacheTimeout, 10);
		if (!this.#canClearCache)
		{
			this.#initCacheTimer();
		}
	}

	#initCacheTimer()
	{
		const timerContainer = Tag.render`
			<div class="biconnector-cache-container"></div>
		`;

		const timerProps = {
			seconds: this.#clearTimeout,
			node: timerContainer,
			onTimerEnd: () => {
				this.#canClearCache = true;
				this.#clearCacheButton.setDisabled(false);
			},
			onTimerUpdate: (data) => {
				this.#updateHintTimer(data);
			},
		};
		new Countdown(timerProps);
	}

	#updateHintTimer(data)
	{
		this.#clearTimeout = data.seconds;
	}

	#clearCache(): Promise
	{
		if (!this.#canClearCache)
		{
			return new Promise((resolve) => {
				resolve();
			});
		}

		this.#clearCacheButton.setDisabled();
		this.#canClearCache = false;

		return Ajax.runAction('biconnector.superset.clearCache')
			.then((response) => {
				UI.Notification.Center.notify({
					content: Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_CLEAR_CACHE_SUCCESS'),
					autoHideDelay: 2000,
				});
				this.#clearTimeout = response.data.timeoutToNextClearCache;
				this.#initCacheTimer();
			})
			.catch(() => {
				UI.Notification.Center.notify({
					content: Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_CLEAR_CACHE_ERROR'),
					autoHideDelay: 2000,
				});
				this.#clearCacheButton.setDisabled(false);
				this.#canClearCache = true;
			})
		;
	}

	layout(options: {})
	{
		this.ensureWrapperCreated({ classNames: ['ui-entity-editor-field-text'] });
		this.adjustWrapper();

		const message = Loc.getMessage(
			'BICONNECTOR_SUPERSET_SETTINGS_CLEAR_CACHE_HINT_LINK',
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
			top.BX.Helper.show('redirect=detail&code=21000502');
		});

		Dom.replace(hint.querySelector('link'), link);
		Dom.insertBefore(hint, this._container);

		this._innerWrapper = Tag.render`<div class='ui-entity-editor-content-block ui-ctl-custom'></div>`;
		Dom.append(this._innerWrapper, this._wrapper);

		this.#initClearCacheButton();

		this.registerLayout(options);
		this._hasLayout = true;
	}

	#initClearCacheButton(): Button
	{
		const buttonContainer = Tag.render`<div></div>`;
		this.#clearCacheButton = new Button({
			text: Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_CLEAR_CACHE_BUTTON'),
			color: ButtonColor.LIGHT_BORDER,
			size: ButtonSize.SMALL,
			onclick: this.#clearCache.bind(this),
			state: this.#canClearCache ? null : ButtonState.DISABLED,
		});
		this.#clearCacheButton.renderTo(buttonContainer);

		// Put the clear button into the section header
		EventEmitter.subscribe('BX.UI.EntityEditorSection:onLayout', (event) => {
			if (event.data[1].id === 'CLEAR_CACHE_SECTION')
			{
				event.data[1].customNodes.push(buttonContainer);
			}
		});

		const node = this.#clearCacheButton.button;
		const hint = BX.UI.Hint.createInstance({
			popupParameters: {
				offsetLeft: -60,
				angle: {
					offset: 160,
				},
			},
		});

		Event.bind(node, 'mouseenter', () => {
			this.#clearCacheButton.button.setAttribute('data-hint-no-icon', '');
			if (this.#clearTimeout)
			{
				const minutesLeft = Math.ceil(parseInt(this.#clearTimeout, 10) / 60);
				hint.show(
					node,
					Loc.getMessagePlural(
						'BICONNECTOR_SUPERSET_SETTINGS_CLEAR_CACHE_BUTTON_HINT_TIME_LEFT',
						minutesLeft,
						{ '#COUNT#': minutesLeft },
					),
				);
			}
		});

		Event.bind(node, 'mouseleave', () => {
			hint.hide(node);
		});
	}
}
