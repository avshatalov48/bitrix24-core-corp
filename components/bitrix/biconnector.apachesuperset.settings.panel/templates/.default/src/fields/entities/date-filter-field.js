/* eslint-disable no-underscore-dangle */
import { Text, Tag, Dom, Event, Type, Loc } from 'main.core';

export class DateFilterField extends BX.UI.EntityEditorList
{
	static RANGE_VALUE = 'range';

	constructor(id, settings)
	{
		super();
		this.dateSelectorBlock = null;
		this.toInput = null;
		this.startInput = null;
		this.includeLastDateCheckbox = null;
	}

	static create(id, settings)
	{
		const self = new this(id, settings);
		self.initialize(id, settings);

		return self;
	}

	static layout(options = {})
	{
		super.layout();
	}

	createTitleNode(): HTMLElement
	{
		return Tag.render`<span></span>`;
	}

	layout(options: {})
	{
		super.layout(options);

		this.layoutRangeField(this.getValue() === DateFilterField.RANGE_VALUE);
		this.layoutHint();
	}

	onItemSelect(e, item)
	{
		this.layoutRangeField(item.value === DateFilterField.RANGE_VALUE);

		super.onItemSelect(e, item);
	}

	refreshLayout()
	{
		super.refreshLayout();
		this.layoutRangeField(this.getModel().getField('FILTER_PERIOD') === DateFilterField.RANGE_VALUE);
	}

	layoutRangeField(isRangeSelected: boolean)
	{
		if (this.dateSelectorBlock !== null)
		{
			Dom.remove(this.dateSelectorBlock);
			this.dateSelectorBlock = null;
			this.startInput = null;
			this.endInput = null;
			this.includeLastDateCheckbox = null;
		}

		if (isRangeSelected)
		{
			const dateStartValue = Text.encode(this.getModel().getField(this.getDateStartFieldName()));
			this.startInput = Tag.render`<input class="ui-ctl-element" type="text" value="${dateStartValue}" name="${this.getDateStartFieldName()}">`;
			Event.bind(this.startInput, 'click', () => {
				DateFilterField.showCalendar(this.startInput);
			});
			Event.bind(this.startInput, 'change', () => {
				this.onChange();
			});
			Event.bind(this.startInput, 'input', () => {
				this.onChange();
			});

			const dateEndValue = Text.encode(this.getModel().getField(this.getDateEndFieldName()));
			this.endInput = Tag.render`<input class="ui-ctl-element" type="text" value="${dateEndValue}" name="${this.getDateEndFieldName()}">`;
			Event.bind(this.endInput, 'click', () => {
				DateFilterField.showCalendar(this.endInput);
			});
			Event.bind(this.endInput, 'change', () => {
				this.onChange();
			});
			Event.bind(this.endInput, 'input', () => {
				this.onChange();
			});

			this.includeLastDateCheckbox = Tag.render`<input class="ui-ctl-element" type="checkbox" name="${this.getIncludeLastDateName()}">`;
			const includeLastDateValue = this.getModel().getField(this.getIncludeLastDateName());
			if (includeLastDateValue)
			{
				this.includeLastDateCheckbox.checked = true;
			}
			Event.bind(this.includeLastDateCheckbox, 'change', () => {
				this.onChange();
			});

			this.dateSelectorBlock =
				Tag.render`
					<div class="ui-ctl-dropdown-range-group">
						<div class="ui-ctl-container">
							<div class="ui-ctl-top">
								<div class="ui-ctl-title">${Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_FROM_TITLE')}</div>
							</div>
							<div class="ui-ctl ui-ctl-before-icon ui-ctl-datetime">
								<div class="ui-ctl-before ui-ctl-icon-calendar"></div>
								${this.startInput}
							</div>
						</div>
						<div class="ui-ctl-container">
							<div class="ui-ctl-dropdown-range-line">
								<span class="ui-ctl-dropdown-range-line-item"></span>
							</div>
						</div>
						<div class="ui-ctl-container">
							<div class="ui-ctl-top">
								<div class="ui-ctl-title">${Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_TO_TITLE')}</div>
							</div>
							<div class="ui-ctl ui-ctl-before-icon ui-ctl-datetime">
								<div class="ui-ctl-before ui-ctl-icon-calendar"></div>
								${this.endInput}
							</div>
							<div class="ui-ctl-bottom">
								<label class="ui-ctl ui-ctl-checkbox">
									${this.includeLastDateCheckbox}
									<div class="ui-ctl-label-text">${Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_INCLUDE_LAST_DATE')}</div>
								</label>
							</div>
						</div>
					</div>
				`;

			Dom.append(this.dateSelectorBlock, this._innerWrapper);
		}
		else
		{
			Dom.addClass(this._selectContainer, 'ui-ctl-w100');
			Dom.removeClass(this._selectContainer, 'ui-ctl-date-range');
		}
	}

	layoutHint(): void
	{
		const hintContainer = Tag.render`
			<div class="biconnector-superset-settings-panel-range__hint">
				${this.getHintText()}
			</div>
		`;

		Dom.insertBefore(hintContainer, this._container);
	}

	getHintText(): string
	{
		const hintLink = `
			<a 
				class="biconnector-superset-settings-panel-range__hint-link"
				onclick="top.BX.Helper.show('redirect=detail&code=20337242&anchor=Defaultreportingperiod')"
			>
				${Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_FIELD_HINT_LINK')}
			</a>
		`;

		return Loc.getMessage('BICONNECTOR_SUPERSET_SETTINGS_COMMON_RANGE_FIELD_HINT')
			.replace('#HINT_LINK#', hintLink);
	}

	getDateStartFieldName(): string
	{
		return this._schemeElement.getData().dateStartFieldName ?? 'DATE_FILTER_START';
	}

	getDateEndFieldName(): string
	{
		return this._schemeElement.getData().dateEndFieldName ?? 'DATE_FILTER_END';
	}

	getIncludeLastDateName(): string
	{
		return this._schemeElement.getData().includeLastDateName ?? 'INCLUDE_LAST_FILTER_DATE';
	}

	save()
	{
		super.save();
		this._model.setField(this.getDateStartFieldName(), null);
		this._model.setField(this.getDateEndFieldName(), null);
		this._model.setField(this.getIncludeLastDateName(), null);

		if (Type.isDomNode(this.endInput))
		{
			this._model.setField(this.getDateEndFieldName(), this.endInput.value);
		}

		if (Type.isDomNode(this.startInput))
		{
			this._model.setField(this.getDateStartFieldName(), this.startInput.value);
		}

		if (Type.isDomNode(this.includeLastDateCheckbox))
		{
			this.includeLastDateCheckbox.value = this.includeLastDateCheckbox.checked ? 'Y' : 'N';
			this._model.setField(this.getIncludeLastDateName(), this.includeLastDateCheckbox.checked ? 'Y' : 'N');
		}
	}

	static showCalendar(input: HTMLElement)
	{
		BX.calendar.get().Close();
		BX.calendar({ node: input, field: input, bTime: false, bSetFocus: false });
	}
}
