<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class Category extends Dto
{
	/** @var int|null */
	public $id;

	/** @var string|null */
	public $name;

	/** @var bool */
	public $editable;

	/** @var bool */
	public $isDefault;

	/** @var int|null */
	public $sort;

	/** @var bool */
	public $categoriesSupported = false;

	/** @var bool */
	public $categoriesEnabled = false;

	/** @var bool */
	public $stagesEnabled = false;

	/** @var bool */
	public $tunnelsEnabled = false;

	/** @var string|null */
	public $access;

	/** @var int|null */
	public $counter;

	/** @var Tunnel[]|null */
	public $tunnels = [];

	/** @var Stage[]|null */
	public $processStages = [];

	/** @var Stage[]|null */
	public $successStages = [];

	/** @var Stage[]|null */
	public $failedStages = [];

	/** @var DocumentField[]|null */
	public $documentFields;

	public function getCasts(): array
	{
		return [
			'id' => Type::int(),
			'name' => Type::string(),
			'editable' => Type::bool(),
			'isDefault' => Type::bool(),
			'sort' => Type::int(),
			'categoriesSupported' => Type::bool(),
			'categoriesEnabled' => Type::bool(),
			'stagesEnabled' => Type::bool(),
			'tunnelsEnabled' => Type::bool(),
			'counter' => Type::int(),
			'access' => Type::string(),
			'tunnels' => Type::collection(Tunnel::class),
			'processStages' => Type::collection(Stage::class),
			'successStages' => Type::collection(Stage::class),
			'failedStages' => Type::collection(Stage::class),
			'documentFields' => Type::collection(DocumentField::class),
		];
	}
}
