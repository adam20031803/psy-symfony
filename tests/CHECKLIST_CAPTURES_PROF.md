# Checklist — documentation des tests unitaires (captures d’écran)

À utiliser comme guide : une capture par étape (ou regrouper 2 étapes si le prof accepte).

1. **Arborescence** — Ouvrir dans l’IDE le dossier `tests/Unit` et montrer les six modules : `ModuleUser`, `ModuleSanteMentale`, `ModuleFitness`, `ModuleReclamation`, `ModuleMotivation`, `ModuleHabitudes`.

2. **Contenu d’un fichier** — Ouvrir par exemple `tests/Unit/ModuleUser/UserEntityTest.php` pour montrer deux méthodes `test...` avec des assertions PHPUnit.

3. **Terminal : version PHPUnit** — Commande : `php bin/phpunit --version` (affiche PHPUnit 9.x).

4. **Terminal : exécution complète** — Depuis la racine du projet :  
   `php bin/phpunit`  
   Capture montrant `OK (12 tests, … assertions)`.

5. **Filtrer un module** — Exemple :  
   `php bin/phpunit tests/Unit/ModuleFitness`  
   Pour montrer que les tests Fitness s’exécutent isolément.

6. **Un test nommé** — Exemple :  
   `php bin/phpunit --filter UserEntityTest`  
   Pour illustrer l’exécution ciblée.

7. **Liste des tests (optionnel)** — `php bin/phpunit --list-tests` pour la liste des 12 méthodes.

8. **Tableau récapitulatif (Word / PDF)** — Pour le rapport écrit : 6 modules × 2 tests = 12 tests, lien avec les classes testées (`User`, `DailyCheckinAnalyticsService`, `FitnessProgramGeneratorService`, `Reclamation`, `CoachMotivation`, `Habitude`).
