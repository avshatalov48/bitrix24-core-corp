(function() {
var BX = window.BX;
if (BX.RenderParts)
{
	return;
}

BX.RenderParts =
{
	currentUserSonetGroupIdList: [],
	mobile: false,
	publicSection: false,
	currentExtranetUser: false,
	availableUsersList: []
};

BX.RenderParts.init = function(params)
{
	if (typeof params.currentUserSonetGroupIdList != 'undefined')
	{
		this.currentUserSonetGroupIdList = params.currentUserSonetGroupIdList;
	}
	if (typeof params.publicSection != 'undefined')
	{
		this.publicSection = !!params.publicSection;
	}
	this.mobile = !!params.mobile;

	if (typeof params.currentExtranetUser != 'undefined')
	{
		this.currentExtranetUser = !!params.currentExtranetUser;
	}

	if (
		this.currentExtranetUser
		&& typeof params.availableUsersList != 'undefined'
	)
	{
		this.availableUsersList = BX.util.array_values(params.availableUsersList);
	}
};

BX.RenderParts.getNodeSG = function(entity)
{
	var hidden = (
		typeof entity.VISIBILITY != 'undefined'
		&& entity.VISIBILITY == 'group_members'
		&& !BX.util.in_array(entity.ENTITY_ID, this.currentUserSonetGroupIdList)
	);

	if (hidden)
	{
		return this.getNodeHiddenDestination();
	}
	else
	{
		return (
			!this.mobile
				? BX.create('a', {
					attrs: {
						href: entity.LINK,
						target: '_blank'
					},
					text: entity.NAME
				})
				: BX.create('span', {
					text: entity.NAME
				})
		);
	}
};

BX.RenderParts.getNodeU = function(entity)
{
	var hidden = (
		this.currentExtranetUser
		&& !BX.util.in_array(entity.ENTITY_ID, this.availableUsersList)
	);

	if (hidden)
	{
		return this.getNodeHiddenDestination();
	}
	else
	{
		return (
			!this.mobile
				? BX.create('a', {
					attrs: {
						href: entity.LINK
					},
					props: {
						className: 'blog-p-user-name' + (entity.VISIBILITY == 'extranet' ? ' blog-p-user-name-extranet' : '')
					},
					text: entity.NAME
				})
				: BX.create('a', {
					attrs: {
						href: entity.LINK
					},
					text: entity.NAME
				})
		);
	}
};

BX.RenderParts.getNodeDR = function(entity)
{
	return (
		!this.mobile
			? BX.create('a', {
				attrs: {
					href: entity.LINK,
					target: '_blank'
				},
				text: entity.NAME
			})
			: BX.create('span', {
				text: entity.NAME
			})
	);
};

BX.RenderParts.getNodeTask = function(entity)
{
	return (
		!this.mobile
		&& !this.publicSection
		&& entity.LINK.length > 0
		&& typeof entity.VISIBILITY != 'undefined'
		&& typeof entity.VISIBILITY.userId != 'undefined'
		&& parseInt(entity.VISIBILITY.userId) == parseInt(BX.message('USER_ID'))
			? BX.create('a', {
				attrs: {
					href: entity.LINK,
					target: '_blank'
				},
				text: entity.NAME
			})
			: BX.create('span', {
				text: entity.NAME
			})
	);
};

BX.RenderParts.getNodePost = function(entity)
{
	return (
		!this.mobile
		&& !this.publicSection
		&& entity.LINK.length > 0
		&& BX.type.isNotEmptyObject(entity.VISIBILITY)
		&& entity.VISIBILITY.available === true

			? BX.create('a', {
				attrs: {
					href: entity.LINK,
					target: '_blank'
				},
				text: entity.NAME
			})
			: BX.create('span', {
				text: entity.NAME
			})
	);
};

BX.RenderParts.getNodeCalendarEvent = function(entity)
{
	return (
		!this.mobile
		&& !this.publicSection
		&& entity.LINK.length > 0
		&& BX.type.isNotEmptyObject(entity.VISIBILITY)
		&& entity.VISIBILITY.available === true

			? BX.create('a', {
				attrs: {
					href: entity.LINK,
					target: '_blank'
				},
				text: entity.NAME
			})
			: BX.create('span', {
				text: entity.NAME
			})
	);
};

BX.RenderParts.getNodeUA = function()
{
	return BX.create('span', {
		text: BX.message('SONET_RENDERPARTS_JS_DESTINATION_ALL')
	});
};

BX.RenderParts.getNodeHiddenDestination = function()
{
	return BX.create('span', {
		text: BX.message('SONET_RENDERPARTS_JS_HIDDEN')
	});
};


})();