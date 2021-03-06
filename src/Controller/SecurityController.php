<?php

namespace App\Controller;

use App\Entity\User;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class SecurityController extends Controller
{
    /**
     * @Route("/security", name="security")
     */
    public function index()
    {
        if (!($this->getUser() == null)) {
            return $this->redirectToRoute('default');
        }
        $this->redirectToRoute('security_login');
    }

    /**
     * @Route("/login", name="security_login")
     */
    public function login(AuthenticationUtils $authenticationUtils) {
        if (!($this->getUser() == null)) {
            return $this->redirectToRoute('default');
        }
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $form = $this->createFormBuilder(['remember_me' => true])
            ->add('username', TextType::class, ['label' => 'Nutzername', 'attr' => ['value' => (isset($lastUsername) ? $lastUsername : "")]])
            ->add('password', PasswordType::class, ['label' => 'Passwort'])
            ->add('remember_me', CheckboxType::class, ['label' => 'Angemeldet bleiben', 'help' => 'Einen Monat angemeldet bleiben'])
            ->add('login', SubmitType::class, ['label' => 'Anmelden'])
            ->getForm()
            ->createView();
        return $this->render('security/login.html.twig', compact('error', 'form'));
    }

    /**
     * @Route("/register", name="security_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, ValidatorInterface $validator) {
        if (!($this->getUser() == null)) {
            return $this->redirectToRoute('default');
        }
        $form = $this->createFormBuilder(new User())
        ->add('username', TextType::class, ['label' => 'Nutzername'])
        ->add('email', EmailType::class, ['label' => 'Email'])
        ->add('password', RepeatedType::class, [
            'type' => PasswordType::class,
            'first_options' => ['label' => 'Passwort'],
            'second_options' => ['label' => 'Passwort wiederholen'],
            ])
        ->add('registrieren', SubmitType::class, ['label' => 'Registrieren'])
        ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();
            $formData = $form->getNormData();
            $user->setUsername($formData->getUsername());
            $user->setEmail($formData->getEmail());
            $user->setPassword($formData->getPassword());

            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $item) {
                    $this->addFlash('warning', $item);
                }
                return $this->redirectToRoute('security_register');
            }
            $entityManager = $this->getDoctrine()->getManager();
            $userRepository = $this->getDoctrine()->getRepository(User::class);

            // When this is the first user to be registered, give him admin permissions
            $userCount = count($userRepository->findAll());
            if ($userCount == 0) {
                $user->setRoles(['ROLE_ADMIN']);
            }
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Registrierung erfolgerich!');

            return $this->redirectToRoute('default');
        }


        $form = $form->createView();
        return $this->render('security/registration.html.twig', compact('error', 'form'));
    }

}
