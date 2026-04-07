<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\Categorie;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\ChallengeCoach;
use App\Entity\ChallengeRecompense;
use App\Repository\ChallengeRepository;
use App\Repository\UserRepository;
use App\Repository\RecompenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/challenge')]
class ChallengeController extends AbstractController
{
    #[Route('/', name: 'app_challenge_index', methods: ['GET'])]
    public function index(Request $request, ChallengeRepository $challengeRepository, EntityManagerInterface $em): Response
    {
        $catRepo = $em->getRepository(Categorie::class);
        if ($catRepo->count([]) == 0) {
            $defaults = ["Confiance en soi", "Gestion du stress", "Psychologie positive", "Productivité", "Bien-être mental"];
            foreach ($defaults as $d) {
                $c = new Categorie(); $c->setNom($d); $em->persist($c);
            }
            $em->flush();
        }

        $keyword  = $request->query->get('search', '');
        $sort     = $request->query->get('sort', 'date_recent');
        $statut   = $request->query->get('statut', '');
        $catId    = $request->query->get('categorie', '');

        $qb = $challengeRepository->createQueryBuilder('c');

        if ($keyword) {
            $qb->andWhere('c.titre LIKE :kw OR c.description LIKE :kw')
               ->setParameter('kw', '%' . $keyword . '%');
        }

        if ($statut) {
            $qb->andWhere('c.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($catId) {
            $qb->andWhere('c.categorie = :catId')
               ->setParameter('catId', $catId);
        }

        switch ($sort) {
            case 'titre_asc':  $qb->orderBy('c.titre', 'ASC'); break;
            case 'titre_desc': $qb->orderBy('c.titre', 'DESC'); break;
            case 'date_old':   $qb->orderBy('c.dateDebut', 'ASC'); break;
            default:           $qb->orderBy('c.dateDebut', 'DESC'); break;
        }

        $challenges = $qb->getQuery()->getResult();

        $total   = count($challenges);
        $actifs  = count(array_filter($challenges, fn($c) => $c->getStatut() === 'actif'));
        $inactifs = $total - $actifs;

        return $this->render('challenge/index.html.twig', [
            'challenges'  => $challenges,
            'categories'  => $catRepo->findAll(),
            'coaches'     => $em->getRepository(\App\Entity\User::class)->findBy(['role' => 'coach']),
            'recompenses' => $em->getRepository(\App\Entity\Recompense::class)->findAll(),
            'filters' => [
                'search' => $keyword,
                'sort' => $sort,
                'statut' => $statut,
                'categorie' => $catId
            ],
            'stats' => [
                'total'    => $total,
                'actifs'   => $actifs,
                'inactifs' => $inactifs,
            ]
        ]);
    }

    #[Route('/new', name: 'app_challenge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserRepository $userRepository, ValidatorInterface $validator): Response
    {
        $catRepo = $em->getRepository(Categorie::class);
        if ($catRepo->count([]) == 0) {
            $defaults = ["Confiance en soi", "Gestion du stress", "Psychologie positive", "Productivité", "Bien-être mental"];
            foreach ($defaults as $d) {
                $c = new Categorie(); $c->setNom($d); $em->persist($c);
            }
            $em->flush();
        }

        if ($request->isMethod('GET')) {
            $categories = $catRepo->findAll();
            $coaches    = $em->getRepository(\App\Entity\User::class)->findBy(['role' => 'coach']);
            return $this->render('challenge/new.html.twig', [
                'categories' => $categories,
                'coaches'    => $coaches
            ]);
        }

        $challenge = new Challenge();
        $challenge->setTitre($request->request->get('titre', ''));
        $challenge->setDescription($request->request->get('description', ''));

        // Handling Media Upload
        $mediaFile = $request->files->get('media');
        if ($mediaFile) {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/challenges';
            if (!file_exists($uploadsDir)) mkdir($uploadsDir, 0777, true);
            
            $mimeType = $mediaFile->getMimeType(); // Get mime before move
            $newFilename = uniqid() . '.' . $mediaFile->guessExtension();
            $mediaFile->move($uploadsDir, $newFilename);
            
            $challenge->setMediaUrl('/uploads/challenges/' . $newFilename);
            $challenge->setMediaType(str_contains($mimeType, 'video') ? 'video' : 'image');
        }

        $dateDebut = $request->request->get('dateDebut');
        $dateFin   = $request->request->get('dateFin');
        $challenge->setDateDebut($dateDebut ? new \DateTime($dateDebut) : new \DateTime());
        $challenge->setDateFin($dateFin ? new \DateTime($dateFin) : (new \DateTime())->modify('+30 days'));
        $challenge->setStatut($request->request->get('statut', 'actif'));

        // Handling Categories (with 'Autre')
        $categorieId = $request->request->get('categorie');
        if ($categorieId === 'autre') {
            $newCatName = $request->request->get('new_categorie');
            if ($newCatName) {
                $newCat = new Categorie();
                $newCat->setNom($newCatName);
                $em->persist($newCat);
                $em->flush();
                $challenge->setCategorie($newCat);
            }
        } elseif ($categorieId) {
            $cat = $em->getRepository(Categorie::class)->find($categorieId);
            if ($cat) $challenge->setCategorie($cat);
        }

        // Use logged in user
        $user = $this->getUser();
        if (!$user) {
            $user = $userRepository->findOneBy([]);
            if (!$user) {
                $user = new \App\Entity\User();
                $user->setNom('Admin')->setPrenom('System')
                     ->setEmail('admin'.uniqid().'@system.com')->setPassword('temp_'.uniqid());
                $em->persist($user);
                $em->flush();
            }
        }
        $challenge->setCreatedBy($user);

        // ── Server-side validation ──
        $errors = $validator->validate($challenge);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash('error', '❌ ' . implode(' | ', $errorMessages));
            return $this->redirectToRoute('app_challenge_index');
        }

        $em->persist($challenge);
        $em->flush();

        $this->addFlash('success', '🏆 Challenge ajouté avec succès!');
        return $this->redirectToRoute('app_challenge_index');
    }

