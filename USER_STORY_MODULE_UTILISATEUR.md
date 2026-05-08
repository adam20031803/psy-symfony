# 📘 USER STORY DOCUMENT — MODULE UTILISATEUR (USER MODULE)

**Projet :** Atomic You — Plateforme de bien-être et développement personnel  
**Module :** Gestion des Utilisateurs (User Management)  
**Stack :** Symfony 6.4, Doctrine ORM, Twig, Gemini AI + Groq Fallback, Symfony Mailer  
**Date :** 21 Avril 2026  
**Version :** 1.0

---

## 🎯 CONTEXTE DU MODULE

Le module **User** permet la gestion complète des utilisateurs depuis le back-office admin. Il couvre les opérations CRUD classiques (création, lecture, mise à jour, suppression) ainsi qu'une fonctionnalité avancée d'analyse intelligente de profil utilisateur alimentée par l'IA Gemini, avec fallback vers Groq, incluant la génération de recommandations personnalisées, la persistance des insights, et l'envoi d'email de notification à l'utilisateur.

---

# PARTIE 1 — SIMPLE USER STORY : CRUD BACK-OFFICE DES UTILISATEURS

## US-001 — Gestion Complète des Utilisateurs depuis l'Administration

**Titre :** Gestion CRUD des utilisateurs avec validation serveur  
**Acteur principal :** Administrateur (ROLE_ADMIN)  
**Priorité :** Haute (Must Have)  
**Estimation :** Voir tableau Story Points ci-dessous

### Description

En tant qu'administrateur de la plateforme Atomic You, je souhaite pouvoir gérer l'ensemble des comptes utilisateurs depuis le panel d'administration, afin d'assurer un suivi complet des comptes, de gérer les accès, les rôles et la sécurité de la plateforme.

### Champs Gérés

| Champ | Type | Contraintes Symfony | Description |
|-------|------|---------------------|-------------|
| `email` | string (180, unique) | `@Assert\NotBlank`, `@Assert\Email`, `@Assert\Length(max=180)`, `@Assert\UniqueEntity(fields={"email"})` | Adresse email (identifiant de connexion) |
| `password` | string (255) | `@Assert\NotBlank` (création), `@Assert\Length(min=8)` (si renseigné) | Mot de passe hashé automatiquement |
| `nom` | string (100) | `@Assert\NotBlank`, `@Assert\Length(max=100)` | Nom de famille |
| `prenom` | string (100) | `@Assert\NotBlank`, `@Assert\Length(max=100)` | Prénom |
| `role` | string (20) | `@Assert\NotBlank`, `@Assert\Choice(choices={"user", "coach", "admin"})` | Rôle : user, coach, admin |
| `createdAt` | datetime | Auto-généré (non modifiable) | Date de création du compte |
| `isActive` | boolean | `@Assert\NotNull` | Statut actif/inactif |
| `telephone` | string (20, nullable) | `@Assert\Regex(pattern="/^[0-9+\-\s()]{8,20}$/")` (si renseigné) | Numéro de téléphone |
| `age` | integer (nullable) | `@Assert\Positive`, `@Assert\LessThan(150)` (si renseigné) | Âge de l'utilisateur |
| `photo` | string (255, nullable) | `@Assert\Url` (si renseigné) | URL de la photo de profil |

### Scénarios d'Utilisation

#### SC-001.1 — Lister les utilisateurs

**Préconditions :** 
- L'admin est authentifié (`ROLE_ADMIN`)
- Accès à `/admin/users`

**Flux nominal :**
1. L'admin accède à la page `/admin/users`
2. Le système récupère tous les utilisateurs via `UserRepository::findAll()`
3. Le système affiche la liste avec :
   - Tableau avec colonnes : email, nom, prénom, rôle, statut (actif/inactif), date de création
   - Statistiques en haut de page : total users, admins, actifs, inactifs
   - Badge coloré pour le statut (vert = actif, rouge = inactif)
   - Badge coloré pour le rôle (bleu = user, orange = coach, violet = admin)
4. L'admin peut rechercher par email/nom/prénom avec filtre instantané

**Postconditions :** 
- Liste affichée avec temps de réponse < 500ms

