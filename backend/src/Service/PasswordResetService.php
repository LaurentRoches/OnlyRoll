<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly PasswordResetTokenRepository $tokenRepo,
        private readonly UserRepository $userRepo,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly string $appFrontendUrl,
    ) {
    }

    /**
     * Envoie un email de réinitialisation si l'email correspond à un compte actif.
     * Ne révèle jamais si l'email existe ou non (sécurité).
     */
    public function sendResetEmail(string $email): void
    {
        $user = $this->userRepo->findOneByEmail($email);

        if (!$user || $user->isDeleted()) {
            return;
        }

        $this->tokenRepo->invalidatePreviousTokens($user);

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = PasswordResetToken::hash($plainToken);
        $expiresAt = new DateTimeImmutable('+1 hour');

        $tokenEntity = new PasswordResetToken($user, $tokenHash, $expiresAt);
        $this->em->persist($tokenEntity);
        $this->em->flush();

        $resetUrl = $this->appFrontendUrl . '/auth/reset-password?token=' . $plainToken;

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe — OnlyRoll')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'pseudo' => $user->getPseudo(),
                'reset_url' => $resetUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Vérifie le token et réinitialise le mot de passe.
     * Retourne true si succès, false si token invalide/expiré.
     */
    public function resetPassword(string $plainToken, string $newPassword): bool
    {
        $tokenHash = PasswordResetToken::hash($plainToken);
        $tokenEntity = $this->tokenRepo->findValidTokenByHash($tokenHash);

        if (!$tokenEntity) {
            return false;
        }

        $user = $tokenEntity->getUser();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $tokenEntity->markAsUsed();
        $this->em->flush();

        return true;
    }
}
