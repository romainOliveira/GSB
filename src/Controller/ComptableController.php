<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Form\ValiderFicheType;
use App\Entity\Visiteur;
use App\Entity\Etat;
use App\Entity\FicheFrais;
use App\Entity\LigneFraisForfait;
use App\Entity\LigneFraisHorsForfait;
use App\Form\ModifierFicheFraisType;
use Symfony\Component\HttpFoundation\Session\Session;


class ComptableController extends AbstractController
{
    /**
     * @Route("/comptable", name="comptable")
     */
    public function index(Request $query)
    {
        return $this->render('comptable/index.html.twig');
    }
    
    /**
     * @Route("/comptable_suivi", name="suivi")
     */
    public function suivi(Request $query)
    {
        $fichefrais = $this->getDoctrine()->getManager()->getRepository(\App\Entity\Visiteur::class)->findAll();
        return $this->render('comptable/suivi.html.twig',array('fichefrais'=>$fichefrais));
    }
    
    /**
     * @Route("/getIdM/{id}", name="getIdM")
     */
    public function getIdM($id, SessionInterface $session)
    {
        $session->set('idM', $id) ;
        return $this->redirectToRoute('upd_route');
    }
    
    
    /**
    *
    *@Route("/modifier",name="upd_route")
    *
    */
    public function modifier(Request $request, SessionInterface $session){
        
        $id = $session->get('idM') ;
        $fichefrais = new FicheFrais();
        $fichefrais = $this->getDoctrine()->getManager()->getRepository(FicheFrais::class)->find($id);
        $fichefrais->setDateModif(new \DateTime());
        $request->getSession()->getFlashBag()->add('notice', '');
        $form = $this->createForm(ModifierFicheFraisType::class, $fichefrais);
        if($request->isMethod('POST')){
            $form->handleRequest($request);
                if($form->isValid()){
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    $request->getSession()->getFlashBag()->add('success', 'Fiche frais modifié avec succès.');

                    return $this->redirectToRoute('suivi');
                }
        }
    return $this->render( 'comptable/modifier.html.twig', array('form' =>$form->createView(), 'fichefrais'=>$fichefrais));
    }
    
    /**
     * @Route("/getIdV/{id}", name="getIdV")
     */
    public function getIdV($id, SessionInterface $session)
    {
        $session->set('idV', $id) ;
        return $this->redirectToRoute('upd_valider');
    }
    
    
    /**
    *
    *@Route("/modifier_validation",name="upd_valider")
    *
    */
    public function modifierValidation(SessionInterface $session){
        
        $id = $session->get('idV') ;
        $fichefrais = new FicheFrais();
        $fichefrais = $this->getDoctrine()->getManager()->getRepository(FicheFrais::class)->find($id);
        $idEtat = $this->getDoctrine()->getManager()->getRepository(Etat::class)->find("VA");
        $fichefrais->setIdEtat($idEtat);
        $fichefrais->setDateModif(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('suivi');
    }
    
    
    
    /**
     * @Route("/comptable_valider", name="valider")
     */
    public function valider(Request $query)
    {
        $ficheAValider = new FicheFrais();
        $form = $this->createForm(ValiderFicheType::class, $ficheAValider);
        $form->handleRequest($query);
        if ($query->isMethod('POST')) {
            if ($form->isValid()) {
                $fiches = $this->getFiches();
                foreach ($fiches as $fiche) {
                    if ($fiche->getMois() == $form['mois']->getData() && $fiche->getIdvisiteur() == $form['idVisiteur']->getData()) {
//                        $lignesFraisForfait = $this->getLignesFraisForfait($fiche->getMois(), $fiche->getIdvisiteur()->getId());
                        $lignesFraisHorsForfait = $this->getLignesFraisHorsForfait($fiche->getMois(), $fiche->getIdvisiteur()->getId());
                        return $this->render('comptable/validation.html.twig', array('lignesFraisHorsForfait' => $lignesFraisHorsForfait));
                    }
                }
                return $this->render('comptable/valider.html.twig', array('form' => $form->createView(), 'error' => 1));
            }
        }
        return $this->render('comptable/valider.html.twig', array('form' => $form->createView(), 'error' => 0));
    }
    
    /**
     * @Route("/getIdU/{id}", name="getIdU")
     */
    public function getIdU($id, SessionInterface $session)
    {
        $session->set('idU', $id) ;
        return $this->redirectToRoute('paiement');
    }
    
    /**
     * @Route("/paiement", name="paiement")
     */
    public function paiement(SessionInterface $session)
    {
        $id = $session->get('idU') ;
        $fichefrais = $this->getDoctrine()->getManager()->getRepository(\App\Entity\FicheFrais::class)->findBy(['idVisiteur' => $id]);
        $fichefraisforfait = $this->getDoctrine()->getManager()->getRepository(\App\Entity\LigneFraisForfait::class)->findBy(['idVisiteur' => $id]);
        $fichefraishorsforfait = $this->getDoctrine()->getManager()->getRepository(\App\Entity\LigneFraisHorsForfait::class)->findBy(['idVisiteur' => $id]);
        return $this->render('comptable/paiement.html.twig',array('fichefrais'=>$fichefrais, 'fichefraisforfait'=>$fichefraisforfait,  'fichefraishorsforfait'=>$fichefraishorsforfait));
    }
    
    public function confirmationPaiement($id)
    {
        $fichefrais = $this->getDoctrine()->getManager()->getRepository(\App\Entity\FicheFrais::class)->findBy(['id' => $id]);
        return $this->render('comptable/paiement.html.twig',array('fichefrais'=>$fichefrais));
    }
    
    public function getFiches() {
        $fiches = $this->getDoctrine()->getRepository(\App\Entity\FicheFrais::class)->findAll();
        return $fiches;
    }
    
    public function getLignesFraisHorsForfait(string $mois, string $idVisiteur) {
        $lignesUtilisateur = array();
        $lignes = $this->getDoctrine()->getRepository(\App\Entity\Lignefraishorsforfait::class)->findBy(array('mois' => $mois, 'idVisiteur' => $idVisiteur));
//        foreach ($lignes as $ligne) {
  //          if ($idVisiteur == $ligne->getIdvisiteur() && $mois == $ligne->getMois()) {
    //            array_push($lignesUtilisateur, $ligne);
//            }
//        }
        return $lignes;
    }
    
    public function getLignesFraisForfait(string $mois, string $idVisiteur) {
        $lignesUtilisateur = array();
        $lignes = $this->getDoctrine()->getRepository(\App\Entity\LigneFraisForfait::class)->findAll();
        foreach ($lignes as $ligne) {
            if ($idVisiteur == $ligne->getIdVisiteur() && $mois == $ligne->getMois()) {
                array_push($lignesUtilisateur, $ligne);
            }
        }
        return $lignesUtilisateur;
    }

    
}

