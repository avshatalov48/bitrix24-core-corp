import {Type, Tag, Loc, Dom, Event, Cache} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Planner} from "calendar.planner";
import {Util} from "calendar.util";

/**
 * @memberOf BX.Crm.Timeline.ToolBar
 * @mixes EventEmitter
 */
export class Zoom
{
	TITLE = 'Zoom';
	error = false;
	errorMessages = [];
	cache = new Cache.MemoryCache();

	constructor(params: {
		id: string,
		container: HTMLElement,
		ownerTypeId: Number,
		ownerId: Number,
		onFinishEdit: Function
	})
	{
		this.container = params.container;
		this.ownerTypeId = params.ownerTypeId;
		this.ownerId = params.ownerId;
		this.userId = +Loc.getMessage('USER_ID');
		this.onFinishEdit = params.onFinishEdit;

		Dom.append(this.getFormContainer(), this.container);
		Dom.append(this.renderButtons(), this.container);

		Event.bind(this.getDateContainer(), 'click', (e) => {this.onDateFieldClick(e)});
		Event.bind(this.getTimeContainer(), 'click', () => {
			this.onTimeSwitchClick(this.getTimeInputField())
		});

		Event.bind(this.getDateContainer(), 'change', () => {this.onUpdateDateTime()});
		Event.bind(this.getTimeContainer(), 'change', () => {this.onUpdateDateTime()});
		Event.bind(this.getDurationInputField(), 'change', () => {this.onUpdateDateTime()});
		Event.bind(this.getDurationTypeInputField(), 'change', () => {this.onUpdateDateTime()});

		this.refreshStartTimeView();
		this.initPlanner();
	}

	static onNotConnectedHandler(userId)
	{
		const url = document.location.href;
		const userProfileUri = '/company/personal/user/' + userId + '/social_services/';
		BX.SidePanel.Instance.open(userProfileUri, {
			events: {
				allowChangeHistory: false,
				onClose: function() {
					top.location.href = url;
				}
			}
		});
	}

	static onNotAvailableHandler()
	{
		BX.UI?.InfoHelper?.show('limit_video_conference_zoom_crm');
	}

	getTitle(): string
	{
		return this.TITLE;
	}

	getStartDateTime(): string
	{
		const ts =  BX.parseDate(this.getDateInputField().value).getTime() + this.unFormatTime(this.getTimeInputField().textContent) * 1000;

		return new Date(ts);
	};

	getEndDateTime(): Date
	{
		let duration = +this.getDurationInputField().value;
		const durationType = this.getDurationTypeInputField().value;

		if (durationType === 'h')
		{
			duration *= 60 * 60 * 1000;
		}
		else
		{
			duration *= 60 * 1000;
		}

		const endDateTime = new Date();
		endDateTime.setTime(this.getStartDateTime().getTime() + duration);

		return endDateTime;
	};

	onUpdateDateTime(): string
	{
		this.planner.updateSelector(this.getStartDateTime(), this.getEndDateTime(), false);
	}

	onDateFieldClick(e)
	{
		BX.calendar({ node: e.currentTarget, field: this.getDateInputField(), bTime: false});
		return false;
	};

	onTimeSwitchClick(element)
	{
		if (!this.clockInstance)
		{
			this.clockInstance = new BX.CClockSelector({
				start_time: this.unFormatTime(element.textContent),
				node: element,
				callback: BX.doNothing
			});
		}

		this.clockInstance.setNode(element);
		this.clockInstance.setTime(this.unFormatTime(element.textContent));
		this.clockInstance.setCallback((v) => {
			element.textContent = v;
			BX.fireEvent(element, 'change');
			this.clockInstance.closeWnd();
		});
		this.clockInstance.Show();
	};

	formatTime(date)
	{
		const dateFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')).replace(/:?\s*s/, ''),
			timeFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')).replace(/:?\s*s/, ''),
			str1 = BX.date.format(dateFormat, date),
			str2 = BX.date.format(timeFormat, date);
		return BX.util.trim(str2.replace(str1, ''));
	}

