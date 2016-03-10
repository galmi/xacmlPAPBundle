<?php

namespace Galmi\XacmlPAPBundle\Controller;


use Galmi\XacmlBundle\Entity\Policy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class PoliciesController extends Controller
{
    /**
     * Get policies list
     *
     * @return Response
     */
    public function getPoliciesAction()
    {
        $policies = $this->getDoctrine()->getRepository('GalmiXacmlBundle:Policy')->findAll();
        return new Response($this->get('serializer')->serialize(['policies' => $policies], 'json'));
    }

    /**
     * Create new policy
     *
     * @ParamConverter("policy", converter="galmi_xacml_post_converter")
     * @param Policy $policy
     * @param ConstraintViolationListInterface $validationErrors
     * @return Response
     */
    public function postPolicyAction(Policy $policy, ConstraintViolationListInterface $validationErrors)
    {
        if ($validationErrors->count() == 0) {
            $policy->setVersion(1);
            $policy->setRuleCombiningAlgId('permit-overrides');
            $this->getDoctrine()->getManager()->persist($policy);
            $this->getDoctrine()->getManager()->flush();
            return new Response($this->get('serializer')->serialize(['policy' => $policy], 'json'));
        }
        return new Response($this->get('serializer')->serialize($validationErrors, 'json'), 400);
    }

    /**
     * @param Policy $policy
     * @return Response
     */
    public function getPolicyAction(Policy $policy)
    {
        return new Response($this->get('serializer')->serialize(['policy' => $policy], 'json'));
    }

    /**
     * @ParamConverter("policy", converter="galmi_xacml_post_converter")
     * @param Policy $originalPolicy
     * @param Policy $policy
     * @param ConstraintViolationListInterface $validationErrors
     * @return Response
     */
    public function putPolicyAction(Request $request, Policy $originalPolicy, Policy $policy, ConstraintViolationListInterface $validationErrors)
    {
        if ($validationErrors->count() == 0) {
            $policy->setId($request->get('id'));
            $this->getDoctrine()->getManager()->merge($policy);
            $this->getDoctrine()->getManager()->flush();
            return new Response($this->get('serializer')->serialize(['policy' => $policy], 'json'));
        }
        return new Response($this->get('serializer')->serialize($validationErrors, 'json'), 400);
    }
}