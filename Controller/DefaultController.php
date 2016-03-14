<?php

namespace Galmi\XacmlPAPBundle\Controller;

use Doctrine\ORM\Mapping\ClassMetadata;
use Galmi\Xacml\CombiningAlgorithmRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('GalmiXacmlPAPBundle:Default:index.html.twig');
    }

    public function attributesAction()
    {
        $attributes = [];

        /** @var ClassMetadata[] $allMetadata */
        $allMetadata = $this->getDoctrine()->getManager()->getMetadataFactory()->getAllMetadata();
        foreach ($allMetadata as $metadata) {
            $namespaceName = explode('\\', $metadata->getName());
            $className = end($namespaceName);
            foreach ($metadata->getFieldNames() as $field) {
                $attributes[] = [
                    'id' => $className.'.'.$field,
                    'name' => $className.'.'.$field,
                    'group' => $className,
                ];
            }
            foreach($metadata->getAssociationMappings() as $association) {
                if ($association['type'] == ClassMetadata::ONE_TO_ONE || $association['type'] == ClassMetadata::MANY_TO_ONE) {
                    $attributes[] = [
                        'id' => $className.'.'.$association['fieldName'],
                        'name' => $className.'.'.$association['fieldName'],
                        'group' => $className,
                    ];
                }
            }
        }

        return new Response($this->get('serializer')->serialize(['attributes' => $attributes], 'json'));
    }

    public function combiningAlgorithmsAction()
    {
        $algorithms = $this->get('galmi_xacml_combining_algorithm_registry')->getAlgorithms();
        $data = [];
        foreach ($algorithms as $id => $name) {
            $data[] = [
                'id' => $id,
                'description' => $id
            ];
        }

        return new Response($this->get('serializer')->serialize(['combiningAlgorithms' => $data], 'json'));
    }

    public function functionEqualitiesAction()
    {
        $functions = $this->get('galmi_xacml_func_registry')->getEqualityFunctions();
        $data = [];
        foreach ($functions as $function) {
            $data[] = [
                'id' => $function,
                'name' => $function
            ];
        }

        return new Response($this->get('serializer')->serialize(['functionEqualities' => $data], 'json'));
    }
}