	unFormatTime(time)
	{
		let q = time.split(/[\s:]+/);
		if (q.length == 3)
		{
			const mt = q[2];
			if (mt == 'pm' && q[0] < 12)
				q[0] = parseInt(q[0], 10) + 12;

			if (mt == 'am' && q[0] == 12)
				q[0] = 0;

		}
		return parseInt(q[0], 10) * 3600 + parseInt(q[1], 10) * 60;
	};

	getDateContainer(): HTMLElement
	{
		return this.cache.remember('startDateContainer', () => {
			return Tag.render`
				<div class="ui-ctl ui-ctl-sm ui-ctl-after-icon ui-ctl-date">
					<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
					${this.getDateInputField()}
				</div>
			`;
		});
	}

	getDateInputField(): HTMLElement
	{
		return this.cache.remember('startDateInputField', () => {
			return Tag.render`
				<input type="text" class="ui-ctl-element">
			`;
		});
	}

	getTimeContainer(): HTMLElement
	{
		return this.cache.remember('startTimeContainer', () => {
			return Tag.render`
				<div class="ui-ctl ui-ctl-sm ui-ctl-after-icon ui-ctl-dropdown crm-entity-stream-content-new-zoom-field-sm">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					${this.getTimeInputField()}
				</div>
			`;
		});
	}

	getTimeInputField(): HTMLElement
	{
		return this.cache.remember('startTimeInputField', () => {
			return Tag.render`
				<div class="ui-ctl-element">12:00</div>
			`;
		});
	}

	getTitleInputField(): HTMLElement
	{
		return this.cache.remember('titleInputField', () => {
			return Tag.render`
				<input type="text" class="ui-ctl-element" value="${Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_TITLE_PLACEHOLDER')}">
			`;
		});
	}

	getDurationInputField(): HTMLElement
	{
		return this.cache.remember('durationInputField', () => {
			return Tag.render`
				<input type="text" class="ui-ctl-element" value="30">
			`;
		});
	}

	getDurationTypeInputField(): HTMLElement
	{
		return this.cache.remember('durationTypeInputField', () => {
			return Tag.render`
				<select class="ui-ctl-element">
					<option value="m">${Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_DURATION_MINUTES')}</option>
					<option value="h">${Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_DURATION_HOURS')}</option>
				</select>
			`;
		});
	}

	getFormContainer(): HTMLElement
	{
		return this.cache.remember('formContainer', () => {
			return Tag.render`
				<div class="crm-entity-stream-content-new-zoom">
					<div class="crm-entity-stream-content-new-zoom-inner">
						<div class="crm-entity-stream-content-new-zoom-field">
							<div class="crm-entity-stream-content-new-zoom-field-inner">
								<label for="" class="crm-entity-stream-content-new-zoom-field-label">${Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_TITLE')}</label>
								<div class="ui-ctl ui-ctl-sm ui-ctl-w100 ui-ctl-textbox">
									${this.getTitleInputField()}
								</div>
							</div>
						</div>
						<div class="crm-entity-stream-content-new-zoom-field">
							<div class="crm-entity-stream-content-new-zoom-field-block">
								<label for="" class="crm-entity-stream-content-new-zoom-field-label">${Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_DATE_CAPTION')}</label>
								<div class="crm-entity-stream-content-new-zoom-field-control">
									${this.getDateContainer()}
									${this.getTimeContainer()}
								</div>
							</div>
							<div class="crm-entity-stream-content-new-zoom-field-block">
								<label for="" class="crm-entity-stream-content-new-zoom-field-label">${Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_DURATION_CAPTION')}</label>
								<div class="crm-entity-stream-content-new-zoom-field-control">
									<div class="ui-ctl ui-ctl-sm crm-entity-stream-content-new-zoom-field-xs">
										${this.getDurationInputField()}
									</div>
									<div class="ui-ctl ui-ctl-sm ui-ctl-after-icon ui-ctl-dropdown crm-entity-stream-content-new-zoom-field-sm">
										<div class="ui-ctl-after ui-ctl-icon-angle"></div>
										${this.getDurationTypeInputField()}
									</div>
								</div>
							</div>
						</div>
						<br>
						${this.renderPlanner()}
					</div>
				</div>
			`;
		});
	}