**Code Controller :**
```php
#[Route('/users', name: 'app_admin_users')]
public function users(UserRepository $userRepository): Response
{
    $users = $userRepository->findAll();
    $stats = [
        'total'  => count($users),
        'admins' => count(array_filter($users, fn(User $u) => in_array($u->getRole(), ['admin', 'coach'], true))),
        'active' => count(array_filter($users, fn(User $u) => $u->isActive())),
    ];

    return $this->render('admin/users.html.twig', [
        'users' => $users,
        'stats' => $stats,
    ]);
}
```

---

#### SC-001.2 — Créer un utilisateur

**Préconditions :** 
- Admin authentifié sur `/admin/users`
- Formulaire disponible via bouton "Nouvel Utilisateur"

**Flux nominal :**
1. L'admin clique sur "Nouvel Utilisateur"
2. Le système affiche le formulaire `AdminUserType` avec champs :
   - Email, Nom, Prénom, Téléphone, Âge, Photo URL, Rôle (dropdown), Mot de passe (input password), Actif (checkbox)
3. L'admin remplit le formulaire et soumet (POST)
4. Le système valide côté serveur :
   ```php
   #[Route('/user/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
   public function newUser(
       Request $request, 
       EntityManagerInterface $em,
       UserPasswordHasherInterface $passwordHasher
   ): Response {
       $user = new User();
       $form = $this->createForm(AdminUserType::class, $user);
       $form->handleRequest($request);

       if ($form->isSubmitted() && $form->isValid()) {
           // Hashage du mot de passe
           $plainPassword = $form->get('plainPassword')->getData();
           if ($plainPassword) {
               $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
           }
           
           // Auto-set creation date
           $user->setCreatedAt(new \DateTime());
           
           $em->persist($user);
           $em->flush();
           
           $this->addFlash('success', sprintf(
               'Utilisateur "%s %s" créé avec succès.',
               $user->getPrenom(),
               $user->getNom()
           ));
           
           return $this->redirectToRoute('app_admin_users');
       }

       return $this->render('admin/user_new.html.twig', [
           'form' => $form->createView(),
       ]);
   }
   ```
5. Le système redirige vers `/admin/users` avec message flash succès : "Utilisateur {Prénom} {Nom} créé avec succès."

**Flux alternatifs :**
- **4a. Validation échouée :** Le système réaffiche le formulaire avec erreurs inline (email invalide, mot de passe trop court, email dupliqué)
- **4b. Email dupliqué :** Erreur `@UniqueEntity` → "Cet email est déjà utilisé par un autre compte"
- **4c. Mot de passe < 8 caractères :** Erreur → "Le mot de passe doit contenir au moins 8 caractères"

**Postconditions :** 
- Utilisateur créé en base (table `user`)
- Mot de passe hashé avec `UserPasswordHasherInterface`
- `createdAt` défini automatiquement

---

#### SC-001.3 — Modifier un utilisateur

**Préconditions :** 
- Admin authentifié
- Utilisateur existant (`/admin/user/{id}/edit`)

**Flux nominal :**
1. L'admin clique sur "Modifier" (icône crayon) pour un utilisateur donné
2. Le système charge l'entité User via `UserRepository::find($id)` et pré-remplit le formulaire
3. L'admin modifie les champs autorisés :
   - ✅ Email, Nom, Prénom, Téléphone, Âge, Photo URL
   - ✅ Rôle (dropdown: user/coach/admin)
   - ✅ Statut Actif/Inactif (checkbox)
   - ❌ Mot de passe (champ séparé pour sécurité)
4. L'admin soumet le formulaire (POST)
5. Validation serveur :
   ```php
   #[Route('/user/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
   public function editUser(Request $request, User $user, EntityManagerInterface $em): Response
   {
       $form = $this->createForm(AdminUserType::class, $user);
       $form->handleRequest($request);

       if ($form->isSubmitted() && $form->isValid()) {
           $em->flush();
           $this->addFlash('success', 'Utilisateur "' . $user->getPrenom() . '" mis à jour.');

           // Security: If current admin downgrades THEMSELVES, log them out
           /** @var User $currentUser */
           $currentUser = $this->getUser();
           if ($user->getId() === $currentUser->getId() && !$user->isAdmin()) {
               return $this->redirectToRoute('app_logout');
           }

           return $this->redirectToRoute('app_admin_users');
       }

       return $this->render('admin/user_edit.html.twig', [
           'user' => $user,
           'form' => $form->createView(),
       ]);
   }
   ```
