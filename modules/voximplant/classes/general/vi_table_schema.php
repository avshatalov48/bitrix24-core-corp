<?

class CVoxImplantTableSchema
{
	public function __construct()
	{
	}

	public static function OnGetTableSchema()
	{
		return array(
			"main" => array(
				"b_user" => array(
					"ID" => array(
						"b_voximplant_statistic" => "PORTAL_USER_ID",
					),
				),
			),
		);
	}
}
?>