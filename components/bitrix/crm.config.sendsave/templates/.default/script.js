if (typeof(BX.CrmSendSaveEditor) === 'undefined')
{
    BX.CrmSendSaveEditor = function ()
    {
        this._settings = {};
        this._currentMailbox = null;
    };

    BX.CrmSendSaveEditor.prototype =
    {
        initialize: function(settings)
        {
            this._settings = settings ? settings : {};

            var form = this.getForm();

            var mailboxLst = BX.findChild(form, { 'tag':'select', 'attr':{ 'name': 'MAILBOX' } }, true, false);
            if(mailboxLst)
            {
                BX.bind(
                    mailboxLst,
                    'change',
                    BX.delegate(this._handleMailboxChange, this)
                );
            }

            var sslChbx = BX.findChild(form, { 'tag':'input', 'attr':{ 'type': 'checkbox', 'name': 'SSL' } }, true, false);
            if(sslChbx)
            {
                BX.bind(
                    sslChbx,
                    'click',
                    BX.delegate(this._handleSslClick, this)
                );
            }

			var createLeadChbx = BX.findChild(form, { 'tag':'input', 'attr':{ 'type': 'checkbox', 'name': 'CREATE_LEAD_FOR_NEW_ADDRESSER' } }, true, false);
			if(createLeadChbx)
	        {
				this._adjustRowVisibility(
					BX.findChild(form, { 'tag':'input', 'attr':{ 'type': 'text', 'name': 'LEAD_RESPONSIBLE_SEARCH' } }, true, false),
					createLeadChbx.checked
				);

                BX.bind(
                    createLeadChbx,
                    'click',
                    BX.delegate(this._handleCreateLeadClick, this)
                );
	        }

            this._layout();
        },
        getSetting: function(name, defaultval)
        {
            return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
        },
        getForm: function()
        {
            return document.getElementById(this.getSetting('FORM_ID'));
        },
        _layout: function()
        {
            var mailboxFld = BX.findChild(this.getForm(), { 'tag':'input', 'attr':{ 'name': 'MAILBOX_ID' } }, true, false);
            if(!mailboxFld)
            {
                return;
            }

            var id = mailboxFld.value;

            if(id === '-1')
            {
                // not selected
                this._currentMailbox = BX.CrmSendSaveMailbox.create({}, this);
                this._currentMailbox.layout();
            }
            else if(id === '0')
            {
                // new pop3 mailbox
                this._currentMailbox = BX.CrmSendSaveMailbox.create({ 'SERVER_TYPE': 'pop3' }, this);
                this._currentMailbox.layout();
            }
            else
            {
                var mailboxSettings = this.getSetting('MAILBOXES');
                if(mailboxSettings)
                {
                    for(var i = 0; i < mailboxSettings.length; i++)
                    {
                        var setting = mailboxSettings[i];
                        if(setting['ID'] === id)
                        {
                            this._currentMailbox = BX.CrmSendSaveMailbox.create(setting, this);
                            this._currentMailbox.layout();
                            break;
                        }
                    }
                }
            }
        },
        _handleMailboxChange: function()
        {
            var mailboxLst = BX.findChild(this.getForm(), { 'tag':'select', 'attr':{ 'name': 'MAILBOX' } }, true, false);
            if(!mailboxLst)
            {
                return;
            }

            var mailboxFld = BX.findChild(this.getForm(), { 'tag':'input', 'attr':{ 'name': 'MAILBOX_ID' } }, true, false);
            if(!mailboxFld)
            {
                return;
            }

            mailboxFld.value = mailboxLst.selectedIndex >= 0 ? mailboxLst.options[mailboxLst.selectedIndex].value : '-1';
            this._layout();
        },
        _handleSslClick: function()
        {
            var form = this.getForm();

            var ssl = BX.findChild(form, { 'tag':'input', 'attr':{ 'type': 'checkbox', 'name': 'SSL' } }, true, false);
            if(!ssl)
            {
                return;
            }

            var port = BX.findChild(form, { 'tag':'input', 'attr':{ 'type': 'text', 'name': 'PORT' } }, true, false);
            if(port)
            {
                port.value = ssl.checked ? '995' : '110';
            }
        },
        _handleCreateLeadClick: function()
        {
            var form = this.getForm();

			var chbx = BX.findChild(form, { 'tag':'input', 'attr':{ 'type': 'checkbox', 'name': 'CREATE_LEAD_FOR_NEW_ADDRESSER' } }, true, false);
			if(chbx)
			{
				this._adjustRowVisibility(
					BX.findChild(form, { 'tag':'input', 'attr':{ 'type': 'text', 'name': 'LEAD_RESPONSIBLE_SEARCH' } }, true, false),
					chbx.checked
				);
			}
        },
        _adjustRowVisibility: function(element, visible)
        {
			var row = BX.findParent(element, { 'tag':'tr' }, true, false);
			if(row)
			{
				row.style.display = !!visible ? '' : 'none';
			}
        }
    };

    BX.CrmSendSaveEditor._default = null;
    BX.CrmSendSaveEditor.createDefault = function(settings)
    {
        var self = new BX.CrmSendSaveEditor();
        self.initialize(settings);
        return (this._default = self);
    };

    BX.CrmSendSaveEditor.getDefault = function()
    {
        if(!this._default)
        {
            this.createDefault(null);
        }
        return this._default;
    };

    BX.CrmSendSaveMailbox = function()
    {
        this._settings = {};
        this._editor = null;
    };

    BX.CrmSendSaveMailbox.prototype =
    {
        initialize: function(settings, editor)
        {
            this._settings = settings ? settings : {};
            this._editor = editor;
        },
        getSetting: function(name, defaultval)
        {
            return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
        },
        _display: function(elem, display)
        {
            var row = BX.findParent(elem, { 'tag':'tr' });
            if(row)
            {
                row.style.display = (display ? '' : 'none');
            }
        },
        _displayHeaders: function(display, offset)
        {
            var headers = BX.findChildren(this._editor.getForm(), { 'tag':'td', 'class':'bx-heading' }, true);
            if(headers && headers.length >= (offset + 1))
            {
                for(var i = offset; i < headers.length; i++)
                {
                    headers[i].style.display = (display ? '' : 'none');
                }
            }
        },
        _findText: function(name)
        {
            return BX.findChild(this._editor.getForm(), { 'tag':'input', 'attr':{ 'type': 'text', 'name': name } }, true, false);
        },
        _findCheckbox: function(name)
        {
            return BX.findChild(this._editor.getForm(), { 'tag':'input', 'attr':{'type': 'checkbox', 'name': name } }, true, false);
        },
        _findPassword: function(name)
        {
            return BX.findChild(this._editor.getForm(), { 'tag':'input', 'attr':{ 'type': 'password', 'name': name } }, true, false);
        },
        _findSelect: function(name)
        {
            return BX.findChild(this._editor.getForm(), { 'tag':'select', 'attr':{ 'name': name } }, true, false);
        },
        _setupText: function(name, val, display)
        {
            var elem = this._findText(name);
            if(elem)
            {
                elem.value = val;
                this._display(elem, display);
            }
        },
        _setupCheckbox: function(name, val, display)
        {
            var elem = this._findCheckbox(name);
            if(elem)
            {
                if(val !== true && val !== false)
                {
                    val = val.toString().toUpperCase() === 'Y';
                }

                elem.checked = val;
                this._display(elem, display);
            }
        },
        _setupPassword: function(name, val, display)
        {
            var elem = this._findPassword(name);
            if(elem)
            {
                elem.value = val;
                this._display(elem, display);
            }
        },
        _setupSelect: function(name, items, display)
        {
            var elem = this._findSelect(name);
            if(elem)
            {
                this._display(elem, display);

                while(elem.options.length > 0)
                {
                    elem.options.remove(0)
                }

                for(var i = 0; i < items.length; i++)
                {
                    var item = items[i];
                    var t, v;
                    if(typeof(item) === 'object')
                    {
                        if(typeof(item['VALUE']) == 'undefined')
                        {
                            continue;
                        }

                        t = typeof(item['TEXT']) != 'undefined' ? item['TEXT'] : item['VALUE'];
                        v = item['VALUE'];
                    }
                    else
                    {
                        if(!BX.type.isString(item))
                        {
                            item = item.toString();
                        }

                        t = v = item;
                    }

                    var option = document.createElement('OPTION');
                    option.text = t;
                    option.value = v;
                    elem.options.add(option);
                }
            }
        },
        layout: function()
        {
            var serverType = this.getSetting('SERVER_TYPE', '');

            if(serverType === 'pop3')
            {
                this._setupCheckbox('ACTIVE', this.getSetting('ACTIVE', true), true);
                this._setupText('POP3_EMAIL', this.getSetting('POP3_EMAIL', ''), true);
                this._setupText('SERVER', this.getSetting('SERVER', ''), true);
                this._setupText('PORT', this.getSetting('PORT', '995'), true);
                this._setupCheckbox('SSL', this.getSetting('SSL', true), true);
                this._setupCheckbox('SKIP_CERT', this.getSetting('SKIP_CERT', false), true);
                this._setupText('LOGIN', this.getSetting('LOGIN', ''), true);
                this._setupPassword('PASSWORD', this.getSetting('PASSWORD', ''), true);
                this._setupCheckbox('DELETE', this.getSetting('DELETE', false), true);
                this._setupText('PERIOD_CHECK', this.getSetting('PERIOD_CHECK', '5'), true);
                this._setupText('SMTP_EMAIL', '', false);
                this._setupSelect('SMTP_DOMAIN', [], false);

                this._displayHeaders(true, 1);

                this._setupText('REGEXP_LEAD', this.getSetting('REGEXP_LEAD', '\\[LID#([0-9]+)\\]'), true);
                this._setupText('REGEXP_CONTACT', this.getSetting('REGEXP_CONTACT', '\\[CID#([0-9]+)\\]'), true);
                this._setupText('REGEXP_COMPANY', this.getSetting('REGEXP_COMPANY', '\\[COID#([0-9]+)\\]'), true);
                this._setupText('REGEXP_DEAL', this.getSetting('REGEXP_DEAL', '\\[DID#([0-9]+)\\]'), true);
            }
            else if(serverType === 'smtp')
            {
                this._setupCheckbox('ACTIVE', true, false);
                this._setupText('POP3_EMAIL', '', false);
                this._setupText('SERVER', '', false);
                this._setupText('PORT', '', false);
                this._setupCheckbox('SSL', true, false);
                this._setupText('LOGIN', '', false);
                this._setupPassword('PASSWORD', '', false);
                this._setupCheckbox('DELETE', false, false);
                this._setupText('PERIOD_CHECK', '', false);

                this._setupText('SMTP_EMAIL', this.getSetting('SMTP_EMAIL', ''), true);
                this._setupSelect('SMTP_DOMAIN', this.getSetting('SMTP_DOMAIN', []), true);

                this._displayHeaders(true, 1);

                this._setupText('REGEXP_LEAD', this.getSetting('REGEXP_LEAD', '\\[LID#([0-9]+)\\]'), true);
                this._setupText('REGEXP_CONTACT', this.getSetting('REGEXP_CONTACT', '\\[CID#([0-9]+)\\]'), true);
                this._setupText('REGEXP_COMPANY', this.getSetting('REGEXP_CONTACT', '\\[COID#([0-9]+)\\]'), true);
                this._setupText('REGEXP_DEAL', this.getSetting('REGEXP_CONTACT', '\\[DID#([0-9]+)\\]'), true);
            }
            else
            {
                this._setupCheckbox('ACTIVE', true, false);
                this._setupText('POP3_EMAIL', '', false);
                this._setupText('SERVER', '', false);
                this._setupText('PORT', '', false);
                this._setupCheckbox('SSL', true, false);
                this._setupText('LOGIN', '', false);
                this._setupPassword('PASSWORD', '', false);
                this._setupCheckbox('DELETE', false, false);
                this._setupText('PERIOD_CHECK', '', false);
                this._setupText('SMTP_EMAIL', '', false);
                this._setupSelect('SMTP_DOMAIN', [], false);

                this._setupText('REGEXP_LEAD', '', false);
                this._setupText('REGEXP_CONTACT', '', false);
                this._setupText('REGEXP_COMPANY', '', false);
                this._setupText('REGEXP_DEAL', '', false);

                this._displayHeaders(false, 1);
            }
        }
    };

    BX.CrmSendSaveMailbox.create = function(settings, editor)
    {
        var self = new BX.CrmSendSaveMailbox();
        self.initialize(settings, editor);
        return self;
    };
}
