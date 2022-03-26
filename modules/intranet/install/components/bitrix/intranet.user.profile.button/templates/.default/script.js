;(function() {
	BX.ready(function() {
		if (BX('user-block') && BX('user-block').dataset.bound !== true)
		{
			BX('user-block').dataset.bound = true;
			BX.addCustomEvent(
				'BX.Intranet.UserProfile:Avatar:changed',
				function(avatarProperties) {
					var url = avatarProperties && avatarProperties['url'] ?avatarProperties['url'] : '';
					var avatarNode = BX('user-block').querySelector('i');
					avatarNode.style = BX.Type.isStringFilled(url) ?
						"background-size: cover; background-image: url('" + url + "')" : '';
				})
			;
		}
	});
})();