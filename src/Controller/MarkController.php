<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Mark;
use App\Entity\Subject;
use App\Entity\SchoolYear;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


use Symfony\Component\Routing\Annotation\Route;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


class MarkController extends AbstractController
{

    private $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    private function map($value, $low1, $high1, $low2, $high2) {
        // return ($value / ($high1 - $low1)) * ($high2 - $low2) + $low2;
        // https://github.com/processing/p5.js/blob/master/src/math/calculation.js#L463
        return ($value - $low1) / ($high1 - $low1) * ($high2 - $low2) + $low2;
    }

    /**
     * @Route("/mark/", name="mark_list_subjects")
     */
    public function index()
    {
        return $this->redirectToRoute('year');
    }

    /**
     * @Route("/mark/edit", name="mark_edit_none")
     */
    public function edit_empty() {
        $this->addFlash('warning', 'Ey! Nicht mit der URL rumspielen!');
        return $this->redirectToRoute('mark_list_subjects');
    }

    /**
     * @Route("/mark/{subject}/", name="mark_detailed_view")
     */
    public function detailed(Subject $subject) {
        $markRange = $this->getUser()->getMarkRange();
        $round = $markRange['round'];
        $repository = $this->getDoctrine()->getRepository(Mark::class);
        $_marks = $repository->findBy(['subject' => $subject->getId()]);
        $marks = [];
        foreach ($_marks as $mark) {
            $map = $this->map($mark->getMark(), $markRange['best'], $markRange['worst'], 1, 6);
            if ($map < 2.49) {
                $markColor = 'success';
            } elseif ($map < 4.49) {
                $markColor = 'warning';
            } else {
                $markColor = 'danger';
            }
            $marks[] = [
                'id' => $mark->getId(),
                'mark' => $mark->getMark(),
                'title' => $mark->getTitle(),
                'weight' => $mark->getWeight(),
                'createdAt' => $mark->getCreatedAt(),
                'changedAt' => $mark->getChangedAt(),
                'color' => $markColor
            ];
        }

        return $this->render('mark/detail.html.twig', compact('marks', 'subject', 'round'));
    }

    /**
     * @Route("/mark/edit/{mark}", name="mark_edit")
     */
    public function edit(Mark $mark, AuthorizationCheckerInterface $authChecker, Request $request) {

        if ($this->getUser()->getId() != $mark->getSubject()->getSchoolYear()->getUser()->getId()) {
            throw $this->createAccessDeniedException($this->translator->trans('Du hast keien Rechte auf diese Seite zuzugreifen'));
        }

        $subjects = $this->getDoctrine()->getRepository(Subject::class)->findBy(['schoolyear' => $mark->getSubject()->getSchoolYear()]);
        $markRange = $this->getUser()->getMarkRange();
        $editForm = $this->createFormBuilder($mark)
            ->add('title', TextType::class, ['label' => 'Titel'])
            ->add('mark', IntegerType::class, ['label' => 'Note', 'attr' => [
                'min' => ($markRange['best'] > $markRange['worst'] ? $markRange['worst'] : $markRange['best']),
                'max' => ($markRange['best'] < $markRange['worst'] ? $markRange['worst'] : $markRange['best']),
                ]])
            ->add('subject', ChoiceType::class, [
                'choices' => $subjects,
                'label' => 'Fach',
                'choice_label' => function($subject, $key, $value) {
                    /** @var Subject $subject */
                    return $subject->getName();
                }, 'choice_value' => function (Subject $subject) {
                    /** @var Subject $subject */
                    return $subject->getId();
                }
                ])
            ->add('weight', NumberType::class, ['label' => 'Gewichtung'])
            ->add('submit', SubmitType::class, ['label' => 'Speichern'])
            ->getForm();

        $entityManager = $this->getDoctrine()->getManager();
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $formData = $editForm->getNormData();
            $formData->setChangedAt(new \Datetime());
            dump($formData);
            $entityManager->persist($formData);
            $entityManager->flush();
            $this->addFlash('success', $this->translator->trans('Änderungen erfolgreich'));
            return $this->redirectToRoute('mark_detailed_view', ['subject' => $formData->getSubject()->getId()]);
        }
        $editForm = $editForm->createView();


