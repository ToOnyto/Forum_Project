<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Slot;
use App\Form\CompanyType;
use App\Repository\CompanyRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/company")
 */
class CompanyController extends AbstractController
{
    /**
     * @Route("/", name="company_index", methods={"GET"})
     */
    public function index(CompanyRepository $companyRepository): Response
    {
        return $this->render('company/index.html.twig', [
            'companies' => $companyRepository->findAll(),
        ]);
    }

    // Créé les créneaux pour l'entreprise
    public function createSlots(Company $company)
    {
        // Récupération des paramètres des créneaux (voir .env)
        
        // Début et fin d'un créneau (par ex: 14:00 et 16:45)
        $slotsStart = getenv('SLOTS_START');
        $slotsEnd = getenv('SLOTS_END');

        // Durée d'un créneau en minutes (par exemple 15)
        $slotsDuration = getenv('SLOTS_DURATION');

        // TODO: Générer les créneaux libres et les associer aux entreprises

        $slotsStartSecond = $this->convertToSecond($slotsStart);
        $slotsEndSecond = $this->convertToSecond($slotsEnd);
        $slotsDurationSecond = $slotsDuration * 60;
        $slotsQuantity = ( $slotsEndSecond - $slotsStartSecond ) / $slotsDurationSecond;

        for ($i = 0; $i < $slotsQuantity; $i++) {
            $slot = new Slot();
           
            $slot->setTime( $this->convertToString($slotsStartSecond) );
            $company->addSlot($slot);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($slot);
            $entityManager->flush();

            $slotsStartSecond += $slotsDurationSecond; 
        }
    }

    // Converts seconds to HH:MM format
    public function convertToString($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);

        if( $minutes === 0){
            return "$hours:$minutes"."0";
        }
        return "$hours:$minutes";
        
    }

    // Convert string format HH:MM to seconds
    public function convertToSecond($time){
        list($h, $m) = explode(':', $time);
	    return ($h * 3600) + ($m * 60);
    }

    /**
     * @Route("/new", name="company_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $this->createSlots($company);
            $entityManager->persist($company);
            $entityManager->flush();

            return $this->redirectToRoute('company_index');
        }

        return $this->render('company/new.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/details/{id}", name="company_details", methods={"GET"})
     */
    public function details(Company $company): Response
    {
        return $this->render('company/details.html.twig', [
            'company' => $company,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="company_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Company $company): Response
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");

        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('company_index');
        }

        return $this->render('company/edit.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="company_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Company $company): Response
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");

        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($company);
            $entityManager->flush();
        }

        return $this->redirectToRoute('company_index');
    }
}