6. Le système persiste les modifications (`$em->flush()`)
7. Redirection vers `/admin/users` avec message : "Utilisateur {Prénom} mis à jour."

**Flux alternatifs :**
- **5a. L'admin modifie son propre rôle et perd les droits ROLE_ADMIN :** 
  - Déconnexion automatique forcée (`return $this->redirectToRoute('app_logout')`)
  - Flash message : "Vos droits ont été modifiés. Veuillez vous reconnecter."
- **5b. Email modifié vers valeur existante :** 
  - Erreur de validation `@UniqueEntity` → "Cet email est déjà utilisé"
- **5c. Tentative de modification de son propre statut à inactif :** 
  - Blocage avec erreur → "Vous ne pouvez pas désactiver votre propre compte"

**Postconditions :** 
- Modifications persistées en base (colonnes mises à jour dans `user`)
- Si rôle modifié : flash message "Rôle mis à jour avec succès"
- Si auto-downgrade : session détruite, redirection login

---

#### SC-001.4 — Activer/Désactiver un utilisateur

**Préconditions :** 
- Admin authentifié
- Utilisateur cible existe et n'est pas l'admin lui-même (pour désactivation)

**Flux nominal (activation) :**
1. L'admin clique sur "Activer" (bouton vert avec icône check)
2. Soumission POST vers `/admin/user/{id}/activate` avec CSRF token
3. Le système exécute :
   ```php
   #[Route('/user/{id}/activate', name: 'app_admin_activate_user', methods: ['POST'])]
   public function activateUser(Request $request, User $user, EntityManagerInterface $em): Response
   {
       if (!$this->isCsrfTokenValid('activate'.$user->getId(), $request->request->get('_token'))) {
           $this->addFlash('danger', 'Token CSRF invalide.');
           return $this->redirectToRoute('app_admin_users');
       }

       $user->setActive(true);
       $em->flush();

       $this->addFlash('success', sprintf(
           'Le compte de %s %s a été activé.',
           $user->getPrenom(),
           $user->getNom()
       ));

       return $this->redirectToRoute('app_admin_users');
   }
   ```
4. Flash message : "Le compte de {Prénom} {Nom} a été activé."
5. Redirection vers liste

**Flux nominal (désactivation) :**
1. L'admin clique sur "Désactiver" (bouton rouge avec icône X)
2. **Vérification de sécurité :** Empêcher l'auto-désactivation
   ```php
   #[Route('/user/{id}/deactivate', name: 'app_admin_deactivate_user', methods: ['POST'])]
   public function deactivateUser(Request $request, User $user, EntityManagerInterface $em): Response
   {
       if (!$this->isCsrfTokenValid('deactivate'.$user->getId(), $request->request->get('_token'))) {
           $this->addFlash('danger', 'Token CSRF invalide.');
           return $this->redirectToRoute('app_admin_users');
       }

       /** @var User $currentUser */
       $currentUser = $this->getUser();
       if ($user->getId() === $currentUser->getId()) {
           $this->addFlash('danger', 'Vous ne pouvez pas désactiver votre propre compte.');
           return $this->redirectToRoute('app_admin_users');
       }

       $user->setActive(false);
       $em->flush();

       $this->addFlash('success', sprintf(
           'Le compte de %s %s a été désactivé.',
           $user->getPrenom(),
           $user->getNom()
       ));

       return $this->redirectToRoute('app_admin_users');
   }
   ```
3. Si l'admin tente de se désactiver lui-même → Blocage avec flash error
4. Sinon, mise à jour `$user->setActive(false)` et flush
5. Flash message de confirmation

**Postconditions :** 
- Statut `isActive` mis à jour en base (`is_active` column dans `user`)
- Utilisateur désactivé ne peut plus se connecter (vérifié au login par `checkCredentials`)
- Flash message affiché

---

