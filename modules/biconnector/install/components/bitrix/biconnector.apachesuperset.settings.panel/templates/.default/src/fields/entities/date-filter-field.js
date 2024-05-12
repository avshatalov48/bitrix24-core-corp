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
		}

		if (isRangeSelected)
		{
			Dom.removeClass(this._selectContainer, 'ui-ctl-w100');
			Dom.addClass(this._innerWrapper, 'ui-entity-editor-content-block__range');
			Dom.addClass(this._selectContainer, 'ui-ctl-date-range');
			this.dateSelectorBlock = Tag.render`<div class="ui-ctl-dropdown-range-group"></div>`;
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
			Dom.append(
				Tag.render`
					<div class="ui-ctl ui-ctl-before-icon ui-ctl-datetime">
						<div class="ui-ctl-before ui-ctl-icon-calendar"></div>
						${this.startInput}
					</div>
				`,
				this.dateSelectorBlock,
			);

			Dom.append(
				Tag.render`
					<div class="ui-ctl-dropdown-range-line">
						<span class="ui-ctl-dropdown-range-line-item"></span>
					</div>
				`,
				this.dateSelectorBlock,
			);
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
			Dom.append(
				Tag.render`
					<div class="ui-ctl ui-ctl-before-icon ui-ctl-datetime">
						<div class="ui-ctl-before ui-ctl-icon-calendar"></div>
						${this.endInput}
					</div>
				`,
				this.dateSelectorBlock,
			);

			Dom.append(this.dateSelectorBlock, this._innerWrapper);
		}
		else
		{
			Dom.addClass(this._selectContainer, 'ui-ctl-w100');
			Dom.removeClass(this._innerWrapper, 'ui-entity-editor-content-block__range');
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

	save()
	{
		super.save();
		this._model.setField(this.getDateStartFieldName(), null);
		this._model.setField(this.getDateEndFieldName(), null);
		if (Type.isDomNode(this.endInput))
		{
			this._model.setField(this.getDateEndFieldName(), this.endInput.value);
		}

		if (Type.isDomNode(this.startInput))
		{
			this._model.setField(this.getDateStartFieldName(), this.startInput.value);
		}
	}

	static showCalendar(input: HTMLElement)
	{
		BX.calendar.get().Close();
		BX.calendar({ node: input, field: input, bTime: false, bSetFocus: false });
	}
}
