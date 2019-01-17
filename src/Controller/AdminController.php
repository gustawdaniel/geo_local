<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 16.01.17
 * Time: 00:30
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function adminAction()
    {
        return $this->render('admin/panel.html.twig', []);
    }
}