    #[Route('/{id}/edit', name: 'app_challenge_edit', methods: ['POST'])]
    public function edit(Request $request, Challenge $challenge, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $challenge->setTitre($request->request->get('titre', $challenge->getTitre()));
        $challenge->setDescription($request->request->get('description', $challenge->getDescription()));

        // Media Upload
        $mediaFile = $request->files->get('media');
        if ($mediaFile) {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/challenges';
            if (!file_exists($uploadsDir)) mkdir($uploadsDir, 0777, true);
            
            $mimeType = $mediaFile->getMimeType(); // Get mime before move
            $newFilename = uniqid() . '.' . $mediaFile->guessExtension();
            $mediaFile->move($uploadsDir, $newFilename);
            
            $challenge->setMediaUrl('/uploads/challenges/' . $newFilename);
            $challenge->setMediaType(str_contains($mimeType, 'video') ? 'video' : 'image');
        }

        $dateDebut = $request->request->get('dateDebut');
        $dateFin   = $request->request->get('dateFin');
        if ($dateDebut) $challenge->setDateDebut(new \DateTime($dateDebut));
        if ($dateFin)   $challenge->setDateFin(new \DateTime($dateFin));
        
        $challenge->setStatut($request->request->get('statut', $challenge->getStatut()));

        // Category Handle
        $categorieId = $request->request->get('categorie');
        if ($categorieId === 'autre') {
            $newCatName = $request->request->get('new_categorie');
            if ($newCatName) {
                $newCat = new Categorie();
                $newCat->setNom($newCatName);
                $em->persist($newCat);
                $em->flush();
                $challenge->setCategorie($newCat);
            }
        } elseif ($categorieId) {
            $cat = $em->getRepository(Categorie::class)->find($categorieId);
            if ($cat) $challenge->setCategorie($cat);
        }

        // ── Server-side validation ──
        $errors = $validator->validate($challenge);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash('error', '❌ ' . implode(' | ', $errorMessages));
            return $this->redirectToRoute('app_challenge_index');
        }