#### SC-001.5 — Changer le rôle d'un utilisateur

**Préconditions :** 
- Admin authentifié
- Dropdown de sélection de rôle visible sur fiche utilisateur (user, coach, admin)

**Flux nominal :**
1. L'admin sélectionne un nouveau rôle dans le dropdown menu
2. Soumission POST vers `/admin/user/{id}/role` avec rôle et CSRF token
3. Le système valide :
   ```php
   #[Route('/user/{id}/role', name: 'app_admin_change_role', methods: ['POST'])]
   public function changeRole(Request $request, User $user, EntityManagerInterface $em): Response
   {
       $role = $request->request->get('role');
       if (in_array($role, ['user', 'coach', 'admin'], true)) {
           $user->setRole($role);
           $em->flush();
           $this->addFlash('success', 'Rôle mis à jour avec succès.');

           // Security: If current admin downgrades THEMSELVES, log them out
           /** @var User $currentUser */
           $currentUser = $this->getUser();
           if ($user->getId() === $currentUser->getId() && !$user->isAdmin()) {
               $this->addFlash('warning', 'Vos droits ont été modifiés. Veuillez vous reconnecter.');
               return $this->redirectToRoute('app_logout');
           }
       }

       return $this->redirectToRoute('app_admin_users');
   }
   ```
4. **Vérification de sécurité post-changement :**
   - Si l'admin a modifié son PROPRE rôle vers `user` ou `coach` (perte de `ROLE_ADMIN`)
   - Déconnexion automatique forcée avec message d'avertissement
5. Flash message : "Rôle mis à jour avec succès."

**Postconditions :** 
- Rôle persisté (`role` column dans table `user`)
- `getRoles()` dans entité User recalculé automatiquement :
  ```php
  public function getRoles(): array
  {
      $roles = [$this->role ? 'ROLE_' . strtoupper($this->role) : 'ROLE_USER'];
      if (in_array($this->role, ['admin', 'coach'], true)) {
          $roles[] = 'ROLE_ADMIN';
      }
      $roles[] = 'ROLE_USER';
      return array_unique($roles);
  }
  ```
- Si l'admin s'est downgrade lui-même : déconnexion forcée, redirection vers `/login`

---

#### SC-001.6 — Supprimer un utilisateur

**Préconditions :** 
- Admin authentifié
- Utilisateur cible sélectionné
- Modal de confirmation affichée

**Flux nominal :**
1. L'admin clique sur "Supprimer" (icône poubelle rouge)
2. Modal de confirmation apparaît : 
   ```
   ⚠️ Êtes-vous sûr de vouloir supprimer cet utilisateur ?
   Cette action est irréversible.
   
   Utilisateur: {Prénom} {Nom} ({email})
   
   [Annuler] [Confirmer la suppression]
   ```
3. L'admin clique sur "Confirmer la suppression"
4. Soumission POST vers `/admin/user/{id}/delete` avec CSRF token
5. Le système exécute :
   ```php
   #[Route('/user/{id}/delete', name: 'app_admin_delete_user', methods: ['POST'])]
   public function deleteUser(Request $request, User $user, EntityManagerInterface $em): Response
   {
       if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
           $em->remove($user);
           $em->flush();
           $this->addFlash('success', 'Utilisateur supprimé.');
       }

       return $this->redirectToRoute('app_admin_users');
   }
   ```
6. Redirect avec message de succès : "Utilisateur supprimé."

**Flux alternatifs :**
- **5a. Violation de contrainte FK (dépendances existantes) :** 
  - Doctrine lève `ForeignKeyConstraintViolationException`
  - Catch dans un try/catch
  - Flash error : "Impossible de supprimer : cet utilisateur a des données associées en base."
- **5b. Admin tente de se supprimer lui-même :**
  - Blocage préalable → "Vous ne pouvez pas supprimer votre propre compte"

