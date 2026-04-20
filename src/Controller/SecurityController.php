<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Utils\LoginFunctions;
use App\Utils\Functions;
// use App\Entity\User; // Entité User n'existe pas, utilisation de l'authentification LDAP
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\UserService;

/**
 * Contrôleur de sécurité gérant l'authentification et l'autorisation.
 *
 * Ce contrôleur gère toutes les opérations liées à la sécurité de l'application,
 * notamment la connexion, la déconnexion et la récupération de l'utilisateur courant.
 * Il utilise l'authentification LDAP via le système de sécurité de Symfony et
 * interagit avec les services d'authentification pour gérer les sessions utilisateur.
 */
class SecurityController extends AbstractController
{
    /**
     * Récupère l'identifiant de l'utilisateur actuellement connecté.
     *
     * Cette méthode récupère l'utilisateur connecté via le token de sécurité Symfony.
     * Pour l'authentification LDAP, elle retourne directement l'identifiant utilisateur
     * (nom d'utilisateur) plutôt qu'une entité utilisateur complète. Si aucun utilisateur
     * n'est connecté, la méthode retourne null.
     *
     * @param AuthenticationUtils $authenticationUtils Le service d'utilitaires d'authentification.
     * @param EntityManagerInterface|null $em Le gestionnaire d'entités Doctrine (non utilisé actuellement).
     *
     * @return string|null L'identifiant de l'utilisateur connecté ou null si aucun utilisateur n'est connecté.
     */
    public function getCurrentUser(AuthenticationUtils $authenticationUtils, EntityManagerInterface $em = null)
    {
        // Récupérer l'utilisateur connecté via le token de sécurité
        $token = $this->getUser();
        
        // Pour l'authentification LDAP, retourner directement le nom d'utilisateur
        return $token ? $token->getUserIdentifier() : null;
    }

    /**
     * Affiche le formulaire de connexion et gère l'authentification.
     *
     * Cette méthode gère la route de connexion "/login" et affiche le formulaire
     * d'authentification. Elle récupère les erreurs d'authentification éventuelles
     * et le dernier nom d'utilisateur saisi pour pré-remplir le formulaire en cas
     * d'échec de connexion. Le formulaire est rendu via le template de connexion.
     *
     * @param AuthenticationUtils $authenticationUtils Le service d'utilitaires d'authentification.
     * @param EntityManagerInterface $em Le gestionnaire d'entités Doctrine.
     * @param UserPasswordHasherInterface $passwordEncoder L'encodeur de mots de passe (non utilisé avec LDAP).
     * @param SessionInterface $session L'interface de session Symfony.
     *
     * @return Response La réponse HTTP contenant le rendu du formulaire de connexion.
     */
    #[Route('/login', name: 'app_home')]
    public function login(AuthenticationUtils $authenticationUtils, EntityManagerInterface $em, UserPasswordHasherInterface $passwordEncoder, SessionInterface $session): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('connexion/index.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

    /**
     * Gère la déconnexion de l'utilisateur.
     *
     * Cette méthode gère la route de déconnexion "/logout". La déconnexion est
     * généralement gérée automatiquement par le système de sécurité de Symfony
     * via la configuration de sécurité. Cette méthode peut être utilisée pour
     * effectuer des opérations supplémentaires avant la déconnexion, comme la
     * destruction de la session ou la journalisation de l'événement.
     *
     * @param SessionInterface $session L'interface de session Symfony.
     *
     * @return Response La réponse HTTP pour la déconnexion (généralement une redirection).
     */
    #[Route('/logout', name: 'app_logout')]
    public function logout(SessionInterface $session): Response
    {   
        
    }
}