	renderPlanner(): HTMLElement
	{
		return this.cache.remember('plannerContainer', () => {
			return Tag.render`
				<div class="crm-entity-stream-content-zoom-planner-container"></div>
			`;
		});
	}

	renderButtons(): HTMLElement
	{
		return this.cache.remember('buttonsContainer', () => {
			return Tag.render`
				<div class="crm-entity-stream-content-zoom-btn-container">
					${this.renderSaveButton()}
					${this.renderCancelButton()}
				</div>
			`;
		});
	}

	renderSaveButton(): HTMLElement
	{
		return this.cache.remember('saveButton', () => {
			return Tag.render`
				<button onclick="${this.save.bind(this)}" class="ui-btn ui-btn-xs ui-btn-primary">
					${Loc.getMessage('UI_BUTTONS_CREATE_BTN_TEXT')}
				</button>
			`;
		});
	}

	refreshStartTimeView()
	{
		const currentDateTime = new Date();
		const minutes = currentDateTime.getMinutes();
		const divisionRemainder = minutes % 5;
		const gap = 5;

		if (divisionRemainder > 0)
		{
			// We add 5 minutes gap to always show future time in the input.
			// Example: current time is 14:51. Then 51 - 1 + 5 => 14:55
			currentDateTime.setMinutes(minutes - divisionRemainder + gap);
		}

		this.getDateInputField().value = BX.formatDate(currentDateTime, BX.message('FORMAT_DATE'));
		this.getTimeInputField().innerHTML = this.formatTime(currentDateTime);
	}

	renderCancelButton(): Element
	{
		return this.cache.remember('cancelButton', () => {
			return Tag.render`
				<span onclick="${this.cancel.bind(this)}" class="ui-btn ui-btn-xs ui-btn-light-border">
					${Loc.getMessage('UI_TIMELINE_EDITOR_COMMENT_CANCEL')}
				</span>
			`;
		});
	}

	initPlanner(): Element
	{
		this.planner = new Planner({
			wrap: this.renderPlanner(),
			showEntryName: false,
			showEntiesHeader: false,
			entriesListWidth: 70,
		});

		this.planner.show();
		this.loadPlannerData({
			codes: ["U" + this.userId],
			from: Util.formatDate(this.getStartDateTime().getTime() - Util.getDayLength() * 3),
			to: Util.formatDate(this.getStartDateTime().getTime() + Util.getDayLength() * 10),
		});
		this.planner.updateSelector(this.getStartDateTime(), this.getEndDateTime(), false);
		this.planner.subscribe('onDateChange', this.handlePlannerSelectorChanges.bind(this));
	}

	handlePlannerSelectorChanges(event)
	{
		if (event instanceof BaseEvent)
		{
			let data = event.getData();

			const startDateTime = data.dateFrom;
			const duration = (data.dateTo - data.dateFrom) / 1000 / 60; //duration in minutes
			const durationType = this.getDurationTypeInputField().value;

			this.getDateInputField().value = BX.formatDate(startDateTime, BX.message('FORMAT_DATE'));
			this.getTimeInputField().innerHTML = this.formatTime(startDateTime);

			if (durationType === 'h' && duration % 60 === 0)
			{
				this.getDurationInputField().value = duration / 60;
				this.getDurationTypeInputField().value = 'h';
			}
			else
			{
				this.getDurationInputField().value = duration;
				this.getDurationTypeInputField().value = 'm';
			}
		}
	}