        $em->flush();
        $this->addFlash('success', '✏️ Challenge mis à jour avec succès!');
        return $this->redirectToRoute('app_challenge_index');
    }

    #[Route('/{id}/delete', name: 'app_challenge_delete', methods: ['POST'])]
    public function delete(Request $request, Challenge $challenge, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$challenge->getId(), $request->request->get('_token'))) {
            $em->remove($challenge);
            $em->flush();
            $this->addFlash('success', '🗑️ Challenge supprimé!');
        }
        return $this->redirectToRoute('app_challenge_index');
    }

    // ===== AJOUTER UN COACH AU CHALLENGE =====
    #[Route('/{id}/add-coach', name: 'app_challenge_add_coach', methods: ['POST'])]
    public function addCoach(Request $request, Challenge $challenge, EntityManagerInterface $em): Response
    {
        $coachId = $request->request->get('coach_id');
        if (!$coachId) {
            $this->addFlash('error', 'Veuillez sélectionner un coach.');
            return $this->redirectToRoute('app_challenge_index');
        }

        $coach = $em->getRepository(\App\Entity\User::class)->find($coachId);
        if (!$coach || $coach->getRole() !== 'coach') {
            $this->addFlash('error', 'Coach invalide.');
            return $this->redirectToRoute('app_challenge_index');
        }

        // Check not already assigned
        $existing = $em->getRepository(ChallengeCoach::class)->findOneBy([
            'challenge' => $challenge,
            'coach'     => $coach,
        ]);

        if ($existing) {
            $this->addFlash('error', 'Ce coach est déjà assigné à ce challenge!');
        } else {
            $cc = new ChallengeCoach();
            $cc->setChallenge($challenge);
            $cc->setCoach($coach);
            $em->persist($cc);
            $em->flush();
            $this->addFlash('success', '👨‍🏫 Coach ajouté au challenge avec succès!');
        }

        return $this->redirectToRoute('app_challenge_index');
    }

    // ===== AJOUTER UNE RÉCOMPENSE AU CHALLENGE =====
    #[Route('/{id}/add-recompense', name: 'app_challenge_add_recompense', methods: ['POST'])]
    public function addRecompense(Request $request, Challenge $challenge, EntityManagerInterface $em): Response
    {
        $recId = $request->request->get('recompense_id');
        if (!$recId) {
            $this->addFlash('error', 'Veuillez sélectionner une récompense.');
            return $this->redirectToRoute('app_challenge_index');
        }

        $recompense = $em->getRepository(\App\Entity\Recompense::class)->find($recId);
        if (!$recompense) {
            $this->addFlash('error', 'Récompense invalide.');
            return $this->redirectToRoute('app_challenge_index');
        }

        // Check not already assigned
        $existing = $em->getRepository(ChallengeRecompense::class)->findOneBy([
            'challenge'  => $challenge,
            'recompense' => $recompense,
        ]);

        if ($existing) {
            $this->addFlash('error', 'Cette récompense est déjà assignée à ce challenge!');
        } else {
            $cr = new ChallengeRecompense();
            $cr->setChallenge($challenge);
            $cr->setRecompense($recompense);
            $em->persist($cr);
            $em->flush();
            $this->addFlash('success', '🎁 Récompense ajoutée au challenge avec succès!');
        }

        return $this->redirectToRoute('app_challenge_index');
    }

    // ===== ENVOYER EMAIL AU COACH =====
    #[Route('/{id}/email-coach/{coachId}', name: 'app_challenge_email_coach', methods: ['POST'])]
    public function emailCoach(
        Request $request,
        Challenge $challenge,
        int $coachId,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $coach = $em->getRepository(\App\Entity\User::class)->find($coachId);
        if (!$coach) {
            $this->addFlash('error', 'Coach introuvable.');
            return $this->redirectToRoute('app_challenge_index');
        }

        $subject = $request->request->get('subject', 'Message de la plateforme Challenge Manager');
        $body    = $request->request->get('message', '');
        
        // Use logged in user email as sender (the actual from address)
        /** @var \App\Entity\User|null $currentUser */
        $currentUser = $this->getUser();
        $senderEmail = $currentUser ? $currentUser->getEmail() : $request->request->get('sender_email', 'noreply@challenge-manager.com');
        $senderName  = $currentUser ? $currentUser->getNom() . ' ' . ($currentUser->getPrenom() ?? '') : 'Utilisateur Challenge Manager';

        try {
            // It's better to send from a fixed verified address to avoid SMTP rejection (spoofing)
            // The coach can still reply to the sender via Reply-To
            $fromEmail = $this->getParameter('app.mailer_from') ?? 'noreply@atomicyou.com';

            $email = (new Email())
                ->from($fromEmail)
                ->replyTo($senderEmail)
                ->to($coach->getEmail())
                ->subject($subject)
                ->html('<div style="font-family:Inter,Arial,sans-serif;max-width:600px;margin:0 auto;padding:0;border:1px solid #eee;border-radius:16px;overflow:hidden;">
                    <div style="background:linear-gradient(135deg,#6c63ff,#00d2ff);padding:30px;text-align:center;">
                        <h1 style="color:white;margin:0;font-size:24px;">📧 Nouveau Message</h1>
                        <p style="color:rgba(255,255,255,0.9);margin:8px 0 0;font-size:14px;">Via Challenge Manager Pro</p>
                    </div>
                    <div style="background:#ffffff;padding:40px;">
                        <div style="background:#f8f9ff;padding:20px;border-radius:12px;margin-bottom:30px;border-left:4px solid #6c63ff;">
                            <p style="margin:0;font-size:14px;color:#666;"><strong style="color:#333;">Challenge:</strong> ' . htmlspecialchars($challenge->getTitre()) . '</p>
                            <p style="margin:6px 0 0;font-size:14px;color:#666;"><strong style="color:#333;">De la part de:</strong> ' . htmlspecialchars($senderName) . '</p>
                            <p style="margin:2px 0 0;font-size:14px;color:#666;"><strong style="color:#333;">Email:</strong> ' . htmlspecialchars($senderEmail) . '</p>
                        </div>
                        <div style="font-size:16px;color:#333;line-height:1.8;white-space:pre-wrap;">' .
                        htmlspecialchars($body) .
                        '</div>
                        <div style="margin-top:40px;text-align:center;">
                            <a href="mailto:' . $senderEmail . '" style="background:#6c63ff;color:white;padding:12px 25px;text-decoration:none;border-radius:8px;font-weight:bold;font-size:14px;">Répondre au participant</a>
                        </div>
                    </div>
                    <div style="background:#f9fafb;padding:20px;text-align:center;border-top:1px solid #eee;">
                        <p style="color:#9ca3af;font-size:12px;margin:0;">Ceci est un email automatique envoyé depuis Atomic You. Ne répondez pas directement à cet email technique.</p>
                    </div>
                </div>');

            $mailer->send($email);
            $this->addFlash('success', '📧 Email envoyé avec succès à ' . $coach->getNom() . ' !');
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // If still null://null, it shows up in profiler
            if (str_contains($e->getMessage(), 'null') || str_contains(get_class($e), 'Null')) {
                $this->addFlash('info', '💡 Mode Dev: L\'email a été capturé par le profiler (MAILER_DSN est null://null).');
            } else {
                $this->addFlash('error', '❌ Erreur SMTP: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_challenge_index');
    }

    // ===== EXPORT PDF =====
    #[Route('/export-pdf', name: 'app_challenge_export_pdf', methods: ['GET'])]
    public function exportPdf(ChallengeRepository $challengeRepository): Response
    {
        $challenges = $challengeRepository->findAll();
        $html = $this->renderView('challenge/pdf.html.twig', [
            'challenges'  => $challenges,
            'generatedAt' => new \DateTime()
        ]);

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->setIsRemoteEnabled(true);
        $pdfOptions->setIsHtml5ParserEnabled(true);

        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();

        $pdfContent = $dompdf->output();

        $response = new Response($pdfContent);
        $disposition = \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition(
            \Symfony\Component\HttpFoundation\HeaderUtils::DISPOSITION_ATTACHMENT,
            'challenges_export.pdf'
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }
}
