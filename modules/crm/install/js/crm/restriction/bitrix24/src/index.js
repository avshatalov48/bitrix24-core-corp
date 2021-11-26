import {Type, Extension, SettingsCollection} from 'main.core';

const Bitrix24 = {
	data: null,

	getData(entityId: ?string): SettingsCollection|Object|undefined
	{
		if (this.data === null)
		{
			this.data = Extension.getSettings('crm.restriction.bitrix24');
		}
		if (Type.isStringFilled(entityId))
		{
			return this.data.get(entityId);
		}
		return this.data;
	},

	isRestricted(entityId)
	{
		return !!this.getData(entityId);
	},

	getHandler(entityId)
	{
		const restrictions = this.getData(entityId);

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
