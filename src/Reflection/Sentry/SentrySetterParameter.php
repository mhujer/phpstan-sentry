<?php declare(strict_types=1);

namespace PHPStan\Reflection\Sentry;

use PHPStan\Reflection\ParameterReflection;

class SentrySetterParameter implements ParameterReflection
{

	public function isOptional(): bool
	{
		return false;
	}

}