**Gestion des dépendances (Entity User) :**
```php
// Dans User.php - orphanRemoval assure la suppression en cascade
#[ORM\OneToMany(mappedBy: 'user', targetEntity: MentalEntries::class, orphanRemoval: true)]
private Collection $mentalEntries;

#[ORM\OneToMany(mappedBy: 'user', targetEntity: Moods::class, orphanRemoval: true)]
private Collection $moods;

#[ORM\OneToMany(mappedBy: 'user', targetEntity: NotificationLog::class, orphanRemoval: true)]
private Collection $notificationLogs;

#[ORM\OneToMany(mappedBy: 'user', targetEntity: Reclamation::class, orphanRemoval: true)]
private Collection $reclamations;
```

**Postconditions :** 
- Utilisateur supprimé de la table `user`
- Données liées supprimées automatiquement grâce à `orphanRemoval: true` :
  - `mentalEntries` associées
  - `moods` associés
  - `notificationLogs` associés
  - `reclamations` associées
- Flash message "Utilisateur supprimé." affiché

---

### Story Points Table

| Story | Complexité | Points | Justification |
|-------|------------|--------|---------------|
| SC-001.1 — Lister les utilisateurs | Simple | **2** | Requête `findAll()` + template Twig basique, pas de logique complexe |
| SC-001.2 — Créer un utilisateur | Moyenne | **5** | Formulaire + validation Symfony + hash password avec `UserPasswordHasherInterface` |
| SC-001.3 — Modifier un utilisateur | Moyenne | **5** | Pré-remplissage formulaire + validation `@UniqueEntity` + logique de sécurité auto-modification |
| SC-001.4 — Activer/Désactiver | Simple | **3** | Toggle boolean `isActive` + validation CSRF + sécurité auto-désactivation |
| SC-001.5 — Changer le rôle | Moyenne | **5** | Update rôle string + logique de déconnexion automatique si auto-downgrade |
| SC-001.6 — Supprimer un utilisateur | Complexe | **8** | Gestion des dépendances FK avec `orphanRemoval` + cascade delete + try/catch exceptions |
| **TOTAL** | — | **28** | ~7 sprints (vélocité estimée 4 pts/story) |

---

# PARTIE 2 — COMPLEX & ADVANCED USER STORY : ANALYSE IA DE PROFIL UTILISATEUR

## US-002 — Analyse Intelligente de Profil Utilisateur avec Recommandations Personnalisées

**Titre :** AI-Powered User Profile Analysis & Personalized Recommendations  
**Acteur principal :** Administrateur déclencheur, Utilisateur bénéficie de l'analyse  
**Priorité :** Haute (Should Have)  
**Estimation :** **13 Story Points** (feature complexe multi-services)

### Description

En tant qu'administrateur, je souhaite pouvoir lancer une analyse IA complète du profil d'un utilisateur (email, nom, prénom, âge, rôle, date de création, statut actif) afin de générer des recommandations personnalisées pour améliorer son expérience sur la plateforme Atomic You, avec envoi automatique d'un email récapitulatif à l'utilisateur et persistance des insights en base de données.

### Architecture Technique

```
┌──────────────────────────────────────────────────────────────┐
│                    FRONT-END (Twig + AJAX)                    │
│  - Bouton "Analyser avec IA" dans /admin/users               │
│  - Affichage loading spinner pendant analyse                 │
│  - Injection dynamique des résultats dans un panneau         │
│  - Notification toast de confirmation                        │
└──────────────────────┬───────────────────────────────────────┘
                       │ AJAX POST /admin/user/{id}/ai-analyze
                       ▼
┌──────────────────────────────────────────────────────────────┐
│               CONTROLLER (AdminController)                   │
│  - Vérification autorisations (ROLE_ADMIN)                   │
│  - Récupération données utilisateur (email, nom, prenom,     │
│    age, role, createdAt, isActive, telephone)                │
│  - Construction du prompt IA avec données user               │
│  - Appel service IA (Gemini primary + Groq fallback)         │
│  - Réception JSON analysé                                    │
│  - Persistance insight en base                               │
│  - Envoi email notification à l'utilisateur                  │
│  - Retour JSON au front-end                                  │
└──────────────────────┬───────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────────┐
│               AI SERVICE (GeminiService + GroqService)       │
│  1. Essai Gemini 2.5 Flash API                               │
│  2. Si erreur (API Key missing, timeout, rate limit) →       │
│     fallback Groq Llama-3.3-70b                              │
│  3. Parsing JSON response                                    │
│  4. Retour structure :                                       │
│     {                                                        │
│       "sentiment_score": 0-100,                              │
│       "engagement_prediction": "faible|moyen|élevé",         │
│       "recommended_actions": [                               │
│         "Envoyer un email de bienvenue personnalisé",        │
│         "Proposer un upgrade vers rôle coach",               │
│         "Suggérer des exercices adaptés à l'âge"             │
│       ],                                                     │
│       "personalized_message": "Message motivant...",         │
│       "risk_level": "low|medium|high",                       │
│       "onboarding_tips": ["tip1", "tip2", "tip3"]            │
│     }                                                        │
└──────────────────────┬───────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────────┐
│               PERSISTENCE + EMAIL NOTIFICATION               │
│  1. Création entité AiInsight liée à l'utilisateur           │
│     - user_id (FK vers User)                                 │
│     - analysis_data (JSON)                                   │
│     - created_at                                             │
│  2. Sauvegarde en base via Doctrine ($em->persist + flush)   │
│  3. Envoi email à utilisateur avec recommandations           │
│     via Symfony Mailer + TemplatedEmail                      │
│     - Destinataire: user.email                               │
│     - Template: emails/ai_analysis_report.html.twig          │
│     - Context: personalized_message + recommended_actions    │
│  4. Retour JSON au front-end avec success=true               │
└──────────────────────────────────────────────────────────────┘
```

