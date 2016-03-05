<?php

namespace Galmi\XacmlPAPBundle\Request\ParamConverter;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PostConverter implements ParamConverterInterface
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var ValidatorInterface */
    private $validator;

    private $validationErrorsArgument = 'validationErrors';

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * Stores the object in the request.
     *
     * @param Request $request The request
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @return bool True if the object has been successfully set, else false
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $content = $request->getContent();
        $data = json_decode($content, true);
        if (isset($data[$configuration->getName()])) {
            $content = json_encode($data[$configuration->getName()]);
            $object = $this->serializer->deserialize($content, $configuration->getClass(), 'json');
        } else {
            $object = $this->serializer->deserialize($content, $configuration->getClass(), 'json');
        }
        $request->attributes->set($configuration->getName(), $object);

        if ($this->validator) {
            $errors = $this->validator->validate($object);
            $request->attributes->set(
                $this->validationErrorsArgument,
                $errors
            );
        }
    }

    /**
     * Checks if the object is supported.
     *
     * @param ParamConverter $configuration Should be an instance of ParamConverter
     *
     * @return bool True if the object is supported, else false
     */
    public function supports(ParamConverter $configuration)
    {
        return null !== $configuration->getClass() && $configuration->getConverter() == 'galmi_xacml_post_converter';
    }
}