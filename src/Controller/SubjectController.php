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
                'label' => 'Schuljahr',
                'choice_label' => function($year, $key, $value) {
                    /** @var SchoolYear $subject */
                    return $year->getName();
                }, 'choice_value' => function (SchoolYear $year) {
                    /** @var SchoolYear $subject */
                    return $year->getId();
                }
                ])
            ->add('save', SubmitType::class, ['label' => 'Speichern'])
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
        if (($this->getUser()->getId() != $year->getUser()->getId()) && !$this->authChecker->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException($this->translator->trans('Du hast keien Rechte auf diese Seite zuzugreifen'));
        }
        $subjects = $year->getSubjects();
        foreach ($subjects as $item) {
            $_marks = $item->getMarks();
            $marks = [];
            $markRange = $this->getUser()->getMarkRange();
            foreach ($_marks as $item) {
                $marks[] = $item->getMark();
            }
            if(count($marks)) {
                $marks = array_filter($marks);
                $avgMark = array_sum($marks)/count($marks);
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
                    "mark" => 'n/a',
                   "markClass" => "light"
                ];
            }
        }
        $subjects = $this->getDoctrine()->getRepository(Subject::class)->findBy(['schoolyear' => $year]);
        return $this->render('subject/index.html.twig', compact('subjects', 'averages', 'year'));
    }

    /**
     * @Route("/subject/edit/{subject}/", name="subject_edit", methods={"GET","HEAD"})
     */
    public function edit(Subject $subject) {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createFormBuilder($subject)
            ->add('name', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Speichern'])
            ->getForm()
            ->createView();
        return $this->render('subject/edit.html.twig', compact('subject', 'form'));
    }

    /**
     * @Route("/subject/edit/{subject}/", name="subject_edit_save", methods={"POST","PUT"})
     */
    public function edit_save(Request $request, Subject $subject) {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager = $this->getDoctrine()->getManager();
        $form = $request->request->get('form');
        $subject->setName($form['name']);
        $entityManager->persist($subject);
        $entityManager->flush();
        $this->addFlash('highlight', $subject->getId());
        $this->addFlash('success', $this->translator->trans('Gespeichert!'));
        return $this->redirectToRoute('subject_list_all', ['_fragment' => $subject->getId()]);
    }

    /**
     * @Route("/subject/add/", name="subject_add_legacy", methods={"GET","HEAD"})
     */

    public function add_legacy() {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createFormBuilder(new Subject)
            ->add('name', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Hinzufügen'])
            ->getForm()
            ->createView();
        return $this->render('subject/add_legacy.html.twig', compact('subject', 'form'));
    }

    /**
     * @Route("/subject/add/", name="subject_add_save", methods={"POST","PUT"})
     */
    public function add_save(Request $request) {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager = $this->getDoctrine()->getManager();
        $form = $request->request->get('form');
        $subject = new Subject();
        $subject->setName($form['name']);
        $entityManager->persist($subject);
        $entityManager->flush();
        $this->addFlash('highlight', $subject->getId());
        $this->addFlash('success', $this->translator->trans('Fach erfolgreich hinzugefügt!'));
        return $this->redirectToRoute('subject_list_all', ['_fragment' => $subject->getId()]);
    }
}