	loadPlannerData(params = {})
	{
		this.planner.showLoader();

		BX.ajax.runAction('calendar.api.calendarajax.updatePlanner', {
			data: {
				codes: params.codes || [],
				dateFrom: params.from || '',
				dateTo: params.to || '',
			}
		})
			.then((response) => {
				this.planner.hideLoader();

				this.planner.update(
					response.data.entries,
					response.data.accessibility
				);
			},(response) => { console.error(response.errors) }
		);
	}

	save()
	{
		this.cleanError();
		Dom.addClass(this.renderSaveButton(), "ui-btn-wait");

		const entityId = this.ownerId;
		const entityType = BX.CrmEntityType.resolveName(this.ownerTypeId);

		const dateStart = this.getDateInputField().value;
		const timeStart = this.getTimeInputField().textContent;
		const timestampStart = this.getStartDateTime().getTime();

		const dateTimeStart = dateStart + " " + timeStart;

		const conferenceTitle = this.getTitleInputField().value;
		const duration = +this.getDurationInputField().value;
		const durationType = this.getDurationTypeInputField().value;

		if (!Type.isString(conferenceTitle) || conferenceTitle === '')
		{
			this.errorMessages.push(`${Loc.getMessage('CRM_ZOOM_ERROR_EMPTY_TITLE')}`);
			this.showError();
		}

		if (!Type.isInteger(timestampStart) || timestampStart < Date.now())
		{
			this.errorMessages.push(`${Loc.getMessage('CRM_ZOOM_ERROR_INCORRECT_DATETIME')}`);
			this.showError();
		}

		if (!Type.isInteger(duration) || duration <= 0 || !['h','m'].includes(durationType))
		{
			this.errorMessages.push(`${Loc.getMessage('CRM_ZOOM_ERROR_INCORRECT_DURATION')}`);
			this.showError();
		}

		if (!this.error)
		{
			BX.ajax.runAction('crm.api.zoomUser.createConference', {
				data: {
					conferenceParams: {
						conferenceTitle: conferenceTitle,
						dateTimeStart: dateTimeStart,
						timestampStart: timestampStart,
						duration: duration,
						durationType: durationType,
					},
					entityId: entityId,
					entityType: entityType,
				},
				analyticsLabel: {}
			}).then(function (response) {
				Dom.removeClass(this.renderSaveButton(), 'ui-btn-wait');
				this.cancel();
			}.bind(this), function (response) {
				Dom.removeClass(this.renderSaveButton(), 'ui-btn-wait');
				this.errorMessages.push(`${Loc.getMessage('CRM_ZOOM_CREATE_MEETING_SERVER_RETURNS_ERROR')}`);
				this.errorMessages.push(response.errors[0].message);
				this.showError();
				console.error(response.errors);
			}.bind(this));
		}
	}

	cancel()
	{
		this.refreshTitle();
		this.refreshStartTimeView();
		this.refreshDuration();
		this.planner.updateSelector(this.getStartDateTime(), this.getEndDateTime(), false);

		if (Type.isFunction(this.onFinishEdit))
		{
			this.onFinishEdit();
		}
	}

	showError()
	{
		let errorText = '';

		this.errorMessages.forEach((message) => {
			errorText += message + "\n";
		})

		if (!this.error && errorText !== '')
		{
			this.errorElement = Tag.render`<div class="zoom-error-message ui-alert ui-alert-danger ui-alert-icon-danger">
				<span class="ui-alert-message">${errorText}</span>
			</div>`;

			Dom.append(this.errorElement, this.container.firstElementChild);
			this.error = true;
		}
		Dom.removeClass(this.renderSaveButton(), 'ui-btn-wait');
	}

	cleanError()
	{
		if (this.error)
		{
			if (Type.isDomNode(this.errorElement))
			{
				Dom.remove(this.errorElement);
				this.error = false;
				this.errorMessages = [];
			}
		}
	}

	refreshTitle()
	{
		this.getTitleInputField().value = Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_TITLE_PLACEHOLDER');
	}

	refreshDuration()
	{
		this.getDurationInputField().value = 30;
		this.getDurationTypeInputField().value = 'm';
	}
}