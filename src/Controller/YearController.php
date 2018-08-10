<?php

namespace App\Controller;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use App\Entity\SchoolYear;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class YearController extends Controller
{

    private $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }
    /**
     * @Route("/year", name="year")
     */
    public function index()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(SchoolYear::class);
        $schoolyears = $repository->findBy(['user' => $this->getUser()]);
        $highlights = $this->get('session')->getFlashBag()->get('highlight');
        return $this->render('year/index.html.twig', compact('schoolyears', 'highlights'));
    }

    /**
     * @Route("/year/edit/{year}", name="year_edit")
     */
    public function edit(SchoolYear $year, Request $request) {
        $form = $this->createFormBuilder($year)
        ->add('name', TextType::class, ['label' => 'Name'])
        ->add('speichern', SubmitType::class, ['label' => 'Speichern'])
        ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getNormData();
            $year->setName($formData->getName());
            $year->setChangedAt(new \Datetime());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($year);
            $entityManager->flush();
            $this->addFlash('success', 'Änderung erfolgreich!');
            $this->addFlash('highlight', $year->getId());
            return $this->redirectToRoute('year');
        }
        $form = $form->createView();

        return $this->render('year/edit.html.twig', compact('form', 'year'));

        return $this->json([$year->getId()]);
    }
}
