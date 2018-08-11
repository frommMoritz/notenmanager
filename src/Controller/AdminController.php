<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use App\Entity\Subject;
use App\Entity\SchoolYear;
use App\Entity\Mark;
use App\Entity\User;
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

class AdminController extends AbstractController
{

    public function __construct(TranslatorInterface $translator, AuthorizationCheckerInterface $authChecker) {
        $this->translator = $translator;
        $this->authChecker = $authChecker;
    }

    /**
     * @Route("/admin", name="admin_dashboard")
     */
    public function index()
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        return $this->render('admin/index.html.twig', compact('users'));
    }
}
