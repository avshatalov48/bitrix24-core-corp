/** @memberof BX.Crm.Timeline.Tools */
export default class SchedulePostponeController
{
	constructor()
	{
		this._item = null;
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};
		this._item = BX.prop.get(this._settings, "item", null);
	}

	getTitle()
	{
		return this.getMessage("title");
	}

	getCommandList()
	{
		return(
			[
				{ name: "postpone_hour_1", title: this.getMessage("forOneHour") },
				{ name: "postpone_hour_2", title: this.getMessage("forTwoHours") },
				{ name: "postpone_hour_3", title: this.getMessage("forThreeHours") },
				{ name: "postpone_day_1", title: this.getMessage("forOneDay") },
				{ name: "postpone_day_2", title: this.getMessage("forTwoDays") },
				{ name: "postpone_day_3", title: this.getMessage("forThreeDays") }
			]
		);
	}

	processCommand(command)
	{
		if(command.indexOf("postpone") !== 0)
		{
			return false;
		}

		let offset = 0;
		if(command === "postpone_hour_1")
		{
			offset = 3600;
		}
		else if(command === "postpone_hour_2")
		{
			offset = 7200;
		}
		else if(command === "postpone_hour_3")
		{
			offset = 10800;
		}
		else if(command === "postpone_day_1")
		{
			offset = 86400;
		}
		else if(command === "postpone_day_2")
		{
			offset = 172800;
		}
		else if(command === "postpone_day_3")
		{
			offset = 259200;
		}

		if(offset > 0 && this._item)
		{
			this._item.postpone(offset);
		}

		return true;
	}

	getMessage(name)
	{
		const m = SchedulePostponeController.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static create(id, settings)
	{
		const self = new SchedulePostponeController();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
