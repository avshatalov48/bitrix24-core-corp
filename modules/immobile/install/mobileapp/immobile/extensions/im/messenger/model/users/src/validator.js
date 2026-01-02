/**
 * @module im/messenger/model/users/validator
 */
jn.define('im/messenger/model/users/validator', (require, exports, module) => {
	const { Type } = require('type');
	const { UserType } = require('im/messenger/const');
	const { Url } = require('im/messenger/lib/helper');

	function validate(fields, options = {})
	{
		const result = {};
		const {
			fromLocalDatabase,
		} = options;

		if (Type.isNumber(fields.id) || Type.isString(fields.id))
		{
			result.id = Number.parseInt(fields.id, 10);
		}

		if (Type.isStringFilled(fields.first_name))
		{
			fields.firstName = fields.first_name;
		}

		if (Type.isStringFilled(fields.last_name))
		{
			fields.lastName = fields.last_name;
		}

		if (Type.isStringFilled(fields.firstName))
		{
			result.firstName = jnComponent.convertHtmlEntities(fields.firstName);
		}

		if (Type.isStringFilled(fields.lastName))
		{
			result.lastName = jnComponent.convertHtmlEntities(fields.lastName);
		}

		if (Type.isStringFilled(fields.name))
		{
			fields.name = jnComponent.convertHtmlEntities(fields.name);
			result.name = fields.name;
		}

		if (Type.isStringFilled(fields.color))
		{
			result.color = fields.color;
		}

		if (Type.isStringFilled(fields.type))
		{
			result.type = fields.type;
		}
		else if (fromLocalDatabase && Type.isNil(fields.type))
		{
			if (Type.isBoolean(fields.bot))
			{
				result.type = UserType.bot;
			}
			else if (Type.isBoolean(fields.extranet))
			{
				result.type = UserType.extranet;
			}
			else
			{
				result.type = UserType.user;
			}
		}

		if (Type.isStringFilled(fields.avatar))
		{
			if (fromLocalDatabase === true)
			{
				result.avatar = fields.avatar;
			}
			else
			{
				result.avatar = prepareAvatar(fields.avatar);
			}
		}

		if (Type.isStringFilled(fields.work_position))
		{
			fields.workPosition = fields.work_position;
		}

		if (Type.isStringFilled(fields.workPosition))
		{
			result.workPosition = jnComponent.convertHtmlEntities(fields.workPosition);
		}

		if (Type.isStringFilled(fields.gender))
		{
			result.gender = fields.gender === 'F' ? 'F' : 'M';
		}

		if (Type.isStringFilled(fields.birthday))
		{
			result.birthday = fields.birthday;
		}

		if (Type.isBoolean(fields.extranet))
		{
			result.extranet = fields.extranet;
		}

		if (Type.isBoolean(fields.network))
		{
			result.network = fields.network;
		}

		if (Type.isBoolean(fields.bot))
		{
			result.bot = fields.bot;
		}

		if (Type.isObject(fields.bot_data))
		{
			result.botData = {
				appId: fields.bot_data.app_id,
				code: fields.bot_data.code,
				isHidden: fields.bot_data.is_hidden,
				isSupportOpenline: fields.bot_data.is_support_openline,
				type: fields.bot_data.type,
				backgroundId: fields.bot_data.background_id,
			};
		}

		if (Type.isObject(fields.botData))
		{
			result.botData = fields.botData;
		}

		if (Type.isBoolean(fields.connector))
		{
			result.connector = fields.connector;
		}

		if (Type.isStringFilled(fields.external_auth_id))
		{
			fields.externalAuthId = fields.external_auth_id;
		}

		if (Type.isStringFilled(fields.externalAuthId))
		{
			result.externalAuthId = fields.externalAuthId;
		}

		if (Type.isStringFilled(fields.status))
		{
			result.status = fields.status;
		}

		if (!Type.isUndefined(fields.idle))
		{
			result.idle = fields.idle;
		}

		if (!Type.isUndefined(fields.last_activity_date))
		{
			fields.lastActivityDate = fields.last_activity_date;
		}

		if (!Type.isUndefined(fields.lastActivityDate))
		{
			result.lastActivityDate = fields.lastActivityDate;
		}

		if (!Type.isUndefined(fields.mobile_last_date))
		{
			fields.mobileLastDate = fields.mobile_last_date;
		}

		if (!Type.isUndefined(fields.mobileLastDate))
		{
			result.mobileLastDate = fields.lastActivityDate;
		}

		if (!Type.isUndefined(fields.absent))
		{
			result.absent = fields.absent;
		}

		if (Array.isArray(fields.departments))
		{
			result.departments = [];
			fields.departments.forEach((departmentId) => {
				departmentId = Number.parseInt(departmentId, 10);
				if (departmentId > 0)
				{
					result.departments.push(departmentId);
				}
			});
		}

		if (Type.isString(fields.departmentName))
		{
			result.departmentName = fields.departmentName;
		}

		if (Type.isPlainObject(fields.phones))
		{
			result.phones = preparePhones(fields.phones);
		}

		if (Type.isBoolean(fields.isCompleteInfo))
		{
			result.isCompleteInfo = fields.isCompleteInfo;
		}

		return result;
	}

	function prepareAvatar(avatar)
	{
		let result = '';

		if (!avatar || avatar.endsWith('/js/im/images/blank.gif'))
		{
			result = '';
		}
		else if (avatar.startsWith('http'))
		{
			result = avatar;
		}
		else
		{
			result = currentDomain + avatar;
		}

		if (result)
		{
			const urlHelper = Url.createFromPath(result);
			if (!urlHelper.isEncoded)
			{
				result = encodeURI(result);
			}
		}

		return result;
	}

	function preparePhones(phones)
	{
		const result = {};

		if (!Type.isUndefined(phones.work_phone))
		{
			phones.workPhone = phones.work_phone;
		}

		if (Type.isStringFilled(phones.workPhone) || Type.isNumber(phones.workPhone))
		{
			result.workPhone = phones.workPhone.toString();
		}

		if (!Type.isUndefined(phones.personal_mobile))
		{
			phones.personalMobile = phones.personal_mobile;
		}

		if (Type.isStringFilled(phones.personalMobile) || Type.isNumber(phones.personalMobile))
		{
			result.personalMobile = phones.personalMobile.toString();
		}

		if (!Type.isUndefined(phones.personal_phone))
		{
			phones.personalPhone = phones.personal_phone;
		}

		if (Type.isStringFilled(phones.personalPhone) || Type.isNumber(phones.personalPhone))
		{
			result.personalPhone = phones.personalPhone.toString();
		}

		if (!Type.isUndefined(phones.inner_phone))
		{
			phones.innerPhone = phones.inner_phone;
		}

		if (Type.isStringFilled(phones.innerPhone) || Type.isNumber(phones.innerPhone))
		{
			result.innerPhone = phones.innerPhone.toString();
		}

		return result;
	}

	module.exports = { validate };
});
