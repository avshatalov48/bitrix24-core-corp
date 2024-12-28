/**
 * @module calendar/event-view-form/fields/name
 */
jn.define('calendar/event-view-form/fields/name', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');

	const { Text6 } = require('ui-system/typography/text');
	const { H3 } = require('ui-system/typography/heading');

	const { CalendarIcon } = require('calendar/event-view-form/layout/calendar-icon');

	class NameField extends PureComponent
	{
		getId()
		{
			return this.props.id;
		}

		isReadOnly()
		{
			return this.props.readOnly;
		}

		isRequired()
		{
			return true;
		}

		isEmpty()
		{
			return false;
		}

		render()
		{
			return View(
				{
					testId: 'calendar-event-view-form-name_FIELD',
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
					},
				},
				this.renderContent(),
				this.renderCalendarIcon(),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						flex: 1,
						paddingTop: Indent.S.toNumber(),
						paddingRight: Indent.XL.toNumber(),
					},
				},
				this.renderName(),
				this.props.permissions?.view_full && this.renderSectionName(),
			);
		}

		renderName()
		{
			return H3({
				testId: 'calendar-event-view-form-name_CONTENT',
				text: this.props.name,
			});
		}

		renderSectionName()
		{
			const messageCode = this.props.collabId > 0
				? 'M_CALENDAR_EVENT_VIEW_FORM_COLLAB_NAME'
				: 'M_CALENDAR_EVENT_VIEW_FORM_SECTION_NAME'
			;
			const sectionName = Loc.getMessage(messageCode, {
				'#NAME#': this.props.sectionName,
			});

			return Text6({
				testId: 'calendar-event-view-form-section-name_CONTENT',
				style: {
					opacity: 0,
					marginTop: Indent.XS.toNumber(),
				},
				text: sectionName,
				color: Color.base4,
				ref: (ref) => ref?.animate({ duration: 200, opacity: 1 }),
			});
		}

		renderCalendarIcon()
		{
			return CalendarIcon({
				dateFromTs: this.props.dateFromTs,
				color: this.props.color,
			});
		}
	}

	module.exports = {
		NameField: (props) => new NameField(props),
	};
});
