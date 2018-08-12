<?php

namespace App\Controller;

use App\Entity\Subject;
use App\Entity\SchoolYear;
use App\Entity\Mark;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SubjectController extends AbstractController
{
    private $translator;
    private $authChecker;

    public function __construct(TranslatorInterface $translator, AuthorizationCheckerInterface $authChecker) {
        $this->translator = $translator;
        $this->authChecker = $authChecker;
    }

    private function map($value, $low1, $high1, $low2, $high2) {
        // https://github.com/processing/p5.js/blob/master/src/math/calculation.js#L463
        return ($value - $low1) / ($high1 - $low1) * ($high2 - $low2) + $low2;
    }

    /**
     * @Route("/subject/{year}/add", name="subject_add")
     */
    public function add(SchoolYear $year, Request $request) {
        $years = $this->getDoctrine()->getRepository(SchoolYear::class)->findBy(['user' => $this->getUser()]);
        $_subject = new Subject();
        $_subject->setSchoolYear($year);
        $form = $this->createFormBuilder($_subject)
            ->add('name', TextType::class, ['label' => 'Name'])
            ->add('schoolyear', ChoiceType::class, [
                'choices' => $this->getUser()->getSchoolYears(),
                'choice_translation_domain' => false,
                'label' => 'Schuljahr',
                'choice_label' => function($year, $key, $value) {
                    /** @var SchoolYear $subject */
                    return $year->getName();
                }, 'choice_value' => function (SchoolYear $year) {
                    /** @var SchoolYear $subject */
                    return $year->getId();
                }
                ]);


        $form = $form->add('save', SubmitType::class, ['label' => 'Speichern'])
            ->getForm();

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager = $this->getDoctrine()->getManager();
                $formData = $form->getNormData();
                $entityManager->persist($formData);
                $entityManager->flush();
                $this->addFlash('success', $this->translator->trans("Fach erfolgreich hinzugefügt"));
                $this->addFlash('highlight', $formData->getId());
                return $this->redirectToRoute("subject_list_all", ['year'=> $year->getId()]);
            }
            $form = $form->createView();
            return $this->render('subject/add.html.twig', compact('form'));
    }

    /**
     * @Route("/subject/{year}", defaults={"year"=null}, name="subject_list_all")
     */
    public function index($year)
    {
        if (!$year) {
            return $this->redirectToRoute('year');
        }
        $year = $this->getDoctrine()->getRepository(SchoolYear::class)->find($year);
        if (($this->getUser()->getId() != $year->getUser()->getId())) {
            throw $this->createAccessDeniedException($this->translator->trans('Du hast keien Rechte auf diese Seite zuzugreifen'));
        }
        $subjects = $year->getSubjects();
        foreach ($subjects as $item) {
            $marks = $item->getMarks();
            // $marks = [];
            $markRange = $this->getUser()->getMarkRange();
            // foreach ($_marks as $item) {
            //     $marks[] = $item->getMark();
            // }
            if(count($marks)) {
                // $marks = array_filter($marks);
                // $avgMark = array_sum($marks)/count($marks);
                $sum = 0;
                $amount = 0;
                foreach ($marks as $mark) {
                    $sum += $mark->getMark()*$mark->getWeight();
                    $amount += $mark->getWeight();
                }
                $avgMark = $sum /$amount;
                $map = $this->map($avgMark, $markRange['best'], $markRange['worst'], 1, 6);
                if ($map < 2.49) {
                    $markColor = 'success';
                } elseif ($map < 4.49) {
                    $markColor = 'warning';
                } else {
                    $markColor = 'danger';
                }
                $averages[] = [
                    'mark' => round($avgMark, $markRange['round']),
                    'markClass' => $markColor
                ];
            } else {
                $averages[] = [
                    "mark" => '',
                   "markClass" => "light"
                ];
            }
        }
        $subjects = $this->getDoctrine()->getRepository(Subject::class)->findBy(['schoolyear' => $year]);
        return $this->render('subject/index.html.twig', compact('subjects', 'averages', 'year'));
    }

    /**
     * @Route("/subject/edit/{subject}/", name="subject_edit")
     */
    public function edit(Subject $subject, Request $request) {

        $form = $this->createFormBuilder($subject)
            ->add('name', TextType::class);
        $form = $form
            ->add('save', SubmitType::class, ['label' => 'Speichern'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager  = $this->getDoctrine()->getManager();
            $formData = $form->getNormData();
            $entityManager->persist($formData);
            $entityManager->flush();
            $this->addFlash("success", "Gespeichert!");
            return $this->redirectToRoute('subject_list_all', ['year' => $subject->getSchoolYear()->getId()]);
        }

        $form = $form->createView();
        return $this->render('subject/edit.html.twig', compact('subject', 'form'));
    }
}
