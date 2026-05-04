# 📋 Rapport DoctrineDoctor — Psy Bien-être
**Application** : Symfony 6.4 | **Base de données** : MySQL (`psy_db`)  
**Date d'analyse** : 02/05/2026  
**Outil** : `doctrine:mapping:info` + `doctrine:schema:validate` + `doctrine:schema:update --dump-sql` + `debug:config doctrine`

---

## 1. 🗂️ Cartographie des entités (`doctrine:mapping:info`)

**Résultat : ✅ 45 entités mappées — Toutes valides**

| Statut | Entité |
|--------|--------|
| ✅ OK | `App\Entity\AiConversation` |
| ✅ OK | `App\Entity\AiInsight` |
| ✅ OK | `App\Entity\AiMessage` |
| ✅ OK | `App\Entity\AiUserProfile` |
| ✅ OK | `App\Entity\AppSettings` |
| ✅ OK | `App\Entity\Categorie` |
| ✅ OK | `App\Entity\Challenge` |
| ✅ OK | `App\Entity\ChallengeChat` |
| ✅ OK | `App\Entity\ChallengeCoach` |
| ✅ OK | `App\Entity\ChallengeParticipation` |
| ✅ OK | `App\Entity\ChallengeRecompense` |
| ✅ OK | `App\Entity\ChallengeTask` |
| ✅ OK | `App\Entity\CoachMotivation` |
| ✅ OK | `App\Entity\Commentaire` |
| ✅ OK | `App\Entity\DailyCheckin` |
| ✅ OK | `App\Entity\Exercise` |
| ✅ OK | `App\Entity\HabitCompletions` |
| ✅ OK | `App\Entity\HabitStreaks` |
| ✅ OK | `App\Entity\Habitude` |
| ✅ OK | `App\Entity\MentalEntries` |
| ✅ OK | `App\Entity\MentalEntry` |
| ✅ OK | `App\Entity\MentalTip` |
| ✅ OK | `App\Entity\MentalTips` |
| ✅ OK | `App\Entity\Mood` |
| ✅ OK | `App\Entity\Moods` |
| ✅ OK | `App\Entity\NotificationLog` |
| ✅ OK | `App\Entity\Post` |
| ✅ OK | `App\Entity\PostLike` |
| ✅ OK | `App\Entity\PostShare` |
| ✅ OK | `App\Entity\Program` |
| ✅ OK | `App\Entity\ProgramAssignment` |
| ✅ OK | `App\Entity\Reclamation` |
| ✅ OK | `App\Entity\ReclamationEvent` |
| ✅ OK | `App\Entity\Recompense` |
| ✅ OK | `App\Entity\SmartMeeting` |
| ✅ OK | `App\Entity\Store` |
| ✅ OK | `App\Entity\Team` |
| ✅ OK | `App\Entity\TeamMember` |
| ✅ OK | `App\Entity\User` |
| ✅ OK | `App\Entity\UserChallengeTask` |
| ✅ OK | `App\Entity\UserRecompense` |
| ✅ OK | `App\Entity\Workout` |
| ✅ OK | `App\Entity\WorkoutPlan` |
| ✅ OK | `App\Entity\WorkoutProgress` |
| ✅ OK | `Vich\UploaderBundle\Entity\File` *(bundle tiers)* |

> **Correction effectuée avant cette analyse** : L'entité `Categorie` ne déclarait pas l'inverse side de la relation `OneToMany → Challenge`. La collection `$challenges` + ses méthodes `getChallenges()`, `addChallenge()`, `removeChallenge()` ont été ajoutées.

---

## 2. ✅ Validation du Mapping (`doctrine:schema:validate` — partie Mapping)

```
Mapping
-------
 [OK] The mapping files are correct.
```

**Toutes les métadonnées ORM sont cohérentes.** Aucune association cassée, aucun champ mal typé.

---

## 3. ⚠️ Validation du Schéma Base de données (`doctrine:schema:validate` — partie Database)

```
Database
--------
 [ERROR] The database schema is not in sync with the current mapping file.
```

### Divergences détectées (`doctrine:schema:update --dump-sql`)

Le mapping PHP (entités) est **plus strict** que la base de données existante. Les différences sont principalement des **valeurs DEFAULT manquantes** côté BDD et quelques colonnes orphelines.

#### Colonnes à modifier (DEFAULT / NOT NULL)

