<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\GameRepository;
use Exception;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur SEO pour la génération du sitemap et robots.txt.
 *
 * Ce contrôleur génère dynamiquement :
 * - sitemap.xml : Liste des URLs publiques indexables
 * - robots.txt : Directives pour les crawlers
 */
final class SeoController extends AbstractController
{
    public function __construct(
        private readonly GameRepository $gameRepository,
    ) {
    }

    /**
     * Génère le sitemap.xml dynamique.
     *
     * Inclut :
     * - Pages statiques (accueil, login, register)
     * - Pages de jeux publics
     * - Futures pages wiki (extensible)
     */
    #[OA\Get(
        path: '/sitemap.xml',
        summary: 'Génère le sitemap.xml dynamique',
        tags: ['SEO'],
        responses: [
            new OA\Response(response: 200, description: 'Sitemap XML généré avec succès'),
        ],
    )]
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function sitemap(): Response
    {
        $urls = [];

        $hostname = $this->getParameter('app.hostname') ?? 'https://onlyroll.fr';

        $staticPages = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => '/auth/login', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => '/auth/register', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => '/games', 'priority' => '0.8', 'changefreq' => 'daily'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = $page;
        }

        try {
            $publicGames = $this->gameRepository->findBy(
                ['isPublic' => true],
                ['updatedAt' => 'DESC'],
                100,
            );

            foreach ($publicGames as $game) {
                $urls[] = [
                    'loc' => '/games/' . $game->getId(),
                    'lastmod' => $game->getUpdatedAt()?->format('Y-m-d'),
                    'priority' => '0.6',
                    'changefreq' => 'weekly',
                ];
            }
        }
        catch (Exception $e) {
            // Log l'erreur mais continue avec les pages statiques
        }

        // ========== FUTURES PAGES WIKI (commentées pour extension) ==========
        // @TODO: Décommenter quand le wiki sera implémenté
        //
        // $spells = $spellRepository->findAll();
        // foreach ($spells as $spell) {
        //     $urls[] = [
        //         'loc' => '/wiki/spells/' . $spell->getSlug(),
        //         'lastmod' => $spell->getUpdatedAt()?->format('Y-m-d'),
        //         'priority' => '0.7',
        //         'changefreq' => 'weekly',
        //     ];
        // }
        //
        // $monsters = $monsterRepository->findAll();
        // foreach ($monsters as $monster) {
        //     $urls[] = [
        //         'loc' => '/wiki/monsters/' . $monster->getSlug(),
        //         'lastmod' => $monster->getUpdatedAt()?->format('Y-m-d'),
        //         'priority' => '0.7',
        //         'changefreq' => 'weekly',
        //     ];
        // }
        //
        // $classes = $classRepository->findAll();
        // foreach ($classes as $class) {
        //     $urls[] = [
        //         'loc' => '/wiki/classes/' . $class->getSlug(),
        //         'lastmod' => $class->getUpdatedAt()?->format('Y-m-d'),
        //         'priority' => '0.7',
        //         'changefreq' => 'monthly',
        //     ];
        // }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/xml');
        $response->headers->set('Cache-Control', 'public, max-age=3600');

        return $this->render('seo/sitemap.xml.twig', [
            'urls' => $urls,
            'hostname' => rtrim($hostname, '/'),
        ], $response);
    }

    /**
     * Génère le fichier robots.txt.
     *
     * Directives :
     * - Autorise l'indexation des pages publiques
     * - Bloque les sections privées (admin, api, profil)
     */
    #[OA\Get(
        path: '/robots.txt',
        summary: 'Génère le fichier robots.txt',
        tags: ['SEO'],
        responses: [
            new OA\Response(response: 200, description: 'Fichier robots.txt généré avec succès'),
        ],
    )]
    #[Route('/robots.txt', name: 'robots', methods: ['GET'])]
    public function robots(): Response
    {
        $hostname = $this->getParameter('app.hostname') ?? 'https://onlyroll.fr';

        $content = <<<EOT
# OnlyRoll - robots.txt
# Virtual Tabletop pour D&D 5e

User-agent: *
Allow: /
Allow: /games
Allow: /auth/login
Allow: /auth/register

# Sections privées
Disallow: /admin/
Disallow: /api/
Disallow: /profile/
Disallow: /dashboard/

# Assets et fichiers techniques
Disallow: /*.json$
Disallow: /*.js$
Disallow: /*.css$

# Sitemap
Sitemap: {$hostname}/sitemap.xml

# Crawl-delay (optionnel - respecté par certains bots)
Crawl-delay: 1
EOT;

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Cache-Control', 'public, max-age=86400');

        return $response;
    }
}
