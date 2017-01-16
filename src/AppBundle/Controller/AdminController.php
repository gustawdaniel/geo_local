<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 16.01.17
 * Time: 00:30
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class AdminController extends Controller
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function adminAction()
    {
        return $this->render('admin/panel.html.twig', []);
    }
}