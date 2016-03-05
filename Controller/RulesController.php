<?php

namespace Galmi\XacmlPAPBundle\Controller;


use Galmi\XacmlBundle\Entity\Rule;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class RulesController
 * @package Galmi\XacmlPAPBundle\Controller
 */
class RulesController extends Controller
{

    /**
     * Get rules list
     *
     * @return Response
     */
    public function getRulesAction()
    {
        $rules = $this->getDoctrine()->getRepository('GalmiXacmlBundle:Rule')->findAll();
        return new Response($this->get('serializer')->serialize(['rules' => $rules], 'json'));
    }

    /**
     * Create new rule
     *
     * @ParamConverter("rule", converter="galmi_xacml_post_converter")
     * @param Rule $rule
     * @param ConstraintViolationListInterface $validationErrors
     * @return Response
     */
    public function postRuleAction(Rule $rule, ConstraintViolationListInterface $validationErrors)
    {
        if ($validationErrors->count() == 0) {
            $this->getDoctrine()->getManager()->persist($rule);
            $this->getDoctrine()->getManager()->flush();
            return new Response($this->get('serializer')->serialize(['rule' => $rule], 'json'));
        }
        return new Response($this->get('serializer')->serialize($validationErrors, 'json'), 400);
    }

    /**
     * @param Rule $rule
     * @return Response
     */
    public function getRuleAction(Rule $rule)
    {
        return new Response($this->get('serializer')->serialize(['rule' => $rule], 'json'));
    }

    /**
     * @ParamConverter("rule", converter="galmi_xacml_post_converter")
     * @param Rule $originalRule
     * @param Rule $rule
     * @param ConstraintViolationListInterface $validationErrors
     * @return Response
     */
    public function putRuleAction(Request $request, Rule $originalRule, Rule $rule, ConstraintViolationListInterface $validationErrors)
    {
        if ($validationErrors->count() == 0) {
            $rule->setId($request->get('id'));
            $this->getDoctrine()->getManager()->merge($rule);
            $this->getDoctrine()->getManager()->flush();
            return new Response($this->get('serializer')->serialize(['rule' => $rule], 'json'));
        }
        return new Response($this->get('serializer')->serialize($validationErrors, 'json'), 400);
    }
}