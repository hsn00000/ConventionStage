# Documentation complete du projet Conventio

## 1. Contexte du projet

Conventio est une application web Symfony (= framework PHP qui aide a creer des sites et applications web) de gestion des conventions de stage. Le projet a ete realise dans un contexte de BTS SIO option SLAM, avec un objectif concret : remplacer un parcours manuel, souvent gere par echanges d'emails et documents Word, par un outil centralise capable de collecter les informations, valider les conventions, generer le document final et lancer une signature electronique.

Le portfolio public presente Conventio comme une application Symfony de gestion, validation, generation PDF et signature electronique des conventions de stage. Le projet s'inscrit donc dans une logique d'application metier : plusieurs acteurs interviennent sur un meme dossier, chacun avec ses droits, ses formulaires et ses validations.

Sources utilisees pour cette documentation :

- analyse du code du depot local ;
- lecture du portfolio public : `https://portfolio.kanely.fr/` ;
- structure Symfony, entites Doctrine (= outil qui relie les objets PHP a la base de donnees), controleurs, services, templates (= fichiers de vues HTML), workflow (= suite d'etapes controlees) et tests presents dans le projet.

## Lexique simple des termes techniques

Cette partie sert a expliquer les mots techniques utilises dans la documentation.

- Symfony (= framework PHP qui fournit une structure pour creer une application web).
- PHP (= langage de programmation utilise cote serveur).
- Framework (= base de travail deja prete qui evite de tout coder de zero).
- Doctrine (= outil qui permet de manipuler la base de donnees avec des objets PHP).
- ORM (= systeme qui transforme les tables de base de donnees en objets dans le code).
- Entite (= classe PHP qui represente une table ou un objet important du projet, par exemple `Contract`).
- Controleur (= fichier qui recoit une requete web et decide quelle page ou action executer).
- Route (= adresse URL qui pointe vers une action du site).
- Service (= classe qui contient une logique reutilisable, par exemple generer un PDF ou envoyer une signature).
- Repository (= classe qui sert a chercher des donnees precises dans la base).
- Template (= fichier qui contient l'affichage HTML d'une page ou d'un email).
- Workflow (= suite d'etapes controlees, par exemple : envoye, valide, signe).
- State machine (= workflow ou un objet ne peut etre que dans un seul etat a la fois).
- API (= moyen pour deux applications de communiquer entre elles).
- Webhook (= notification automatique envoyee par un service externe vers l'application).
- Token (= code unique utilise pour securiser un lien ou identifier une action).
- CSRF (= protection contre l'envoi frauduleux d'un formulaire).
- Hash (= version protegee d'un mot de passe, non lisible directement).
- PDF (= document final consultable et imprimable).
- DOCX (= document Word utilise ici comme modele de convention).
- JSON (= format de donnees pratique pour stocker une structure, comme les horaires).
- Migration (= fichier qui modifie la structure de la base de donnees).
- Fixtures (= donnees de demonstration ajoutees en base pour tester l'application).
- Sandbox (= environnement de test qui imite un vrai service sans etre en production).
- CRUD (= actions de base : creer, lire, modifier, supprimer).
- CLI (= commande lancee dans le terminal).
- HMAC (= signature de securite permettant de verifier qu'un message vient bien du bon service).
- Dashboard (= tableau de bord qui regroupe les informations importantes).

## 2. Probleme initial

La gestion classique d'une convention de stage pose plusieurs difficultes :

- les informations sont saisies plusieurs fois par l'etudiant, l'entreprise et l'administration ;
- les donnees peuvent etre incompletes ou incoherentes ;
- le suivi du statut est difficile quand plusieurs personnes doivent valider ;
- le document final doit respecter un modele officiel ;
- la signature manuelle ralentit fortement le processus ;
- l'administration doit savoir quelles conventions sont en attente, validees, signees ou refusees.

Conventio apporte une reponse applicative a ces contraintes en transformant le processus en workflow (= suite d'etapes controlees) clair et suivi.

## 3. Objectifs

- Permettre a un etudiant de lancer une demande de convention.
- Envoyer un lien securise a l'entreprise pour completer les informations.
- Faire relire et valider la convention par l'etudiant.
- Faire valider pedagogiquement la convention par un professeur referent.
- Faire valider administrativement la convention par la DDF.
- Generer un PDF conforme au modele de convention.
- Envoyer le document en signature electronique via YouSign.
- Suivre l'etat de signature et recuperer le PDF signe.
- Centraliser les tableaux de bord et les actions selon les roles.

## 4. Stack technique

| Element | Technologie |
| --- | --- |
| Backend (= partie serveur de l'application) | PHP 8.2+, Symfony 7.4 |
| ORM (= lien entre objets PHP et base de donnees) | Doctrine ORM 3 |
| Base de donnees (= endroit ou les informations sont stockees) | PostgreSQL par defaut, SQLite possible en local/test |
| Templates (= fichiers d'affichage) | Twig |
| Formulaires (= zones de saisie controlees) | Symfony Form |
| Securite | Symfony Security, roles (= droits utilisateurs), CSRF (= protection formulaire), hash (= mot de passe protege) |
| Emails | Symfony Mailer, templates Twig, configuration Brevo possible |
| PDF | Gotenberg (= outil qui transforme un document en PDF) avec conversion LibreOffice/DOCX et fallback HTML |
| Signature electronique | YouSign API (= service externe de signature) sandbox (= espace de test) v3 |
| Workflow | Symfony Workflow en state machine (= un seul statut actif a la fois) |
| Tests | PHPUnit, Symfony BrowserKit |

## 5. Architecture generale

Le projet suit l'organisation classique d'une application Symfony (= framework PHP) :

- `src/Entity` : modele metier Doctrine. Une entite (= classe PHP liee a une table) represente un objet comme une convention.
- `src/Controller` : routes HTTP et orchestration des cas d'utilisation. Un controleur (= fichier qui gere une page ou une action) recoit la demande de l'utilisateur.
- `src/Form` : formulaires Symfony pour les saisies utilisateur.
- `src/Repository` : requetes specifiques Doctrine. Un repository (= classe de recherche) sert a recuperer des donnees precises.
- `src/Service` : logique transverse, generation PDF, signature, notifications. Un service (= classe reutilisable) evite de mettre trop de logique dans les controleurs.
- `templates` : vues Twig HTML, emails et PDF.
- `config/packages/workflow.yaml` : definition du cycle de vie d'une convention, donc son workflow (= suite d'etapes).
- `migrations` : evolution du schema de base de donnees. Une migration (= fichier de modification de base) ajoute ou modifie des tables.
- `tests` : tests fonctionnels.

## 6. Acteurs

### Etudiant

L'etudiant cree une demande de convention, renseigne l'entreprise et le tuteur, consulte son tableau de bord, relit les informations completees par l'entreprise et valide la convention avant transmission au professeur.

### Entreprise / tuteur

Le tuteur recoit un lien de collecte. Il complete les donnees de l'organisme, les informations de contact, les horaires, les avantages et les activites prevues.

### Professeur referent

Le professeur controle la coherence pedagogique du stage. Il peut valider ou refuser la convention avec un motif.

### DDF / administration

La DDF pilote les campagnes de stage, valide administrativement les conventions, genere les PDF, lance les signatures et suit les conventions signees.

### Proviseur

Le proviseur intervient dans la phase finale de signature. Ses informations sont stockees dans les parametres de l'application.

## 7. Fonctionnalites principales

### Authentification et comptes

- Connexion via `/login`.
- Deconnexion via `/logout`.
- Inscription etudiant via `/register/student`.
- Inscription professeur via `/register/professor`.
- Verification email via SymfonyCasts VerifyEmail (= composant qui confirme qu'une adresse email appartient bien a l'utilisateur).
- Reinitialisation de mot de passe via SymfonyCasts ResetPassword (= composant qui permet de changer un mot de passe oublie).
- Hash (= transformation securisee) automatique des mots de passe.
- Roles (= droits d'acces) applicatifs : `ROLE_STUDENT`, `ROLE_PROFESSOR`, `ROLE_TUTOR`, `ROLE_ADMIN`.

### Gestion des niveaux

- Creation, modification et suppression des classes/niveaux.
- Association des etudiants a un niveau.
- Association de professeurs a des sections.
- Definition d'un professeur principal pour affecter automatiquement un referent.

### Gestion des campagnes de stage

- Creation d'une campagne de stage par niveau.
- Ajout d'une ou plusieurs periodes de stage.
- Activation/desactivation logique via `isActive`.
- Synchronisation des conventions ouvertes quand les dates changent.

### Creation de convention

L'etudiant lance une demande depuis `/etudiant/convention/nouveau`.

Le systeme :

- recupere l'etudiant connecte ;
- verifie qu'il possede une classe ;
- recherche une campagne active pour cette classe ;
- cree ou reutilise un compte tuteur selon l'email saisi ;
- cree une organisation partiellement renseignee ;
- genere un token (= code unique de securite) de partage ;
- assigne un professeur referent ;
- envoie un email a l'entreprise.

### Collecte entreprise

L'entreprise accede au formulaire via `/company/fill/{token}`. Le token (= code unique dans le lien) permet d'ouvrir uniquement la convention concernee.

Le formulaire permet de completer :

- identite de l'organisme ;
- adresse du siege ;
- lieu du stage ;
- responsable ;
- tuteur ;
- assurances ;
- horaires hebdomadaires ;
- avantages ;
- activites prevues.

Quand l'entreprise valide, le workflow (= suite d'etapes controlees) passe de `collection_sent` a `filled_by_company` et un email est envoye a l'etudiant.

### Validation etudiant

L'etudiant consulte la convention puis valide les informations. Le workflow (= suite d'etapes controlees) passe de `filled_by_company` a `validated_by_student`. Le professeur referent recoit ensuite une demande de validation.

### Validation professeur

Le professeur accede a son tableau de bord et aux conventions a valider. Il peut :

- consulter les informations ;
- ouvrir le PDF si disponible ;
- valider pedagogiquement ;
- refuser avec un motif.

En cas de validation, la convention passe a `validated_by_prof`. En cas de refus, elle passe a `refused`.

### Validation DDF

La DDF visualise les conventions classees par statut :

- a valider ;
- validees par la DDF ;
- en signature ;
- signees.

Elle peut previsualiser le PDF (= document final lisible et imprimable), valider, refuser, generer le PDF, lancer la signature, relancer les notifications et synchroniser (= mettre a jour avec le service externe) le document signe.

### Generation PDF

Le service `ContractPdfService` (= classe responsable de la creation du document) genere le PDF final :

- en priorite depuis `Conventions de stage-template-fr.docx` ;
- avec remplacement des placeholders (= mots temporaires remplaces par les vraies donnees) dans le XML (= structure interne du fichier) du DOCX ;
- avec remplissage automatique du tableau des horaires ;
- avec normalisation des ancres de signature ;
- via Gotenberg (= outil qui convertit des documents en PDF) et LibreOffice ;
- avec fallback (= solution de secours) HTML/Twig si le modele DOCX n'existe pas.

Les PDF non signes sont stockes dans `var/contracts/unsigned`.
Les PDF signes sont stockes dans `var/contracts/signed`.

### Signature electronique

Le service `YouSignService` (= classe qui communique avec YouSign) :

- cree une demande de signature ;
- upload (= envoie le fichier vers le service externe) le PDF ;
- ajoute les signataires dans l'ordre ;
- active la demande ;
- consulte les statuts ;
- telecharge le document signe ;
- gere les relances manuelles ;
- verifie la signature HMAC (= preuve cryptographique que le message est fiable) des webhooks (= notifications automatiques).

Les signataires attendus sont :

1. l'etudiant ;
2. l'organisme / tuteur ;
3. le proviseur.

### Webhook YouSign

La route `/webhooks/yousign` recoit les evenements YouSign. Un webhook (= notification automatique envoyee par YouSign a l'application) indique par exemple qu'une signature est terminee. Seul l'evenement `signature_request.done` est traite. Le controleur verifie la signature du webhook, retrouve la convention avec l'identifiant YouSign, telecharge le document signe et marque la convention comme signee.

### Commande de synchronisation

La commande `app:contracts:sync-signed` permet de synchroniser (= recuperer les dernieres informations depuis YouSign) manuellement les conventions en attente de signature. Elle est utile si le webhook (= notification automatique) n'est pas disponible en local ou si l'on veut forcer une verification.

## 8. Workflow metier

```mermaid
stateDiagram-v2
    [*] --> collection_sent
    collection_sent --> filled_by_company: fill_by_company
    filled_by_company --> validated_by_student: validate_by_student
    validated_by_student --> validated_by_prof: validate_by_prof
    validated_by_prof --> validated_by_ddf: validate_by_ddf
    validated_by_ddf --> signature_requested: request_signature
    signature_requested --> signed: mark_signed
    filled_by_company --> refused: refuse_subject
    validated_by_student --> refused: refuse_subject
    validated_by_prof --> refused: refuse_by_ddf
```

## 9. Diagramme de classes

```mermaid
classDiagram
    class User {
        int id
        string email
        string password
        string lastname
        string firstname
        array roles
        bool isVerified
    }

    class Student {
        Level level
        Professor profReferent
    }

    class Professor {
        Collection studentsReferred
        Collection contracts
        Collection sections
    }

    class Tutor {
        string telMobile
        string telOther
        Collection contracts
    }

    class Level {
        int id
        string levelCode
        string levelName
        Professor mainProfessor
    }

    class Organisation {
        int id
        string name
        string addressHq
        string cityHq
        string addressInternship
        string cityInternship
        string respName
        string respEmail
        string insuranceName
        string insuranceContract
    }

    class Contract {
        int id
        string status
        bool deplacement
        bool transportFreeTaken
        bool lunchTaken
        bool hostTaken
        bool bonus
        float bonusAmount
        json workHours
        text plannedActivities
        string sharingToken
        datetime tokenExpDate
        string pdfUnsigned
        string pdfSigned
        string yousignDocumentId
        string yousignSignatureRequestId
    }

    class InternshipSchedule {
        int id
        string name
        bool isActive
    }

    class InternshipDate {
        int id
        date startDate
        date endDate
    }

    class Parameters {
        int id
        string provisorName
        string provisorEmail
        string ddfptName
        string ddfptPhone
        string ddfptEmail
    }

    User <|-- Student
    User <|-- Professor
    User <|-- Tutor
    Level "1" --> "0..*" Student
    Professor "1" --> "0..*" Student : referent
    Professor "0..*" --> "0..*" Level : sections
    Professor "1" --> "0..*" Contract : coordinator
    Student "1" --> "0..*" Contract
    Tutor "1" --> "0..*" Contract
    Organisation "1" --> "0..*" Contract
    InternshipSchedule "1" --> "0..*" InternshipDate
    InternshipSchedule "0..1" --> "0..*" Contract
```

## 10. Modele de donnees

### `User`

Classe parente des utilisateurs. Doctrine (= outil base de donnees) utilise un heritage `SINGLE_TABLE` (= tous les types d'utilisateurs sont stockes dans une seule table) avec une colonne discriminante `discr` (= colonne qui indique si l'utilisateur est etudiant, professeur ou tuteur). Les etudiants, professeurs et tuteurs partagent donc la meme table utilisateur.

### `Student`

Un etudiant possede un niveau et un professeur referent. Il peut avoir plusieurs conventions.

### `Professor`

Un professeur peut etre referent de plusieurs etudiants, coordonner plusieurs conventions et etre associe a plusieurs sections.

### `Tutor`

Un tuteur herite de `User` et ajoute des numeros de telephone. Il est rattache aux conventions de son entreprise.

### `Organisation`

Contient les informations de l'entreprise ou structure d'accueil : siege, lieu de stage, responsable, assurance et contact.

### `Contract`

Entite centrale du projet. Elle porte :

- le statut du workflow (= etape actuelle de la convention) ;
- les relations vers l'etudiant, le tuteur, l'entreprise et le professeur ;
- les horaires au format JSON (= format de donnees structurees) ;
- les informations de generation PDF ;
- les identifiants YouSign ;
- les motifs de refus professeur/DDF.

### `InternshipSchedule` et `InternshipDate`

Une campagne de stage est rattachee a un niveau et contient une ou plusieurs periodes. Les conventions se rattachent ensuite a une campagne pour calculer les dates de debut et fin.

### `Parameters`

Stocke les informations institutionnelles utiles a la signature finale : proviseur et DDFPT.

## 11. Parcours utilisateur complet

1. L'administration cree les niveaux, professeurs et campagnes de stage.
2. L'etudiant cree son compte et confirme son email.
3. L'etudiant lance une demande de convention.
4. L'entreprise recoit un lien de collecte.
5. L'entreprise complete les informations.
6. L'etudiant relit et valide.
7. Le professeur valide ou refuse.
8. La DDF valide ou refuse.
9. La DDF genere le PDF.
10. La DDF envoie la convention en signature YouSign.
11. Les signataires signent dans l'ordre.
12. Le webhook ou la commande recupere le PDF signe.
13. La convention passe au statut `signed`.

## 12. Securite

Mesures deja presentes :

- hash (= version protegee et non lisible) des mots de passe avec Symfony ;
- authentification par formulaire ;
- roles (= droits d'acces) Symfony ;
- controle d'acces sur les espaces etudiant, professeur et DDF ;
- verification CSRF (= protection contre l'envoi frauduleux d'un formulaire) sur les actions sensibles ;
- verification email a l'inscription ;
- token (= code unique) aleatoire pour le lien entreprise ;
- verification que l'etudiant consulte uniquement ses conventions ;
- verification que le professeur consulte uniquement ses conventions ;
- signature HMAC (= verification de l'origine du message) des webhooks YouSign ;
- requetes Doctrine parametrees (= requetes protegees contre les injections SQL).

Points d'attention :

- `tokenExpDate` existe dans l'entite `Contract`, mais la route entreprise ne bloque pas encore explicitement les tokens (= codes de lien) expires.
- Quelques routes CRUD (= creer, lire, modifier, supprimer) historiques comme certains index et creation manuelle restent a proteger plus strictement si l'application passe en production.
- Les secrets ne doivent jamais etre stockes dans un fichier versionne ; il faut utiliser `.env.local`, les variables serveur ou Symfony Secrets.
- La configuration YouSign actuelle utilise l'API sandbox (= version de test de l'API).

## 13. Routes importantes

| Route | Role | Usage |
| --- | --- | --- |
| `/` | Public | Accueil |
| `/login` | Public | Connexion |
| `/register/student` | Public | Creation compte etudiant |
| `/register/professor` | Public | Creation compte professeur |
| `/etudiant/convention/nouveau` | Etudiant | Demande de convention |
| `/company/fill/{token}` | Entreprise | Collecte via lien securise |
| `/convention/{id}` | Etudiant | Consultation convention |
| `/convention/{id}/valider` | Etudiant | Validation etudiant |
| `/professor/{id}` | Professeur | Dashboard professeur |
| `/professor/contract/{id}/validate` | Professeur | Validation pedagogique |
| `/admin/ddf/contracts` | Admin | Dashboard DDF |
| `/admin/ddf/contracts/{id}/generate-and-send` | Admin | PDF + signature |
| `/admin/ddf/campaigns` | Admin | Gestion campagnes |
| `/webhooks/yousign` | YouSign | Synchronisation automatique |

## 14. Services applicatifs

### `ContractPdfService`

Responsable de la production du PDF. Il centralise les contraintes documentaires : modele DOCX (= fichier Word), placeholders (= zones a remplacer), tableau des horaires, ancres de signature (= reperes ou signer) et conversion Gotenberg (= transformation en PDF).

### `ContractSignatureService`

Service d'orchestration (= service qui coordonne plusieurs actions). Il valide cote DDF, genere le PDF, lance YouSign, applique les transitions du workflow (= passages d'une etape a une autre), relance les notifications et synchronise le document signe.

### `YouSignService`

Service d'integration externe (= lien entre l'application et un service exterieur). Il encapsule les appels HTTP (= requetes envoyees sur internet) a YouSign, la creation de demande, l'ajout des signataires, le suivi des statuts, le telechargement et la verification des webhooks.

### `ProvisorNotificationService`

Envoie au proviseur une notification quand la convention est prete a signer.

## 15. Installation locale

Prérequis :

- PHP 8.2 ou superieur ;
- Composer ;
- une base PostgreSQL ou SQLite ;
- Gotenberg si l'on veut generer les PDF ;
- une cle YouSign sandbox si l'on veut tester la signature electronique.

Commandes typiques :

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
php -S 127.0.0.1:8000 -t public
```

Variables importantes :

```dotenv
APP_ENV=dev
APP_SECRET=...
DATABASE_URL=...
MAILER_DSN=...
GOTENBERG_URL=http://localhost:3000
YOUSIGN_API_KEY=...
YOUSIGN_WEBHOOK_SECRET=...
```

## 16. Tests

Le projet contient des tests fonctionnels sur l'inscription professeur :

- creation d'un compte professeur ;
- redirection apres inscription ;
- verification du role ;
- rejet d'une adresse email non academique.

Commande :

```bash
php bin/phpunit
```

## 17. Problemes rencontres et solutions apportees

| Probleme | Cause | Solution |
| --- | --- | --- |
| Suivre un processus avec plusieurs validateurs | Une convention change d'etat selon l'acteur | Mise en place d'une state machine (= workflow avec un seul etat actif) Symfony Workflow |
| Eviter les conventions modifiees au mauvais moment | Une convention ne doit pas etre modifiable apres certaines validations | Controle des statuts avant edition et transitions (= passages d'une etape a une autre) controlees |
| Assigner automatiquement le bon professeur | L'etudiant peut ne pas avoir de referent direct | Recherche du professeur referent, puis du professeur principal du niveau |
| Collecter les donnees entreprise sans compte complet | L'entreprise doit acceder rapidement au formulaire | Token (= code unique) aleatoire de partage envoye par email |
| Produire un document conforme | Le modele officiel est un DOCX (= fichier Word) | Remplacement des placeholders (= zones temporaires) dans `word/document.xml` puis conversion avec Gotenberg (= outil de generation PDF) |
| Les placeholders DOCX peuvent etre coupes par Word | Word separe parfois un placeholder en plusieurs balises XML (= structure interne du document) | Regex (= regle de recherche dans du texte) dediee pour detecter les placeholders fragmentes |
| Remplir proprement les horaires | Les horaires sont structures par jour et demi-journee | Stockage JSON (= format de donnees structurees) normalise dans `Contract::workHours` |
| Integrer la signature electronique | YouSign impose une API (= moyen de communication entre applications) avec document, signataires et activation | Encapsulation (= regrouper la logique dans une classe) dans `YouSignService` et orchestration dans `ContractSignatureService` |
| Recuperer le PDF signe | Le webhook (= notification automatique) peut ne pas etre disponible en local | Webhook securise + commande CLI (= commande terminal) de synchronisation manuelle |
| Gérer les erreurs externes | Gotenberg ou YouSign peuvent etre indisponibles | Exceptions explicites et messages utilisateur dans le dashboard DDF |
| Empêcher les validations non autorisees | Chaque acteur ne doit agir que sur ses conventions | Verifications d'identite dans les controleurs et CSRF (= protection formulaire) sur les POST |

## 18. Limites actuelles

- Le controle d'expiration du token (= code unique du lien) entreprise peut etre renforce.
- Les routes CRUD (= creer, lire, modifier, supprimer) historiques doivent etre auditees avant production.
- La signature electronique est configuree pour le sandbox (= environnement de test) YouSign.
- Le projet depend de Gotenberg pour la generation fiable des PDF.
- Il manque des tests fonctionnels sur le parcours complet convention -> signature.
- Les fixtures (= donnees de demonstration) contiennent des comptes de demonstration qui doivent etre adaptes hors environnement local.

## 19. Ameliorations possibles

- Ajouter une page d'administration des parametres `Parameters`.
- Ajouter un historique des transitions (= changements d'etape) de workflow visible dans l'interface.
- Ajouter des notifications plus detaillees en cas de refus.
- Ajouter des tests de bout en bout pour le parcours etudiant/entreprise/professeur/DDF.
- Ajouter une expiration reelle du lien entreprise.
- Ajouter un systeme d'archivage par annee scolaire.
- Ajouter un export CSV (= fichier tableur simple) des conventions pour l'administration.
- Ajouter une recherche et des filtres sur les dashboards.
- Ajouter un mode brouillon pour l'entreprise avant soumission.

## 20. Competences BTS SIO mobilisees

- Analyse d'un besoin metier.
- Conception d'une solution applicative.
- Modelisation de donnees relationnelles.
- Developpement backend (= partie serveur) avec Symfony.
- Gestion des formulaires et validations.
- Gestion des droits et de la securite.
- Integration d'API (= communication avec un service externe) externe.
- Generation documentaire.
- Tests fonctionnels.
- Documentation technique et utilisateur.
- Maintenance corrective et evolutive.

## 21. Synthese

Conventio est une application metier complete centree sur le cycle de vie d'une convention de stage. Le coeur du projet repose sur une entite `Contract` (= objet principal qui represente une convention), un workflow (= suite d'etapes controlees) de validation strict, des tableaux de bord par role, une generation PDF automatisee et une integration YouSign pour la signature electronique.

Le projet montre une progression importante : il ne se limite pas a du CRUD (= creer, lire, modifier, supprimer), mais traite un processus reel avec des dependances externes (= services ou outils utilises par l'application), des contraintes documentaires, des droits d'acces, des notifications et un suivi d'etat complet.
