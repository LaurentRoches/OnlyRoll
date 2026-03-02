<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use App\Repository\EmailVerificationTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class EmailVerificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly EmailVerificationTokenRepository $tokenRepo,
        private readonly string $appFrontendUrl,
    ) {
    }

    /**
     * Génère un token de vérification et envoie l'email.
     */
    public function sendVerificationEmail(User $user): void
    {
        $this->tokenRepo->invalidatePreviousTokens($user);

        $token = bin2hex(random_bytes(50));
        $expiresAt = new DateTimeImmutable('+24 hours');
        $verificationToken = new EmailVerificationToken($user, $token, $expiresAt);
        $this->em->persist($verificationToken);
        $this->em->flush();

        $verificationUrl = $this->appFrontendUrl . '/auth/verify-email?token=' . $token;

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Vérifiez votre adresse email — OnlyRoll')
            ->htmlTemplate('emails/verify_email.html.twig')
            ->context([
                'pseudo' => $user->getPseudo(),
                'verification_url' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Vérifie un token et active le compte.
     * Retourne l'utilisateur si succès, null sinon.
     */
    public function verifyToken(string $token): ?User
    {
        $verificationToken = $this->tokenRepo->findValidToken($token);

        if (!$verificationToken) {
            return null;
        }

        $user = $verificationToken->getUser();
        $user->setIsVerified(true);
        $verificationToken->markAsUsed();
        $this->em->flush();

        return $user;
    }

    /**
     * Vérifie si l'utilisateur peut demander un renvoi (délai min 2 min).
     */
    public function canResend(User $user): bool
    {
        $last = $this->tokenRepo->findLastTokenForUser($user);

        if (!$last) {
            return true;
        }

        return $last->getCreatedAt() < new DateTimeImmutable('-2 minutes');
    }
}
