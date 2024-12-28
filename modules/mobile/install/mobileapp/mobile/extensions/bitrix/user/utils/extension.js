(() =>
{
	/**
	 * @class UserListUtils
	 * @type {{getFormattedName: (function(*=, *=): any), prepareListForDraw: (function(*=): []), getFormattedHumanName: (function(*, *=): *)}}
	 */
	let Utils = {
		prepareListForDraw: function (list)
		{
			let result = [];
			let userFormatFunction = user => ({
				title: Utils.getFormattedName(user),
				subtitle: user.WORK_POSITION,
				shortTitle: user.NAME || Utils.getFormattedName(user),
				hasName: (Utils.getFormattedHumanName(user) !== ""),
				sectionCode: "people",
				color: "#5D5C67",
				useLetterImage: true,
				id: user.ID,
				imageUrl: (user.PERSONAL_PHOTO === null ? undefined : encodeURI(user.PERSONAL_PHOTO)),
				sortValues: {
					name: user.LAST_NAME
				},
				params: {
					id: user.ID,
					profileUrl: "/mobile/users/?user_id=" + user.ID,
					userType: (typeof user.USER_TYPE !== 'undefined' ? user.USER_TYPE : '')
				},
			});

			if (list)
			{
				result = list
					.filter(user => user["UF_DEPARTMENT"] !== false && Utils.getFormattedHumanName(user))
					.map(userFormatFunction);
				let unknownUsers = list
					.filter(user => user["UF_DEPARTMENT"] !== false && !Utils.getFormattedHumanName(user))
					.map(userFormatFunction)
					.sort((u1, u2) => u1.title > u2.title ? 1 : (u1.title === u2.title ? 0 : -1));
				result = unknownUsers.concat(result);
			}

			return result;

		},
		getFormattedName: function (userData, format = null)
		{
			let name = Utils.getFormattedHumanName(userData, format);
			if (name === "")
			{
				if (userData.EMAIL)
				{
					name = userData.EMAIL;
				}
				else if (userData.PERSONAL_MOBILE)
				{
					name = userData.PERSONAL_MOBILE;
				}
				else if (userData.PERSONAL_PHONE)
				{
					name = userData.PERSONAL_PHONE;
				}
				else
				{
					name = BX.message("USER_LIST_NO_NAME")
				}
			}

			return name !== "" ? name : userData.EMAIL;

		},
		getFormattedHumanName:function(userData, format = null){
			let replace = {
				"#NAME#": userData.NAME,
				"#LAST_NAME#": userData.LAST_NAME,
				"#SECOND_NAME#": userData.SECOND_NAME,

			};

			if (format == null)
			{
				format = "#NAME# #LAST_NAME#";
			}

			if (userData.LAST_NAME)
			{
				replace["#LAST_NAME_SHORT#"] = userData.LAST_NAME[0].toUpperCase() + ".";
			}
			if (userData.SECOND_NAME)
			{
				replace["#SECOND_NAME_SHORT#"] = userData.SECOND_NAME[0].toUpperCase() + ".";
			}
			if (userData.NAME)
			{
				replace["#NAME_SHORT#"] = userData.NAME[0].toUpperCase() + ".";
			}

			return format
				.replace(/#NAME#|#LAST_NAME#|#SECOND_NAME#|#LAST_NAME_SHORT#|#SECOND_NAME_SHORT#|#NAME_SHORT#/gi,
					match => (typeof replace[match] != "undefined" && replace[match] != null) ? replace[match] : "")
				.trim();
		}


	};
	jnexport([Utils, "UserListUtils"]);

})();
