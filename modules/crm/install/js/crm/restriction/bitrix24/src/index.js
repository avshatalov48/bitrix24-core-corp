import {Type, Extension, SettingsCollection} from 'main.core';

const Bitrix24 = {
	settings: null,

	getSettings(entityId: ?string): SettingsCollection|Object
	{
		if (this.settings === null)
		{
			this.settings = Extension.getSettings('crm.restriction.bitrix24');
		}
		if (Type.isStringFilled(entityId))
		{
			return this.settings.get(entityId);
		}
		return this.settings;
	},

	isRestricted(entityId)
	{
		return !!this.getSettings(entityId);
	},

	getHandler(entityId)
	{
		const restrictions = this.getSettings(entityId);

		if (restrictions)
		{
			return function(e) {
				if (e)
				{
					BX.PreventDefault(e);
				}

				if (BX.Type.isStringFilled(restrictions['infoHelperScript']))
				{
					eval(restrictions['infoHelperScript']);
				}
				else if (restrictions['id'])
				{
					top.BX.UI.InfoHelper.show(restrictions['id']);
				}
				return false;
			}.bind(this);
		}
		return null;
	}
}
export {Bitrix24};
