import { Loc } from 'main.core';

export class TimePeriod
{
	#errorMessage: ?string = null;

	validate(startTime: string, endTime: string): boolean
	{
		const parseTime = (dateTimeStr) => {
			const [, time] = dateTimeStr.split(' ');
			const [hours, minutes] = time.split(':').map(Number);

			return new Date(9999, 11, 31, hours, minutes);
		};

		const start = parseTime(startTime);
		const end = parseTime(endTime);

		if (Number.isNaN(start) || Number.isNaN(end))
		{
			this.#errorMessage = Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_VALIDATION_TIME_ERROR_1');

			return false;
		}

		if (start.getTime() >= end.getTime())
		{
			this.#errorMessage = Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_VALIDATION_TIME_ERROR_2');

			return false;
		}

		this.#errorMessage = null;

		return true;
	}

	getError(): ?string
	{
		return this.#errorMessage;
	}
}
