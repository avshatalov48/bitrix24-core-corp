const
	BX = window.BX,
	BXMobileApp = window.BXMobileApp;

const nodeSelectUser = (function ()
{
	const nodeSelectUser = function (select, eventNode, useOnChangeEvent)
	{
		this.click = BX.delegate(this.click, this);
		this.callback = BX.delegate(this.callback, this);
		this.drop = BX.delegate(this.drop, this);
		this.select = BX(select);
		this.container = this.select.nextElementSibling;
		this.eventNode = BX(eventNode);
		this.useOnChangeEvent = useOnChangeEvent || false;
		BX.bind(this.eventNode, "click", this.click);
		this.multiple = select.hasAttribute("multiple");
		this.showDrop = (!(
			select.hasAttribute("bx-can-drop")
			&&
			select.getAttribute("bx-can-drop").toString() == "false"
		));
		this.urls = {
			"list": BX.message('SITE_DIR') + 'mobile/index.php?mobile_action=get_user_list',
			"profile": BX.message("interface_form_user_url")
		};
		this.actualizeNodes();
	};
	nodeSelectUser.prototype = {
		click: function (e)
		{
			this.show();
			return BX.PreventDefault(e);
		},
		show: function ()
		{
			(new BXMobileApp.UI.Table({
				url: this.urls.list,
				table_settings: {
					callback: this.callback,
					markmode: true,
					multiple: this.multiple,
					return_full_mode: true,
					skipSpecialChars: true,
					modal: true,
					alphabet_index: true,
					outsection: false,
					okname: BX.message("interface_form_select"),
					cancelname: BX.message("interface_form_cancel")
				}
			}, "users")).show();
		},
		drop: function ()
		{
			const node = BX.proxy_context,
				id = node.id.replace(this.select.id + '_del_', '');

			for (let ii = 0; ii < this.select.options.length; ii++)
			{
				if (this.select.options[ii].value === id)
				{
					BX.remove(BX.findParent(node, {
						"tagName": "DIV",
						"className": "mobile-grid-field-select-user-item-outer"
					}));
					BX.remove(this.select.options[ii]);
				}
			}

			if (this.select.options.length <= 0 && !this.multiple)
			{
				this.eventNode.innerHTML = BX.message('interface_form_select');
			}

			if (this.useOnChangeEvent)
			{
				BX.onCustomEvent(this, "onChange", [this, this.select]);
			}
		},
		actualizeNodes: function ()
		{
			for (let ii = 0; ii < this.select.options.length; ii++)
			{
				if (BX(this.select.id + '_del_' + this.select.options[ii].value))
				{
					BX.bind(BX(this.select.id + '_del_' + this.select.options[ii].value), "click", this.drop);
				}
			}
		},
		buildNodes: function (items)
		{
			let options = '',
				html = '',
				ii, c = 0,
				user, existedUsers = [];
			for (let ii = 0; ii < this.select.options.length; ii++)
			{
				existedUsers.push(this.select.options[ii].value.toString());
				c++;
			}
			for (let ii = 0; ii < Math.min((this.multiple ? items.length : 1), items.length); ii++)
			{
				user = items[ii];
				if (existedUsers.includes(user['ID']))
				{
					continue;
				}
				options += '<option value="' + user['ID'] + '" selected>' + user["NAME"] + '</option>';
				html += ([
					'<div class="mobile-grid-field-select-user-item-outer">',
					'<div class="mobile-grid-field-select-user-item">',
					(this.showDrop ? '<del id="' + this.select.id + '_del_' + user["ID"] + '"></del>' : ''),
					'<div class="avatar"', (user["IMAGE"] ? ' style="background-image:url(\'' + user["IMAGE"] + '\')"' : ''), '></div>',
					'<span onclick="BXMobileApp.Events.postToComponent(\'onUserProfileOpen\', '+[user['ID']]+', \'communication\');">' + user["NAME"] + '</span>',
					'</div>',
					'</div>'
				].join('').replace(' style="background-image:url(\'\')"', ''));
				c++;
			}

			if (html !== '')
			{
				this.select.innerHTML = (this.multiple ? this.select.innerHTML : '') + options;
				this.container.innerHTML = (this.multiple ? this.container.innerHTML : '') + html;
				if (this.select.innerHTML !== '' && !this.multiple)
				{
					this.eventNode.innerHTML = BX.message('interface_form_change');
				}

				if (this.useOnChangeEvent)
				{
					BX.onCustomEvent(this, "onChange", [this, this.select]);
				}

				let ij = 0,
					f = BX.proxy(function ()
					{
						if (ij < 100)
						{
							if (this.container.childNodes.length > 0)
							{
								this.actualizeNodes();
							}
							else if (ij++)
							{
								setTimeout(f, 50);
							}
						}
					}, this);
				setTimeout(f, 50);
			}
		},
		callback: function (data)
		{
			if (data && data.a_users)
			{
				this.buildNodes(data.a_users);
			}
		}
	};
	return nodeSelectUser;
})();

window.app.exec('enableCaptureKeyboard', true);

BX.Mobile.Field.SelectUser = function (params)
{
	this.useOnChangeEvent = params['useOnChangeEvent'] || false;
	this.init(params);
};

BX.Mobile.Field.SelectUser.prototype = {
	__proto__: BX.Mobile.Field.prototype,
	bindElement: function (node)
	{
		let result = null;
		if (BX(node))
		{
			result = new nodeSelectUser(node, BX(`${node.id}_select`), this.useOnChangeEvent);
		}
		return result;
	}
};
