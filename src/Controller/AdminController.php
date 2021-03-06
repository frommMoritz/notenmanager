<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use App\Entity\Subject;
use App\Entity\SchoolYear;
use App\Entity\Mark;
use App\Entity\User;
use App\Entity\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AdminController extends AbstractController
{

    public function __construct(TranslatorInterface $translator, AuthorizationCheckerInterface $authChecker, \Swift_Mailer $mailer) {
        $this->translator = $translator;
        $this->authChecker = $authChecker;
        $this->mailer = $mailer;
    }

    public function random_string($length, $chars = "abcdefghiklmnopqrstuvwxyzABCDEFGHIKLMNOPQRSTUVWXYZ1234567890") {
        $chars = str_split($chars);
        $string = "";
        for ($i=0;$i<mt_rand(100, 1000);$i++) {
            $string .= shuffle($chars)[0];
        }
        return \substr(\hash('sha256', $string), 0, $length);
    }

    /**
     * @Route("/admin", name="admin_dashboard")
     */
    public function index()
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        $templates = $this->getDoctrine()->getRepository(Template::class)->findBy(['is_active' => true, 'is_global' => true]);
        return $this->render('admin/index.html.twig', compact('users', 'templates'));
    }
    /**
     * @Route("/admin/user/{user}/edit", name="admin_user_edit")
     */

     public function user_edit(User $user, Request $request) {  
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $form = $this->createFormBuilder($user)
            ->add('username', TextType::class, [
                'label' => 'Nutzername',
                'attr' => [
                    'readonly' => true
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'help' => 'Email des Nutzers ' . $user->getUsername()
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Nutzerrollen',
                'choices' => ['ROLE_ADMIN', 'ROLE_SUPPORT', 'ROLE_USER'],
                'multiple' => true,
                'choice_label' => function($role, $key, $item) {
                    return $role;
                }, 'choice_value' => function($role) {
                    return $role;
                }
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Speichern'
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $formData = $form->getNormData();
            $user->setEmail($formData->getEmail());

            if ($user->getId() == $this->getUser()->getId()) {
                if (!in_array('ROLE_ADMIN', $formData->getRoles())) {
                    $this->addFlash("danger", $this->translator->trans("Du kannst dir nicht selber die Adminisrative Rolle entfernen"));
                    return $this->redirectToRoute("admin_dashboard");
                }
            }
            
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Änderung erfolgreich');
            return $this->redirectToRoute('admin_dashboard');
        }
        $form = $form->createView();
        
        return $this->render('admin/user/edit.html.twig', compact('user', 'form'));
    }

    /**
     * @Route("/admin/user/{user}/newpass", name="admin_user_newpass")
     */
    public function user_newpass(User $user, Request $request, UserPasswordEncoderInterface $passwordEncoder) {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $form = $this->createFormBuilder([])
            -> add('regenerate', CheckBoxType::class, [
                'label' => ' Ich möchte das Nutzerpasswort neu generieren!',
                'required' => true
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Passwort des Nutzers regenerieren',
                'attr' => [
                    'class' => 'btn btn-warning'
                    ]
                ])            
            ->getForm();
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $this->addFlash('success', 'Regenerierung des Passworts erfolgreich');
            $password = $this->random_string(16);
            $user->setPassword($password);
            $newPass = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($newPass);

            $message = (new \Swift_Message($this->translator->trans('Benachrichtigung über Passwortänderung')))
            ->setFrom('moritz@fromm-media.de')
            ->setTo($user->getEmail())
            ->setBody("Hello, \n I would just like to inform you that your password on " . $request->getHost() . ' has been changed to "' . $password . "\"\n.Please do not reply to this email(!!!) and keep in mind, that wel'l be soon launching some features to change / reset your password without sending you a random one in plaintext. \n Regards, Moritz Fromm", 'text/plain');
            $this->mailer->send($message);

            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('admin_dashboard');
        }

        $form = $form->createView();
        return $this->render('admin/user/newpass.html.twig', compact('user', 'form'));
    }

    /**
     * @Route("/admin/template/", name="admin_template")
     */
    public function template() {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $repository = $this->getDoctrine()->getRepository(Template::class);
        $templates = $repository->findAll();
        return $this->render('admin/template/index.html.twig', compact('templates'));
    }

    /**
     * @Route("/admin/template/add/", name="admin_template_add")
     */

    public function template_add(Request $request) {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $template = new Template();
        $template->setIsGlobal(true);
        $template->setIsActive(true);
        $form = $this->createFormBuilder($template)
            ->add('name', TextType::class, [
                'label' => 'Name',
                'help' => 'Name der Vorlage wie Nutzer ihn sehen'
            ])
            ->add('isglobal', CheckboxType::class, [
                'label' => 'Vorlage als Global markieren',
                'help' => 'Aktuell sind allel Vorlagen global, dies wird sich in Zukunft ggf. ändern',
                'required' => true,
                'disabled' => true
            ])
            ->add('isactive', CheckboxType::class, [
                'label' => 'Vorlage ist aktiv',
                'help' => 'Nutzer können diese Vorlage wirklich nutzen',
                'required' => false
            ])
            ->add('add', SubmitType::class, [
                'label' => 'Vorlage Hinzufügen'
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { 
            $em = $this->getDoctrine()->getManager();
            $template = $form->getNormData();
            $template->setCreator($this->getUser()); 
            $em->persist($template);
            $em->flush();
            $this->addFlash("success", "Hinzufügen erfolgreich!");
            return $this->redirectToRoute("admin_template");
        }
        $form = $form->createView();
        return $this->render('admin/template/add.html.twig', compact('form'));
    }

    /**
     * @Route("/admin/template/edit/{template}", name="admin_template_edit")
     */
    public function template_edit(Template $template, Request $request) {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $form = $this->createFormBuilder($template)
            ->add('name', TextType::class, [
                'label' => 'Name',
                'help' => 'Name der Vorlage wie Nutzer ihn sehen'
            ])
            ->add('isglobal', CheckboxType::class, [
                'label' => 'Vorlage als Global markieren',
                'help' => 'Aktuell sind allel Vorlagen global, dies wird sich in Zukunft ggf. ändern',
                'disabled' => true
            ])
            ->add('isactive', CheckboxType::class, [
                'label' => 'Vorlage ist aktiv',
                'help' => 'Nutzer können diese Vorlage wirklich nutzen',
                'required' => false
            ])
            ->add('add', SubmitType::class, [
                'label' => 'Änderungen Speichern'
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { 
            $em = $this->getDoctrine()->getManager();
            $template = $form->getNormData();
            $template->setChangedAt(new \Datetime()); 
            $em->persist($template);
            $em->flush();
            $this->addFlash("success", "Hinzufügen erfolgreich!");
            return $this->redirectToRoute("admin_template");
        }
        $form = $form->createView();
        return $this->render('admin/template/add.html.twig', compact('form'));
    }
}
