<?php

namespace App\Controller;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use App\Entity\SchoolYear;
use App\Entity\Subject;
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

    /**
     * @Route("/year/add", name="year_add")
     */
    public function add(Request $request) {
        $subjects = $this->getDoctrine()->getRepository(Subject::class)->findBy(['is_template' => true]);
        $form = $this->createFormBuilder(new SchoolYear())
            ->add('name', TextType::class, ['label' => 'Name'])
            ->add('subjects', ChoiceType::class, [
                'label' => 'Fächer',
                'required' => false,
                'multiple' => true,
                'expanded' => false,
                'data' => $subjects,
                'choices' => $subjects,
                'choice_label' => function($subject, $key, $value) {
                    /** @var Subject $subject */
                    return $subject->getName();
                },
                'choice_value' => function($subject) {
                    return $subject->getId();
                }
                ])
            ->add('save', SubmitType::class, ['label' => 'Hinzufügen'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $formData = $form->getNormData();
            $formData->setChangedAt(new \Datetime());
            $formData->setUser($this->getUser());
            $subjects = null;
            foreach ($formData->getSubjects() as $subject) {
                $s = clone($subject);
                $formData->removeSubject($subject);
                $formData->addSubject($s);
                $entityManager->persist($s);
                $entityManager->flush();
                dump($formData);
            }
            $entityManager->persist($formData);
            $entityManager->flush();
            return $this->redirectToRoute('year');
        }
        $form = $form->createView();
        return $this->render('year/add.html.twig', compact('form'));
    }
}
