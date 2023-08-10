<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Hybridauth\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Auth\Identity;
use EnjoysCMS\Core\Block\AbstractBlock;
use EnjoysCMS\Core\Block\Annotation\Block;
use EnjoysCMS\Module\Hybridauth\Entities\Hybridauth;
use EnjoysCMS\Module\Hybridauth\HybridauthApp;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Block(
    name: 'Hybridauth Attach for Profile',
    options: [
        'template' => [
            'value' => '../modules/hybridauth/template/blocks/attach_block.twig',
            'name' => 'Путь до шаблона',
            'description' => 'Обязательно'
        ]
    ]
)]
final class AttachBlock extends AbstractBlock
{

    public function __construct(
        private readonly Environment $twig,
        private readonly Identity $identity,
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly HybridauthApp $hybridauthApp,
    ) {
    }


    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws NotSupported
     * @throws Exception
     */
    public function view(): string
    {
        if (!$this->identity->getUser()->isUser()) {
            return '';
        }

        $attachedProviders = $this->em->getRepository(Hybridauth::class)->findBy([
            'user' => $this->identity->getUser()
        ]);


        $removeQuery = function (UriInterface $uri, string|array $removedQuery) {
            parse_str($uri->getQuery(), $query);
            return http_build_query(
                array_filter((array)$query, function ($k) use ($removedQuery) {
                    return !in_array($k, (array)$removedQuery);
                }, ARRAY_FILTER_USE_KEY)
            );
        };

        return $this->twig->render(
            $this->getBlockOptions()->getValue('template'),
            [
                'blockOptions' => $this->getBlockOptions(),
                'attachedProviders' => $attachedProviders,
                'currentUrl' => $this->request
                    ->getUri()
                    ->withQuery(
                        $removeQuery(
                            $this->request->getUri(),
                            [HybridauthApp::ERROR_QUERY]
                        )
                    )->__toString(),
                'hybridauth' => $this->hybridauthApp->getHybridauth(),
            ]
        );
    }
}
