/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
this.BX.Intranet.Invitation = this.BX.Intranet.Invitation || {};
(function (exports,ui_buttons,main_core) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;
	class Row {
	  constructor(rowOptions) {
	    this.email = null;
	    this.name = null;
	    this.lastName = null;
	    this.cache = new main_core.Cache.MemoryCache();
	    const options = main_core.Type.isPlainObject(rowOptions) ? rowOptions : {};
	    if (main_core.Type.isStringFilled(options.email)) {
	      this.getEmailTextBox().value = options.email;
	    }
	    if (main_core.Type.isStringFilled(options.name)) {
	      this.getNameTextBox().value = options.name;
	    }
	    if (main_core.Type.isStringFilled(options.lastName)) {
	      this.getLastNameTextBox().value = options.lastName;
	    }
	  }
	  isEmpty() {
	    const email = this.getEmailTextBox().value.trim();
	    return !main_core.Type.isStringFilled(email);
	  }
	  validate() {
	    const email = this.getEmail();
	    const name = this.getName();
	    const lastName = this.getLastName();
	    if (main_core.Type.isStringFilled(email)) {
	      const atom = '=_0-9a-z+~\'!\$&*^`|\\#%/?{}-';
	      const regExp = new RegExp('^[' + atom + ']+(\\.[' + atom + ']+)*@(([-0-9a-z]+\\.)+)([a-z0-9-]{2,20})$', 'i');
	      if (!email.match(regExp)) {
	        main_core.Dom.addClass(this.getEmailTextBox().parentNode, 'ui-ctl-danger');
	        return false;
	      }
	    } else if (main_core.Type.isStringFilled(name) || main_core.Type.isStringFilled(lastName)) {
	      main_core.Dom.addClass(this.getEmailTextBox().parentNode, 'ui-ctl-danger');
	      return false;
	    }
	    main_core.Dom.removeClass(this.getEmailTextBox().parentNode, 'ui-ctl-danger');
	    return true;
	  }
	  focus() {
	    this.getEmailTextBox().focus();
	  }
	  getEmail() {
	    return this.getEmailTextBox().value.trim();
	  }
	  getName() {
	    return this.getNameTextBox().value.trim();
	  }
	  getLastName() {
	    return this.getLastNameTextBox().value.trim();
	  }
	  getContainer() {
	    return this.cache.remember('container', () => {
	      return main_core.Tag.render(_t || (_t = _`
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="ui-ctl-label-text">${0}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox">
							${0}
						</div>
					</div>
					<div class="invite-form-col">
						<div class="ui-ctl-label-text">${0}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox">
							${0}
						</div>
					</div>
					<div class="invite-form-col">
						<div class="ui-ctl-label-text">${0}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox">
							${0}
						</div>
					</div>
				</div>
			`), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_FIELD_EMAIL'), this.getEmailTextBox(), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_FIELD_NAME'), this.getNameTextBox(), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_FIELD_LAST_NAME'), this.getLastNameTextBox());
	    });
	  }
	  getEmailTextBox() {
	    return this.cache.remember('email', () => {
	      return main_core.Tag.render(_t2 || (_t2 = _`
				<input 
					type="email"
					class="ui-ctl-element" 
					placeholder="${0}"
				>
			`), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_ENTER_EMAIL'));
	    });
	  }
	  getNameTextBox() {
	    return this.cache.remember('name', () => {
	      return main_core.Tag.render(_t3 || (_t3 = _`
				<input 
					type="text"
					class="ui-ctl-element" 
				>
			`));
	    });
	  }
	  getLastNameTextBox() {
	    return this.cache.remember('last-name', () => {
	      return main_core.Tag.render(_t4 || (_t4 = _`
				<input 
					type="text"
					class="ui-ctl-element"
				>
			`));
	    });
	  }
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3$1;
	class Form {
	  constructor(formOptions) {
	    this.targetNode = null;
	    this.cache = new main_core.Cache.MemoryCache();
	    this.saveButton = null;
	    this.cancelButton = null;
	    this.rows = [];
	    this.error = null;
	    this.userOptions = {};
	    const options = main_core.Type.isPlainObject(formOptions) ? formOptions : {};
	    this.targetNode = options.targetNode;
	    this.userOptions = main_core.Type.isPlainObject(options.userOptions) ? options.userOptions : {};
	    main_core.Dom.append(this.getContainer(), this.targetNode);
	    if (main_core.Type.isElementNode(options.saveButtonNode)) {
	      this.saveButton = ui_buttons.ButtonManager.createFromNode(options.saveButtonNode);
	      this.saveButton.bindEvent('click', this.handleSaveButtonClick.bind(this));
	    }
	    if (main_core.Type.isElementNode(options.cancelButtonNode)) {
	      this.cancelButton = ui_buttons.ButtonManager.createFromNode(options.cancelButtonNode);
	      this.cancelButton.bindEvent('click', this.handleCancelButtonClick.bind(this));
	    }
	    if (main_core.Type.isArrayFilled(options.rows)) {
	      options.rows.forEach(row => {
	        this.addRow(row);
	      });
	      this.addRows(Math.max(2, 5 - options.rows.length));
	      this.getRows()[0].focus();
	    } else {
	      this.addRows();
	    }
	    main_core.Runtime.loadExtension('ui.hint').then(() => {
	      const hint = BX.UI.Hint.createInstance();
	      const node = hint.createNode(main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_HINT'));
	      const title = document.querySelector('#pagetitle') || this.getTitleContainer();
	      main_core.Dom.append(node, title);
	    });
	  }
	  getRows() {
	    return this.rows;
	  }
	  lock() {
	    main_core.Dom.style(this.getContainer(), 'pointer-events', 'none');
	  }
	  unlock() {
	    main_core.Dom.style(this.getContainer(), 'pointer-events', 'none');
	  }
	  submit() {
	    let valid = true;
	    const guests = [];
	    let invalidRow = null;
	    this.getRows().forEach(row => {
	      if (!row.validate()) {
	        invalidRow = invalidRow || row;
	        valid = false;
	      }
	      if (valid && !row.isEmpty()) {
	        guests.push({
	          email: row.getEmail(),
	          name: row.getName(),
	          lastName: row.getLastName()
	        });
	      }
	    });
	    if (!valid) {
	      return Promise.reject(new main_core.BaseError(main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_WRONG_DATA'), 'wrong_data', {
	        invalidRow
	      }));
	    } else if (!main_core.Type.isArrayFilled(guests)) {
	      return Promise.reject(new main_core.BaseError(main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_EMPTY_DATA'), 'empty_data', {
	        invalidRow: this.getRows()[0]
	      }));
	    }
	    return new Promise((resolve, reject) => {
	      return main_core.ajax.runComponentAction('bitrix:intranet.invitation.guest', 'addGuests', {
	        mode: 'class',
	        json: {
	          guests,
	          userOptions: this.userOptions
	        }
	      }).then(response => {
	        resolve(response);
	      }, reason => {
	        const error = reason && main_core.Type.isArrayFilled(reason.errors) ? reason.errors.map(error => main_core.Text.encode(error.message)).join('<br><br>') : 'Server Response Error';
	        reject(new main_core.BaseError(error, 'wrong_response'));
	      });
	    });
	  }
	  getSaveButton() {
	    return this.saveButton;
	  }
	  getCancelButton() {
	    return this.cancelButton;
	  }
	  getContainer() {
	    return this.cache.remember('container', () => {
	      return main_core.Tag.render(_t$1 || (_t$1 = _$1`
				<div class="invite-wrap">
					${0}
					<div class="invite-content-container">
						${0}
					</div>
					<div class="invite-form-buttons">
						<button 
							class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round"
							onclick="${0}">${0}
						</button>
					</div>
				</div>
			`), this.getTitleContainer(), this.getRowsContainer(), this.handleAddMoreClick.bind(this), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_ADD_MORE'));
	    });
	  }
	  getRowsContainer() {
	    return this.cache.remember('rows-container', () => {
	      return main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
				<div class="invite-form-container"></div>
			`));
	    });
	  }
	  getTitleContainer() {
	    return this.cache.remember('title-container', () => {
	      return main_core.Tag.render(_t3$1 || (_t3$1 = _$1`
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-message"></div>
					<div class="invite-title-text">${0}</div>
				</div>
			`), main_core.Loc.getMessage('INTRANET_INVITATION_GUEST_TITLE'));
	    });
	  }
	  addRow(rowOptions) {
	    const row = new Row(rowOptions);
	    this.rows.push(row);
	    main_core.Dom.append(row.getContainer(), this.getRowsContainer());
	    return row;
	  }
	  addRows(numberOfRows = 5) {
	    Array(numberOfRows).fill().forEach((el, index) => {
	      const row = this.addRow();
	      if (index === 0) {
	        row.focus();
	      }
	    });
	  }
	  removeRows() {
	    this.getRows().forEach(row => {
	      main_core.Dom.remove(row.getContainer());
	    });
	    this.rows = [];
	  }
	  showError(reason) {
	    const animate = this.error === null;
	    this.hideError();
	    this.error = new BX.UI.Alert({
	      color: BX.UI.Alert.Color.DANGER,
	      animated: animate,
	      text: reason
	    });
	    main_core.Dom.prepend(this.error.getContainer(), this.getContainer());
	  }
	  hideError() {
	    if (this.error !== null) {
	      main_core.Dom.remove(this.error.container);
	      this.error = null;
	    }
	  }
	  handleSaveButtonClick() {
	    if (this.getSaveButton().isWaiting()) {
	      return;
	    }
	    this.getSaveButton().setWaiting();
	    this.submit().then(response => {
	      this.getSaveButton().setWaiting(false);
	      this.hideError();
	      this.removeRows();
	      this.addRows();
	      BX.SidePanel.Instance.postMessageAll(window, 'BX.Intranet.Invitation.Guest:onAdd', response.data);
	      BX.SidePanel.Instance.close();
	    }).catch(error => {
	      this.getSaveButton().setWaiting(false);
	      this.showError(error.getMessage());
	      if (error.getCustomData() && error.getCustomData()['invalidRow']) {
	        error.getCustomData()['invalidRow'].focus();
	      }
	    });
	  }
	  handleCancelButtonClick() {
	    BX.SidePanel.Instance.close();
	  }
	  handleAddMoreClick() {
	    const row = this.addRow();
	    row.focus();
	  }
	}

	exports.Form = Form;

}((this.BX.Intranet.Invitation.Guest = this.BX.Intranet.Invitation.Guest || {}),BX.UI,BX));
//# sourceMappingURL=script.js.map
