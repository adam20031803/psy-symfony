<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\ChallengeTask;
use App\Service\AiTaskGeneratorService;
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

use Symfony\Component\HttpFoundation\JsonResponse;


#[Route('/challenge')]
class ChallengeController extends AbstractController
{
    // ===== DASHBOARD MOTIVATION =====
    #[Route('/dashboard', name: 'app_challenge_dashboard', methods: ['GET'])]
public function dashboard(EntityManagerInterface $em, \Symfony\UX\Chartjs\Builder\ChartBuilderInterface $chartBuilder): Response
{
    $challenges = $em->getRepository(Challenge::class)->findBy([], ['dateDebut' => 'DESC'], 50);
    
    $actifs = 0;
    $termines = 0;
    $categoriesStats = [];
    
    foreach ($challenges as $c) {
        if ($c->getStatut() === 'actif') $actifs++;
        elseif ($c->getStatut() === 'termine') $termines++;
        
        $catName = $c->getCategorie() ? $c->getCategorie()->getNom() : 'Divers';
        if (!isset($categoriesStats[$catName])) {
            $categoriesStats[$catName] = 0;
        }
        $categoriesStats[$catName]++;
    }

    // Get coaches statistics
    $coaches = $em->getRepository(\App\Entity\User::class)->findBy(['role' => 'coach']);
    $coachesTotal = count($coaches);
    $coachesActive = count(array_filter($coaches, function($c) { return $c->isActive(); })); // Adjust based on your User entity
    
    // Get recompenses statistics
    $recompenses = $em->getRepository(\App\Entity\Recompense::class)->findBy([], ['points' => 'DESC'], 50);
    $recompensesTotal = count($recompenses);
    $recompensesPoints = array_sum(array_map(function($r) { return $r->getPoints(); }, $recompenses));
    
    // Get reclamations (adjust based on your actual entity)
    // This assumes you have a Reclamation entity - if not, set to 0
    $reclamationsOuvertes = 0; // Replace with actual query if you have reclamations
    
    // Build the stats array
    $stats = [
        'ch_total' => count($challenges),
        'ch_active' => $actifs,
        'ch_done' => $termines,
        'co_total' => $coachesTotal,
        'co_active' => $coachesActive,
        'rec_total' => $recompensesTotal,
        'rec_points' => $recompensesPoints,
        'rec_ouvert' => $reclamationsOuvertes,
    ];

    // Chart data for timeline (last 6 months)
    $timelineData = $this->getTimelineData($em);
    
    // Chart data for categories
    $categoriesData = [
        'labels' => array_keys($categoriesStats),
        'data' => array_values($categoriesStats)
    ];
    
    // Chart data for statuts
    $statutsData = [
        'labels' => ['Actifs', 'Terminés', 'À venir'],
        'data' => [$actifs, $termines, count($challenges) - $actifs - $termines],
        'colors' => ['#22c55e', '#6c63ff', '#f97316']
    ];
    
    // Chart data for coaches
    $coachesData = [
        'labels' => ['Disponibles', 'Occupés'],
        'data' => [$coachesActive, $coachesTotal - $coachesActive],
        'colors' => ['#22d3a5', '#64748b']
    ];
    
    // Chart data for recompenses (top 5)
    $sortedRecompenses = $recompenses;
    usort($sortedRecompenses, function($a, $b) {
        return $b->getPoints() <=> $a->getPoints();
    });
    $topRecompenses = array_slice($sortedRecompenses, 0, 5);
    $recompensesData = [
        'labels' => array_map(function($r) { return $r->getNom(); }, $topRecompenses),
        'data' => array_map(function($r) { return $r->getPoints(); }, $topRecompenses)
    ];

    // 1. Chart: Status Pie
    $chartStatus = $chartBuilder->createChart(\Symfony\UX\Chartjs\Model\Chart::TYPE_PIE);
    $chartStatus->setData([
        'labels' => ['Actifs', 'Terminés'],
        'datasets' => [
            [
                'label' => 'Statut',
                'backgroundColor' => ['rgba(46, 204, 113, 0.6)', 'rgba(52, 152, 219, 0.6)'],
                'borderColor' => ['#2ecc71', '#3498db'],
                'data' => [$actifs, $termines],
            ],
        ],
    ]);
    $chartStatus->setOptions(['responsive' => true, 'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['color' => '#fff']]]]);

    // 2. Chart: Categories Bar
    $chartCat = $chartBuilder->createChart(\Symfony\UX\Chartjs\Model\Chart::TYPE_BAR);
    $chartCat->setData([
        'labels' => array_keys($categoriesStats),
        'datasets' => [
            [
                'label' => 'Nombre de Challenges',
                'backgroundColor' => 'rgba(108, 99, 255, 0.6)',
                'borderColor' => '#6c63ff',
                'data' => array_values($categoriesStats),
            ],
        ],
    ]);
    $chartCat->setOptions([
        'responsive' => true, 
        'plugins' => ['legend' => ['display' => false]],
        'scales' => [
            'x' => ['ticks' => ['color' => '#fff']], 
            'y' => ['ticks' => ['color' => '#fff'], 'suggestedMin' => 0]
        ]
    ]);

    // 3. Chart: Evolution Line
    $chartEvolution = $chartBuilder->createChart(\Symfony\UX\Chartjs\Model\Chart::TYPE_LINE);
    $chartEvolution->setData([
        'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil'],
        'datasets' => [
            [
                'label' => 'Progression Utilisateur (Points)',
                'backgroundColor' => 'rgba(0, 210, 255, 0.2)',
                'borderColor' => '#00d2ff',
                'data' => [0, 20, 60, 110, 170, 250, 310],
                'fill' => true,
                'tension' => 0.4
            ],
        ],
    ]);
    $chartEvolution->setOptions([
        'responsive' => true,
        'plugins' => ['legend' => ['labels' => ['color' => '#fff']]],
        'scales' => [
            'x' => ['ticks' => ['color' => '#fff']], 
            'y' => ['ticks' => ['color' => '#fff'], 'suggestedMin' => 0]
        ]
    ]);

    return $this->render('challenge/dashboard.html.twig', [
        'chartStatus' => $chartStatus,
        'chartCat' => $chartCat,
        'chartEvolution' => $chartEvolution,
        'total' => count($challenges),
        'stats' => $stats,  // ← ADD THIS LINE
        'challenges' => $challenges,  // ← ADD THIS LINE
        'recompenses' => $recompenses,  // ← ADD THIS LINE
        'chartTimeline' => $timelineData,  // ← ADD THIS LINE
        'chartStatuts' => $statutsData,  // ← ADD THIS LINE
        'chartCategories' => $categoriesData,  // ← ADD THIS LINE
        'chartCoaches' => $coachesData,  // ← ADD THIS LINE
        'chartRecompenses' => $recompensesData,  // ← ADD THIS LINE
    ]);
}

// Add this helper method to your controller
private function getTimelineData(EntityManagerInterface $em): array
{
    $labels = [];
    $data = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $date = new \DateTime("-$i months");
        $labels[] = $date->format('M Y');
        
        $start = (clone $date)->modify('first day of this month')->setTime(0, 0, 0);
        $end = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);
        
        $count = $em->getRepository(Challenge::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
        
        $data[] = $count;
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}
    // ===== CALENDRIER =====
   #[Route('/calendar', name: 'app_challenge_calendar', methods: ['GET'])]
public function calendar(EntityManagerInterface $em): Response
{
    // Get all challenges for statistics
    $challenges = $em->getRepository(Challenge::class)->findBy([], ['dateDebut' => 'DESC'], 50);
    
    // Calculate statistics
    $total = count($challenges);
    $actifs = 0;
    $termine = 0;
    $annule = 0;
    
    foreach ($challenges as $challenge) {
        switch ($challenge->getStatut()) {
            case 'actif':
                $actifs++;
                break;
            case 'termine':
                $termine++;
                break;
            case 'annule':
                $annule++;
                break;
        }
    }
    
    $stats = [
        'total' => $total,
        'actifs' => $actifs,
        'termine' => $termine,
        'annule' => $annule,
    ];
    
    return $this->render('challenge/calendar.html.twig', [
        'stats' => $stats,
    ]);
}
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

        $challenges = $qb
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
        
        $participationsRaw = [];
        $user = $this->getUser();
        if ($user) {
            $participationsRaw = $em->getRepository(\App\Entity\ChallengeParticipation::class)->findBy(['user' => $user]);
        }
        $userParticipations = [];
        foreach ($participationsRaw as $p) {
            $userParticipations[$p->getChallenge()->getId()] = $p->getStatut();
        }

        $total   = count($challenges);
        $actifs  = count(array_filter($challenges, fn($c) => $c->getStatut() === 'actif'));
        $inactifs = $total - $actifs;

        return $this->render('challenge/index.html.twig', [
            'challenges'  => $challenges,
            'categories'  => $catRepo->findAll(),
            'coaches'     => $em->getRepository(\App\Entity\User::class)->findBy(['role' => 'coach']),
            'recompenses' => $em->getRepository(\App\Entity\Recompense::class)->findAll(),
            'userParticipations' => $userParticipations,
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
        $challenge->setAdresse($request->request->get('adresse', ''));

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
        $challenge->setAdresse($request->request->get('adresse', $challenge->getAdresse()));

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
   // ===== ENVOYER EMAIL AU COACH =====
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
    
    /** @var \App\Entity\User|null $currentUser */
    $currentUser = $this->getUser();
    
    // Email de l'utilisateur connecté (expéditeur réel)
    $senderEmail = $currentUser ? $currentUser->getEmail() : null;
    
    if (!$senderEmail) {
        $this->addFlash('error', 'Vous devez être connecté avec un email valide pour envoyer un message.');
        return $this->redirectToRoute('app_challenge_index');
    }
    
    $senderName = $currentUser ? trim($currentUser->getPrenom() . ' ' . $currentUser->getNom()) : 'Utilisateur';

    try {
        // IMPORTANT: Avec Gmail, on ne peut pas utiliser un "from" différent du compte authentifié
        // On utilise donc le compte Gmail configuré comme expéditeur technique,
        // mais on met l'email de l'utilisateur en Reply-To pour que le coach puisse répondre directement
        
        $fromEmail = 'mahdiabderrahmen8@gmail.com'; // Votre email Gmail configuré dans MAILER_DSN
        
        $email = (new Email())
            ->from($fromEmail)  // Email technique (votre compte Gmail)
            ->replyTo($senderEmail)  // Le coach répondra à l'utilisateur connecté
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
                    nl2br(htmlspecialchars($body)) .
                    '</div>
                    <div style="margin-top:40px;text-align:center;">
                        <a href="mailto:' . $senderEmail . '" style="background:#6c63ff;color:white;padding:12px 25px;text-decoration:none;border-radius:8px;font-weight:bold;font-size:14px;">Répondre à ' . htmlspecialchars($senderName) . '</a>
                    </div>
                </div>
                <div style="background:#f9fafb;padding:20px;text-align:center;border-top:1px solid #eee;">
                    <p style="color:#9ca3af;font-size:12px;margin:0;">Ce message vous a été envoyé par ' . htmlspecialchars($senderName) . ' via Atomic You. Pour répondre, cliquez sur le bouton ci-dessus ou répondez directement à cet email.</p>
                </div>
            </div>');

        $mailer->send($email);
        $this->addFlash('success', '📧 Email envoyé avec succès à ' . $coach->getNom() . ' ! Une copie vous a été envoyée.');
        
    } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
        $this->addFlash('error', '❌ Erreur d\'envoi: ' . $e->getMessage());
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

    // ===== GENERATE AI TASKS =====
    #[Route('/{id}/generate-tasks', name: 'app_challenge_generate_tasks', methods: ['POST'])]
    public function generateTasks(
        Challenge $challenge,
        AiTaskGeneratorService $aiTaskGenerator
    ): Response {
        $aiTaskGenerator->generateAndPersist($challenge);
        $this->addFlash('success', '🤖 Tâches IA générées avec succès !');
        return $this->redirectToRoute('app_challenge_tasks', ['id' => $challenge->getId()]);
    }

    // ===== VIEW TASKS PAGE =====
    #[Route('/{id}/tasks', name: 'app_challenge_tasks', methods: ['GET'])]
    public function tasks(Challenge $challenge, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Connectez-vous pour voir les tâches.');
            return $this->redirectToRoute('app_challenge_index');
        }

        // Check participation
        $participation = null;
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            $participation = $em->getRepository(\App\Entity\ChallengeParticipation::class)
                                ->findOneBy(['user' => $user, 'challenge' => $challenge]);
            
            if (!$participation || $participation->getStatut() !== 'approved') {
                $this->addFlash('error', 'Vous devez être approuvé par un admin pour voir ces tâches.');
                return $this->redirectToRoute('app_challenge_index');
            }
        }

        $tasks = $em->getRepository(ChallengeTask::class)->findBy(
            ['challenge' => $challenge],
            ['sortOrder' => 'ASC']
        );

        $userDoneTaskIds = [];
        if ($user) {
            $userTasks = $em->getRepository(\App\Entity\UserChallengeTask::class)
                            ->findBy(['user' => $user, 'isDone' => true]);
            foreach ($userTasks as $ut) {
                $userDoneTaskIds[] = $ut->getTask()->getId();
            }
        }

        return $this->render('challenge/tasks.html.twig', [
            'challenge'       => $challenge,
            'tasks'           => $tasks,
            'userDoneTaskIds' => $userDoneTaskIds
        ]);
    }

    // ===== TOGGLE TASK DONE (AJAX) =====
    #[Route('/task/{id}/toggle', name: 'app_challenge_task_toggle', methods: ['POST'])]
    public function toggleTask(
        int $id,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $task = $em->getRepository(ChallengeTask::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Task not found'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 403);
        }

        $userTask = $em->getRepository(\App\Entity\UserChallengeTask::class)
                       ->findOneBy(['user' => $user, 'task' => $task]);
        
        $isDone = false;
        if ($userTask) {
            $isDone = !$userTask->isDone();
            $userTask->setIsDone($isDone);
            if ($isDone) $userTask->setCompletedAt(new \DateTimeImmutable());
        } else {
            $userTask = new \App\Entity\UserChallengeTask();
            $userTask->setUser($user);
            $userTask->setTask($task);
            $userTask->setIsDone(true);
            $em->persist($userTask);
            $isDone = true;
        }

        $em->flush();

        // Check if all tasks finished to unlock rewards
        $challenge = $task->getChallenge();
        $allTasks = $em->getRepository(ChallengeTask::class)->findBy(['challenge' => $challenge]);
        $totalTaskCount = count($allTasks);

        $allUserTasks = $em->getRepository(\App\Entity\UserChallengeTask::class)->findBy(['user' => $user, 'isDone' => true]);
        $completedTaskIds = array_map(fn($ut) => $ut->getTask()->getId(), $allUserTasks);
        
        $challengeTaskIds = array_map(fn($t) => $t->getId(), $allTasks);
        $doneCount = count(array_intersect($challengeTaskIds, $completedTaskIds));

        $unlockedRewards = false;
        if ($totalTaskCount > 0 && $doneCount === $totalTaskCount) {
            // Check participation and mark complete
            $participation = $em->getRepository(\App\Entity\ChallengeParticipation::class)
                                ->findOneBy(['user' => $user, 'challenge' => $challenge]);
            
            if ($participation && $participation->getStatut() !== 'completed') {
                $participation->setStatut('completed');
                $participation->setCompletedAt(new \DateTimeImmutable());

                // Unlock rewards
                foreach ($challenge->getRecompenses() as $cr) {
                    $ur = new \App\Entity\UserRecompense();
                    $ur->setUser($user);
                    $ur->setRecompense($cr->getRecompense());
                    $ur->setSourceChallenge($challenge);
                    $em->persist($ur);
                }
                $unlockedRewards = true;
                $em->flush();
            }
        }

        return $this->json([
            'done'        => $isDone,
            'progressPct' => $isDone ? 100 : 0,
            'unlockedRewards' => $unlockedRewards,
            'completedRatio' => "$doneCount / $totalTaskCount"
        ]);
    }

    #[Route('/{id}/participate', name: 'app_challenge_participate', methods: ['POST'])]
    public function participate(Challenge $challenge, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour participer.');
            return $this->redirectToRoute('app_login');
        }

        $existing = $em->getRepository(\App\Entity\ChallengeParticipation::class)
                       ->findOneBy(['user' => $user, 'challenge' => $challenge]);
        
        if ($existing) {
            $this->addFlash('info', 'Vous avez déjà une demande (statut: ' . $existing->getStatut() . ').');
        } else {
            $p = new \App\Entity\ChallengeParticipation();
            $p->setUser($user);
            $p->setChallenge($challenge);
            $p->setStatut('pending');
            $em->persist($p);
            $em->flush();
            $this->addFlash('success', 'Votre demande de participation a été envoyée à l\'administrateur.');
        }

        return $this->redirectToRoute('app_challenge_index');
    }