        $subject = $mark->getSubject();
        return $this->render('mark/edit.html.twig', compact(
            'subject'
            ,'editForm'
            // ,'deleteForm'
        ));
        return $this->json([$mark->getId()]);
    }

    /**
     * @Route("/mark/delete/{mark}", name="mark_delete")
     */
    public function delete(Mark $mark, Request $request) {
        if ($this->getUser()->getId() != $mark->getSubject()->getSchoolYear()->getUser()->getId()) {
            throw $this->createAccessDeniedException($this->translator->trans('Du hast keien Rechte auf diese Seite zuzugreifen'));
        }
        $deleteForm = $this->createFormBuilder($mark)
            ->add('delete', SubmitType::class, ['label' => 'Permanent Löschen', 'attr' => ['class' => 'btn-danger']])
            ->getForm();
        $deleteForm->handleRequest($request);
        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $formData = $deleteForm->getNormData();
            $entityManager->remove($formData);
            $entityManager->flush();
            $this->addFlash('success', $this->translator->trans('Löschen erfolgreich'));
            return $this->redirectToRoute('mark_detailed_view', ['subject' => $formData->getSubject()->getId()]);
        }

        $deleteForm = $deleteForm->createView();
        return $this->render('mark/delete.html.twig', compact(
            'deleteForm', 'mark'
        ));
    }

    /**
     * @Route("/mark/add/{year}/{subject}", name="mark_add", defaults={"subject" = null})
     */
    public function add($subject, SchoolYear $year, Request $request) {
        $subjectRepository = $this->getDoctrine()->getRepository(Subject::class);
        $yearRepository = $this->getDoctrine()->getRepository(SchoolYear::class);
        if ($year->getUser()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException($this->translator->trans('Du hast keien Rechte auf diese Seite zuzugreifen'));
        }
        if ($subject !== null) {
            $subject = $subjectRepository->find($subject);
        }
        dump($subject);
        $markRange = $this->getUser()->getMarkRange();
        $subjects = $subjectRepository->findBy(['schoolyear' => $year]);
        $mark = new Mark();
        $mark->setSubject((!$subject) ? new Subject() : $subject);
        $form = $this->createFormBuilder($mark)
            ->add('title', TextType::class,['label' => 'Titel'])
            ->add('mark', TextType::class, ['label' => 'Note'])
            ->add('subject', ChoiceType::class, [
                'choices' => $subjects,
                'label' => 'Fach',
                'choice_label' => function($subject, $key, $value) {
                    /** @var Subject $subject */
                    return $subject->getName();
                }, 'choice_value' => function (Subject $subject) {
                    /** @var Subject $subject */
                    return $subject->getId();
                }, 'attr' => [
                'min' => ($markRange['best'] > $markRange['worst'] ? $markRange['worst'] : $markRange['best']),
                'max' => ($markRange['best'] < $markRange['worst'] ? $markRange['worst'] : $markRange['best']),
                ]
            ])
            ->add('weight', NumberType::class, ['label' => 'Gewichtung'])
            ->add('submit', SubmitType::class, ['label' => 'Speichern'])
            ->getForm();
            if (is_null($subject)) {
                $title = $this->translator->trans('Note Hinzufügen');
            } else {
                $title = $this->translator->trans('Neue Note in %subjectName% hinzufügen', [
                    '%subjectName%' => $this->translator->trans($subject->getName())
                ]);
            }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $formData = $form->getNormData();
            $entityManager->persist($formData);
            $entityManager->flush();
            return $this->redirectToRoute("mark_detailed_view", ['subject' => $subject->getId()]);
        }
        $form = $form->createView();
        return $this->render('mark/add.html.twig', compact('form', 'title'));
    }

    /**
     * @Route("random", name="mark_random")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function random() {

    }
}
