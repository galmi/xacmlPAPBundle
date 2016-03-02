<?php

namespace Galmi\XacmlPAPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('GalmiXacmlPAPBundle:Default:index.html.twig');
    }
}
