<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Form\ValiderFicheType;
use App\Entity\Etat;
use App\Entity\FicheFrais;
use App\Entity\LigneFraisForfait;
use App\Entity\LigneFraisHorsForfait;
use App\Form\ChoisirMoisType;
use App\Entity\Visiteur;
use App\Form\ModifierFicheFraisType;
use App\Form\ModifierQuantiteRepasType;
use App\Form\ModifierQuantiteEtapeType;
use App\Form\ModifierQuantiteKilometresType;
use App\Form\ModifierQuantiteNuiteeType;
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
     * @Route("/consulter", name="consulter")
     */
    public function consulter(Request $query, SessionInterface $session)
    {
        $renseigner = new Fichefrais();
        $form = $this->createForm(ChoisirMoisType::class, $renseigner);
        $form->handleRequest($query);

        if ($form->isSubmitted()) {
                    $session->set('mois', $renseigner->getMois()) ;
                    return $this->redirectToRoute('getIdM');

        }

        return $this->render('comptable/choisirMois.html.twig', array('form' => $form->createView()));
    }
    
        /**
     * @Route("/comptable_suivi", name="suivi")
     */
    public function choisirMois(Request $query, SessionInterface $session)
    {
        $renseigner = new Fichefrais();
        $form = $this->createForm(ChoisirMoisType::class, $renseigner);
        $form->handleRequest($query);

        if ($form->isSubmitted()) {
                    $session->set('mois', $renseigner->getMois()) ;
                    return $this->redirectToRoute('suivi_fiche');

        }
        
        return $this->render('comptable/choisirMois.html.twig', array('form' => $form->createView()));
    }
    
    /**
     * @Route("/comptable_suivi_fiche", name="suivi_fiche")
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
    public function valider(Request $query, SessionInterface $session)
    {
        $ficheAValider = new FicheFrais();
        $form = $this->createForm(ValiderFicheType::class, $ficheAValider);
        $form->handleRequest($query);
        if ($query->isMethod('POST')) {
            if ($form->isValid()) {
                $fiches = $this->getFiches();
                foreach ($fiches as $fiche) {
                    if ($fiche->getMois() == $form['mois']->getData() && $fiche->getIdvisiteur() == $form['idVisiteur']->getData() && $fiche->getIdEtat()->getId() != "VA") {
                        $session->set('ficheMois', $fiche->getMois());
                        $session->set('ficheIdVisiteur', $fiche->getIdVisiteur()->getId());
                        $session->set('ficheId', $fiche->getId());
                        return $this->redirectToRoute('comptable_validation');
                    }
                }
                return $this->render('comptable/valider.html.twig', array('form' => $form->createView(), 'error' => 1, 'modifier' => 0));
            }
        }
        if ($session->get('modifier') == 1) {
            $session->set('modifier', 0);
            return $this->render('comptable/valider.html.twig', array('form' => $form->createView(), 'error' => 0, 'modifier' => 1));
        }
        return $this->render('comptable/valider.html.twig', array('form' => $form->createView(), 'error' => 0, 'modifier' => 0));
    }
    
    /**
     * @Route("/comptable_validation", name="comptable_validation")
     */
    public function validation(Request $query, SessionInterface $session)
    {
        $ficheId = $session->get('ficheId');
        $ficheMois = $session->get('ficheMois');
        $ficheIdVisiteur = $session->get('ficheIdVisiteur');
        $fiches = $this->getFiches();
        foreach ($fiches as $fiche) {
            if ($fiche->getId() == $ficheId) {
                $lignesFraisForfait = $this->getLignesFraisForfait($ficheMois, $ficheIdVisiteur);
                $lignesFraisHorsForfait = $this->getLignesFraisHorsForfait($ficheMois, $ficheIdVisiteur);
                $modifierLigneRepas = $this->getForfaitRepas($lignesFraisForfait);
                $form1 = $this->createForm(ModifierQuantiteRepasType::class, $modifierLigneRepas);
                $form1->handleRequest($query);
                $modifierLigneNuitee = $this->getForfaitNuitee($lignesFraisForfait);
                $form2 = $this->createForm(ModifierQuantiteNuiteeType::class, $modifierLigneNuitee);
                $form2->handleRequest($query);
                $modifierLigneKilometres = $this->getForfaitKilometres($lignesFraisForfait);
                $form3 = $this->createForm(ModifierQuantiteKilometresType::class, $modifierLigneKilometres);
                $form3->handleRequest($query);
                $modifierLigneEtape = $this->getForfaitEtape($lignesFraisForfait);
                $form4 = $this->createForm(ModifierQuantiteEtapeType::class, $modifierLigneEtape);
                $form4->handleRequest($query);
                $lignesFraisForfait = array($modifierLigneRepas, $modifierLigneNuitee, $modifierLigneKilometres, $modifierLigneEtape);
                if ($query->isMethod('POST')) {
                    $em = $this->getDoctrine()->getManager();
                    if ($form1->isSubmitted() && $form1->isValid() && $form1->getData()) {
                        $em->persist($modifierLigneRepas);
                        $em->flush();
                    }
                    if ($form2->isSubmitted() && $form2->isValid()) {
                        $em->persist($modifierLigneNuitee);
                        $em->flush();
                    }
                    if ($form3->isSubmitted() && $form3->isValid()) {
                        $em->persist($modifierLigneKilometres);
                        $em->flush();
                    }
                    if ($form4->isSubmitted() && $form4->isValid()) {
                        $em->persist($modifierLigneEtape);
                        $em->flush();
                    }
                    return $this->render('comptable/validation.html.twig', array('fiche' => $fiche, 'lignesFraisHorsForfait' => $lignesFraisHorsForfait, 'lignesFraisForfait' => $lignesFraisForfait, 'form1' => $form1->createView(), 'form2' => $form2->createView(), 'form3' => $form3->createView(), 'form4' => $form4->createView()));
                }
                return $this->render('comptable/validation.html.twig', array('fiche' => $fiche, 'lignesFraisHorsForfait' => $lignesFraisHorsForfait, 'lignesFraisForfait' => $lignesFraisForfait, 'form1' => $form1->createView(), 'form2' => $form2->createView(), 'form3' => $form3->createView(), 'form4' => $form4->createView()));
            }
        }
    }
    
    /**
     * @Route("/supprimerHorsForfait/{id}", name="supprimerHorsForfait")
     */
    public function supprimerHorsForfait(Request $query, SessionInterface $session, $id) {
        $ficheMois = $session->get('ficheMois');
        $ficheIdVisiteur = $session->get('ficheIdVisiteur');
        $entityManager = $this->getDoctrine()->getManager();
        $lignesFraisHorsForfait = $this->getLignesFraisHorsForfait($ficheMois, $ficheIdVisiteur);
        foreach ($lignesFraisHorsForfait as $ligneFraisHorsForfait) {
            if ($ligneFraisHorsForfait->getId() == $id) {
                $entityManager->remove($ligneFraisHorsForfait);
                $entityManager->flush();
            }
        }
        return $this->redirectToRoute('comptable_validation');
    }
    
    /**
     * @Route("/validerFicheFrais/{ficheId}", name="validerFicheFrais")
     */
    public function validerFicheFrais(Request $query, SessionInterface $session, $ficheId)
    {
        $fiches = $this->getFiches();
        foreach ($fiches as $fiche) {
            if ($fiche->getId() == $ficheId) {
                $entityManager = $this->getDoctrine()->getManager();
                $etats = $this->getEtat();
                foreach ($etats as $etat) {
                    if ($etat->getId() == 'VA') {
                        $fiche->setIdEtat($etat);
                    }
                }
                $montantValide = 0;
                $ligneFraisForfaits = $fiche->getLigneFraisForfaits();
                foreach ($ligneFraisForfaits as $ligne) {
                    $montantValide = $montantValide + $ligne->getIdFraisForfait()->getMontant() * $ligne->getQuantite();
                }
                $ligneFraisHorsForfaits = $fiche->getLigneFraisHorsForfaits();
                foreach ($ligneFraisHorsForfaits as $ligne) {
                    $montantValide = $montantValide + $ligne->getMontant();
                }
                $fiche->setMontantValide($montantValide);
                $fiche->setDateModif(new \DateTime());
                $entityManager->persist($fiche);
                $entityManager->flush();
            }
        }
        $session->set('modifier', 1);
        return $this->redirectToRoute('valider');
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
        $mois = $session->get('mois') ;
        $fichefrais = $this->getDoctrine()->getManager()->getRepository(\App\Entity\FicheFrais::class)->findBy(['idVisiteur' => $id, 'mois' => $mois]);
        $fichefraisforfait = $this->getLignesFraisForfait($mois, $id);
        $fichefraishorsforfait = $this->getLignesFraisHorsForfait($mois, $id);
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
        $lignes = $this->getDoctrine()->getRepository(\App\Entity\LigneFraisHorsForfait::class)->findAll();
        foreach ($lignes as $ligne) {
            if ($idVisiteur == $ligne->getIdvisiteur()->getId() && $mois == $ligne->getMois()->getMois()) {
                array_push($lignesUtilisateur, $ligne);
            }
        }
        return $lignesUtilisateur;
    }
    
    public function getLignesFraisForfait(string $mois, string $idVisiteur) {
        $lignesUtilisateur = array();
        $lignes = $this->getDoctrine()->getRepository(\App\Entity\LigneFraisForfait::class)->findAll();
        foreach ($lignes as $ligne) {
            if ($idVisiteur == $ligne->getIdVisiteur()->getId() && $mois == $ligne->getMois()->getMois()) {
                array_push($lignesUtilisateur, $ligne);
            }
        }
        return $lignesUtilisateur;
    }

    public function getForfaitRepas(array $lignes) {
        foreach($lignes as $ligne) {
            if ($ligne->getIdFraisForfait()->getId() == 'REP') {
                return $ligne;
            }
        }
    }
    
    public function getForfaitNuitee(array $lignes) {
        foreach($lignes as $ligne) {
            if ($ligne->getIdFraisForfait()->getId() == 'NUI') {
                return $ligne;
            }
        }
    }
    
    public function getForfaitEtape(array $lignes) {
        foreach($lignes as $ligne) {
            if ($ligne->getIdFraisForfait()->getId() == 'ETP') {
                return $ligne;
            }
        }
    }
    
    public function getForfaitKilometres(array $lignes) {
        foreach($lignes as $ligne) {
            if ($ligne->getIdFraisForfait()->getId() == 'KM') {
                return $ligne;
            }
        }
    }
    
    public function getEtat() {
        $etats = $this->getDoctrine()->getRepository(\App\Entity\Etat::class)->findAll();
        return $etats;
    }
    
}