| Table | Colonne | Problème |
|-------|---------|---------|
| `ai_conversation` | `personality_type`, `communication_style`, `last_activity_at` | DEFAULT NULL manquant |
| `ai_insight` | `confidence` | DEFAULT NULL manquant |
| `ai_message` | `emotion_detected` | DEFAULT NULL manquant |
| `ai_user_profile` | `wake_up_time`, `sleep_time`, `work_schedule`, `last_checkin_at`, `updated_at`, `current_mood_avg` | DEFAULT NULL manquant |
| `categorie` | `nom`, `icon` | DEFAULT NULL manquant |
| `challenge` | `statut`, `media_url`, `media_type`, `adresse` | DEFAULT / NOT NULL |
| `challenge_chat` | `message`, `created_at`, `challenge_id`, `user_id` | DEFAULT NULL manquant |
| `challenge_coach` | `assigned_at`, `challenge_id`, `coach_id` | DEFAULT NULL manquant |
| `challenge_recompense` | `challenge_id`, `recompense_id` | DEFAULT NULL manquant |
| `daily_checkin` | `image_name` | DEFAULT NULL manquant |
| `exercise` | `image_url` | DEFAULT NULL manquant |
| `habitude` | `end_date`, `image_name` | DEFAULT NULL manquant |
| `mental_entries` | `humeur`, `updated_at` | DEFAULT NULL manquant |
| `mental_entry` | `note` | DEFAULT NULL manquant |
| `post` | `image`, `updated_at` | DEFAULT NULL manquant |
| `post_like` | `type` | DEFAULT manquant |
| `program_assignment` | `status`, `responded_at` | DEFAULT manquant |
| `smart_meeting` | `meeting_url`, `ai_context` | DEFAULT NULL manquant |
| `store` | `address`, `phone`, `website`, `opening_hours`, `rating` | Types / DEFAULT |
| `team_member` | `role` | DEFAULT manquant |
| `workout` | `nom`, `description`, `duree`, `type`, `niveau`, `image`, `created_at` | DEFAULT NULL |
| `workout_plan` | `statut` | DEFAULT manquant |

#### Index à renommer

| Table | Ancien index | Nouvel index |
|-------|-------------|-------------|
| `post_like` | `idx_post_like_post` | `IDX_653627B84B89032C` |
| `post_like` | `idx_post_like_user` | `IDX_653627B8A76ED395` |
| `program_exercise` | `idx_program_exercise_program` | `IDX_2FEF29293EB8070A` |
| `program_exercise` | `idx_program_exercise_exercise` | `IDX_2FEF2929E934951A` |
| `program_assignment` | `idx_program_assignment_program` | `IDX_26FFB90E3EB8070A` |
| `program_assignment` | `idx_program_assignment_user` | `IDX_26FFB90EA76ED395` |
| `user` | `uniq_user_password_reset_token` | `UNIQ_8D93D6496B7BA4B6` |

#### Colonne orpheline à supprimer

| Table | Colonne |
|-------|---------|
| `reclamation` | `priorite`, `categorie` — présentes en BDD mais absentes du mapping |
| `user` | `checkin_reminder_time` — présente en BDD mais absente du mapping |

---

## 4. 🔄 Statut des Migrations (`doctrine:migrations:status`)

```
[ERROR] The metadata storage is not up to date.
        Please run: doctrine:migrations:sync-metadata-storage
```

> **Explication** : La table de suivi des migrations (`doctrine_migration_versions`) n'est pas à jour. L'application utilise probablement un schéma géré manuellement (SQL direct) sans passer par les migrations Doctrine.

**Commande corrective :**
```bash
php bin/console doctrine:migrations:sync-metadata-storage
```

---

## 5. ⚙️ Configuration ORM (`debug:config doctrine`)

| Paramètre | Valeur | Évaluation |
|-----------|--------|-----------|
| Driver | `pdo_mysql` | ✅ Correct |
| `auto_generate_proxy_classes` | `true` | ⚠️ À désactiver en production |
| `enable_lazy_ghost_objects` | `true` | ✅ Optimisation activée |
| `naming_strategy` | `underscore_number_aware` | ✅ Standard Symfony |
| `auto_mapping` | `true` | ✅ Correct |
| Mapping type | `attribute` (PHP 8 natif) | ✅ Modern |
| `query_cache_driver` | `null` | ⚠️ À configurer en production (Redis/APCu) |
| `result_cache_driver` | `null` | ⚠️ À configurer en production |
| Profiling | `true` | ⚠️ Désactiver en production |
| `idle_connection_ttl` | `600s` | ✅ Correct |

---

## 6. 📊 Résumé des résultats

| Critère | Résultat |
|---------|----------|
| Nombre d'entités | **45** |
| Mapping valide | ✅ **100% OK** |
| BDD en sync | ❌ **Désynchronisée** |
| Migrations | ❌ **Metadata storage obsolète** |
| Config ORM | ⚠️ **Optimisable pour la production** |

---

## 7. 🛠️ Recommandations

### Priorité haute
```bash
# 1. Synchroniser la table de migrations
php bin/console doctrine:migrations:sync-metadata-storage

# 2. Appliquer les différences de schéma (ATTENTION : vérifier en dev d'abord)
php bin/console doctrine:schema:update --force
```

### Priorité moyenne
- Supprimer les colonnes orphelines `reclamation.priorite`, `reclamation.categorie`, `user.checkin_reminder_time` manuellement si elles ne sont plus utilisées.
- Générer une migration propre avec `doctrine:migrations:diff` pour tracer les changements.

### Pour la production
- Désactiver `auto_generate_proxy_classes` (mettre `false`, utiliser `cache:warmup`)
- Configurer un `result_cache_driver` (APCu ou Redis)
- Désactiver `profiling: true` dans la config DBAL

---

*Rapport généré le 02/05/2026 — Application Psy Bien-être, Symfony 6.4*