### Flux Détaillé

#### PHASE 1 : Input Utilisateur & Trigger AJAX

**Interface Admin (liste des utilisateurs) :**
```twig
{# templates/admin/users.html.twig #}
<div class="users-list-container">
    <h1>👥 Gestion des Utilisateurs</h1>
    
    {# Stats cards #}
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-number">{{ stats.total }}</span>
            <span class="stat-label">Total Utilisateurs</span>
        </div>
        <div class="stat-card active">
            <span class="stat-number">{{ stats.active }}</span>
            <span class="stat-label">Actifs</span>
        </div>
        <div class="stat-card admins">
            <span class="stat-number">{{ stats.admins }}</span>
            <span class="stat-label">Admins/Coachs</span>
        </div>
    </div>
    
    {# Users table #}
    <table class="users-table">
        <thead>
            <tr>
                <th>Email</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Créé le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% for user in users %}
            <tr>
                <td>{{ user.email }}</td>
                <td>{{ user.nom }}</td>
                <td>{{ user.prenom }}</td>
                <td>
                    <span class="badge badge-{{ user.role }}">
                        {{ user.role|upper }}
                    </span>
                </td>
                <td>
                    {% if user.isActive %}
                        <span class="status status-active">✅ Actif</span>
                    {% else %}
                        <span class="status status-inactive">❌ Inactif</span>
                    {% endif %}
                </td>
                <td>{{ user.createdAt|date('d/m/Y') }}</td>
                <td class="actions">
                    <button class="btn-ai-analyze" 
                            onclick="analyzeUserWithAI({{ user.id }})"
                            title="Analyser avec l'IA">
                        🤖 Analyser IA
                    </button>
                    <a href="{{ path('app_admin_user_edit', {id: user.id}) }}" 
                       class="btn-edit">✏️ Modifier</a>
                </td>
            </tr>
            {% endfor %}
        </tbody>
    </table>
</div>

{# Modal pour afficher résultats IA #}
<div id="aiAnalysisModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>🤖 Analyse IA du Profil</h2>
        <div id="aiAnalysisContent">
            <div class="loading">
                <i class="bi bi-robot"></i>
                <p>L'IA analyse le profil utilisateur...</p>
            </div>
        </div>
    </div>
</div>

<script>
function analyzeUserWithAI(userId) {
    // Afficher modal avec loading
    const modal = document.getElementById('aiAnalysisModal');
    const content = document.getElementById('aiAnalysisContent');
    modal.style.display = 'block';
    
    content.innerHTML = `
        <div class="loading">
            <i class="bi bi-hourglass-split fa-spin"></i>
            <p>Analyse en cours via Gemini AI...</p>
        </div>
    `;
    
    // Appel AJAX
    fetch(`/admin/user/${userId}/ai-analyze`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type':