<?php

namespace App\Controller;

use App\Entity\SchoolYear;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="default")
     */
    public function index()
    {
        $years = $this->getDoctrine()->getRepository(SchoolYear::class)->findBy(['user' => $this->getUser()]);
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'years' => $years
        ]);
    }
}
