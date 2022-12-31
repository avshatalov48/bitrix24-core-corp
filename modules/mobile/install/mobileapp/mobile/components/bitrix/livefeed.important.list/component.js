"use strict";
(()=>{

class LivefeedImportantList
{
	constructor()
	{
		this.postId = 0;
		this.init();

		this.sectionCode = 'users';
	}

	init()
	{
		this.postId = parseInt(BX.componentParameters.get('POST_ID', 0));
		this.settings = BX.componentParameters.get('SETTINGS', {});

		if (this.postId <= 0)
		{
			return;
		}

		this.getPage({
			pageNumber: 1
		});
	}

	showResult(type, items)
	{
		if (type === 'error')
		{
			livefeedImportantListWidget.setItems([ListHolder.EmptyResult], []);
			return;
		}

		livefeedImportantListWidget.setSections([
			{ id: this.sectionCode }
		]);

		items = this.prepareItems(type, items);

		livefeedImportantListWidget.setSectionItems(items, this.sectionCode);
		livefeedImportantListWidget.setListener((eventName, user) => {
			if (eventName === 'onItemSelected')
			{
				const { ProfileView } = jn.require("user/profile");
				ProfileView.open(
					{
						userId: user.id,
						name: user.title,
					}
				);
			}
		})
	}

	getPage({
		pageNumber
	})
	{
		BX.ajax.runAction('socialnetwork.api.livefeed.blogpost.important.getUsers', {
			data: {
				params: {
					POST_ID: this.postId,
					NAME: 'BLOG_POST_IMPRTNT',
					VALUE: 'Y',
					AVATAR_SIZE: 100,
					NAME_TEMPLATE: (typeof this.settings.nameTemplate !== 'undefined' ? this.settings.nameTemplate : '')
				}
			}}).then((response) => {
			if (response.status === 'error')
			{
				this.showResult('error', []);
			}
			else
			{
				if (typeof response.data.items !== 'undefined')
				{
					this.showResult('info', response.data.items);
				}
				else
				{
					this.showResult('error', []);
				}
			}
		}, (response) => {
			this.showResult('error', []);
		});
	}

	prepareItems(type, items)
	{
		if (
			!Array.isArray(items)
			&& typeof items === 'object'
		)
		{
			const res = [];
			for (var key in items)
			{
				if(!items.hasOwnProperty(key))
				{
					continue;
				}
				res.push(items[key]);
			}
			items = res;
		}

		return items.map(item => {
			return {
				sectionCode: this.sectionCode,
				type: type,
				id: item.ID,
				title: item.FULL_NAME,
				imageUrl: (item.PHOTO_SRC.length > 0 ? item.PHOTO_SRC : ''),
				height: 64
			};
		});
	}
}

this.LivefeedImportantList = new LivefeedImportantList();


})();
