<?php declare(strict_types=1);

namespace PHPStan\Reflection\Sentry;

use Consistence\Sentry\Metadata\SentryAccess;
use Consistence\Sentry\Metadata\Visibility;
use Consistence\Sentry\MetadataSource\MetadataSource;
use Consistence\Sentry\SentryAware;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ClassReflectionExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;

class SentryAwareClassReflectionExtension implements ClassReflectionExtension, BrokerAwareClassReflectionExtension
{

	/** @var \Consistence\Sentry\MetadataSource\MetadataSource */
	private $metadataSource;

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	public function __construct(MetadataSource $metadataSource)
	{
		$this->metadataSource = $metadataSource;
	}

	public function setBroker(Broker $broker)
	{
		$this->broker = $broker;
	}

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return false;
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
	{
		return false;
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		if (!$classReflection->getNativeReflection()->implementsInterface(SentryAware::class)) {
			return false;
		}

		$metadata = $this->metadataSource->getMetadataForClass($classReflection->getNativeReflection());

		try {
			$metadata->getSentryMethodByNameAndRequiredVisibility($methodName, Visibility::get(Visibility::VISIBILITY_PRIVATE));
			return true;
		} catch (\Consistence\Sentry\Metadata\MethodNotFoundException $e) {
			return false;
		}
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		if (!$classReflection->getNativeReflection()->implementsInterface(SentryAware::class)) {
			return false;
		}

		$metadata = $this->metadataSource->getMetadataForClass($classReflection->getNativeReflection());
		$sentryMethodSearchResult = $metadata->getSentryMethodByNameAndRequiredVisibility($methodName, Visibility::get(Visibility::VISIBILITY_PRIVATE));
		$property = $sentryMethodSearchResult->getProperty();
		$sentryMethod = $sentryMethodSearchResult->getSentryMethod();
		$sentryAccess = $sentryMethod->getSentryAccess();
		$isSetter = $sentryAccess->equals(new SentryAccess('set'));
		$methodHasParameter = $isSetter
			|| $sentryAccess->equals(new SentryAccess('add'))
			|| $sentryAccess->equals(new SentryAccess('remove'))
			|| $sentryAccess->equals(new SentryAccess('contains'));
		return new SentryMethodReflection(
			$this->broker->getClass($property->getClassName()),
			$sentryMethod->getMethodVisibility(),
			$methodHasParameter ? ($isSetter ? $property->isNullable() : false) : null
		);
	}
}
