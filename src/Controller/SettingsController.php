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

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SettingsController extends Controller
{
    private function getRefererParams() {
        $request = $this->getRequest();
        $referer = $request->headers->get('referer');
        $baseUrl = $request->getBaseUrl();
        $lastPath = substr($referer, strpos($referer, $baseUrl) + strlen($baseUrl));
        return $this->get('router')->getMatcher()->match($lastPath);
    }


    private function map($value, $low1, $high1, $low2, $high2) {
        // return ($value / ($high1 - $low1)) * ($high2 - $low2) + $low2;
        // https://github.com/processing/p5.js/blob/master/src/math/calculation.js#L463
        return ($value - $low1) / ($high1 - $low1) * ($high2 - $low2) + $low2;
    }

    /**
     * @Route("/settings", name="settings")
     */
    public function index(Request $request)
    {
        $form = $this->createFormBuilder($this->getUser()->getMarkRange())
            ->add('best', IntegerType::class, ['label' => 'Beste Note'])
            ->add('worst', IntegerType::class, ['label' => 'Schlechteste Note'])
            ->add('round', IntegerType::class, ['label' => 'Runden auf n stellen'])
            ->add('update', CheckboxType::class, [
                'disabled' => false,
                'label'    => 'Alte Noten Anpassen?',
                'required' => false,
                'data' => true,
            ])
            ->add('speichern', SubmitType::class, ['label' => 'Speichern'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $user = $this->getUser();
            $formData = $form->getNormData();
            if ($formData['update']) {
                $oldRange = $this->getUser()->getMarkRange();
            }
            $markRange = [
                'best' => $formData['best'],
                'worst' => $formData['worst'],
                'round' => $formData['round']
            ];
            $subjectRepository = $this->getDoctrine()->getRepository(Subject::class);;
            $yearsRepository = $this->getDoctrine()->getRepository(SchoolYear::class);;
            $years = $yearsRepository->findBy(['user' => $this->getUser()]);
            if ($formData['update']) {
                foreach ($years as $year) {
                    $subjects = $subjectRepository->findBy(['schoolyear' => $year->getId()]);
                    foreach ($subjects as $subject) {
                        foreach ($subject->getMarks() as $mark) {
                            dump([$mark->getMark(), $markRange['best'], $markRange['worst'], 1, 6]);
                            $map = $this->map($mark->getMark(), $oldRange['best'], $oldRange['worst'], 10, 60);
                            $map = $this->map($map, 10, 60, $markRange['best'], $markRange['worst']);
                            dump($mark->getMark() . ' => ' . $map);
                            $mark->setMark($map);
                            $entityManager->persist($mark);
                            $entityManager->flush();
                        }
                    }
                }
            }
            $user->setMarkRange($markRange);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Änderung erfolgreich!');
            $this->redirectToRoute('settings');
        }
        $form = $form->createView();

        return $this->render('settings/index.html.twig', compact('form'));
    }

    /**
     * @Route("/language", name="language_settings")
     */

     public function language(Request $request) {
        return $this->render('settings/language.html.twig');
     }
}
