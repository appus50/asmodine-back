# Size Advisor Bundle

## Généralités

Ce plugin permet de faire des extractions des produits pour fournir :
 - Un guide des tailles détaillé par produit
 - Une notation détaillé en fonction du style ou de la couleur du produit
 - Relier le tout pour fournir différentes notes à l'utilisateur pour chaque produit
   - Note Style
   - Note Couleur
   - Note Taille (nombre de mensurations correspondantes)
   - Nombre total de mensurations du produit
   
## Services et Commands

La commande *[asmodine:sas:full](../../../Command/AsmodineSizeAdvisorFullCommand.php)* permet de lancer la totalité des commandes ci-dessous.


### asmodine.size_advisor.note:

Class : [NoteService](../../../Service/NoteService.php)
Command : [asmodine:sas:note](../../../Command/AsmodineSizeAdvisorNoteCommand.php)
           
Calcul une note sur le style et une note sur la couleur en fonction du morphotype poru la couleur ou de l'association taille, morphoweigt et morphoprofile pour le style.
           
###  asmodine.size_advisor.size_guide

Class : [SizeGuideService](../../../Service/SizeGuideService.php)
Command : [asmodine:sas:sizeguide](../../../Command/AsmodineSizeAdvisorSizeGuideCommand.php)

Extrait toutes les mensurations associées à chaque produit.

###  asmodine.size_advisor.user_score

Class : [NoteService](../../../Service/UserScoreService.php)
Command : [asmodine:sas:user](../../../Command/AsmodineSizeAdvisorUserScoreCommand.php)
        
Donne 3 notes pour chaque produit par utilisateur.

