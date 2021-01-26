<?php
namespace App\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AbcController extends AbstractController
{

    /**
     * @Route("/abc", name="app_lucky_number")
     *
     * @return Response
     */
    public function number(): Response
    {      
        $resonse = new Response(
            '<html><body>Lucky number:This is a test route </body></html>'
        );

        $resonse->headers->set('Route-Name', 'abcd');

        return $resonse;

    }
}