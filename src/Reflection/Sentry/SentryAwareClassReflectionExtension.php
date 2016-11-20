<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Sentry;

use Consistence\Sentry\Metadata\SentryAccess;
use Consistence\Sentry\Metadata\Visibility;
use Consistence\Sentry\MetadataSource\MetadataSource;
use Consistence\Sentry\SentryAware;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;

class SentryAwareClassReflectionExtension implements MethodsClassReflectionExtension, BrokerAwareClassReflectionExtension
{

	/** @var \Consistence\Sentry\MetadataSource\MetadataSource */
	private $metadataSource;

	/** @var \PHPStan\Reflection\Php\PhpClassReflectionExtension */
	private $phpClassReflectionExtension;

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	public function __construct(
		MetadataSource $metadataSource,
		PhpClassReflectionExtension $phpClassReflectionExtension
	)
	{
		$this->metadataSource = $metadataSource;
		$this->phpClassReflectionExtension = $phpClassReflectionExtension;
	}

	public function setBroker(Broker $broker)
	{
		$this->broker = $broker;
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

		$propertyClass = $this->broker->getClass($property->getClassName());

		return new SentryMethodReflection(
			$methodName,
			$propertyClass,
			$sentryMethod->getMethodVisibility(),
			$this->phpClassReflectionExtension->getProperty($propertyClass, $property->getName())->getType(),
			$methodHasParameter ? ($isSetter ? $property->isNullable() : false) : null
		);
	}

}
