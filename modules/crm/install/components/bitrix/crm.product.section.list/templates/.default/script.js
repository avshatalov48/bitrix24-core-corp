BX.CrmProductSectionManager = function()
{
    this._id = '';
    this._settings = {}; //formID, nameFieldID, actionFieldID
    this._dialogs = {};
};

BX.CrmProductSectionManager.prototype =
{
    initialize: function(id, settings)
    {
        this._id = id;
        this._settings = settings ? settings : {};
    },
    getSetting: function(name, defaultval)
    {
        return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
    },
    getMessage: function(name)
    {
        return typeof(BX.CrmProductSectionManager.messages[name]) != 'undefined' ? BX.CrmProductSectionManager.messages[name] : '';
    },
    addSection: function()
    {
        this._dialogs['ADD'] = {};
        var dlg = this._dialogs['ADD']['POPUP'] = new BX.PopupWindow(
            this._id + 'SectionAdd',
            null,
            {
                autoHide: false,
                draggable: true,
                offsetLeft: 0,
                offsetTop: 0,
                bindOptions: { forceBindPosition: false },
                closeByEsc: true,
                closeIcon: { top: '10px', right: '15px' },
                titleBar: this.getMessage('addDialogTitle'),
                events:
                {
                    onPopupShow: function()
                    {
                    },
                    onPopupClose: BX.delegate(
                        function()
                        {
                            this._dialogs['ADD']['POPUP'].destroy();
                        },
                        this
                    ),
                    onPopupDestroy: BX.delegate(
                        function()
                        {
                            delete(this._dialogs['ADD']);
                        },
                        this
                    )
                },
                content: this._prepareSectionAddDialogContent(),
                buttons: this._prepareSectionAddDialogButtons()
            }
        );

        dlg.show();

        var nameElem = this._dialogs['ADD']['ELEMENTS']['NAME'];
        BX.focus(nameElem);
        nameElem.select();
    },
    _prepareSectionAddDialogContent: function()
    {
        //table
        var tab = BX.create('TABLE');

        // NAME
        var row = tab.insertRow(-1);
        var ttl = row.insertCell(-1);
        ttl.innerHTML = this.getMessage('nameFieldTitle') + ':';
        var cnt = row.insertCell(-1);
        if(!this._dialogs['ADD'])
        {
            this._dialogs['ADD'] = {};
        }

        this._dialogs['ADD']['ELEMENTS'] = {};

        cnt.appendChild(
            this._dialogs['ADD']['ELEMENTS']['NAME'] =
                BX.create(
                    'INPUT',
                    {
                        props:
                        {
                            type: 'text',
                            value: this.getMessage('defaultName')
                        },
                        style:
                        {
                            width:'300px'
                        }
                    }
                )
        );
        return tab;
    },
    _prepareSectionAddDialogButtons: function()
    {
        var result = [];
        result.push(
            new BX.PopupWindowButton(
                {
                    text: this.getMessage('addBtnText'),
                    className: 'popup-window-button-accept',
                    events:
                    {
                        click : BX.delegate(this._hanleSectionAddDialogSave, this)
                    }
                }
            )
        );
        result.push(
            new BX.PopupWindowButtonLink(
                {
                    text: this.getMessage('cancelBtnText'),
                    className: 'popup-window-button-link-cancel',
                    events:
                    {
                        click :
                            function()
                            {
                                this.popupWindow.close();
                            }
                    }
                }
            )
        );
        return result;
    },
    _hanleSectionAddDialogSave: function()
    {
        var form = BX(this.getSetting('formID'));
        var actionField = BX(this.getSetting('actionField'));
        var nameField = BX(this.getSetting('nameField'));
        var nameInput = this._dialogs['ADD']['ELEMENTS']['NAME'];

        if(form && actionField && nameField && nameInput)
        {
            var name = nameInput.value;
            if(!BX.type.isNotEmptyString(name))
            {
                alert(this.getMessage('emptyNameError'));
                return;
            }

            if(nameInput.value.length > 0)
            {
                actionField.value = 'ADD';
                nameField.value = nameInput.value;
                BX.showWait();
                form.submit();
            }
        }
    },
    renameSection: function(id, name)
    {
        this._dialogs['RENAME'] = {};
        this._dialogs['RENAME']['DATA'] = {};

        this._dialogs['RENAME']['DATA']['ID'] = id;
        this._dialogs['RENAME']['DATA']['NAME'] = name;

        var dlg = this._dialogs['RENAME']['POPUP'] = new BX.PopupWindow(
            this._id + 'SectionRename',
            null,
            {
                autoHide: false,
                draggable: true,
                offsetLeft: 0,
                offsetTop: 0,
                bindOptions: { forceBindPosition: false },
                closeByEsc: true,
                closeIcon: { top: '10px', right: '15px' },
                titleBar: this.getMessage('renameDialogTitle'),
                events:
                {
                    onPopupShow: function()
                    {
                    },
                    onPopupClose: BX.delegate(
                        function()
                        {
                            this._dialogs['RENAME']['POPUP'].destroy();
                        },
                        this
                    ),
                    onPopupDestroy: BX.delegate(
                        function()
                        {
                            delete(this._dialogs['RENAME']);
                        },
                        this
                    )
                },
                content: this._prepareSectionRenameDialogContent(),
                buttons: this._prepareSectionRenameDialogButtons()
            }
        );

        dlg.show();

        var nameElem = this._dialogs['RENAME']['ELEMENTS']['NAME'];
        BX.focus(nameElem);
        nameElem.select();
    },
    _prepareSectionRenameDialogContent: function()
    {
        //table
        var tab = BX.create('TABLE');

        // NAME
        var row = tab.insertRow(-1);
        var ttl = row.insertCell(-1);
        ttl.innerHTML = this.getMessage('nameFieldTitle') + ':';
        var cnt = row.insertCell(-1);
        if(!this._dialogs['RENAME'])
        {
            this._dialogs['RENAME'] = {};
        }

        this._dialogs['RENAME']['ELEMENTS'] = {};

        cnt.appendChild(
            this._dialogs['RENAME']['ELEMENTS']['NAME'] =
                BX.create(
                    'INPUT',
                    {
                        props:
                        {
                            type: 'text',
                            value: this._dialogs['RENAME']['DATA']['NAME']
                        },
                        style:
                        {
                            width:'300px'
                        }
                    }
                )
        );
        return tab;
    },
    _prepareSectionRenameDialogButtons: function()
    {
        var result = [];
        result.push(
            new BX.PopupWindowButton(
                {
                    text: this.getMessage('renameBtnText'),
                    className: 'popup-window-button-accept',
                    events:
                    {
                        click : BX.delegate(this._hanleSectionRenameDialogSave, this)
                    }
                }
            )
        );
        result.push(
            new BX.PopupWindowButtonLink(
                {
                    text: this.getMessage('cancelBtnText'),
                    className: 'popup-window-button-link-cancel',
                    events:
                    {
                        click :
                            function()
                            {
                                this.popupWindow.close();
                            }
                    }
                }
            )
        );
        return result;
    },
    _hanleSectionRenameDialogSave: function()
    {
        BX.showWait();

        var form = BX(this.getSetting('formID'));
        var actionField = BX(this.getSetting('actionField'));
        var nameField = BX(this.getSetting('nameField'));
        var idField = BX(this.getSetting('IDField'));

        var nameInput = this._dialogs['RENAME']['ELEMENTS']['NAME'];
        var id = this._dialogs['RENAME']['DATA']['ID'];

        if(form && actionField && nameField && nameInput && idField && id > 0)
        {
            if(nameInput.value.length > 0)
            {
                actionField.value = 'RENAME';
                nameField.value = nameInput.value;
                idField.value = id;
                form.submit();
            }
        }
    }
};

BX.CrmProductSectionManager.getDefault = function()
{
    return this._default;
};

BX.CrmProductSectionManager.items = {};
BX.CrmProductSectionManager._default = null;
BX.CrmProductSectionManager.create = function(id, settings)
{
    var self = new BX.CrmProductSectionManager();
    self.initialize(id, settings);
    this.items[id] = self;

    if(!this._default)
    {
        this._default = self;
    }

    return self;
};

if(typeof(BX.CrmProductSectionManager.messages) == 'undefined')
{
    // STUB
    BX.CrmProductSectionManager.messages = { };
}
