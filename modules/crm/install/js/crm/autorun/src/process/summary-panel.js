import { Loc } from 'main.core';
import '../css/process.css';
import 'ui.design-tokens';

/**
 * @memberOf BX.Crm.Autorun
 * @alias BX.Crm.ProcessSummaryPanel
 */
export class SummaryPanel
{
	_id = '';
	_settings = {};

	_data = null;
	_container = null;
	_wrapper = null;

	static messages = {};

	static create(id, settings): SummaryPanel
	{
		return new SummaryPanel(id, settings);
	}

	constructor(id, settings)
	{
		this._id = id;
		this._settings = settings || {};

		this._container = BX(BX.prop.get(this._settings, 'container'));
		if (!BX.type.isElementNode(this._container))
		{
			throw 'BatchConversionPanel: Could not find container.';
		}
		this._data = BX.prop.getObject(this._settings, 'data', {});
	}

	getId()
	{
		return this._id;
	}

	getMessage(name)
	{
		const messages = BX.prop.getObject(this._settings, 'messages', SummaryPanel.messages);

		return BX.prop.getString(messages, name, name);
	}

	layout()
	{
		if (this._hasLayout)
		{
			return;
		}

		this._wrapper = BX.create('DIV', { attrs: { className: 'crm-view-progress' } });
		BX.addClass(this._wrapper, this._isHidden ? 'crm-view-progress-hide' : 'crm-view-progress-show');
		BX.addClass(this._wrapper, 'crm-view-progress-row-hidden');

		this._container.appendChild(this._wrapper);

		const summaryElements = [BX.create('span', { text: this.getMessage('summaryCaption') })];

		const substitution = new RegExp(BX.prop.getString(this._settings, 'numberSubstitution', '#number#'), 'ig');

		const succeeded = BX.prop.getInteger(this._data, 'succeededCount', 0);
		if (succeeded > 0)
		{
			summaryElements.push(
				BX.create(
					'span',
					{
						attrs: { className: 'crm-view-progress-text' },
						text: this.getMessage('summarySucceeded').replace(substitution, succeeded),
					},
				),
			);
		}

		const failed = BX.prop.getInteger(this._data, 'failedCount', 0);
		if (failed > 0)
		{
			summaryElements.push(
				BX.create(
					'span',
					{
						attrs: { className: 'crm-view-progress-link crm-view-progress-text-button' },
						text: this.getMessage('summaryFailed').replace(substitution, failed),
						events: { click: this.onToggleErrorButtonClick.bind(this) },
					},
				),
			);
		}

		const elements = [];
		elements.push(
			BX.create(
				'DIV',
				{
					attrs: { className: 'crm-view-progress-info' },
					children: summaryElements,
				},
			),
		);

		elements.push(
			BX.create(
				'a',
				{
					attrs: { className: 'crm-view-progress-link', href: '#' },
					text: Loc.getMessage('JS_CORE_WINDOW_CLOSE'),
					events: { click: this.onCloseButtonClick.bind(this) },
				},
			),
		);

		this._wrapper.appendChild(
			BX.create('DIV', {
				attrs: { className: 'crm-view-progress-row' },
				children: elements,
			}),
		);

		const errors = BX.prop.getArray(this._data, 'errors', []);
		if (errors.length > 0)
		{
			for (
				let i = 0,
					length = errors.length; i < length; i++
			)
			{
				const error = errors[i];
				const errorElements = [];

				const info = BX.prop.getObject(
					BX.prop.getObject(error, 'customData', {}),
					'info',
					null,
				);

				if (info)
				{
					const title = BX.prop.getString(info, 'title', '');
					const showUrl = BX.prop.getString(info, 'showUrl', '');

					if (title !== '' && showUrl !== '')
					{
						errorElements.push(
							BX.create(
								'a',
								{
									props: { className: 'crm-view-progress-link', href: showUrl, target: '_blank' },
									text: `${title}:`,
								},
							),
						);
					}
				}

				errorElements.push(
					BX.create(
						'span',
						{
							attrs: { className: 'crm-view-progress-text' },
							text: error.message,
						},
					),
				);

				this._wrapper.appendChild(
					BX.create(
						'DIV',
						{
							attrs: { className: 'crm-view-progress-row' },
							children:
								[
									BX.create(
										'DIV',
										{
											attrs: { className: 'crm-view-progress-info' },
											children: errorElements,
										},
									),
								],
						},
					),
				);
			}
		}
		else
		{
			const timeout = this.getDisplayTimeout();
			if (timeout > 0)
			{
				window.setTimeout(() => {
					this.clearLayout();
				}, timeout);
			}
		}
		this._hasLayout = true;

		BX.onCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onLayout', [this]);
	}

	hasLayout()
	{
		return this._hasLayout;
	}

	isHidden()
	{
		return this._isHidden;
	}

	show()
	{
		if (!this._isHidden)
		{
			return;
		}

		if (!this._hasLayout)
		{
			return;
		}

		BX.removeClass(this._wrapper, 'crm-view-progress-hide');
		BX.addClass(this._wrapper, 'crm-view-progress-show');

		this._isHidden = false;
	}

	hide()
	{
		if (this._isHidden)
		{
			return;
		}

		if (!this._hasLayout)
		{
			return;
		}

		BX.removeClass(this._wrapper, 'crm-view-progress-show');
		BX.addClass(this._wrapper, 'crm-view-progress-hide');

		this._isHidden = true;
	}

	clearLayout()
	{
		if (!this._hasLayout)
		{
			return;
		}

		BX.remove(this._wrapper);
		this._wrapper = null;

		this._hasLayout = false;

		BX.onCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onClearLayout', [this]);
	}

	getDisplayTimeout()
	{
		return BX.prop.getInteger(this._settings, 'displayTimeout', 0);
	}

	onCloseButtonClick(e)
	{
		this.clearLayout();

		return BX.eventReturnFalse(e);
	}

	onToggleErrorButtonClick()
	{
		BX.toggleClass(this._wrapper, 'crm-view-progress-row-hidden');
	}
}