    #[Route('/admin/participations', name: 'app_admin_participations', methods: ['GET'])]
    public function adminParticipations(EntityManagerInterface $em): Response
    {
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            throw $this->createAccessDeniedException();
        }

        $participations = $em->getRepository(\App\Entity\ChallengeParticipation::class)->findBy([], ['createdAt' => 'DESC']);
        return $this->render('challenge/admin_participations.html.twig', [
            'participations' => $participations
        ]);
    }

    #[Route('/admin/participations/{id}/status', name: 'app_admin_participations_status', methods: ['POST'])]
    public function changeParticipationStatus(
        \App\Entity\ChallengeParticipation $participation, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            throw $this->createAccessDeniedException();
        }

        $status = $request->request->get('status');
        if (in_array($status, ['approved', 'rejected'])) {
            $participation->setStatut($status);
            $em->flush();
            $this->addFlash('success', 'Statut mis à jour !');
        }

        return $this->redirectToRoute('app_admin_participations');
    }


 

#[Route('/fc/load-events', name: 'fc_load_events', methods: ['GET', 'POST'])]
public function loadEvents(Request $request, ChallengeRepository $challengeRepository): JsonResponse
{
    // Get start and end dates from request
    $start = $request->query->get('start') ?: $request->request->get('start');
    $end = $request->query->get('end') ?: $request->request->get('end');
    
    if (!$start || !$end) {
        // If no dates provided, return all events for current month
        $start = (new \DateTime())->modify('first day of this month')->format('Y-m-d');
        $end = (new \DateTime())->modify('last day of this month')->format('Y-m-d');
    }
    
    $startDate = new \DateTime($start);
    $endDate = new \DateTime($end);
    
    // Fetch challenges within date range
    $challenges = $challengeRepository->createQueryBuilder('c')
        ->where('c.dateDebut BETWEEN :start AND :end')
        ->orWhere('c.dateFin BETWEEN :start AND :end')
        ->orWhere('c.dateDebut <= :start AND c.dateFin >= :end')
        ->setParameter('start', $startDate->format('Y-m-d 00:00:00'))
        ->setParameter('end', $endDate->format('Y-m-d 23:59:59'))
        ->getQuery()
        ->getResult();
    
    $events = [];
    foreach ($challenges as $challenge) {
        $startDateTime = $challenge->getDateDebut() ? clone $challenge->getDateDebut() : new \DateTime();
        $endDateTime = $challenge->getDateFin() ? clone $challenge->getDateFin() : (clone $startDateTime)->modify('+1 day');
        
        // Determine color based on status
        $color = match($challenge->getStatut()) {
            'actif' => '#6c63ff',
            'termine' => '#22d3a5',
            'annule' => '#ef4444',
            default => '#6c63ff'
        };
        
        $events[] = [
            'id' => $challenge->getId(),
            'title' => $challenge->getTitre(),
            'start' => $startDateTime->format('Y-m-d'),
            'end' => $endDateTime->format('Y-m-d'),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => '#ffffff',
            'allDay' => true,
            'url' => $this->generateUrl('app_challenge_tasks', ['id' => $challenge->getId()]),
            'extendedProps' => [
                'categorie' => $challenge->getCategorie() ? $challenge->getCategorie()->getNom() : 'Général',
                'coaches' => $challenge->getCoaches() ? $challenge->getCoaches()->count() : 0,
                'recompenses' => $challenge->getRecompenses() ? $challenge->getRecompenses()->count() : 0,
                'description' => $challenge->getDescription() ?: 'Aucune description',
                'statut' => $challenge->getStatut(),
            ]
        ];
    }
    
    return $this->json($events);
}

}
