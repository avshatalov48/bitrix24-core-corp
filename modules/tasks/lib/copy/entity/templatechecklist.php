<?
namespace Bitrix\Tasks\Copy\Entity;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;

Loc::loadMessages(__FILE__);

class TemplateCheckList extends CheckList
{
	const CHECKLIST_COPY_ERROR = "TEMPLATE_CHECKLIST_COPY_ERROR";

	public function __construct()
	{
		parent::__construct();

		$this->facade = TemplateCheckListFacade::class;
	}
}