# [obtic_teinte](https://github.com/OBVIL/obtic_teinte)

[Teinte](https://github.com/oeuvres/teinte_php) pour l’[ObTIC](https://obtic.sorbonne-universite.fr/), une plate-forme de transformation des textes électroniques en différents formats.

![ObTIC, Teinte](public/img/obtic_teinte.png)

## Installation sur un serveur Debian en SSH

Cette installation suppose un serveur web opérationnel pour les applications php.

```bash
# installer le gestionnaire de paquets php (composer)
sudo apt install composer
# aller dans un dossier ou il y a de la place
cd /data/app/
# obtenir les dernières sources du projet
git clone https://github.com/OBVIL/obtic_teinte.git
# rentrer dans l’application
cd obtic_teinte
# installer les librairies avec composer
composer install
# créer un lien symbolique du dossier Apache vers l’appli
sudo ln -s `pwd`/public  /var/www/html/teinte
```


