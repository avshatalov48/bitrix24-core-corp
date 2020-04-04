var tasksProjectsOverviewNS = {
	userPopupList : function(users)
	{
		this.employeesPopup = null;
		this.employees = users;	// expected keys are ID, NAME, PHOTO, PROFILE, POSITION, IS_HEAD

		this.showEmployees = function()
		{
			if (!this.employeesPopup)
			{
				var data = BX.create('DIV', {props: {className: 'structure-dept-emp-popup'}});

				for (var i=0; i < this.employees.length; i++)
				{
					var obEmployee = BX.create('DIV', {
						props: {className: 'structure-boss-block'},
						attrs: {
							'title': this.employees[i].NAME,
							'data-user': this.employees[i].ID
						},

						html: '<a' + (this.employees[i].PHOTO ? ' style="background: url(\''+this.employees[i].PHOTO+'\') no-repeat scroll center center transparent; background-size: cover;"' : '') + ' class="structure-avatar" href="'+this.employees[i].PROFILE+'"></a><div class="structure-employee-name"><a href="'+this.employees[i].PROFILE+'">' + this.employees[i].NAME + '</a></div>' + (this.employees[i].POSITION ? '<div class="structure-employee-post">'+BX.util.htmlspecialchars(this.employees[i].POSITION)+'</div>' : '')
					});

					if (this.employees[i].IS_HEAD)
					{
						obEmployee.className += ' bx-popup-head';
						if (data.firstChild)
						{
							data.insertBefore(obEmployee, data.firstChild);
							continue;
						}
					}

					data.appendChild(obEmployee);
				}

				this.employeesPopup = new BX.PopupWindow('vis_emp_' + Math.random(), BX.proxy_context, {
					closeByEsc: true,
					autoHide: true,
					lightShadow: true,
					zIndex: 2,
					content: data,
					offsetLeft: 50,
					angle : true
				});
			};

			this.employeesPopup.show();
		}
	}
}
