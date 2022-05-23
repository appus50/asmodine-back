# Lancement rapide :

## Installation

Récupérer les sources :

```bash
git clone http://80.12.81.32:8089/appus/asmodine.admin.git
```

>>>
Si vous utilisez Docker avec le [projet associé](http://80.12.81.32:8089/appus/asmodine.docker),
connectez-vous avec la commande suivante une fois le projet *asmodine.docker* configuré et lancé :

```bash
docker exec -it asmodine_back_php /bin/bash
```
Puis, continuer normalement.
>>>

Générer les clefs nécessaire à l'import de *asmodine.common* : [Voir README de asmodine.common](http://80.12.81.32:8089/appus/asmodine.common/blob/master/README.md)

Installer les dépendances et création du parameters.yml :

```bash
composer install
```

## Chargement des données et calculs

Lancer les commandes suivantes :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console asmodine:fixtures:import
php bin/console asmodine:admin:full:import
php bin/console asmodine:admin:associate
php bin/console asmodine:admin:front:update --brands --categories
php bin/console asmodine:admin:elasticsearch:populate
php bin/console asmodine:sas:full
```

Ces commandes réalisent les actions suivantes :
 - Création de la BDD (Inutile avec Docker => La création est automatique)
 - Création des tables
 - Import des données initialies
 - Import de tous les catalogues et toutes les marques
 - Associations des couleurs, styles et catégories de la marque avec Asmodine
 - Envoi des marques et catégories sur le Front *(A lancer lorsque le Front est installé et actif)*
 - Envoi des models, products et images sur Elasticsearch
 - Mise à jour complet du Size Advisor System (dont l'envoi des scores sur Elasticsearch)
