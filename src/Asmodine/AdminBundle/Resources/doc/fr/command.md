# Commands

Pour accéder à l'aide des *commands*, il est possible d'utiliser l'option **--help**

```bash
php bin/console asmodine:admin:catalog:import --help
php bin/console asmodine:admin:associate --help
php bin/console asmodine:admin:brand:import --help
...
```

## asmodine:fixtures:import

Charge, dans la base de données **qui doit être vide**, les données écrites en YAML dans les dossiers :
- [DataFixtures/fixtures/fr/](../../../DataFixtures/fixtures/fr).
- [DataFixtures/PHP/fr/](../../../DataFixtures/PHP/fr).

Le code PHP correpondant à l'import se trouve ici : [Command/AsmodineBackFixturesImportCommand.php](src/Asmodine/AdminBundle/Command/AsmodineBackFixturesImportCommand.php).

Une documentation détaillant les différentes données est disponible ici : [fixtures.md](fixtures.md)

## asmodine:admin:catalog:import

Import d'un catalogue. 

Pour avoir la liste des catalogues disponibles :

```bash
php bin/console asmodine:admin:catalog:import list
```

Pour fonctionner, la commande utilise le service [ImportCatalogService.php](../../../Service/ImportCatalogService.php).

## asmodine:admin:brand:import

Import d'une marque. 

Pour avoir la liste des marques disponibles :

```bash
php bin/console asmodine:admin:brand:import list
```

Si le catalogue associé à la marque n'est pas téléchargé, la commande *asmodine:admin:catalog:import* sera exécutée automatiquement.

Pour fonctionner, la commande utilise le service [ImportBrandService.php](../../../Service/ImportBrandService.php).

## asmodine:admin:front:update

Cette commande publie les informations (hors modèles et produits) administrées par le back-office et utilisées par le front.

Pour fonctionner, la commande utilise le service [FrontUpdateService.php](../../../Service/FrontUpdateService.php).

## asmodine:admin:associate

Cette commande permet l'association des catégories, styles et couleurs d'une marque à ceux d'Asmodine.

L'algorithme, utilisant des synonymes, s'inspire directement d'un projet réalisé par deux élèves de Polytech Lille.

Pour fonctionner, la commande utilise le service [AssociateService.php](../../../Service/AssociateService.php) dans lequel est implémenté l'algorithme.

