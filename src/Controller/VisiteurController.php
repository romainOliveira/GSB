<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Form\RenseignerType;
use App\Form\ChoisirMoisType;
use App\Form\LigneFraisHorsForfaitType;
use App\Form\ModifierFicheFraisVisiteurType;
use App\Entity\FicheFrais;
use App\Entity\LigneFraisHorsForfait;
use Symfony\Component\HttpFoundation\Session\Session;

class VisiteurController extends AbstractController
{
    /**
     * @Route("/visiteur", name="visiteur")
     */
    public function index()
    {
        return $this->render('visiteur/index.html.twig', [
            'controller_name' => 'VisiteurController',
        ]);
    }
    
            /**
     * @Route("/consulter", name="consulter")
     */
    public function consulter(Request $query, SessionInterface $session)
    {
        $renseigner = new Fichefrais();
        $form = $this->createForm(ChoisirMoisType::class, $renseigner);
        $form->handleRequest($query);

        if ($form->isSubmitted()) {
                    $session->set('mois', $renseigner->getMois()) ;
                    return $this->redirectToRoute('consulterFicheFrais');
        }

        return $this->render('visiteur/choisirMois.html.twig', array('form' => $form->createView()));
    }
    
        /**
     * @Route("/consulterFicheFrais", name="consulterFicheFrais")
     */
    public function consulterFicheFrais(SessionInterface $session)
    {
        $id = $session->get('id') ;
        $mois = $session->get('mois') ;
        $fichefrais = $this->getDoctrine()->getManager()->getRepository(\App\Entity\FicheFrais::class)->findBy(['idVisiteur' => $id, 'mois' => $mois]);;
        $fichefraisforfait = $this->getDoctrine()->getManager()->getRepository(\App\Entity\LigneFraisForfait::class)->findBy(['idVisiteur' => $id, 'mois' => $mois]);
        $fichefraishorsforfait = $this->getDoctrine()->getManager()->getRepository(\App\Entity\LigneFraisHorsForfait::class)->findBy(['idVisiteur' => $id, 'mois' => $mois]);
        return $this->render('visiteur/consulter.html.twig',array('fichefrais'=>$fichefrais, 'fichefraisforfait'=>$fichefraisforfait,  'fichefraishorsforfait'=>$fichefraishorsforfait));
    }
    
       /**
     * @Route("/renseigner", name="renseigner")
     */
    function creerFicheFrais(Request $query, SessionInterface $session) {
        $renseigner = new Fichefrais();
        $form = $this->createForm(RenseignerType::class, $renseigner);
        $form->handleRequest($query);
        $ligneFraisHorsForfait = new LigneFraisHorsForfait();
        $form2 = $this->createForm(LigneFraisHorsForfaitType::class, $ligneFraisHorsForfait);
        $form2->handleRequest($query);
        if ($query->isMethod('POST')) {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($renseigner);
                    $em->flush();
                    return $this->redirectToRoute('renseigner', array('id' => $renseigner->getIdvisiteur()));
                }
            }
            if ($form2->isSubmitted()) {
                if ($form2->isValid()) {
                    $fiches = $this->getFiches();
                    foreach ($fiches as $fiche) {
                        if ($fiche->getIdVisiteur()->getId() == $session->get('id')) {
                            $ligneFraisHorsForfait->setIdVisiteur($fiche->getIdVisiteur());
                        }
                    }
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($ligneFraisHorsForfait);
                    $em->flush();
                    return $this->redirectToRoute('renseigner', array('id' => $ligneFraisHorsForfait->getIdvisiteur()));
                }     
            }
        }
        return $this->render('visiteur/renseigner.html.twig', array('form' => $form->createView(), 'form2' => $form2->createView()));
    }
    
    public function getFiches() {
        $fiches = $this->getDoctrine()->getRepository(\App\Entity\FicheFrais::class)->findAll();
        return $fiches;
   }
   
   /**
     * @Route("/getIdVI/{id}", name="getIdVI")
     */
    public function getIdVI($id, SessionInterface $session)
    {
        $session->set('idVI', $id) ;
        return $this->redirectToRoute('upd_route_fiche_frais');
    }
    
    
    /**
    *
    *@Route("/modifierFicheFrais",name="upd_route_fiche_frais")
    *
    */
    public function modifierFicheFrais(Request $request, SessionInterface $session){
        
        $id = $session->get('idVI') ;
        $fichefrais = new FicheFrais();
        $fichefrais = $this->getDoctrine()->getManager()->getRepository(FicheFrais::class)->find($id);
        $fichefrais->setDateModif(new \DateTime());
        $request->getSession()->getFlashBag()->add('notice', '');
        $form = $this->createForm(ModifierFicheFraisVisiteurType::class, $fichefrais);
        if($request->isMethod('POST')){
            $form->handleRequest($request);
                if($form->isValid()){
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    $request->getSession()->getFlashBag()->add('success', 'Fiche frais modifié avec succès.');

                    return $this->redirectToRoute('consulter');
                }
        }
    return $this->render( 'visiteur/modifierFicheFrais.html.twig', array('form' =>$form->createView(), 'fichefrais'=>$fichefrais));
    }
   
}

