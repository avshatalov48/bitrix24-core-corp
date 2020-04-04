(()=>{
	/**
	 * @class DepartmentsList
	 */
	class DepartmentsList extends BaseList
	{
		static method()
		{
			return "mobile.intranet.departments.get"
		}

		static id()
		{
			return "departments";
		}

		static prepareItemForDrawing(department)
		{
			if(department.full_name && department.name)
			{
				return {
					title: department.name,
					subtitle: department.full_name,
					sectionCode: DepartmentsList.id(),
					color: "#5D5C67",
					useLetterImage: true,
					id: department.id,
					sortValues: {
						name: department.name
					},
					params: {
						id: department.id,
					},
				}
			}

			return department;

		}
	}

	jnexport(DepartmentsList);

})();