<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Classe principale du noyau de l'application Symfony.
 *
 * Cette classe étend le BaseKernel de Symfony et utilise le MicroKernelTrait pour
 * fournir les fonctionnalités de base du framework. Elle représente le point d'entrée
 * principal de l'application et gère le cycle de vie du kernel Symfony, incluant
 * la configuration, le chargement des bundles, et la gestion des services.
 *
 * Le MicroKernelTrait permet de configurer l'application de manière déclarative
 * en utilisant des attributs PHP et des méthodes de configuration simplifiées.
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
