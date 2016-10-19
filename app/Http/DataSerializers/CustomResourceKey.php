<?php

namespace App\Http\DataSerializers;

use League\Fractal\Serializer\ArraySerializer;

class CustomResourceKey extends ArraySerializer
{
	public function collection($resourceKey, array $data)
	{
		return $resourceKey === false ? $data : [$resourceKey => $data];
	}

	public function item($resourceKey, array $data)
	{
		return $resourceKey === false ? $data : [$resourceKey => $data];
	}
}