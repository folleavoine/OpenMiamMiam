<?php

/*
 * This file is part of the OpenMiamMiam project.
 *
 * (c) Isics <contact@isics.fr>
 *
 * This source file is subject to the AGPL v3 license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Isics\Bundle\OpenMiamMiamBundle\Controller\Admin\Super;

use Isics\Bundle\OpenMiamMiamBundle\Entity\Producer;
use Isics\Bundle\OpenMiamMiamBundle\Model\Producer\ProducerWithOwner;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ProducerController extends Controller
{
    /**
     * List producers
     *
     * @param Request $request
     *
     * @return Response
     */
    public function listAction(Request $request)
    {
        $associations = $this->getDoctrine()->getRepository('IsicsOpenMiamMiamBundle:Association')->findAll();

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter(
            $this->getDoctrine()->getRepository('IsicsOpenMiamMiamBundle:Producer')
                ->createQueryBuilder('p')
                ->where('p.deletedAt is null')
                ->addOrderBy('p.name')
                ->getQuery()
        ));
        $pagerfanta->setMaxPerPage($this->container->getParameter('open_miam_miam.super.pagination.producer'));

        try {
            $pagerfanta->setCurrentPage($request->query->get('page', 1));
        } catch(NotValidCurrentPageException $e) {
            throw $this->createNotFoundException();
        }

        return $this->render('IsicsOpenMiamMiamBundle:Admin\Super\Producer:list.html.twig', array(
            'associations' => $associations,
            'producers'    => $pagerfanta,
        ));
    }

    /**
     * Create Producer
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createAction(Request $request)
    {
        $producerManager = $this->get('open_miam_miam.producer_manager');
        $producerWithOwner = $producerManager->getProducerWithOwner();

        $form = $this->getForm($producerWithOwner);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $producerManager->saveProducerWithOwner($producerWithOwner, $this->get('security.context')->getToken()->getUser());
                $this->get('session')->getFlashBag()->add('notice', 'admin.super.producers.message.created');

                return $this->redirect($this->generateUrl('open_miam_miam.admin.super.producer.list'));
            }
        }

        return $this->render('IsicsOpenMiamMiamBundle:Admin\Super\Producer:create.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Edit Producer
     *
     * @ParamConverter("producer", class="IsicsOpenMiamMiamBundle:Producer", options={"mapping": {"producerId": "id"}})
     *
     * @param Request  $request
     * @param Producer $producer
     *
     * @return Response
     */
    public function editAction(Request $request, Producer $producer)
    {
        $producerManager = $this->get('open_miam_miam.producer_manager');
        $producerWithOwner = $producerManager->getProducerWithOwner($producer);

        $form = $this->getForm($producerWithOwner);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $producerManager->saveProducerWithOwner($producerWithOwner, $this->get('security.context')->getToken()->getUser());
                $this->get('session')->getFlashBag()->add('notice', 'admin.super.producers.message.updated');

                return $this->redirect($this->generateUrl('open_miam_miam.admin.super.producer.list'));
            }
        }

        return $this->render('IsicsOpenMiamMiamBundle:Admin\Super\Producer:edit.html.twig', array(
            'form'       => $form->createView(),
            'activities' => $producerManager->getActivities($producer),
        ));
    }

    /**
     * Return producer form
     *
     * @param ProducerWithOwner $producerWithOwner
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm(ProducerWithOwner $producerWithOwner)
    {
        if (null === $producerWithOwner->getProducer()->getId()) {
            $action = $this->generateUrl(
                'open_miam_miam.admin.super.producer.create'
            );
        } else {
            $action = $this->generateUrl(
                'open_miam_miam.admin.super.producer.edit',
                array('producerId' => $producerWithOwner->getProducer()->getId())
            );
        }

        return $this->createForm(
            $this->get('open_miam_miam.form.type.super_producer'),
            $producerWithOwner,
            array('action' => $action, 'method' => 'POST')
        );
    }

    /**
     * Delete Producer
     *
     * @ParamConverter("producer", class="IsicsOpenMiamMiamBundle:Producer", options={"mapping": {"producerId": "id"}})
     *
     * @param Producer $producer
     *
     * @return Response
     */
    public function deleteAction(Producer $producer)
    {
        $producerManager = $this->get('open_miam_miam.producer_manager');
        $producerManager->delete($producer, $this->get('security.context')->getToken()->getUser());
        $this->get('session')->getFlashBag()->add('notice', 'admin.super.producers.message.deleted');

        return $this->redirect($this->generateUrl('open_miam_miam.admin.super.producer.list'));
    }
}
