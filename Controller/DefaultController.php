<?php

namespace Galmi\XacmlPAPBundle\Controller;

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
        $attributes = [
            [
                'id' => 'Action',
                'name' => 'Action',
                'gorup' => 'Action'
            ],
            [
                'id' => 'Environment',
                'name' => 'Environment',
                'group' => 'Environment'
            ],
            [
                'id' => 'Resource',
                'name' => 'Resource',
                'group' => 'Resource'
            ]
        ];

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
