<?php

namespace Galmi\XacmlPAPBundle\Serializer\Normalizer;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class XacmlNormalizer extends AbstractNormalizer
{

    private $objectManager;
    private $propertyAccessor;

    public function __construct(ClassMetadataFactoryInterface $classMetadataFactory = null, NameConverterInterface $nameConverter = null, PropertyAccessor $propertyAccessor, ObjectManager $objectManager)
    {
        parent::__construct($classMetadataFactory, $nameConverter);

        $this->objectManager = $objectManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->nameConverter = null;
    }

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @param mixed $data data to restore
     * @param string $class the expected class to instantiate
     * @param string $format format the given data was extracted from
     * @param array $context options available to the denormalizer
     *
     * @return object
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $allowedAttributes = $this->getAllowedAttributes($class, $context, true);
        $normalizedData = $this->prepareForDenormalization($data);

        $reflectionClass = new \ReflectionClass($class);
        $object = $this->instantiateObject($normalizedData, $class, $context, $reflectionClass, $allowedAttributes);
        if ($this->objectManager->getMetadataFactory()->hasMetadataFor($class)) {
            $metadata = $this->objectManager->getClassMetadata($class);
        } else {
            $metadata = null;
        }

        foreach ($normalizedData as $attribute => $value) {
            if ($this->nameConverter) {
                $attribute = $this->nameConverter->denormalize($attribute);
            }

            $allowed = $allowedAttributes === false || in_array($attribute, $allowedAttributes);
            $ignored = in_array($attribute, $this->ignoredAttributes);

            if ($allowed && !$ignored) {
                try {
                    $hasAssociation = false;
                    if ($metadata) {
                        $hasAssociation = $metadata->hasAssociation($attribute);
                    }
                    if ($hasAssociation) {
                        $associationMapping = $metadata->getAssociationMapping($attribute);
                        $targetClass = $metadata->getAssociationTargetClass($attribute);
                        if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE && is_scalar($value)) {
                            $id = $value;
                            $value = $this->objectManager->getRepository($targetClass)->find($id);
                        } else if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY && is_array($value)) {
                            $data = $value;
                            $value = [];
                            foreach ($data as $id) {
                                $targetObject = $this->objectManager->getRepository($targetClass)->find($id);
                                if ($targetObject instanceof $targetClass) {
                                    $value[] = $targetObject;
                                }
                            }
                        }
                    } else {
                        $setter = 'set' . $this->camelize($attribute);
                        if ($reflectionClass->hasMethod($setter)) {
                            $reflectionMethod = $parameters = $reflectionClass->getMethod($setter);
                            $reflectionComment  = $reflectionMethod->getDocComment();
                            $outputArray = [];
                            $regExp = "/.*\s+(\S+)\[\]\s+\\$".$attribute."/";
                            preg_match($regExp, $reflectionComment, $outputArray);
                            if (count($outputArray) == 2 && is_array($value)) {
                                $valueNew = [];
                                foreach ($value as $valueOne) {
                                    $type = $outputArray[1];
                                    if ($outputArray[1] == 'Expression' && isset($valueOne['type'])) {
                                        $type = $valueOne['type'];
                                        $type = str_replace('.', '\\', $type);
                                        $type = str_replace('/', '\\', $type);
                                        $type = $this->camelize($type);
                                        $type = 'Galmi\\Xacml\\' .$type;
                                    }
                                    if (!class_exists($type)) {
                                        $type = $reflectionClass->getNamespaceName() . '\\' . $outputArray[1];
                                    }
                                    $valueNew[] = $this->denormalize($valueOne, $type, $format, $context);
                                }
                                $value = $valueNew;
                            } else {
                                $parameters = $reflectionMethod->getParameters();
                                if (count($parameters) === 1) {
                                    $reflectionClassInner = $classInner = $parameters[0]->getClass();
                                    if ($reflectionClassInner) {
                                        $classInner = $reflectionClassInner->getName();
                                        if ($classInner == 'Galmi\\Xacml\\Expression' && isset($value['type'])) {
                                            $type = $value['type'];
                                            $type = str_replace('.', '\\', $type);
                                            $type = str_replace('/', '\\', $type);
                                            $type = $this->camelize($type);
                                            $classInner = 'Galmi\\Xacml\\' .$type;
                                        }
                                        if ($classInner == 'DateTime') {
                                            $value = new \DateTime($value);
                                        } elseif (class_exists($classInner)) {
                                            $value = $this->denormalize($value, $classInner, $format, $context);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $this->propertyAccessor->setValue($object, $attribute, $value);
                } catch (NoSuchPropertyException $exception) {
                    // Properties not found are ignored
                }
            }
        }

        return $object;

    }

    /**
     * Checks whether the given class is supported for denormalization by this normalizer.
     *
     * @param mixed $data Data to denormalize from.
     * @param string $type The class to which the data should be denormalized.
     * @param string $format The format being deserialized from.
     *
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $format == 'json';
    }

    /**
     * Camelizes a given string.
     *
     * @param string $string Some string
     *
     * @return string The camelized version of the string
     */
    private function camelize($string)
    {
        return strtr(ucwords(strtr($string, array('_' => ' ', '-' => ' ')), " \t\r\n\f\v\\\/\."), array(' ' => ''));
    }

    /**
     * Normalizes an object into a set of arrays/scalars.
     *
     * @param object $object object to normalize
     * @param string $format format the normalization result will be encoded as
     * @param array $context Context options for the normalizer
     *
     * @return array|string|bool|int|float|null
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return null;
    }

    /**
     * Checks whether the given class is supported for normalization by this normalizer.
     *
     * @param mixed $data Data to normalize.
     * @param string $format The format being (de-)serialized from or into.
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        return false;
    }
}